-- ============================================================
-- Multi-Account Support Migration SQL
-- Personal Finance Dashboard
-- ============================================================
-- This migration adds user authentication and multi-account support
-- to the Personal Finance Dashboard application.
--
-- IMPORTANT: Backup your database before running this migration!
--
-- Usage:
--   mysql -u username -p database_name < migration-multi-account.sql
-- ============================================================

-- Set character set and collation
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- STEP 1: Create users table
-- ============================================================

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(255) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_username` (`username`),
  INDEX `idx_email` (`email`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- STEP 2: Add user_id column to existing tables
-- ============================================================

-- Add user_id to source table (transactions)
ALTER TABLE `source`
  ADD COLUMN `user_id` INT DEFAULT NULL AFTER `id`,
  ADD INDEX `idx_user_id` (`user_id`);

-- Add user_id to cat_1_labels table (shops)
ALTER TABLE `cat_1_labels`
  ADD COLUMN `user_id` INT DEFAULT NULL AFTER `id`,
  ADD INDEX `idx_user_id` (`user_id`);

-- Add user_id to cat_2_labels table (categories)
ALTER TABLE `cat_2_labels`
  ADD COLUMN `user_id` INT DEFAULT NULL AFTER `id`,
  ADD INDEX `idx_user_id` (`user_id`);

-- Add user_id to budgets table
ALTER TABLE `budgets`
  ADD COLUMN `user_id` INT DEFAULT NULL AFTER `id`,
  ADD INDEX `idx_user_id` (`user_id`);

-- Add user_id to monthly_summary_cache table if it exists
ALTER TABLE `monthly_summary_cache`
  ADD COLUMN `user_id` INT DEFAULT NULL AFTER `id`,
  ADD INDEX `idx_user_id` (`user_id`);

-- ============================================================
-- STEP 3: Create default user for existing data
-- ============================================================
-- This creates a default admin user for migration purposes
-- Default credentials: admin / admin123
-- IMPORTANT: Change this password after first login!

INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `is_active`)
VALUES (
  'admin',
  'admin@example.com',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: admin123
  'Administrator',
  1
);

-- Get the default user ID
SET @default_user_id = LAST_INSERT_ID();

-- ============================================================
-- STEP 4: Migrate existing data to default user
-- ============================================================

UPDATE `source` SET `user_id` = @default_user_id WHERE `user_id` IS NULL;
UPDATE `cat_1_labels` SET `user_id` = @default_user_id WHERE `user_id` IS NULL;
UPDATE `cat_2_labels` SET `user_id` = @default_user_id WHERE `user_id` IS NULL;
UPDATE `budgets` SET `user_id` = @default_user_id WHERE `user_id` IS NULL;
UPDATE `monthly_summary_cache` SET `user_id` = @default_user_id WHERE `user_id` IS NULL;

-- ============================================================
-- STEP 5: Add foreign key constraints
-- ============================================================

ALTER TABLE `source`
  MODIFY COLUMN `user_id` INT NOT NULL,
  ADD CONSTRAINT `fk_source_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users`(`id`)
    ON DELETE CASCADE;

ALTER TABLE `cat_1_labels`
  MODIFY COLUMN `user_id` INT NOT NULL,
  ADD CONSTRAINT `fk_cat1_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users`(`id`)
    ON DELETE CASCADE;

ALTER TABLE `cat_2_labels`
  MODIFY COLUMN `user_id` INT NOT NULL,
  ADD CONSTRAINT `fk_cat2_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users`(`id`)
    ON DELETE CASCADE;

ALTER TABLE `budgets`
  MODIFY COLUMN `user_id` INT NOT NULL,
  ADD CONSTRAINT `fk_budget_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users`(`id`)
    ON DELETE CASCADE;

ALTER TABLE `monthly_summary_cache`
  MODIFY COLUMN `user_id` INT NOT NULL,
  ADD CONSTRAINT `fk_cache_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users`(`id`)
    ON DELETE CASCADE;

-- ============================================================
-- STEP 6: Update unique constraints to include user_id
-- ============================================================

-- Drop old unique constraint on cat_1_labels and add new one with user_id
ALTER TABLE `cat_1_labels`
  DROP INDEX `label`,
  ADD UNIQUE KEY `unique_label_per_user` (`user_id`, `label`);

-- Drop old unique constraint on cat_2_labels and add new one with user_id
ALTER TABLE `cat_2_labels`
  DROP INDEX `label`,
  ADD UNIQUE KEY `unique_label_per_user` (`user_id`, `label`);

-- Update budgets unique constraint to include user_id
ALTER TABLE `budgets`
  DROP INDEX `budget_type`,
  ADD UNIQUE KEY `unique_budget_per_user` (`user_id`, `budget_type`, `target_id`, `target_year`, `target_month`);

-- Update cache table constraint to include user_id
ALTER TABLE `monthly_summary_cache`
  DROP INDEX IF EXISTS `unique_month_cache`,
  ADD UNIQUE KEY `unique_month_cache_per_user` (`user_id`, `year`, `month`);

-- ============================================================
-- STEP 7: Recreate views with user_id filtering
-- ============================================================

-- Drop existing views
DROP VIEW IF EXISTS `view1`;
DROP VIEW IF EXISTS `v_monthly_summary`;
DROP VIEW IF EXISTS `v_yearly_summary`;
DROP VIEW IF EXISTS `v_shop_summary`;
DROP VIEW IF EXISTS `v_category_summary`;
DROP VIEW IF EXISTS `v_weekday_stats`;
DROP VIEW IF EXISTS `v_seasonal_pattern`;

-- Recreate view1 with user_id
CREATE VIEW `view1` AS
SELECT
    s.id,
    s.user_id,
    s.re_date,
    c1.label AS shop_name,
    c2.label AS category_name,
    s.cat_1,
    s.cat_2,
    s.price
FROM source s
LEFT JOIN cat_1_labels c1 ON s.cat_1 = c1.id
LEFT JOIN cat_2_labels c2 ON s.cat_2 = c2.id;

-- Recreate v_monthly_summary with user_id
CREATE VIEW `v_monthly_summary` AS
SELECT
    user_id,
    YEAR(re_date) AS year,
    MONTH(re_date) AS month,
    COUNT(*) AS transaction_count,
    SUM(price) AS total_amount,
    AVG(price) AS avg_amount,
    MIN(price) AS min_amount,
    MAX(price) AS max_amount
FROM source
GROUP BY user_id, YEAR(re_date), MONTH(re_date);

-- Recreate v_yearly_summary with user_id
CREATE VIEW `v_yearly_summary` AS
SELECT
    user_id,
    YEAR(re_date) AS year,
    COUNT(*) AS transaction_count,
    SUM(price) AS total_amount,
    AVG(price) AS avg_amount,
    MIN(price) AS min_amount,
    MAX(price) AS max_amount
FROM source
GROUP BY user_id, YEAR(re_date);

-- Recreate v_shop_summary with user_id
CREATE VIEW `v_shop_summary` AS
SELECT
    s.user_id,
    c1.label AS shop_name,
    COUNT(*) AS transaction_count,
    SUM(s.price) AS total_amount,
    AVG(s.price) AS avg_amount,
    MIN(s.re_date) AS first_transaction,
    MAX(s.re_date) AS last_transaction
FROM source s
LEFT JOIN cat_1_labels c1 ON s.cat_1 = c1.id
GROUP BY s.user_id, c1.label;

-- Recreate v_category_summary with user_id
CREATE VIEW `v_category_summary` AS
SELECT
    s.user_id,
    c2.label AS category_name,
    COUNT(*) AS transaction_count,
    SUM(s.price) AS total_amount,
    AVG(s.price) AS avg_amount,
    MIN(s.re_date) AS first_transaction,
    MAX(s.re_date) AS last_transaction
FROM source s
LEFT JOIN cat_2_labels c2 ON s.cat_2 = c2.id
GROUP BY s.user_id, c2.label;

-- Recreate v_weekday_stats with user_id
CREATE VIEW `v_weekday_stats` AS
SELECT
    user_id,
    DAYOFWEEK(re_date) AS weekday,
    DAYNAME(re_date) AS weekday_name,
    COUNT(*) AS transaction_count,
    SUM(price) AS total_amount,
    AVG(price) AS avg_amount
FROM source
GROUP BY user_id, DAYOFWEEK(re_date), DAYNAME(re_date);

-- Recreate v_seasonal_pattern with user_id
CREATE VIEW `v_seasonal_pattern` AS
SELECT
    user_id,
    QUARTER(re_date) AS quarter,
    YEAR(re_date) AS year,
    COUNT(*) AS transaction_count,
    SUM(price) AS total_amount,
    AVG(price) AS avg_amount
FROM source
GROUP BY user_id, QUARTER(re_date), YEAR(re_date);

-- ============================================================
-- STEP 8: Create composite indexes for better performance
-- ============================================================

-- Add composite indexes for common queries
ALTER TABLE `source` ADD INDEX `idx_user_date` (`user_id`, `re_date`);
ALTER TABLE `source` ADD INDEX `idx_user_cat1` (`user_id`, `cat_1`);
ALTER TABLE `source` ADD INDEX `idx_user_cat2` (`user_id`, `cat_2`);
ALTER TABLE `budgets` ADD INDEX `idx_user_type_year_month` (`user_id`, `budget_type`, `target_year`, `target_month`);

-- ============================================================
-- STEP 9: Create user session table (optional)
-- ============================================================

CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `session_token` VARCHAR(255) NOT NULL UNIQUE,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `last_activity` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_session_token` (`session_token`),
  INDEX `idx_expires_at` (`expires_at`),
  CONSTRAINT `fk_session_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users`(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- STEP 10: Create audit log table (optional but recommended)
-- ============================================================

CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `table_name` VARCHAR(100) DEFAULT NULL,
  `record_id` INT DEFAULT NULL,
  `old_values` JSON DEFAULT NULL,
  `new_values` JSON DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_action` (`action`),
  INDEX `idx_created_at` (`created_at`),
  CONSTRAINT `fk_audit_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users`(`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Migration Complete!
-- ============================================================
--
-- Post-Migration Checklist:
-- 1. Verify all tables have user_id column
-- 2. Check foreign key constraints are active
-- 3. Test login with admin/admin123
-- 4. Change default admin password immediately
-- 5. Test creating new user accounts
-- 6. Verify data isolation between users
-- 7. Test all CRUD operations with multiple users
-- 8. Run ANALYZE TABLE on all modified tables
--
-- To analyze tables for optimization:
-- ANALYZE TABLE users, source, cat_1_labels, cat_2_labels, budgets, monthly_summary_cache;
--
-- To verify migration:
-- SELECT table_name, column_name
-- FROM information_schema.columns
-- WHERE column_name = 'user_id'
--   AND table_schema = DATABASE();
-- ============================================================
