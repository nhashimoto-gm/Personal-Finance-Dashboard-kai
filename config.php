<?php
// config.php - 設定ファイル

// エラー表示設定（環境別）
$appEnv = getenv('APP_ENV') ?: 'development';
if ($appEnv === 'production') {
    // 本番環境: エラーをログに記録し、画面には表示しない
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/error.log');
} else {
    // 開発環境: エラーを画面に表示
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// 環境変数読み込み
function loadEnvironment() {
    $envFilePath = __DIR__ . '/.env_db';
    if (!file_exists($envFilePath)) {
        die(".env_db file does not exist.");
    }

    $envVariables = @parse_ini_file($envFilePath);
    if ($envVariables === false) {
        die("Failed to parse .env_db file. Please check the file format.\n" .
            "Expected format:\n" .
            "DB_HOST=localhost\n" .
            "DB_USERNAME=your_username\n" .
            "DB_PASSWORD=your_password\n" .
            "DB_DATABASE=your_database\n\n" .
            "Common issues:\n" .
            "- Remove any PHP code (<?php tags)\n" .
            "- Remove parentheses or special characters from values\n" .
            "- Use key=value format only\n" .
            "- Comments should start with # or ;");
    }

    foreach ($envVariables as $key => $value) {
        putenv("$key=$value");
    }
}

// データベース接続
function getDatabaseConnection() {
    $dbHost = getenv('DB_HOST');
    $dbUsername = getenv('DB_USERNAME');
    $dbPassword = getenv('DB_PASSWORD');
    $dbDatabase = getenv('DB_DATABASE');
    
    try {
        $pdo = new PDO(
            "mysql:host=$dbHost;dbname=$dbDatabase;charset=utf8mb4",
            $dbUsername,
            $dbPassword
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("データベース接続エラー: " . $e->getMessage());
    }
}

// テーブル名設定取得
function getTableNames() {
    return [
        'source' => getenv('DB_TABLE_SOURCE') ?: 'source',
        'cat_1_labels' => getenv('DB_TABLE_SHOP') ?: 'cat_1_labels',
        'cat_2_labels' => getenv('DB_TABLE_CATEGORY') ?: 'cat_2_labels',
        'view1' => getenv('DB_VIEW_MAIN') ?: 'view1',
        'budgets' => getenv('DB_TABLE_BUDGETS') ?: 'budgets'
    ];
}

// CSRF保護関数
function generateCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// レート制限設定
define('RATE_LIMIT_REQUESTS', 10);  // 最大リクエスト数
define('RATE_LIMIT_WINDOW', 60);    // 時間枠（秒）

/**
 * レート制限をチェック
 * @param string $action アクション名（例: 'add_transaction', 'add_shop'）
 * @return array ['allowed' => bool, 'message' => string, 'retry_after' => int]
 */
function checkRateLimit($action = 'default') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $key = 'rate_limit_' . $action;
    $now = time();

    // セッションに記録がない場合は初期化
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [];
    }

    // 古いリクエストを削除（時間枠外のもの）
    $_SESSION[$key] = array_filter($_SESSION[$key], function($timestamp) use ($now) {
        return ($now - $timestamp) < RATE_LIMIT_WINDOW;
    });

    // リクエスト数をチェック
    $requestCount = count($_SESSION[$key]);

    if ($requestCount >= RATE_LIMIT_REQUESTS) {
        // 制限超過：最も古いリクエストから何秒後にリトライ可能かを計算
        $oldestRequest = min($_SESSION[$key]);
        $retryAfter = RATE_LIMIT_WINDOW - ($now - $oldestRequest);

        return [
            'allowed' => false,
            'message' => 'Too many requests. Please try again in ' . $retryAfter . ' seconds.',
            'retry_after' => $retryAfter
        ];
    }

    return [
        'allowed' => true,
        'message' => '',
        'retry_after' => 0
    ];
}

/**
 * リクエストを記録
 * @param string $action アクション名
 */
function recordRequest($action = 'default') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $key = 'rate_limit_' . $action;
    $now = time();

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [];
    }

    // 現在のタイムスタンプを追加
    $_SESSION[$key][] = $now;
}

/**
 * レート制限情報を取得（デバッグ用）
 * @param string $action アクション名
 * @return array
 */
function getRateLimitInfo($action = 'default') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $key = 'rate_limit_' . $action;
    $now = time();

    if (!isset($_SESSION[$key])) {
        return [
            'requests' => 0,
            'limit' => RATE_LIMIT_REQUESTS,
            'remaining' => RATE_LIMIT_REQUESTS,
            'reset' => 0
        ];
    }

    // 古いリクエストを削除
    $_SESSION[$key] = array_filter($_SESSION[$key], function($timestamp) use ($now) {
        return ($now - $timestamp) < RATE_LIMIT_WINDOW;
    });

    $requestCount = count($_SESSION[$key]);
    $remaining = max(0, RATE_LIMIT_REQUESTS - $requestCount);

    $reset = 0;
    if (!empty($_SESSION[$key])) {
        $oldestRequest = min($_SESSION[$key]);
        $reset = $oldestRequest + RATE_LIMIT_WINDOW;
    }

    return [
        'requests' => $requestCount,
        'limit' => RATE_LIMIT_REQUESTS,
        'remaining' => $remaining,
        'reset' => $reset
    ];
}

// 設定初期化
loadEnvironment();
