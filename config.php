<?php
// config.php - 設定ファイル

// エラー表示設定（開発時のみ）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// 設定初期化
loadEnvironment();
