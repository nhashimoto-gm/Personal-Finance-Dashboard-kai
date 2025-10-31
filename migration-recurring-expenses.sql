-- ============================================================
-- Recurring Expenses Migration SQL
-- Personal Finance Dashboard
-- ============================================================
-- This migration adds recurring expense support to track monthly
-- recurring payments like rent, subscriptions, utilities, etc.
--
-- IMPORTANT: Backup your database before running this migration!
--
-- Usage:
--   mysql -u username -p database_name < migration-recurring-expenses.sql
-- ============================================================

-- Set character set and collation
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- Create recurring_expenses table
-- ============================================================

CREATE TABLE IF NOT EXISTS `recurring_expenses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL COMMENT 'Name of recurring expense (e.g., Rent, Netflix)',
  `cat_1` INT NOT NULL COMMENT 'Shop/Service ID (foreign key to cat_1_labels)',
  `cat_2` INT NOT NULL COMMENT 'Category ID (foreign key to cat_2_labels)',
  `price` INT NOT NULL COMMENT 'Amount of expense',
  `day_of_month` INT NOT NULL COMMENT 'Day of month (1-31) when expense occurs',
  `start_date` DATE NOT NULL COMMENT 'Start date of recurring expense',
  `end_date` DATE DEFAULT NULL COMMENT 'End date of recurring expense (NULL = ongoing)',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Active status (1=active, 0=inactive)',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  -- Indexes
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_user_active` (`user_id`, `is_active`),
  INDEX `idx_cat_1` (`cat_1`),
  INDEX `idx_cat_2` (`cat_2`),
  INDEX `idx_start_date` (`start_date`),
  INDEX `idx_end_date` (`end_date`),

  -- Foreign key constraints
  CONSTRAINT `fk_recurring_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users`(`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_recurring_cat1`
    FOREIGN KEY (`cat_1`)
    REFERENCES `cat_1_labels`(`id`)
    ON DELETE RESTRICT,
  CONSTRAINT `fk_recurring_cat2`
    FOREIGN KEY (`cat_2`)
    REFERENCES `cat_2_labels`(`id`)
    ON DELETE RESTRICT,

  -- Constraints
  CONSTRAINT `chk_day_of_month` CHECK (`day_of_month` >= 1 AND `day_of_month` <= 31),
  CONSTRAINT `chk_price_positive` CHECK (`price` > 0),
  CONSTRAINT `chk_dates` CHECK (`end_date` IS NULL OR `end_date` >= `start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Create view for recurring expenses with labels
-- ============================================================

CREATE OR REPLACE VIEW `v_recurring_expenses` AS
SELECT
    r.id,
    r.user_id,
    r.name,
    r.cat_1,
    r.cat_2,
    c1.label AS shop_name,
    c2.label AS category_name,
    r.price,
    r.day_of_month,
    r.start_date,
    r.end_date,
    r.is_active,
    r.created_at,
    r.updated_at
FROM recurring_expenses r
LEFT JOIN cat_1_labels c1 ON r.cat_1 = c1.id
LEFT JOIN cat_2_labels c2 ON r.cat_2 = c2.id;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Migration Complete!
-- ============================================================
--
-- Post-Migration Checklist:
-- 1. Verify recurring_expenses table exists
-- 2. Verify v_recurring_expenses view exists
-- 3. Test adding a recurring expense
-- 4. Test updating a recurring expense
-- 5. Test deactivating a recurring expense
-- 6. Verify foreign key constraints work correctly
--
-- To verify migration:
-- SHOW CREATE TABLE recurring_expenses;
-- SELECT * FROM v_recurring_expenses;
-- ============================================================
