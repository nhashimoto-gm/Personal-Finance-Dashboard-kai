<?php
// queries.php - データ取得クエリ（Multi-Account対応）

// サマリー取得
function getSummary($pdo, $user_id, $start_date, $end_date) {
    // ユーザーID検証
    if (!is_numeric($user_id) || (int)$user_id <= 0) {
        return ['total' => 0, 'record_count' => 0, 'shop_count' => 0];
    }

    $tables = getTableNames();
    $stmt = $pdo->prepare("
        SELECT SUM(price) as total, COUNT(*) as record_count, COUNT(DISTINCT cat_1) as shop_count
        FROM {$tables['source']}
        WHERE user_id = ? AND re_date BETWEEN ? AND ?
    ");
    $stmt->execute([$user_id, $start_date, $end_date]);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);

    return [
        'total' => $summary['total'] ?? 0,
        'record_count' => $summary['record_count'] ?? 0,
        'shop_count' => $summary['shop_count'] ?? 0
    ];
}

// アクティブ日数取得
function getActiveDays($pdo, $user_id, $start_date, $end_date) {
    // ユーザーID検証
    if (!is_numeric($user_id) || (int)$user_id <= 0) {
        return 1;
    }

    $tables = getTableNames();
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT re_date) as active_days
        FROM {$tables['source']}
        WHERE user_id = ? AND re_date BETWEEN ? AND ?
    ");
    $stmt->execute([$user_id, $start_date, $end_date]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['active_days'] ?? 1;
}

// ショップ別集計取得
function getShopData($pdo, $user_id, $start_date, $end_date) {
    // ユーザーID検証
    if (!is_numeric($user_id) || (int)$user_id <= 0) {
        return ['above_4pct' => [], 'below_4pct_total' => 0, 'others_shop' => null];
    }

    $tables = getTableNames();
    $stmt = $pdo->prepare("
        SELECT s.cat_1, c1.label as label1, SUM(s.price) as total
        FROM {$tables['source']} s
        LEFT JOIN {$tables['cat_1_labels']} c1 ON s.cat_1 = c1.id
        WHERE s.user_id = ? AND s.re_date BETWEEN ? AND ?
        GROUP BY s.cat_1, c1.label
        ORDER BY total DESC
    ");
    $stmt->execute([$user_id, $start_date, $end_date]);
    $shop_data_all = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $shop_data_raw = [];
    $others_shop = null;

    foreach ($shop_data_all as $d) {
        if ($d['label1'] === 'その他' || $d['label1'] === 'Others') {
            $others_shop = $d;
        } else {
            $shop_data_raw[] = $d;
        }
    }

    $shop_data_above_4pct = array_slice($shop_data_raw, 0, 7);

    $unification_others_total = 0;
    for ($i = 7; $i < count($shop_data_raw); $i++) {
        $unification_others_total += (float)$shop_data_raw[$i]['total'];
    }

    return [
        'above_4pct' => $shop_data_above_4pct,
        'below_4pct_total' => $unification_others_total,
        'others_shop' => $others_shop
    ];
}

// カテゴリ別集計取得
function getCategoryData($pdo, $user_id, $start_date, $end_date) {
    // ユーザーID検証
    if (!is_numeric($user_id) || (int)$user_id <= 0) {
        return [];
    }

    $tables = getTableNames();
    $stmt = $pdo->prepare("
        SELECT s.cat_2, c2.label as label2, SUM(s.price) as total
        FROM {$tables['source']} s
        LEFT JOIN {$tables['cat_2_labels']} c2 ON s.cat_2 = c2.id
        WHERE s.user_id = ? AND s.re_date BETWEEN ? AND ?
        GROUP BY s.cat_2, c2.label
        ORDER BY total DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id, $start_date, $end_date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 日別推移取得
function getDailyData($pdo, $user_id, $start_date, $end_date) {
    // ユーザーID検証
    if (!is_numeric($user_id) || (int)$user_id <= 0) {
        return [];
    }

    $tables = getTableNames();
    $stmt = $pdo->prepare("
        SELECT re_date, SUM(price) as daily_total
        FROM {$tables['source']}
        WHERE user_id = ? AND re_date BETWEEN ? AND ?
        GROUP BY re_date
        ORDER BY re_date
    ");
    $stmt->execute([$user_id, $start_date, $end_date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 期間別推移取得
function getPeriodData($pdo, $user_id, $period_range) {
    // ユーザーID検証
    if (!is_numeric($user_id) || (int)$user_id <= 0) {
        return [];
    }

    $tables = getTableNames();
    $period_is_monthly = $period_range < 60;

    if ($period_is_monthly) {
        $period_query = "
            SELECT
                DATE_FORMAT(s.re_date, '%Y-%m') as period,
                c1.label as shop_name,
                SUM(s.price) as total
            FROM {$tables['source']} s
            LEFT JOIN {$tables['cat_1_labels']} c1 ON s.cat_1 = c1.id
            WHERE s.user_id = ? AND s.re_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY DATE_FORMAT(s.re_date, '%Y-%m'), c1.label
            ORDER BY period, shop_name
        ";
    } else {
        $period_query = "
            SELECT
                YEAR(s.re_date) as period,
                c1.label as shop_name,
                SUM(s.price) as total
            FROM {$tables['source']} s
            LEFT JOIN {$tables['cat_1_labels']} c1 ON s.cat_1 = c1.id
            WHERE s.user_id = ? AND s.re_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY YEAR(s.re_date), c1.label
            ORDER BY period, shop_name
        ";
    }

    $stmt = $pdo->prepare($period_query);
    $stmt->execute([$user_id, $period_range]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 最新取引履歴取得
function getRecentTransactions($pdo, $user_id, $start_date, $end_date, $search_shop, $search_category, $recent_limit) {
    // ユーザーID検証
    if (!is_numeric($user_id) || (int)$user_id <= 0) {
        return [];
    }

    $tables = getTableNames();
    $recent_sql = "SELECT s.id, s.re_date, c1.label as label1, c2.label as label2, s.price
                   FROM {$tables['source']} s
                   LEFT JOIN {$tables['cat_1_labels']} c1 ON s.cat_1 = c1.id
                   LEFT JOIN {$tables['cat_2_labels']} c2 ON s.cat_2 = c2.id
                   WHERE s.user_id = ? AND s.re_date BETWEEN ? AND ?";
    $recent_params = [$user_id, $start_date, $end_date];

    if (!empty($search_shop)) {
        $recent_sql .= " AND c1.label = ?";
        $recent_params[] = $search_shop;
    }

    if (!empty($search_category)) {
        $recent_sql .= " AND c2.label = ?";
        $recent_params[] = $search_category;
    }

    $recent_sql .= " ORDER BY s.re_date DESC, s.id DESC LIMIT " . (int)$recent_limit;

    $stmt = $pdo->prepare($recent_sql);
    $stmt->execute($recent_params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 検索結果取得
function getSearchResults($pdo, $user_id, $search_shop, $search_category, $search_limit) {
    // ユーザーID検証
    if (!is_numeric($user_id) || (int)$user_id <= 0) {
        return [];
    }

    if (empty($search_shop) && empty($search_category)) {
        return [];
    }

    $tables = getTableNames();
    $search_sql = "SELECT s.id, s.re_date, c1.label as label1, c2.label as label2, s.price
                   FROM {$tables['source']} s
                   LEFT JOIN {$tables['cat_1_labels']} c1 ON s.cat_1 = c1.id
                   LEFT JOIN {$tables['cat_2_labels']} c2 ON s.cat_2 = c2.id
                   WHERE s.user_id = ?";
    $params = [$user_id];

    if (!empty($search_shop)) {
        $search_sql .= " AND c1.label = ?";
        $params[] = $search_shop;
    }

    if (!empty($search_category)) {
        $search_sql .= " AND c2.label = ?";
        $params[] = $search_category;
    }

    $search_sql .= " ORDER BY s.re_date DESC, s.id DESC LIMIT " . (int)$search_limit;

    $stmt = $pdo->prepare($search_sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 予算一覧取得
function getBudgets($pdo, $user_id, $year = null, $month = null) {
    // ユーザーID検証
    if (!is_numeric($user_id) || (int)$user_id <= 0) {
        return [];
    }

    try {
        $tables = getTableNames();
        $sql = "SELECT * FROM {$tables['budgets']} WHERE user_id = ?";
        $params = [$user_id];

        if ($year !== null) {
            $sql .= " AND target_year = ?";
            $params[] = $year;
        }

        if ($month !== null) {
            $sql .= " AND target_month = ?";
            $params[] = $month;
        }

        $sql .= " ORDER BY target_year DESC, target_month DESC, budget_type, target_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Budget fetch error: ' . $e->getMessage());
        return [];
    }
}

// 予算進捗取得
function getBudgetProgress($pdo, $user_id, $year, $month) {
    // ユーザーID検証
    if (!is_numeric($user_id) || (int)$user_id <= 0) {
        return null;
    }

    try {
        $tables = getTableNames();

        // 月次全体予算を取得（ユーザー固有）
        $stmt = $pdo->prepare("SELECT * FROM {$tables['budgets']} WHERE user_id = ? AND budget_type = 'monthly' AND target_id IS NULL AND target_year = ? AND target_month = ?");
        $stmt->execute([$user_id, $year, $month]);
        $budget = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$budget) {
            return null;
        }

        // その月の実績を取得（ユーザー固有）
        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date));

        $stmt = $pdo->prepare("SELECT SUM(price) as total FROM {$tables['source']} WHERE user_id = ? AND re_date BETWEEN ? AND ?");
        $stmt->execute([$user_id, $start_date, $end_date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $actual = $result['total'] ?? 0;

        $budget_amount = $budget['amount'];
        $percentage = $budget_amount > 0 ? round(($actual / $budget_amount) * 100, 1) : 0;

        return [
            'budget_id' => $budget['id'],
            'budget_amount' => $budget_amount,
            'actual_amount' => $actual,
            'remaining' => $budget_amount - $actual,
            'percentage' => $percentage,
            'alert_level' => $percentage >= 100 ? 'danger' : ($percentage >= 80 ? 'warning' : 'success')
        ];
    } catch (PDOException $e) {
        error_log('Budget progress fetch error: ' . $e->getMessage());
        return null;
    }
}
