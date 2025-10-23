<?php
/**
 * Secure Session Configuration
 * Personal Finance Dashboard - Multi-User Version
 */

// Prevent direct access
if (!defined('APP_INITIALIZED')) {
    die('Direct access not permitted');
}

/**
 * Configure secure session settings
 */
function configureSecureSession() {
    // Session configuration
    ini_set('session.cookie_httponly', 1);      // Prevent JavaScript access
    ini_set('session.cookie_secure', 1);        // HTTPS only (disable in dev if needed)
    ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
    ini_set('session.use_strict_mode', 1);      // Strict session ID validation
    ini_set('session.use_only_cookies', 1);     // Don't use URL session IDs
    ini_set('session.use_trans_sid', 0);        // Don't pass session ID in URL

    // Session name (change from default PHPSESSID)
    session_name('FINANCE_SESSION');

    // Session timeout (30 minutes)
    ini_set('session.gc_maxlifetime', 1800);
    ini_set('session.cookie_lifetime', 0); // Session cookie (expires when browser closes)

    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Initialize session security checks
    initSessionSecurity();
}

/**
 * Initialize session security checks
 */
function initSessionSecurity() {
    // Check if user is logged in
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {

        // Initialize security variables if not set
        if (!isset($_SESSION['ip_address'])) {
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        } else {
            // Validate IP address (strict check)
            if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
                handleSessionHijacking('IP address mismatch');
            }

            // Validate User-Agent
            if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
                handleSessionHijacking('User agent mismatch');
            }
        }

        // Check session timeout (30 minutes)
        if (isset($_SESSION['last_activity'])) {
            $inactiveTime = time() - $_SESSION['last_activity'];
            if ($inactiveTime > 1800) {
                handleSessionTimeout();
            }
        }

        // Update last activity time
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Handle session hijacking attempt
 *
 * @param string $reason Reason for hijacking detection
 */
function handleSessionHijacking($reason) {
    global $pdo;

    // Log the incident
    error_log("Session hijacking detected: $reason - User ID: " . ($_SESSION['user_id'] ?? 'N/A') . " - IP: " . $_SERVER['REMOTE_ADDR']);

    // Destroy session
    if (function_exists('logoutUser')) {
        logoutUser($pdo);
    } else {
        session_destroy();
    }

    // Redirect to login with security warning
    header('Location: /login.php?error=security');
    exit;
}

/**
 * Handle session timeout
 */
function handleSessionTimeout() {
    global $pdo;

    // Destroy session
    if (function_exists('logoutUser')) {
        logoutUser($pdo);
    } else {
        session_destroy();
    }

    // Redirect to login with timeout message
    header('Location: /login.php?error=timeout');
    exit;
}

/**
 * Regenerate session ID periodically (call after sensitive operations)
 */
function regenerateSessionId() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/**
 * Set session flash message
 *
 * @param string $type Message type (success, error, warning, info)
 * @param string $message Message text
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 *
 * @return array|null Flash message array or null
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}
