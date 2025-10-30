-- ============================================================
-- 既存2ユーザーデータの統合マイグレーションSQL
-- Personal Finance Dashboard - Multi-Account Migration
-- ============================================================
-- このスクリプトは既存の2ユーザー分のテーブルを統合します
--
-- 既存テーブル:
--   User 1: source, cat_1_labels, cat_2_labels, budgets1
--   User 2: source_hiromi, cat_11_labels, cat_12_labels, budgets11
--
-- 実行前の確認事項:
-- 1. データベースのバックアップを取得してください
-- 2. 既存テーブルは _backup サフィックスでリネームされます
-- 3. budgetsテーブルが存在する場合も移行されます
--
-- 使用方法:
--   mysql -u username -p database_name < migration-existing-data.sql
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- STEP 1: usersテーブルの作成
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
-- STEP 2: 2人のユーザーアカウントを作成
-- ============================================================

-- User 1 (メインユーザー)
-- デフォルトパスワード: password123 (必ず変更してください)
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `is_active`)
VALUES (
  'user1',
  'user1@example.com',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  'User 1',
  1
) ON DUPLICATE KEY UPDATE id=id;

-- User 2 (Hiromi)
-- デフォルトパスワード: password123 (必ず変更してください)
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `is_active`)
VALUES (
  'hiromi',
  'hiromi@example.com',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  'Hiromi',
  1
) ON DUPLICATE KEY UPDATE id=id;

-- ユーザーIDを変数に保存
SET @user1_id = (SELECT id FROM users WHERE username = 'user1');
SET @user2_id = (SELECT id FROM users WHERE username = 'hiromi');

-- ============================================================
-- STEP 3: 新しいテーブル構造を作成
-- ============================================================

-- 新しいsourceテーブル
CREATE TABLE IF NOT EXISTS `source_new` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `re_date` DATE NOT NULL,
  `cat_1` INT NOT NULL,
  `cat_2` INT NOT NULL,
  `price` INT NOT NULL,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_re_date` (`re_date`),
  INDEX `idx_cat_1` (`cat_1`),
  INDEX `idx_cat_2` (`cat_2`),
  INDEX `idx_price` (`price`),
  INDEX `idx_user_date` (`user_id`, `re_date`),
  INDEX `idx_user_cat1` (`user_id`, `cat_1`),
  INDEX `idx_user_cat2` (`user_id`, `cat_2`),
  CONSTRAINT `fk_source_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users`(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 新しいcat_1_labelsテーブル（ショップ）
CREATE TABLE IF NOT EXISTS `cat_1_labels_new` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `label` VARCHAR(255) NOT NULL,
  INDEX `idx_user_id` (`user_id`),
  UNIQUE KEY `unique_label_per_user` (`user_id`, `label`),
  CONSTRAINT `fk_cat1_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users`(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 新しいcat_2_labelsテーブル（カテゴリ）
CREATE TABLE IF NOT EXISTS `cat_2_labels_new` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `label` VARCHAR(255) NOT NULL,
  INDEX `idx_user_id` (`user_id`),
  UNIQUE KEY `unique_label_per_user` (`user_id`, `label`),
  CONSTRAINT `fk_cat2_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users`(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 新しいbudgetsテーブル
CREATE TABLE IF NOT EXISTS `budgets_new` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `budget_type` ENUM('monthly', 'category', 'shop') NOT NULL,
  `target_id` INT DEFAULT NULL,
  `target_year` INT NOT NULL,
  `target_month` INT NOT NULL,
  `amount` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_user_type_year_month` (`user_id`, `budget_type`, `target_year`, `target_month`),
  UNIQUE KEY `unique_budget_per_user` (`user_id`, `budget_type`, `target_id`, `target_year`, `target_month`),
  CONSTRAINT `fk_budget_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users`(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- STEP 4: User 1のcat_1_labels（ショップ）データを移行
-- ============================================================

INSERT INTO `cat_1_labels_new` (`user_id`, `label`)
SELECT @user1_id, `label`
FROM `cat_1_labels`
ON DUPLICATE KEY UPDATE `cat_1_labels_new`.`label` = VALUES(`label`);

-- User 1のcat_1_labelsのIDマッピングテーブルを作成（一時テーブル）
CREATE TEMPORARY TABLE cat_1_mapping_user1 (
  old_id INT,
  new_id INT,
  PRIMARY KEY (old_id)
);

INSERT INTO cat_1_mapping_user1 (old_id, new_id)
SELECT old.id, new.id
FROM cat_1_labels old
INNER JOIN cat_1_labels_new new ON old.label = new.label AND new.user_id = @user1_id;

-- ============================================================
-- STEP 5: User 2のcat_1_labels（ショップ）データを移行
-- ============================================================

INSERT INTO `cat_1_labels_new` (`user_id`, `label`)
SELECT @user2_id, `label`
FROM `cat_11_labels`
ON DUPLICATE KEY UPDATE `cat_1_labels_new`.`label` = VALUES(`label`);

-- User 2のcat_1_labelsのIDマッピングテーブルを作成（一時テーブル）
CREATE TEMPORARY TABLE cat_1_mapping_user2 (
  old_id INT,
  new_id INT,
  PRIMARY KEY (old_id)
);

INSERT INTO cat_1_mapping_user2 (old_id, new_id)
SELECT old.id, new.id
FROM cat_11_labels old
INNER JOIN cat_1_labels_new new ON old.label = new.label AND new.user_id = @user2_id;

-- ============================================================
-- STEP 6: User 1のcat_2_labels（カテゴリ）データを移行
-- ============================================================

INSERT INTO `cat_2_labels_new` (`user_id`, `label`)
SELECT @user1_id, `label`
FROM `cat_2_labels`
ON DUPLICATE KEY UPDATE `cat_2_labels_new`.`label` = VALUES(`label`);

-- User 1のcat_2_labelsのIDマッピングテーブルを作成（一時テーブル）
CREATE TEMPORARY TABLE cat_2_mapping_user1 (
  old_id INT,
  new_id INT,
  PRIMARY KEY (old_id)
);

INSERT INTO cat_2_mapping_user1 (old_id, new_id)
SELECT old.id, new.id
FROM cat_2_labels old
INNER JOIN cat_2_labels_new new ON old.label = new.label AND new.user_id = @user1_id;

-- ============================================================
-- STEP 7: User 2のcat_2_labels（カテゴリ）データを移行
-- ============================================================

INSERT INTO `cat_2_labels_new` (`user_id`, `label`)
SELECT @user2_id, `label`
FROM `cat_12_labels`
ON DUPLICATE KEY UPDATE `cat_2_labels_new`.`label` = VALUES(`label`);

-- User 2のcat_2_labelsのIDマッピングテーブルを作成（一時テーブル）
CREATE TEMPORARY TABLE cat_2_mapping_user2 (
  old_id INT,
  new_id INT,
  PRIMARY KEY (old_id)
);

INSERT INTO cat_2_mapping_user2 (old_id, new_id)
SELECT old.id, new.id
FROM cat_12_labels old
INNER JOIN cat_2_labels_new new ON old.label = new.label AND new.user_id = @user2_id;

-- ============================================================
-- STEP 8: User 1のsourceデータを移行（IDマッピングを使用）
-- ============================================================

INSERT INTO `source_new` (`user_id`, `re_date`, `cat_1`, `cat_2`, `price`)
SELECT
  @user1_id,
  s.re_date,
  COALESCE(m1.new_id, s.cat_1) as cat_1,
  COALESCE(m2.new_id, s.cat_2) as cat_2,
  s.price
FROM `source` s
LEFT JOIN cat_1_mapping_user1 m1 ON s.cat_1 = m1.old_id
LEFT JOIN cat_2_mapping_user1 m2 ON s.cat_2 = m2.old_id;

-- ============================================================
-- STEP 9: User 2のsourceデータを移行（IDマッピングを使用）
-- ============================================================

INSERT INTO `source_new` (`user_id`, `re_date`, `cat_1`, `cat_2`, `price`)
SELECT
  @user2_id,
  s.re_date,
  COALESCE(m1.new_id, s.cat_1) as cat_1,
  COALESCE(m2.new_id, s.cat_2) as cat_2,
  s.price
FROM `source_hiromi` s
LEFT JOIN cat_1_mapping_user2 m1 ON s.cat_1 = m1.old_id
LEFT JOIN cat_2_mapping_user2 m2 ON s.cat_2 = m2.old_id;

-- ============================================================
-- STEP 10: budgetsデータを移行（存在する場合）
-- ============================================================

-- User 1のbudgetsデータ（存在する場合）
INSERT INTO `budgets_new` (`user_id`, `budget_type`, `target_id`, `target_year`, `target_month`, `amount`, `created_at`, `updated_at`)
SELECT
  @user1_id,
  budget_type,
  target_id,
  target_year,
  target_month,
  amount,
  COALESCE(created_at, NOW()),
  COALESCE(updated_at, NOW())
FROM `budgets1`
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'budgets1')
ON DUPLICATE KEY UPDATE `budgets_new`.`amount` = VALUES(`amount`);

-- User 2のbudgetsデータ（存在する場合）
INSERT INTO `budgets_new` (`user_id`, `budget_type`, `target_id`, `target_year`, `target_month`, `amount`, `created_at`, `updated_at`)
SELECT
  @user2_id,
  budget_type,
  target_id,
  target_year,
  target_month,
  amount,
  COALESCE(created_at, NOW()),
  COALESCE(updated_at, NOW())
FROM `budgets11`
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'budgets11')
ON DUPLICATE KEY UPDATE `budgets_new`.`amount` = VALUES(`amount`);

-- ============================================================
-- STEP 11: 一時テーブルのクリーンアップ
-- ============================================================

DROP TEMPORARY TABLE IF EXISTS cat_1_mapping_user1;
DROP TEMPORARY TABLE IF EXISTS cat_1_mapping_user2;
DROP TEMPORARY TABLE IF EXISTS cat_2_mapping_user1;
DROP TEMPORARY TABLE IF EXISTS cat_2_mapping_user2;

-- ============================================================
-- STEP 12: 既存テーブルをバックアップとしてリネーム
-- ============================================================

-- User 1のテーブルをバックアップ
RENAME TABLE `source` TO `source_backup_user1`;
RENAME TABLE `cat_1_labels` TO `cat_1_labels_backup_user1`;
RENAME TABLE `cat_2_labels` TO `cat_2_labels_backup_user1`;

-- budgets1テーブルが存在する場合はバックアップ
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'budgets1');
SET @sql = IF(@table_exists > 0, 'RENAME TABLE `budgets1` TO `budgets1_backup_user1`', 'SELECT "budgets1 table does not exist" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- User 2のテーブルをバックアップ
RENAME TABLE `source_hiromi` TO `source_backup_user2`;
RENAME TABLE `cat_11_labels` TO `cat_11_labels_backup_user2`;
RENAME TABLE `cat_12_labels` TO `cat_12_labels_backup_user2`;

-- budgets11テーブルが存在する場合はバックアップ
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'budgets11');
SET @sql = IF(@table_exists > 0, 'RENAME TABLE `budgets11` TO `budgets11_backup_user2`', 'SELECT "budgets11 table does not exist" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- STEP 13: 新しいテーブルを正式なテーブル名にリネーム
-- ============================================================

RENAME TABLE `source_new` TO `source`;
RENAME TABLE `cat_1_labels_new` TO `cat_1_labels`;
RENAME TABLE `cat_2_labels_new` TO `cat_2_labels`;
RENAME TABLE `budgets_new` TO `budgets`;

-- ============================================================
-- STEP 14: monthly_summary_cacheテーブルの処理
-- ============================================================

-- 既存のキャッシュテーブルがある場合はバックアップしてから新規作成
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'monthly_summary_cache');
SET @sql = IF(@table_exists > 0, 'RENAME TABLE `monthly_summary_cache` TO `monthly_summary_cache_backup`', 'SELECT "monthly_summary_cache table does not exist" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 新しいmonthly_summary_cacheテーブルを作成
CREATE TABLE IF NOT EXISTS `monthly_summary_cache` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `year` INT NOT NULL,
  `month` INT NOT NULL,
  `total_amount` BIGINT DEFAULT 0,
  `transaction_count` INT DEFAULT 0,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_user_id` (`user_id`),
  UNIQUE KEY `unique_month_cache_per_user` (`user_id`, `year`, `month`),
  CONSTRAINT `fk_cache_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users`(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- マイグレーション完了！
-- ============================================================
--
-- 以下のテーブルがバックアップとして保存されています:
-- - source_backup_user1, source_backup_user2
-- - cat_1_labels_backup_user1, cat_11_labels_backup_user2
-- - cat_2_labels_backup_user1, cat_12_labels_backup_user2
-- - budgets1_backup_user1, budgets11_backup_user2 (存在する場合)
-- - monthly_summary_cache_backup (存在する場合)
--
-- 新しいテーブル:
-- - users (2ユーザー作成済み)
-- - source (統合済み)
-- - cat_1_labels (統合済み)
-- - cat_2_labels (統合済み)
-- - budgets (統合済み)
-- - monthly_summary_cache (新規作成)
--
-- ログイン情報:
--
-- User 1:
--   ユーザー名: user1
--   メールアドレス: user1@example.com
--   パスワード: password123
--
-- User 2 (Hiromi):
--   ユーザー名: hiromi
--   メールアドレス: hiromi@example.com
--   パスワード: password123
--
-- ⚠️ 重要: 両方のユーザーでログイン後、必ずパスワードを変更してください！
--
-- データ確認クエリ:
--
-- SELECT * FROM users;
-- SELECT user_id, COUNT(*) as count FROM source GROUP BY user_id;
-- SELECT user_id, COUNT(*) as count FROM cat_1_labels GROUP BY user_id;
-- SELECT user_id, COUNT(*) as count FROM cat_2_labels GROUP BY user_id;
--
-- バックアップテーブルの削除（確認後に実行）:
--
-- DROP TABLE source_backup_user1, source_backup_user2;
-- DROP TABLE cat_1_labels_backup_user1, cat_11_labels_backup_user2;
-- DROP TABLE cat_2_labels_backup_user1, cat_12_labels_backup_user2;
-- DROP TABLE IF EXISTS budgets1_backup_user1, budgets11_backup_user2;
-- DROP TABLE IF EXISTS monthly_summary_cache_backup;
--
-- ============================================================
