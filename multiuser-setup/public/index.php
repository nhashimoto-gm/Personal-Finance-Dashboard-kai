<?php
/**
 * Main Entry Point
 * Personal Finance Dashboard - Multi-User Version
 */

// Load configuration
require_once __DIR__ . '/../src/core/config.php';

// Load session configuration
require_once __DIR__ . '/../src/core/session_config.php';

// Load CSRF protection
require_once __DIR__ . '/../src/core/csrf.php';

// Load authentication
require_once __DIR__ . '/../src/core/auth.php';

// Load table resolver
require_once __DIR__ . '/../src/core/table_resolver.php';

// Configure secure session
configureSecureSession();

// Get database connection (already initialized in config.php)
global $pdo;

// Simple routing
$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
$path = str_replace($scriptName, '', $requestUri);
$path = parse_url($path, PHP_URL_PATH);
$path = trim($path, '/');

// Default to dashboard if empty
if (empty($path)) {
    $path = 'dashboard';
}

// Route handling
switch ($path) {
    case 'login':
    case 'login.php':
        // Login page
        if (isAuthenticated()) {
            redirect('/dashboard');
        }
        // Login page implementation will go here
        echo '<h1>Login Page</h1>';
        echo '<p>Login functionality will be implemented here.</p>';
        echo '<p><a href="/register">Register</a></p>';
        break;

    case 'register':
    case 'register.php':
        // Registration page
        if (isAuthenticated()) {
            redirect('/dashboard');
        }
        // Registration page implementation will go here
        echo '<h1>Register Page</h1>';
        echo '<p>Registration functionality will be implemented here.</p>';
        echo '<p><a href="/login">Login</a></p>';
        break;

    case 'logout':
    case 'logout.php':
        // Logout
        logoutUser($pdo);
        redirect('/login');
        break;

    case 'dashboard':
    case '':
        // Dashboard (requires authentication)
        requireAuth();

        // Dashboard will be implemented here
        echo '<h1>Dashboard</h1>';
        echo '<p>Welcome, ' . htmlspecialchars($_SESSION['username']) . '!</p>';
        echo '<p>Table Prefix: ' . htmlspecialchars($_SESSION['table_prefix']) . '</p>';
        echo '<p><a href="/logout">Logout</a></p>';
        break;

    default:
        // 404 Not Found
        http_response_code(404);
        echo '<h1>404 - Page Not Found</h1>';
        echo '<p><a href="/">Go to Dashboard</a></p>';
        break;
}
