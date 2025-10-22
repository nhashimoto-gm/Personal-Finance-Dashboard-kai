<?php
// queries.php - データ取得クエリ

// サマリー取得
function getSummary($pdo, $start_date, $end_date) {
    $stmt = $pdo->prepare("
        SELECT SUM(price) as total, COUNT(*) as record_count, COUNT(DISTINCT label1) as shop_count
        FROM view1
        WHERE re_date BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date, $end_date]);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'total' => $summary['total'] ?? 0,
        'record_count' => $summary['record_count'] ?? 0,
        'shop_count' => $summary['shop_count'] ?? 0
    ];
}

// アクティブ日数取得
function getActiveDays($pdo, $start_date, $end_date) {
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT re_date) as active_days
        FROM source
        WHERE re_date BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date, $end_date]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['active_days'] ?? 1;
}

// ショップ別集計取得
function getShopData($pdo, $start_date, $end_date) {
    $stmt = $pdo->prepare("
        SELECT cat_1, label1, SUM(price) as total
        FROM view1
        WHERE re_date BETWEEN ? AND ?
        GROUP BY cat_1, label1
        ORDER BY total DESC
    ");
    $stmt->execute([$start_date, $end_date]);
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
function getCategoryData($pdo, $start_date, $end_date) {
    $stmt = $pdo->prepare("
        SELECT cat_2, label2, SUM(price) as total
        FROM view1
        WHERE re_date BETWEEN ? AND ?
        GROUP BY cat_2, label2
        ORDER BY total DESC
        LIMIT 10
    ");
    $stmt->execute([$start_date, $end_date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 日別推移取得
function getDailyData($pdo, $start_date, $end_date) {
    $stmt = $pdo->prepare("
        SELECT re_date, SUM(price) as daily_total
        FROM source
        WHERE re_date BETWEEN ? AND ?
        GROUP BY re_date
        ORDER BY re_date
    ");
    $stmt->execute([$start_date, $end_date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 期間別推移取得
function getPeriodData($pdo, $period_range) {
    $period_is_monthly = $period_range < 60;
    
    if ($period_is_monthly) {
        $period_query = "
            SELECT 
                DATE_FORMAT(re_date, '%Y-%m') as period,
                label1 as shop_name,
                SUM(price) as total
            FROM view1
            WHERE re_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY DATE_FORMAT(re_date, '%Y-%m'), label1
            ORDER BY period, shop_name
        ";
    } else {
        $period_query = "
            SELECT 
                YEAR(re_date) as period,
                label1 as shop_name,
                SUM(price) as total
            FROM view1
            WHERE re_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY YEAR(re_date), label1
            ORDER BY period, shop_name
        ";
    }
    
    $stmt = $pdo->prepare($period_query);
    $stmt->execute([$period_range]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 最新取引履歴取得
function getRecentTransactions($pdo, $start_date, $end_date, $search_shop, $search_category, $recent_limit) {
    $recent_sql = "SELECT id, re_date, label1, label2, price FROM view1 WHERE re_date BETWEEN ? AND ?";
    $recent_params = [$start_date, $end_date];
    
    if (!empty($search_shop)) {
        $recent_sql .= " AND label1 = ?";
        $recent_params[] = $search_shop;
    }
    
    if (!empty($search_category)) {
        $recent_sql .= " AND label2 = ?";
        $recent_params[] = $search_category;
    }
    
    $recent_sql .= " ORDER BY re_date DESC, id DESC LIMIT " . (int)$recent_limit;
    
    $stmt = $pdo->prepare($recent_sql);
    $stmt->execute($recent_params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 検索結果取得
function getSearchResults($pdo, $search_shop, $search_category, $search_limit) {
    if (empty($search_shop) && empty($search_category)) {
        return [];
    }
    
    $search_sql = "SELECT id, re_date, label1, label2, price FROM view1 WHERE 1=1";
    $params = [];
    
    if (!empty($search_shop)) {
        $search_sql .= " AND label1 = ?";
        $params[] = $search_shop;
    }
    
    if (!empty($search_category)) {
        $search_sql .= " AND label2 = ?";
        $params[] = $search_category;
    }
    
    $search_sql .= " ORDER BY re_date DESC, id DESC LIMIT " . (int)$search_limit;
    
    $stmt = $pdo->prepare($search_sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
