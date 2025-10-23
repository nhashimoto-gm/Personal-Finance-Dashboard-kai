<?php
/**
 * Table Name Resolver
 * Personal Finance Dashboard - Multi-User Version
 *
 * Handles dynamic table name resolution with security validation
 */

// Prevent direct access
if (!defined('APP_INITIALIZED')) {
    die('Direct access not permitted');
}

/**
 * Validate table prefix for security
 *
 * @param PDO $pdo Database connection
 * @param string $prefix Table prefix to validate
 * @return bool True if valid
 * @throws SecurityException if invalid
 */
function validateTablePrefix($pdo, $prefix) {
    // Check format (must be user_N where N is a number)
    if (!preg_match('/^user_\d+$/', $prefix)) {
        throw new SecurityException('Invalid table prefix format');
    }

    // Verify prefix exists in database
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE table_prefix = ?");
    $stmt->execute([$prefix]);

    if ($stmt->fetchColumn() == 0) {
        throw new SecurityException('Table prefix not found in database');
    }

    return true;
}

/**
 * Get user table names
 *
 * @param string $prefix Optional table prefix (uses current user's prefix if not specified)
 * @return array Associative array of table names
 */
function getUserTableNames($prefix = null) {
    global $pdo;

    // Use current user's prefix if not specified
    if ($prefix === null) {
        $prefix = getCurrentTablePrefix();
    }

    // Validate prefix for security
    validateTablePrefix($pdo, $prefix);

    return [
        'source' => $prefix . '_source',
        'cat_1_labels' => $prefix . '_cat_1_labels',
        'cat_2_labels' => $prefix . '_cat_2_labels',
        'view' => $prefix . '_view'
    ];
}

/**
 * Get table name for specific type
 *
 * @param string $type Table type (source, cat_1_labels, cat_2_labels, view)
 * @param string $prefix Optional table prefix
 * @return string Table name
 */
function getTableName($type, $prefix = null) {
    $tables = getUserTableNames($prefix);

    if (!isset($tables[$type])) {
        throw new InvalidArgumentException("Invalid table type: $type");
    }

    return $tables[$type];
}

/**
 * Execute query with user-specific table names
 *
 * @param PDO $pdo Database connection
 * @param string $sql SQL query with {table} placeholders
 * @param array $params Query parameters
 * @param array $tables Table name mapping
 * @return PDOStatement Executed statement
 */
function executeUserQuery($pdo, $sql, $params = [], $tables = null) {
    if ($tables === null) {
        $tables = getUserTableNames();
    }

    // Replace table placeholders
    foreach ($tables as $key => $tableName) {
        $sql = str_replace('{' . $key . '}', '`' . $tableName . '`', $sql);
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt;
}

/**
 * Check if user tables exist
 *
 * @param PDO $pdo Database connection
 * @param string $prefix Table prefix
 * @return bool True if all tables exist
 */
function userTablesExist($pdo, $prefix) {
    try {
        validateTablePrefix($pdo, $prefix);
        $tables = getUserTableNames($prefix);

        foreach ($tables as $type => $tableName) {
            if ($type === 'view') {
                // Check if view exists
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM information_schema.VIEWS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
                ");
            } else {
                // Check if table exists
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM information_schema.TABLES
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
                ");
            }

            $stmt->execute([$tableName]);
            if ($stmt->fetchColumn() == 0) {
                return false;
            }
        }

        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Create user tables
 *
 * @param PDO $pdo Database connection
 * @param string $prefix Table prefix
 * @param bool $insertSampleData Whether to insert sample data
 * @return bool True if successful
 */
function createUserTables($pdo, $prefix, $insertSampleData = true) {
    try {
        // Validate prefix format
        if (!preg_match('/^user_\d+$/', $prefix)) {
            throw new InvalidArgumentException('Invalid table prefix format');
        }

        // Call stored procedure to create tables
        $stmt = $pdo->prepare("CALL create_user_tables(?)");
        $stmt->execute([$prefix]);

        // Insert sample data if requested
        if ($insertSampleData) {
            $stmt = $pdo->prepare("CALL insert_sample_data(?)");
            $stmt->execute([$prefix]);
        }

        return true;
    } catch (Exception $e) {
        error_log("Failed to create user tables: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete user tables
 *
 * @param PDO $pdo Database connection
 * @param string $prefix Table prefix
 * @return bool True if successful
 */
function deleteUserTables($pdo, $prefix) {
    try {
        validateTablePrefix($pdo, $prefix);

        // Call stored procedure to delete tables
        $stmt = $pdo->prepare("CALL delete_user_tables(?)");
        $stmt->execute([$prefix]);

        return true;
    } catch (Exception $e) {
        error_log("Failed to delete user tables: " . $e->getMessage());
        return false;
    }
}

/**
 * Get table statistics for user
 *
 * @param PDO $pdo Database connection
 * @param string $prefix Table prefix
 * @return array Table statistics
 */
function getUserTableStats($pdo, $prefix) {
    try {
        validateTablePrefix($pdo, $prefix);
        $tables = getUserTableNames($prefix);

        $stats = [];

        // Get transaction count
        $stmt = $pdo->query("SELECT COUNT(*) FROM `{$tables['source']}`");
        $stats['transaction_count'] = $stmt->fetchColumn();

        // Get shop count
        $stmt = $pdo->query("SELECT COUNT(*) FROM `{$tables['cat_1_labels']}` WHERE is_active = TRUE");
        $stats['shop_count'] = $stmt->fetchColumn();

        // Get category count
        $stmt = $pdo->query("SELECT COUNT(*) FROM `{$tables['cat_2_labels']}` WHERE is_active = TRUE");
        $stats['category_count'] = $stmt->fetchColumn();

        // Get date range
        $stmt = $pdo->query("
            SELECT MIN(re_date) as min_date, MAX(re_date) as max_date
            FROM `{$tables['source']}`
        ");
        $dateRange = $stmt->fetch();
        $stats['date_range'] = $dateRange;

        return $stats;
    } catch (Exception $e) {
        error_log("Failed to get table stats: " . $e->getMessage());
        return [];
    }
}

/**
 * Custom exception for security violations
 */
class SecurityException extends Exception {}
