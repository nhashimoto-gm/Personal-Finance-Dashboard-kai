-- Personal Finance Dashboard Database Schema

-- sourceテーブル（取引データ）
CREATE TABLE IF NOT EXISTS source (
    id INT AUTO_INCREMENT PRIMARY KEY,
    re_date DATE NOT NULL,
    cat_1 INT NOT NULL,
    cat_2 INT NOT NULL,
    price INT NOT NULL,
    INDEX idx_re_date (re_date),
    INDEX idx_cat_1 (cat_1),
    INDEX idx_cat_2 (cat_2)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- cat_1_labelsテーブル（ショップマスタ）
CREATE TABLE IF NOT EXISTS cat_1_labels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(255) NOT NULL,
    UNIQUE KEY unique_label (label)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- cat_2_labelsテーブル（カテゴリマスタ）
CREATE TABLE IF NOT EXISTS cat_2_labels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(255) NOT NULL,
    UNIQUE KEY unique_label (label)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- view1ビュー（取引データとラベルを結合）
CREATE OR REPLACE VIEW view1 AS
SELECT 
    s.id,
    s.re_date,
    s.cat_1,
    s.cat_2,
    s.price,
    c1.label AS label1,
    c2.label AS label2
FROM source s
LEFT JOIN cat_1_labels c1 ON s.cat_1 = c1.id
LEFT JOIN cat_2_labels c2 ON s.cat_2 = c2.id;

-- サンプルデータ（オプション）
INSERT INTO cat_1_labels (label) VALUES 
('Supermarket'),
('Restaurant'),
('Online Shop'),
('Convenience Store'),
('Others');

INSERT INTO cat_2_labels (label) VALUES
('Food'),
('Drink'),
('Daily Goods'),
('Entertainment'),
('Transportation'),
('Others');

-- budgetsテーブル（予算管理）
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