-- Personal Finance Dashboard - Multi-User Version
-- Database Schema
-- Version: 1.0.0
-- Last Updated: 2025-10-23

-- ==================================================
-- SHARED TABLES (Common to all users)
-- ==================================================

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100),
    table_prefix VARCHAR(50) NOT NULL UNIQUE,
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(100),
    password_reset_token VARCHAR(100),
    password_reset_expires DATETIME,
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_table_prefix (table_prefix),
    INDEX idx_is_active (is_active),
    INDEX idx_email_verified (email_verified)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='User account management';

-- Sessions Table
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    payload TEXT,
    last_activity INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Session management';

-- Login Attempts Table (Security)
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username_or_email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    success BOOLEAN DEFAULT FALSE,

    INDEX idx_username_email (username_or_email),
    INDEX idx_ip_address (ip_address),
    INDEX idx_attempted_at (attempted_at),
    INDEX idx_composite (ip_address, attempted_at, success)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Login attempt tracking for rate limiting';

-- User Preferences Table
CREATE TABLE IF NOT EXISTS user_preferences (
    user_id INT PRIMARY KEY,
    language VARCHAR(5) DEFAULT 'ja',
    theme VARCHAR(20) DEFAULT 'light',
    timezone VARCHAR(50) DEFAULT 'Asia/Tokyo',
    date_format VARCHAR(20) DEFAULT 'Y-m-d',
    currency VARCHAR(3) DEFAULT 'JPY',
    items_per_page INT DEFAULT 20,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='User preferences and settings';

-- ==================================================
-- USER-SPECIFIC TABLES (Created dynamically per user)
-- ==================================================

-- These tables are created automatically when a user registers
-- Template for user tables:
--
-- {prefix}_source (Transaction data)
-- {prefix}_cat_1_labels (Shop master)
-- {prefix}_cat_2_labels (Category master)
-- {prefix}_view (Joined view)
--
-- Example for user_1:
-- user_1_source
-- user_1_cat_1_labels
-- user_1_cat_2_labels
-- user_1_view

-- ==================================================
-- STORED PROCEDURES
-- ==================================================

DELIMITER //

-- Create User Tables Procedure
CREATE PROCEDURE IF NOT EXISTS create_user_tables(IN prefix VARCHAR(50))
BEGIN
    -- Declare variables for dynamic SQL
    SET @source_table = CONCAT(prefix, '_source');
    SET @cat1_table = CONCAT(prefix, '_cat_1_labels');
    SET @cat2_table = CONCAT(prefix, '_cat_2_labels');
    SET @view_name = CONCAT(prefix, '_view');

    -- Create source table (transactions)
    SET @sql = CONCAT('
        CREATE TABLE IF NOT EXISTS `', @source_table, '` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            re_date DATE NOT NULL,
            cat_1 INT NOT NULL,
            cat_2 INT NOT NULL,
            price INT NOT NULL,
            memo TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_re_date (re_date),
            INDEX idx_cat_1 (cat_1),
            INDEX idx_cat_2 (cat_2),
            INDEX idx_created_at (created_at),
            INDEX idx_composite_date_cat (re_date, cat_1, cat_2)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT=''User transaction data''
    ');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    -- Create cat_1_labels table (shops)
    SET @sql = CONCAT('
        CREATE TABLE IF NOT EXISTS `', @cat1_table, '` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            label VARCHAR(255) NOT NULL,
            sort_order INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_label (label),
            INDEX idx_is_active (is_active),
            INDEX idx_sort_order (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT=''Shop master data''
    ');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    -- Create cat_2_labels table (categories)
    SET @sql = CONCAT('
        CREATE TABLE IF NOT EXISTS `', @cat2_table, '` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            label VARCHAR(255) NOT NULL,
            sort_order INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_label (label),
            INDEX idx_is_active (is_active),
            INDEX idx_sort_order (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT=''Category master data''
    ');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    -- Create view
    SET @sql = CONCAT('
        CREATE OR REPLACE VIEW `', @view_name, '` AS
        SELECT
            s.id,
            s.re_date,
            s.cat_1,
            s.cat_2,
            s.price,
            s.memo,
            c1.label AS label1,
            c2.label AS label2,
            s.created_at,
            s.updated_at
        FROM `', @source_table, '` s
        LEFT JOIN `', @cat1_table, '` c1 ON s.cat_1 = c1.id
        LEFT JOIN `', @cat2_table, '` c2 ON s.cat_2 = c2.id
    ');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

END//

-- Insert Sample Data Procedure
CREATE PROCEDURE IF NOT EXISTS insert_sample_data(IN prefix VARCHAR(50))
BEGIN
    -- Declare variables
    SET @cat1_table = CONCAT(prefix, '_cat_1_labels');
    SET @cat2_table = CONCAT(prefix, '_cat_2_labels');
    SET @source_table = CONCAT(prefix, '_source');

    -- Insert sample shops
    SET @sql = CONCAT('
        INSERT INTO `', @cat1_table, '` (label, sort_order) VALUES
        (''Supermarket'', 1),
        (''Restaurant'', 2),
        (''Online Shop'', 3),
        (''Convenience Store'', 4),
        (''Others'', 99)
    ');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    -- Insert sample categories
    SET @sql = CONCAT('
        INSERT INTO `', @cat2_table, '` (label, sort_order) VALUES
        (''Food'', 1),
        (''Drink'', 2),
        (''Daily Goods'', 3),
        (''Entertainment'', 4),
        (''Transportation'', 5),
        (''Others'', 99)
    ');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    -- Insert sample transactions
    SET @sql = CONCAT('
        INSERT INTO `', @source_table, '` (re_date, cat_1, cat_2, price, memo) VALUES
        (CURDATE(), 1, 1, 3000, ''Sample transaction 1''),
        (CURDATE() - INTERVAL 1 DAY, 2, 1, 1500, ''Sample transaction 2''),
        (CURDATE() - INTERVAL 2 DAY, 1, 3, 800, ''Sample transaction 3'')
    ');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

END//

-- Delete User Tables Procedure
CREATE PROCEDURE IF NOT EXISTS delete_user_tables(IN prefix VARCHAR(50))
BEGIN
    -- Declare variables
    SET @source_table = CONCAT(prefix, '_source');
    SET @cat1_table = CONCAT(prefix, '_cat_1_labels');
    SET @cat2_table = CONCAT(prefix, '_cat_2_labels');
    SET @view_name = CONCAT(prefix, '_view');

    -- Drop view first
    SET @sql = CONCAT('DROP VIEW IF EXISTS `', @view_name, '`');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    -- Drop tables
    SET @sql = CONCAT('DROP TABLE IF EXISTS `', @source_table, '`');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    SET @sql = CONCAT('DROP TABLE IF EXISTS `', @cat1_table, '`');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    SET @sql = CONCAT('DROP TABLE IF EXISTS `', @cat2_table, '`');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

END//

DELIMITER ;

-- ==================================================
-- CLEANUP OLD LOGIN ATTEMPTS (Optional, run periodically)
-- ==================================================

-- Event to clean up old login attempts (keep last 30 days)
-- Uncomment to enable
/*
CREATE EVENT IF NOT EXISTS cleanup_login_attempts
ON SCHEDULE EVERY 1 DAY
DO
DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
*/

-- ==================================================
-- INITIAL DATA
-- ==================================================

-- No initial users - users are created through registration

-- ==================================================
-- VERIFICATION QUERIES
-- ==================================================

-- Run these to verify the schema was created correctly:

-- Show all tables
-- SHOW TABLES;

-- Show users table structure
-- DESCRIBE users;

-- Test creating user tables for user_1
-- CALL create_user_tables('user_1');
-- SHOW TABLES;

-- Test inserting sample data
-- CALL insert_sample_data('user_1');
-- SELECT * FROM user_1_view;

-- ==================================================
-- NOTES
-- ==================================================

/*
TABLE NAMING CONVENTION:
- Prefix format: user_{id} where {id} is the user's ID from the users table
- Example: user_1_source, user_1_cat_1_labels, user_1_cat_2_labels, user_1_view

SECURITY NOTES:
- Always validate table_prefix before using in queries
- Use prepared statements for all user input
- Implement rate limiting for login attempts
- Enable HTTPS in production

PERFORMANCE NOTES:
- All tables have appropriate indexes
- Consider partitioning login_attempts table if it grows large
- Use connection pooling for better performance
- Implement caching for frequently accessed data

BACKUP STRATEGY:
- Regular full database backups
- Consider per-user table backups for large installations
- Test restore procedures regularly

SCALING CONSIDERATIONS:
- Current design supports up to ~1,000 users efficiently
- Beyond 1,000 users, consider row-level separation approach
- Monitor table count: SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE();
*/
