<?php
/**
 * Configuration File
 * Personal Finance Dashboard - Multi-User Version
 */

// Initialize application
define('APP_INITIALIZED', true);

// Error reporting (disable in production)
$isProduction = getenv('APP_ENV') === 'production';
if ($isProduction) {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
} else {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

/**
 * Load environment variables from .env_db file
 */
function loadEnvironment() {
    // Try multiple possible locations for .env_db
    $possiblePaths = [
        __DIR__ . '/../../.env_db',
        __DIR__ . '/../../../.env_db',
        dirname($_SERVER['DOCUMENT_ROOT']) . '/.env_db'
    ];

    $envFilePath = null;
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $envFilePath = $path;
            break;
        }
    }

    if (!$envFilePath) {
        die("ERROR: .env_db file not found. Please copy .env_db.example to .env_db and configure it.");
    }

    $envVariables = parse_ini_file($envFilePath);
    if ($envVariables === false) {
        die("ERROR: Failed to parse .env_db file.");
    }

    foreach ($envVariables as $key => $value) {
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

/**
 * Get database connection
 *
 * @return PDO Database connection
 */
function getDatabaseConnection() {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dbHost = getenv('DB_HOST') ?: 'localhost';
    $dbUsername = getenv('DB_USERNAME');
    $dbPassword = getenv('DB_PASSWORD');
    $dbDatabase = getenv('DB_DATABASE');

    if (!$dbUsername || !$dbPassword || !$dbDatabase) {
        die("ERROR: Database configuration incomplete. Please check .env_db file.");
    }

    try {
        $pdo = new PDO(
            "mysql:host=$dbHost;dbname=$dbDatabase;charset=utf8mb4",
            $dbUsername,
            $dbPassword,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        );

        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        die("ERROR: Database connection failed. Please check your configuration.");
    }
}

/**
 * Get application configuration value
 *
 * @param string $key Configuration key
 * @param mixed $default Default value if not found
 * @return mixed Configuration value
 */
function config($key, $default = null) {
    $value = getenv($key);
    return $value !== false ? $value : $default;
}

/**
 * Get base URL
 *
 * @return string Base URL
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['SCRIPT_NAME']);

    return $protocol . '://' . $host . $path;
}

/**
 * Redirect to URL
 *
 * @param string $url URL to redirect to
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Get current URL
 *
 * @return string Current URL
 */
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Asset URL helper
 *
 * @param string $path Asset path
 * @return string Full asset URL
 */
function asset($path) {
    return getBaseUrl() . '/assets/' . ltrim($path, '/');
}

// Load environment variables
loadEnvironment();

// Initialize database connection (available globally)
$pdo = getDatabaseConnection();
