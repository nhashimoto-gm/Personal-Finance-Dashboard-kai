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

// ショップリスト取得（使用頻度順 - 直近1年間）
function getShops($pdo) {
    try {
        $tables = getTableNames();
        $stmt = $pdo->query("
            SELECT c.label, COUNT(s.id) as usage_count
            FROM {$tables['cat_1_labels']} c
            LEFT JOIN {$tables['source']} s ON c.id = s.cat_1
                AND s.re_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY c.id, c.label
            ORDER BY usage_count DESC, c.label ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        return [];
    }
}

// カテゴリリスト取得（使用頻度順 - 直近1年間）
function getCategories($pdo) {
    try {
        $tables = getTableNames();
        $stmt = $pdo->query("
            SELECT c.label, COUNT(s.id) as usage_count
            FROM {$tables['cat_2_labels']} c
            LEFT JOIN {$tables['source']} s ON c.id = s.cat_2
                AND s.re_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY c.id, c.label
            ORDER BY usage_count DESC, c.label ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        return [];
    }
}

// トランザクション取得（単一）
function getTransaction($pdo, $id) {
    try {
        $tables = getTableNames();
        $stmt = $pdo->prepare("SELECT * FROM {$tables['view1']} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Transaction fetch error: ' . $e->getMessage());
        return null;
    }
}

// トランザクション更新
function updateTransaction($pdo, $id, $re_date, $price, $label1, $label2) {
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

        $stmt = $pdo->prepare("UPDATE {$tables['source']} SET re_date = ?, cat_1 = ?, cat_2 = ?, price = ? WHERE id = ?");
        $stmt->execute([$re_date, $cat_1, $cat_2, $price, $id]);

        return ['success' => true, 'message' => 'Transaction updated successfully'];
    } catch (PDOException $e) {
        error_log('Transaction update error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while updating the transaction. Please try again.'];
    }
}

// トランザクション削除
function deleteTransaction($pdo, $id) {
    if (!is_numeric($id) || (int)$id <= 0) {
        return ['success' => false, 'message' => 'Invalid transaction ID'];
    }

    try {
        $tables = getTableNames();

        // トランザクションが存在するか確認
        $stmt = $pdo->prepare("SELECT id FROM {$tables['source']} WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'message' => 'Transaction not found'];
        }

        // 削除実行
        $stmt = $pdo->prepare("DELETE FROM {$tables['source']} WHERE id = ?");
        $stmt->execute([$id]);

        return ['success' => true, 'message' => 'Transaction deleted successfully'];
    } catch (PDOException $e) {
        error_log('Transaction delete error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while deleting the transaction. Please try again.'];
    }
}

// 予算追加または更新
function setBudget($pdo, $budget_type, $target_id, $target_year, $target_month, $amount) {
    // バリデーション
    if (!in_array($budget_type, ['monthly', 'category', 'shop'])) {
        return ['success' => false, 'message' => 'Invalid budget type'];
    }

    if (!is_numeric($target_year) || $target_year < 2000 || $target_year > 2100) {
        return ['success' => false, 'message' => 'Invalid year'];
    }

    if (!is_numeric($target_month) || $target_month < 1 || $target_month > 12) {
        return ['success' => false, 'message' => 'Invalid month'];
    }

    if (!is_numeric($amount) || (int)$amount <= 0 || (int)$amount > 100000000) {
        return ['success' => false, 'message' => 'Invalid amount. Please enter a positive number (max 100,000,000)'];
    }

    try {
        $tables = getTableNames();

        // 予算が既に存在するかチェック
        $stmt = $pdo->prepare("SELECT id FROM {$tables['budgets']} WHERE budget_type = ? AND target_id <=> ? AND target_year = ? AND target_month = ?");
        $stmt->execute([$budget_type, $target_id, $target_year, $target_month]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // 更新
            $stmt = $pdo->prepare("UPDATE {$tables['budgets']} SET amount = ? WHERE id = ?");
            $stmt->execute([$amount, $existing['id']]);
            $message = 'Budget updated successfully';
        } else {
            // 新規追加
            $stmt = $pdo->prepare("INSERT INTO {$tables['budgets']} (budget_type, target_id, target_year, target_month, amount) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$budget_type, $target_id, $target_year, $target_month, $amount]);
            $message = 'Budget added successfully';
        }

        return ['success' => true, 'message' => $message];
    } catch (PDOException $e) {
        error_log('Budget set error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while setting the budget. Please try again.'];
    }
}

// 予算削除
function deleteBudget($pdo, $id) {
    if (!is_numeric($id) || (int)$id <= 0) {
        return ['success' => false, 'message' => 'Invalid budget ID'];
    }

    try {
        $tables = getTableNames();

        $stmt = $pdo->prepare("DELETE FROM {$tables['budgets']} WHERE id = ?");
        $stmt->execute([$id]);

        return ['success' => true, 'message' => 'Budget deleted successfully'];
    } catch (PDOException $e) {
        error_log('Budget delete error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while deleting the budget. Please try again.'];
    }
}
