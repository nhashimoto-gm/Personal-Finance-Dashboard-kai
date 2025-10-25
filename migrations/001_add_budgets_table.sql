-- Migration: Add budgets table
-- This migration adds the budgets table to an existing database
-- Run this if you're upgrading from an older version without budget functionality

-- Create budgets table
CREATE TABLE IF NOT EXISTS budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    budget_type ENUM('monthly', 'category', 'shop') NOT NULL DEFAULT 'monthly',
    target_id INT DEFAULT NULL COMMENT 'Category ID or Shop ID (NULL for overall monthly budget)',
    target_year INT NOT NULL,
    target_month INT NOT NULL COMMENT 'Month (1-12)',
    amount INT NOT NULL COMMENT 'Budget amount',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_budget (budget_type, target_id, target_year, target_month),
    INDEX idx_year_month (target_year, target_month),
    INDEX idx_type (budget_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Verify the table was created
SELECT 'Budgets table created successfully' AS status;
