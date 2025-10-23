<?php
/**
 * CSRF Protection
 * Personal Finance Dashboard - Multi-User Version
 */

// Prevent direct access
if (!defined('APP_INITIALIZED')) {
    die('Direct access not permitted');
}

/**
 * Generate CSRF token
 *
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Get current CSRF token
 *
 * @return string|null CSRF token or null
 */
function getCSRFToken() {
    return $_SESSION['csrf_token'] ?? null;
}

/**
 * Validate CSRF token
 *
 * @param string $token Token to validate
 * @return bool True if valid
 * @throws CSRFException if invalid
 */
function validateCSRFToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        throw new CSRFException('No CSRF token in session');
    }

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        throw new CSRFException('Invalid CSRF token');
    }

    return true;
}

/**
 * Generate CSRF hidden input field
 *
 * @return string HTML hidden input field
 */
function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verify CSRF token from POST request
 *
 * @return bool True if valid
 * @throws CSRFException if invalid or missing
 */
function verifyCSRF() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true; // Only check POST requests
    }

    if (!isset($_POST['csrf_token'])) {
        throw new CSRFException('CSRF token missing from request');
    }

    return validateCSRFToken($_POST['csrf_token']);
}

/**
 * Middleware to check CSRF token on POST requests
 * Call this at the beginning of scripts that handle POST requests
 */
function requireCSRF() {
    try {
        verifyCSRF();
    } catch (CSRFException $e) {
        error_log('CSRF validation failed: ' . $e->getMessage());
        http_response_code(403);
        die('CSRF validation failed. Please refresh the page and try again.');
    }
}

/**
 * Custom exception for CSRF violations
 */
class CSRFException extends Exception {}
