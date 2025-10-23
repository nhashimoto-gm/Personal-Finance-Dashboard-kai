<?php
/**
 * Authentication System
 * Personal Finance Dashboard - Multi-User Version
 *
 * Handles user registration, login, logout, and authentication checks
 */

// Prevent direct access
if (!defined('APP_INITIALIZED')) {
    die('Direct access not permitted');
}

/**
 * Register a new user
 *
 * @param PDO $pdo Database connection
 * @param string $username Username
 * @param string $email Email address
 * @param string $password Plain text password
 * @param string $displayName Display name (optional)
 * @return array Result array with success/error message
 */
function registerUser($pdo, $username, $email, $password, $displayName = null) {
    try {
        // Validate input
        $errors = validateRegistration($username, $email, $password);
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        // Check if username or email already exists
        if (isUserExists($pdo, $username, $email)) {
            return [
                'success' => false,
                'message' => 'Username or email already exists'
            ];
        }

        $pdo->beginTransaction();

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);

        // Generate table prefix
        $tablePrefix = getNextTablePrefix($pdo);

        // Generate email verification token
        $verificationToken = bin2hex(random_bytes(32));

        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, display_name, table_prefix, email_verification_token)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $username,
            $email,
            $passwordHash,
            $displayName ?: $username,
            $tablePrefix,
            $verificationToken
        ]);

        $userId = $pdo->lastInsertId();

        // Create user preferences
        $stmt = $pdo->prepare("INSERT INTO user_preferences (user_id) VALUES (?)");
        $stmt->execute([$userId]);

        // Create user tables using stored procedure
        $stmt = $pdo->prepare("CALL create_user_tables(?)");
        $stmt->execute([$tablePrefix]);

        // Insert sample data (optional - can be disabled)
        $stmt = $pdo->prepare("CALL insert_sample_data(?)");
        $stmt->execute([$tablePrefix]);

        $pdo->commit();

        // Send verification email (implement if needed)
        // sendVerificationEmail($email, $verificationToken);

        return [
            'success' => true,
            'message' => 'Registration successful. Please login.',
            'user_id' => $userId,
            'table_prefix' => $tablePrefix
        ];

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log("Registration error: " . $e->getMessage());

        return [
            'success' => false,
            'message' => 'Registration failed. Please try again later.'
        ];
    }
}

/**
 * Login user
 *
 * @param PDO $pdo Database connection
 * @param string $usernameOrEmail Username or email
 * @param string $password Plain text password
 * @param string $ipAddress User's IP address
 * @param string $userAgent User's browser user agent
 * @return array Result array with success/error message
 */
function loginUser($pdo, $usernameOrEmail, $password, $ipAddress, $userAgent) {
    try {
        // Check rate limiting
        if (isLoginBlocked($pdo, $ipAddress)) {
            return [
                'success' => false,
                'message' => 'Too many failed login attempts. Please try again in 15 minutes.'
            ];
        }

        // Find user
        $stmt = $pdo->prepare("
            SELECT id, username, email, password_hash, table_prefix, is_active, email_verified
            FROM users
            WHERE (username = ? OR email = ?) AND is_active = TRUE
        ");
        $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify password
        if (!$user || !password_verify($password, $user['password_hash'])) {
            // Record failed attempt
            recordLoginAttempt($pdo, $usernameOrEmail, $ipAddress, false);

            return [
                'success' => false,
                'message' => 'Invalid username/email or password'
            ];
        }

        // Check email verification (optional - can be disabled)
        // if (!$user['email_verified']) {
        //     return [
        //         'success' => false,
        //         'message' => 'Please verify your email address before logging in.'
        //     ];
        // }

        // Record successful login
        recordLoginAttempt($pdo, $usernameOrEmail, $ipAddress, true);

        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['table_prefix'] = $user['table_prefix'];
        $_SESSION['logged_in'] = true;
        $_SESSION['ip_address'] = $ipAddress;
        $_SESSION['user_agent'] = $userAgent;
        $_SESSION['last_activity'] = time();

        // Save session to database
        saveSessionToDB($pdo, session_id(), $user['id'], $ipAddress, $userAgent);

        // Update last login time
        updateLastLogin($pdo, $user['id']);

        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email']
            ]
        ];

    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());

        return [
            'success' => false,
            'message' => 'Login failed. Please try again later.'
        ];
    }
}

/**
 * Logout user
 */
function logoutUser($pdo) {
    if (isset($_SESSION['user_id'])) {
        // Delete session from database
        try {
            $stmt = $pdo->prepare("DELETE FROM sessions WHERE id = ?");
            $stmt->execute([session_id()]);
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
        }
    }

    // Clear session
    $_SESSION = [];

    // Destroy session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // Destroy session
    session_destroy();
}

/**
 * Check if user is authenticated
 *
 * @return bool True if authenticated, false otherwise
 */
function isAuthenticated() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Require authentication (redirect to login if not authenticated)
 */
function requireAuth() {
    if (!isAuthenticated()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /login.php');
        exit;
    }

    // Check session timeout (30 minutes)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        logoutUser($GLOBALS['pdo']);
        header('Location: /login.php?timeout=1');
        exit;
    }

    // Update last activity
    $_SESSION['last_activity'] = time();

    // Check session hijacking (IP and User-Agent)
    if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
        logoutUser($GLOBALS['pdo']);
        header('Location: /login.php?security=1');
        exit;
    }

    if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        logoutUser($GLOBALS['pdo']);
        header('Location: /login.php?security=1');
        exit;
    }
}

/**
 * Get current user's table prefix
 *
 * @return string Table prefix
 */
function getCurrentTablePrefix() {
    requireAuth();
    return $_SESSION['table_prefix'] ?? null;
}

/**
 * Get current user ID
 *
 * @return int User ID
 */
function getCurrentUserId() {
    requireAuth();
    return $_SESSION['user_id'] ?? null;
}

/**
 * Validate registration input
 *
 * @param string $username Username
 * @param string $email Email
 * @param string $password Password
 * @return array Array of error messages (empty if valid)
 */
function validateRegistration($username, $email, $password) {
    $errors = [];

    // Username validation
    if (empty($username)) {
        $errors[] = 'Username is required';
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $errors[] = 'Username must be between 3 and 20 characters';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username can only contain letters, numbers, and underscores';
    }

    // Email validation
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }

    // Password validation
    $passwordErrors = validatePassword($password);
    $errors = array_merge($errors, $passwordErrors);

    return $errors;
}

/**
 * Validate password strength
 *
 * @param string $password Password to validate
 * @return array Array of error messages (empty if valid)
 */
function validatePassword($password) {
    $errors = [];

    if (empty($password)) {
        $errors[] = 'Password is required';
        return $errors;
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }

    // Check common passwords
    $commonPasswords = ['password', '12345678', 'qwerty', 'abc123', 'password123'];
    if (in_array(strtolower($password), $commonPasswords)) {
        $errors[] = 'Password is too common';
    }

    return $errors;
}

/**
 * Check if user exists
 *
 * @param PDO $pdo Database connection
 * @param string $username Username
 * @param string $email Email
 * @return bool True if user exists
 */
function isUserExists($pdo, $username, $email) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM users WHERE username = ? OR email = ?
    ");
    $stmt->execute([$username, $email]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Get next table prefix
 *
 * @param PDO $pdo Database connection
 * @return string Next table prefix (e.g., 'user_1', 'user_2', etc.)
 */
function getNextTablePrefix($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    return 'user_' . ($count + 1);
}

/**
 * Record login attempt
 *
 * @param PDO $pdo Database connection
 * @param string $usernameOrEmail Username or email
 * @param string $ipAddress IP address
 * @param bool $success Whether login was successful
 */
function recordLoginAttempt($pdo, $usernameOrEmail, $ipAddress, $success) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO login_attempts (username_or_email, ip_address, success)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$usernameOrEmail, $ipAddress, $success]);
    } catch (Exception $e) {
        error_log("Failed to record login attempt: " . $e->getMessage());
    }
}

/**
 * Check if login is blocked due to too many failed attempts
 *
 * @param PDO $pdo Database connection
 * @param string $ipAddress IP address
 * @return bool True if blocked
 */
function isLoginBlocked($pdo, $ipAddress) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as failed_count
            FROM login_attempts
            WHERE ip_address = ?
            AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            AND success = FALSE
        ");
        $stmt->execute([$ipAddress]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['failed_count'] >= 5;
    } catch (Exception $e) {
        error_log("Failed to check login block: " . $e->getMessage());
        return false;
    }
}

/**
 * Save session to database
 *
 * @param PDO $pdo Database connection
 * @param string $sessionId Session ID
 * @param int $userId User ID
 * @param string $ipAddress IP address
 * @param string $userAgent User agent
 */
function saveSessionToDB($pdo, $sessionId, $userId, $ipAddress, $userAgent) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO sessions (id, user_id, ip_address, user_agent, last_activity)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            last_activity = VALUES(last_activity),
            ip_address = VALUES(ip_address)
        ");
        $stmt->execute([$sessionId, $userId, $ipAddress, $userAgent, time()]);
    } catch (Exception $e) {
        error_log("Failed to save session to DB: " . $e->getMessage());
    }
}

/**
 * Update last login time
 *
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 */
function updateLastLogin($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    } catch (Exception $e) {
        error_log("Failed to update last login: " . $e->getMessage());
    }
}
