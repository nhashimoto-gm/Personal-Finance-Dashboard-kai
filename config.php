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
    
    $envVariables = parse_ini_file($envFilePath);
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
        'view1' => getenv('DB_VIEW_MAIN') ?: 'view1'
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

// 設定初期化
loadEnvironment();
