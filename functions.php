<?php
// functions.php - ビジネスロジック

// トランザクション追加
function addTransaction($pdo, $re_date, $price, $label1, $label2) {
    if (empty($re_date) || $price <= 0 || empty($label1) || empty($label2)) {
        return ['success' => false, 'message' => 'Please enter all required fields in correct format'];
    }
    
    try {
        // cat_1 IDを取得
        $stmt = $pdo->prepare("SELECT id FROM cat_1_labels WHERE label = ?");
        $stmt->execute([$label1]);
        $cat_1_result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // cat_2 IDを取得
        $stmt = $pdo->prepare("SELECT id FROM cat_2_labels WHERE label = ?");
        $stmt->execute([$label2]);
        $cat_2_result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cat_1_result || !$cat_2_result) {
            return ['success' => false, 'message' => 'Selected shop or category not found'];
        }
        
        $cat_1 = $cat_1_result['id'];
        $cat_2 = $cat_2_result['id'];
        
        $stmt = $pdo->prepare("INSERT INTO source (re_date, cat_1, cat_2, price) VALUES (?, ?, ?, ?)");
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
        return ['success' => false, 'message' => 'Error occurred: ' . $e->getMessage()];
    }
}

// ショップ追加
function addShop($pdo, $name) {
    $shopName = trim($name);
    if (empty($shopName)) {
        return ['success' => false, 'message' => 'Shop name is required'];
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO cat_1_labels (label) VALUES (?)");
        $stmt->execute([$shopName]);
        return ['success' => true, 'message' => 'Shop added successfully'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error occurred: ' . $e->getMessage()];
    }
}

// カテゴリ追加
function addCategory($pdo, $name) {
    $categoryName = trim($name);
    if (empty($categoryName)) {
        return ['success' => false, 'message' => 'Category name is required'];
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO cat_2_labels (label) VALUES (?)");
        $stmt->execute([$categoryName]);
        return ['success' => true, 'message' => 'Category added successfully'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error occurred: ' . $e->getMessage()];
    }
}

// ショップリスト取得
function getShops($pdo) {
    try {
        $stmt = $pdo->query("SELECT label FROM cat_1_labels ORDER BY label");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        return [];
    }
}

// カテゴリリスト取得
function getCategories($pdo) {
    try {
        $stmt = $pdo->query("SELECT label FROM cat_2_labels ORDER BY label");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        return [];
    }
}
