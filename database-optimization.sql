-- ========================================
-- パフォーマンス最適化SQL
-- 既存のデータベースに追加して高速化
-- ========================================

-- インデックスの追加（まだない場合）
-- これらは既存のテーブルに影響を与えずに追加できます

-- sourceテーブルの最適化
ALTER TABLE source 
    ADD INDEX IF NOT EXISTS idx_re_date (re_date),
    ADD INDEX IF NOT EXISTS idx_cat_1 (cat_1),
    ADD INDEX IF NOT EXISTS idx_cat_2 (cat_2),
    ADD INDEX IF NOT EXISTS idx_price (price),
    ADD INDEX IF NOT EXISTS idx_date_cat1 (re_date, cat_1),
    ADD INDEX IF NOT EXISTS idx_date_cat2 (re_date, cat_2);

-- ========================================
-- 集計用マテリアライズドビュー（高速化）
-- ========================================

-- 月次サマリービュー
CREATE OR REPLACE VIEW v_monthly_summary AS
SELECT 
    DATE_FORMAT(re_date, '%Y-%m') as month,
    YEAR(re_date) as year,
    MONTH(re_date) as month_num,
    SUM(price) as total_expense,
    COUNT(*) as transaction_count,
    COUNT(DISTINCT re_date) as active_days,
    ROUND(AVG(price)) as avg_transaction,
    MAX(price) as max_transaction,
    MIN(price) as min_transaction,
    COUNT(DISTINCT cat_1) as unique_shops,
    COUNT(DISTINCT cat_2) as unique_categories
FROM source
GROUP BY DATE_FORMAT(re_date, '%Y-%m')
ORDER BY re_date ASC;

-- 年次サマリービュー
CREATE OR REPLACE VIEW v_yearly_summary AS
SELECT 
    YEAR(re_date) as year,
    SUM(price) as total_expense,
    COUNT(*) as transaction_count,
    COUNT(DISTINCT DATE_FORMAT(re_date, '%Y-%m')) as months_count,
    COUNT(DISTINCT re_date) as active_days,
    COUNT(DISTINCT cat_1) as unique_shops,
    COUNT(DISTINCT cat_2) as unique_categories,
    ROUND(AVG(price)) as avg_transaction
FROM source
GROUP BY YEAR(re_date)
ORDER BY year ASC;

-- ショップ別サマリービュー
CREATE OR REPLACE VIEW v_shop_summary AS
SELECT 
    s.cat_1,
    c1.label as shop_name,
    SUM(s.price) as total,
    COUNT(*) as transaction_count,
    ROUND(AVG(s.price)) as avg_amount,
    MIN(s.re_date) as first_purchase,
    MAX(s.re_date) as last_purchase,
    COUNT(DISTINCT DATE_FORMAT(s.re_date, '%Y-%m')) as active_months
FROM source s
LEFT JOIN cat_1_labels c1 ON s.cat_1 = c1.id
GROUP BY s.cat_1, c1.label
ORDER BY total DESC;

-- カテゴリ別サマリービュー
CREATE OR REPLACE VIEW v_category_summary AS
SELECT 
    s.cat_2,
    c2.label as category_name,
    SUM(s.price) as total,
    COUNT(*) as transaction_count,
    ROUND(AVG(s.price)) as avg_amount,
    MIN(s.re_date) as first_transaction,
    MAX(s.re_date) as last_transaction
FROM source s
LEFT JOIN cat_2_labels c2 ON s.cat_2 = c2.id
GROUP BY s.cat_2, c2.label
ORDER BY total DESC;

-- 曜日別統計ビュー
CREATE OR REPLACE VIEW v_weekday_stats AS
SELECT 
    DAYNAME(re_date) as day_name,
    DAYOFWEEK(re_date) as day_num,
    COUNT(DISTINCT re_date) as day_count,
    ROUND(AVG(daily_total)) as avg_expense,
    ROUND(SUM(daily_total)) as total_expense
FROM (
    SELECT re_date, SUM(price) as daily_total
    FROM source
    GROUP BY re_date
) daily
GROUP BY DAYNAME(re_date), DAYOFWEEK(re_date)
ORDER BY day_num;

-- 月別季節性パターンビュー
CREATE OR REPLACE VIEW v_seasonal_pattern AS
SELECT 
    MONTH(re_date) as month,
    COUNT(DISTINCT YEAR(re_date)) as year_count,
    ROUND(AVG(monthly_total)) as avg_expense,
    ROUND(MIN(monthly_total)) as min_expense,
    ROUND(MAX(monthly_total)) as max_expense
FROM (
    SELECT 
        re_date,
        DATE_FORMAT(re_date, '%Y-%m') as ym,
        MONTH(re_date) as month,
        SUM(price) as monthly_total
    FROM source
    GROUP BY DATE_FORMAT(re_date, '%Y-%m')
) monthly
GROUP BY MONTH(re_date)
ORDER BY month;

-- ========================================
-- 実テーブル（さらなる高速化が必要な場合）
-- ========================================

-- 月次サマリーテーブル（実テーブル版）
-- 注意: これは定期的な更新が必要です
CREATE TABLE IF NOT EXISTS monthly_summary_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    month VARCHAR(7) NOT NULL UNIQUE,
    year INT NOT NULL,
    month_num INT NOT NULL,
    total_expense INT NOT NULL,
    transaction_count INT NOT NULL,
    active_days INT NOT NULL,
    unique_shops INT NOT NULL,
    unique_categories INT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_year (year),
    INDEX idx_month (month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 初期データ投入
INSERT INTO monthly_summary_cache 
    (month, year, month_num, total_expense, transaction_count, active_days, unique_shops, unique_categories)
SELECT 
    DATE_FORMAT(re_date, '%Y-%m') as month,
    YEAR(re_date) as year,
    MONTH(re_date) as month_num,
    SUM(price) as total_expense,
    COUNT(*) as transaction_count,
    COUNT(DISTINCT re_date) as active_days,
    COUNT(DISTINCT cat_1) as unique_shops,
    COUNT(DISTINCT cat_2) as unique_categories
FROM source
GROUP BY DATE_FORMAT(re_date, '%Y-%m')
ON DUPLICATE KEY UPDATE
    total_expense = VALUES(total_expense),
    transaction_count = VALUES(transaction_count),
    active_days = VALUES(active_days),
    unique_shops = VALUES(unique_shops),
    unique_categories = VALUES(unique_categories);

-- ========================================
-- 定期更新用ストアドプロシージャ
-- ========================================

DELIMITER //

-- 月次キャッシュ更新プロシージャ
CREATE PROCEDURE IF NOT EXISTS update_monthly_cache()
BEGIN
    INSERT INTO monthly_summary_cache 
        (month, year, month_num, total_expense, transaction_count, active_days, unique_shops, unique_categories)
    SELECT 
        DATE_FORMAT(re_date, '%Y-%m') as month,
        YEAR(re_date) as year,
        MONTH(re_date) as month_num,
        SUM(price) as total_expense,
        COUNT(*) as transaction_count,
        COUNT(DISTINCT re_date) as active_days,
        COUNT(DISTINCT cat_1) as unique_shops,
        COUNT(DISTINCT cat_2) as unique_categories
    FROM source
    WHERE re_date >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 3 MONTH), '%Y-%m-01')
    GROUP BY DATE_FORMAT(re_date, '%Y-%m')
    ON DUPLICATE KEY UPDATE
        total_expense = VALUES(total_expense),
        transaction_count = VALUES(transaction_count),
        active_days = VALUES(active_days),
        unique_shops = VALUES(unique_shops),
        unique_categories = VALUES(unique_categories);
END //

DELIMITER ;

-- ========================================
-- 便利なクエリ集
-- ========================================

-- 1. 直近12ヶ月の詳細統計
-- SELECT * FROM v_monthly_summary WHERE month >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 12 MONTH), '%Y-%m') ORDER BY month DESC;

-- 2. 年別推移
-- SELECT * FROM v_yearly_summary ORDER BY year DESC;

-- 3. トップ10ショップ（全期間）
-- SELECT * FROM v_shop_summary LIMIT 10;

-- 4. カテゴリ別支出（今年）
-- SELECT * FROM v_category_summary WHERE first_transaction >= CONCAT(YEAR(NOW()), '-01-01');

-- 5. 曜日別の平均支出
-- SELECT * FROM v_weekday_stats;

-- 6. 月別季節性パターン
-- SELECT * FROM v_seasonal_pattern;

-- 7. 最近の高額取引（TOP 20）
-- SELECT v.*, c1.label as shop, c2.label as category
-- FROM view1 v
-- ORDER BY v.price DESC
-- LIMIT 20;

-- 8. 月別成長率
-- SELECT 
--     current.month,
--     current.total_expense as current_expense,
--     previous.total_expense as previous_year_expense,
--     ROUND((current.total_expense - previous.total_expense) / previous.total_expense * 100, 2) as growth_rate
-- FROM v_monthly_summary current
-- LEFT JOIN v_monthly_summary previous 
--     ON current.month_num = previous.month_num 
--     AND current.year = previous.year + 1
-- WHERE previous.total_expense IS NOT NULL
-- ORDER BY current.month DESC;

-- ========================================
-- メンテナンス
-- ========================================

-- インデックスの状態確認
-- SHOW INDEX FROM source;

-- テーブルサイズ確認
-- SELECT 
--     table_name AS "Table",
--     ROUND(((data_length + index_length) / 1024 / 1024), 2) AS "Size (MB)"
-- FROM information_schema.TABLES
-- WHERE table_schema = DATABASE()
-- ORDER BY (data_length + index_length) DESC;

-- クエリパフォーマンス分析（EXPLAIN使用）
-- EXPLAIN SELECT * FROM v_monthly_summary WHERE month >= '2024-01';

-- ========================================
-- キャッシュテーブルを使った高速APIクエリ例
-- ========================================

-- analytics-api.php で使用する最適化クエリ

-- 月次データ（キャッシュ使用）
-- SELECT * FROM monthly_summary_cache 
-- WHERE month BETWEEN '2024-01' AND '2024-12'
-- ORDER BY month ASC;

-- 年次データ（ビュー使用）
-- SELECT * FROM v_yearly_summary;

-- ショップランキング（ビュー使用）
-- SELECT * FROM v_shop_summary LIMIT 10;

-- ========================================
-- 注意事項
-- ========================================

-- 1. monthly_summary_cache テーブルを使用する場合、
--    新しいデータが追加されたら定期的に update_monthly_cache() を実行

-- 2. インデックスを追加すると書き込み速度がわずかに低下しますが、
--    読み取り（SELECT）は大幅に高速化されます

-- 3. ビューは常に最新データを表示しますが、
--    キャッシュテーブルは更新が必要です

-- 4. データ量が多い場合は、キャッシュテーブルの使用を推奨
