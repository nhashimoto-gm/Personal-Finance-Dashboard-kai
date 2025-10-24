<?php
// functions.php - ビジネスロジック

// トランザクション追加
function addTransaction($pdo, $re_date, $price, $label1, $label2) {
    // 基本検証
    if (empty($re_date) || empty($label1) || empty($label2)) {
        return ['success' => false, 'message' => 'Please enter all required fields'];
    }

    // 日付フォーマット検証
    $date = DateTime::createFromFormat('Y-m-d', $re_date);
    if (!$date || $date->format('Y-m-d') !== $re_date) {
        return ['success' => false, 'message' => 'Invalid date format. Please use YYYY-MM-DD'];
    }

    // 金額検証
    if (!is_numeric($price) || (int)$price <= 0 || (int)$price > 100000000) {
        return ['success' => false, 'message' => 'Invalid amount. Please enter a positive number (max 100,000,000)'];
    }

    // 文字列長検証
    if (strlen($label1) > 255 || strlen($label2) > 255) {
        return ['success' => false, 'message' => 'Shop or category name is too long (max 255 characters)'];
    }

    try {
        $tables = getTableNames();

        // cat_1 IDを取得
        $stmt = $pdo->prepare("SELECT id FROM {$tables['cat_1_labels']} WHERE label = ?");
        $stmt->execute([$label1]);
        $cat_1_result = $stmt->fetch(PDO::FETCH_ASSOC);

        // cat_2 IDを取得
        $stmt = $pdo->prepare("SELECT id FROM {$tables['cat_2_labels']} WHERE label = ?");
        $stmt->execute([$label2]);
        $cat_2_result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cat_1_result || !$cat_2_result) {
            return ['success' => false, 'message' => 'Selected shop or category not found'];
        }

        $cat_1 = $cat_1_result['id'];
        $cat_2 = $cat_2_result['id'];

        $stmt = $pdo->prepare("INSERT INTO {$tables['source']} (re_date, cat_1, cat_2, price) VALUES (?, ?, ?, ?)");
        $stmt->execute([$re_date, $cat_1, $cat_2, $price]);

        return [
            'success' => true,
            'message' => 'Transaction added successfully',
            'data' => [
                're_date' => $re_date,
                'label1' => $label1,
                'label2' => $label2
            ]
        ];
    } catch (PDOException $e) {
        // エラーをログに記録（本番環境では詳細を非表示）
        error_log('Transaction add error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while adding the transaction. Please try again.'];
    }
}

// ショップ追加
function addShop($pdo, $name) {
    $shopName = trim($name);
    if (empty($shopName)) {
        return ['success' => false, 'message' => 'Shop name is required'];
    }

    // 文字列長検証
    if (strlen($shopName) > 255) {
        return ['success' => false, 'message' => 'Shop name is too long (max 255 characters)'];
    }

    try {
        $tables = getTableNames();
        $stmt = $pdo->prepare("INSERT INTO {$tables['cat_1_labels']} (label) VALUES (?)");
        $stmt->execute([$shopName]);
        return ['success' => true, 'message' => 'Shop added successfully'];
    } catch (PDOException $e) {
        // エラーをログに記録
        error_log('Shop add error: ' . $e->getMessage());

        // 重複エラーの場合は特別なメッセージ
        if ($e->getCode() == 23000) {
            return ['success' => false, 'message' => 'Shop name already exists'];
        }

        return ['success' => false, 'message' => 'An error occurred while adding the shop. Please try again.'];
    }
}

// カテゴリ追加
function addCategory($pdo, $name) {
    $categoryName = trim($name);
    if (empty($categoryName)) {
        return ['success' => false, 'message' => 'Category name is required'];
    }

    // 文字列長検証
    if (strlen($categoryName) > 255) {
        return ['success' => false, 'message' => 'Category name is too long (max 255 characters)'];
    }

    try {
        $tables = getTableNames();
        $stmt = $pdo->prepare("INSERT INTO {$tables['cat_2_labels']} (label) VALUES (?)");
        $stmt->execute([$categoryName]);
        return ['success' => true, 'message' => 'Category added successfully'];
    } catch (PDOException $e) {
        // エラーをログに記録
        error_log('Category add error: ' . $e->getMessage());

        // 重複エラーの場合は特別なメッセージ
        if ($e->getCode() == 23000) {
            return ['success' => false, 'message' => 'Category name already exists'];
        }

        return ['success' => false, 'message' => 'An error occurred while adding the category. Please try again.'];
    }
}

// ショップリスト取得
function getShops($pdo) {
    try {
        $tables = getTableNames();
        $stmt = $pdo->query("SELECT label FROM {$tables['cat_1_labels']} ORDER BY label");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        return [];
    }
}

// カテゴリリスト取得
function getCategories($pdo) {
    try {
        $tables = getTableNames();
        $stmt = $pdo->query("SELECT label FROM {$tables['cat_2_labels']} ORDER BY label");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        return [];
    }
}
