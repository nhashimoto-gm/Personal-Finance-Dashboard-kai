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

// ============================================================
// HTTPS強制（本番環境のみ）
// ============================================================
/**
 * HTTPSを強制的に使用させる
 * 本番環境でのみ有効化
 *
 * 注意: SSL証明書がインストールされている必要があります
 * 開発環境で無効化する場合は、環境変数 FORCE_HTTPS=0 を設定してください
 */
function forceHttps() {
    $appEnv = getenv('APP_ENV') ?: 'development';
    $forceHttps = getenv('FORCE_HTTPS');

    // 本番環境、またはFORCE_HTTPS=1が設定されている場合
    if ($appEnv === 'production' || $forceHttps === '1') {
        // HTTPSかどうかをチェック
        $isHttps = (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        );

        if (!$isHttps) {
            // HTTPSにリダイレクト
            $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header('Location: ' . $redirect, true, 301);
            exit('Redirecting to HTTPS...');
        }

        // HSTSヘッダーを設定（1年間有効）
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
}

// HTTPS強制を実行（CLIモードでは実行しない）
if (php_sapi_name() !== 'cli') {
    forceHttps();
}

// 環境変数読み込み
function loadEnvironment() {
    $envFilePath = __DIR__ . '/.env_db';
    if (!file_exists($envFilePath)) {
        error_log("Environment file not found: " . $envFilePath);
        die(".env_db file does not exist.");
    }

    if (!is_readable($envFilePath)) {
        error_log("Environment file not readable: " . $envFilePath);
        die(".env_db file is not readable. Please check file permissions.");
    }

    // エラー抑制演算子を削除し、適切なエラーハンドリングを追加
    $envVariables = parse_ini_file($envFilePath);
    if ($envVariables === false) {
        $error = error_get_last();
        $errorMessage = $error['message'] ?? 'Unknown error';
        error_log("Failed to parse .env_db file: " . $errorMessage);
        die("Failed to parse .env_db file. Error: " . $errorMessage . "\n\n" .
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

// ============================================================
// 認証関数（Multi-Account Support）
// ============================================================

/**
 * ユーザーログイン
 * @param PDO $pdo データベース接続
 * @param string $username ユーザー名またはメールアドレス
 * @param string $password パスワード
 * @return array ['success' => bool, 'message' => string, 'user' => array|null]
 */
function loginUser($pdo, $username, $password) {
    if (empty($username) || empty($password)) {
        return ['success' => false, 'message' => 'Username and password are required', 'user' => null];
    }

    try {
        // ユーザー名またはメールアドレスでユーザーを検索
        $stmt = $pdo->prepare(
            "SELECT id, username, email, password_hash, full_name, is_active
             FROM users
             WHERE (username = ? OR email = ?) AND is_active = 1"
        );
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid credentials', 'user' => null];
        }

        // パスワード検証
        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid credentials', 'user' => null];
        }

        // セッション開始
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // セッション固定攻撃対策
        session_regenerate_id(true);

        // ユーザー情報をセッションに保存
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();

        // パスワードハッシュを除外して返す
        unset($user['password_hash']);

        return ['success' => true, 'message' => 'Login successful', 'user' => $user];

    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Login failed', 'user' => null];
    }
}

/**
 * ユーザーログアウト
 */
function logoutUser() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // セッション変数をクリア
    $_SESSION = [];

    // セッションクッキーを削除
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // セッションを破棄
    session_destroy();
}

/**
 * ログイン状態をチェック
 * @return bool ログインしている場合true
 */
function isLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user_id']);
}

/**
 * 現在のユーザーIDを取得
 * @return int|null ログインしている場合はユーザーID、それ以外はnull
 */
function getCurrentUserId() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * 現在のユーザー情報を取得
 * @return array|null ユーザー情報の連想配列、ログインしていない場合はnull
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null
    ];
}

/**
 * 認証が必要なページの保護（リダイレクト）
 * @param string $redirectTo リダイレクト先（デフォルト: login.php）
 */
function requireLogin($redirectTo = 'login.php') {
    if (!isLoggedIn()) {
        header('Location: ' . $redirectTo);
        exit;
    }
}

/**
 * ユーザー情報を更新
 * @param PDO $pdo データベース接続
 * @param int $userId ユーザーID
 * @param array $data 更新データ（'email', 'full_name'など）
 * @return array ['success' => bool, 'message' => string]
 */
function updateUserProfile($pdo, $userId, $data) {
    $allowedFields = ['email', 'full_name'];
    $updates = [];
    $params = [];

    foreach ($data as $field => $value) {
        if (in_array($field, $allowedFields)) {
            if ($field === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email address'];
            }
            $updates[] = "$field = ?";
            $params[] = $value;
        }
    }

    if (empty($updates)) {
        return ['success' => false, 'message' => 'No valid fields to update'];
    }

    $params[] = $userId;

    try {
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // セッション情報も更新
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $_SESSION[$field] = $value;
            }
        }

        return ['success' => true, 'message' => 'Profile updated successfully'];

    } catch (PDOException $e) {
        error_log("Profile update error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Update failed'];
    }
}

/**
 * パスワードを変更
 * @param PDO $pdo データベース接続
 * @param int $userId ユーザーID
 * @param string $currentPassword 現在のパスワード
 * @param string $newPassword 新しいパスワード
 * @return array ['success' => bool, 'message' => string]
 */
function changePassword($pdo, $userId, $currentPassword, $newPassword) {
    if (strlen($newPassword) < 8) {
        return ['success' => false, 'message' => '新しいパスワードは8文字以上である必要があります'];
    }

    try {
        // 現在のパスワードを検証
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            return ['success' => false, 'message' => '現在のパスワードが正しくありません'];
        }

        // 新しいパスワードをハッシュ化
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        // パスワードを更新
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$newPasswordHash, $userId]);

        return ['success' => true, 'message' => 'パスワードを変更しました'];

    } catch (PDOException $e) {
        error_log("Password change error: " . $e->getMessage());
        return ['success' => false, 'message' => 'パスワードの変更に失敗しました'];
    }
}

/**
 * セッションタイムアウトをチェック（30分）
 * @param int $timeout タイムアウト時間（秒）デフォルト: 1800秒（30分）
 * @return bool タイムアウトした場合true
 */
function checkSessionTimeout($timeout = 1800) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION['login_time'])) {
        if (time() - $_SESSION['login_time'] > $timeout) {
            logoutUser();
            return true;
        }
        // アクティビティがあればログイン時刻を更新
        $_SESSION['login_time'] = time();
    }

    return false;
}

// 設定初期化
loadEnvironment();
