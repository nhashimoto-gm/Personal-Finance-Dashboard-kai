-- migration-add-indexes.sql
-- パフォーマンス改善のためのインデックス追加マイグレーション
-- 作成日: 2025-11-07
-- 目的: user_id カラムにインデックスを追加してクエリパフォーマンスを向上

-- =============================================
-- このスクリプトの実行方法:
-- mysql -u [username] -p [database_name] < migration-add-indexes.sql
-- =============================================

-- インデックスが既に存在するかチェックしてから追加する安全な方法を使用

-- sourceテーブルにuser_idインデックスを追加
-- このインデックスはWHERE user_id = ?のクエリを高速化します
SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE source ADD INDEX idx_user_id (user_id)',
        'SELECT "Index idx_user_id already exists on source table" as message'
    )
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name = 'source'
    AND index_name = 'idx_user_id'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- sourceテーブルにuser_id + re_date複合インデックスを追加
-- このインデックスはWHERE user_id = ? AND re_date BETWEEN ? AND ?のクエリを高速化します
SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE source ADD INDEX idx_user_re_date (user_id, re_date)',
        'SELECT "Index idx_user_re_date already exists on source table" as message'
    )
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name = 'source'
    AND index_name = 'idx_user_re_date'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- cat_1_labelsテーブルにuser_idインデックスを追加
SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE cat_1_labels ADD INDEX idx_user_id (user_id)',
        'SELECT "Index idx_user_id already exists on cat_1_labels table" as message'
    )
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name = 'cat_1_labels'
    AND index_name = 'idx_user_id'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- cat_2_labelsテーブルにuser_idインデックスを追加
SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE cat_2_labels ADD INDEX idx_user_id (user_id)',
        'SELECT "Index idx_user_id already exists on cat_2_labels table" as message'
    )
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name = 'cat_2_labels'
    AND index_name = 'idx_user_id'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- recurring_expensesテーブルにuser_idインデックスを追加
SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE recurring_expenses ADD INDEX idx_user_id (user_id)',
        'SELECT "Index idx_user_id already exists on recurring_expenses table" as message'
    )
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name = 'recurring_expenses'
    AND index_name = 'idx_user_id'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- budgetsテーブルにuser_idインデックスを追加
SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE budgets ADD INDEX idx_user_id (user_id)',
        'SELECT "Index idx_user_id already exists on budgets table" as message'
    )
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name = 'budgets'
    AND index_name = 'idx_user_id'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- インデックスの追加が完了したことを確認
SELECT
    table_name,
    index_name,
    GROUP_CONCAT(column_name ORDER BY seq_in_index) as columns
FROM information_schema.statistics
WHERE table_schema = DATABASE()
AND index_name LIKE 'idx_user%'
GROUP BY table_name, index_name
ORDER BY table_name, index_name;

-- 完了メッセージ
SELECT 'Migration completed: User ID indexes added successfully' as status;
