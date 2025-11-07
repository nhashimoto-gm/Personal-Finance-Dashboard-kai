<?php
// queries.php - データ取得クエリ（Multi-Account対応）

// N+1クエリ対策: 繰り返し経費データのキャッシュ
$GLOBALS['recurring_expenses_cache'] = [];

// サマリー取得
function getSummary($pdo, $user_id, $start_date, $end_date) {
    // ユーザーID検証
    if (!is_numeric($user_id) || (int)$user_id <= 0) {
        return ['total' => 0, 'record_count' => 0, 'shop_count' => 0];
    }

    $tables = getTableNames();

    // Get actual transaction summary
    $stmt = $pdo->prepare("
        SELECT SUM(price) as total, COUNT(*) as record_count, COUNT(DISTINCT cat_1) as shop_count
        FROM {$tables['source']}
        WHERE user_id = ? AND re_date BETWEEN ? AND ?
    ");
    $stmt->execute([$user_id, $start_date, $end_date]);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);

    $total = (float)($summary['total'] ?? 0);
    $record_count = (int)($summary['record_count'] ?? 0);
    $shop_ids = [];

    // Get shop IDs from actual transactions
    $stmt = $pdo->prepare("
        SELECT DISTINCT cat_1
        FROM {$tables['source']}
        WHERE user_id = ? AND re_date BETWEEN ? AND ?
    ");
    $stmt->execute([$user_id, $start_date, $end_date]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $shop_ids[$row['cat_1']] = true;
    }

    // Get recurring expense instances
    $recurring_instances = generateRecurringExpenseInstances($pdo, $user_id, $start_date, $end_date);

    // Add recurring expenses to totals
    foreach ($recurring_instances as $instance) {
        $total += $instance['price'];
        $record_count++;
        $shop_ids[$instance['cat_1']] = true;
    }

    return [
        'total' => $total,
        'record_count' => $record_count,
        'shop_count' => count($shop_ids)
    ];
}

// アクティブ日数取得
function getActiveDays($pdo, $user_id, $start_date, $end_date) {
    // ユーザーID検証
    if (!is_numeric($user_id) || (int)$user_id <= 0) {
        return 1;
    }

    $tables = getTableNames();
    $active_dates = [];

    // Get dates from actual transactions
    $stmt = $pdo->prepare("
        SELECT DISTINCT re_date
        FROM {$tables['source']}
        WHERE user_id = ? AND re_date BETWEEN ? AND ?
    ");
    $stmt->execute([$user_id, $start_date, $end_date]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $active_dates[$row['re_date']] = true;
    }

    // Get dates from recurring expenses
    $recurring_instances = generateRecurringExpenseInstances($pdo, $user_id, $start_date, $end_date);
    foreach ($recurring_instances as $instance) {
        $active_dates[$instance['date']] = true;
    }

    return count($active_dates) > 0 ? count($active_dates) : 1;
}

// ショップ別集計取得
function getShopData($pdo, $user_id, $start_date, $end_date) {
    // ユーザーID検証
    if (!is_numeric($user_id) || (int)$user_id <= 0) {
        return ['above_4pct' => [], 'below_4pct_total' => 0, 'others_shop' => null];
    }

    $tables = getTableNames();

    // Get actual transaction shop data
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

    // Create a map for merging
    $shop_map = [];
    foreach ($shop_data_all as $d) {
        $key = $d['cat_1'] . '|' . $d['label1'];
        $shop_map[$key] = [
            'cat_1' => $d['cat_1'],
            'label1' => $d['label1'],
            'total' => (float)$d['total']
        ];
    }

    // Get recurring expense instances and aggregate by shop
    $recurring_instances = generateRecurringExpenseInstances($pdo, $user_id, $start_date, $end_date);
    foreach ($recurring_instances as $instance) {
        $key = $instance['cat_1'] . '|' . $instance['shop_name'];
        if (isset($shop_map[$key])) {
            $shop_map[$key]['total'] += $instance['price'];
        } else {
            $shop_map[$key] = [
                'cat_1' => $instance['cat_1'],
                'label1' => $instance['shop_name'],
                'total' => (float)$instance['price']
            ];
        }
    }

    // Convert map to array and sort by total descending
    $merged_shop_data = array_values($shop_map);
    usort($merged_shop_data, function($a, $b) {
        return $b['total'] <=> $a['total'];
    });

    $shop_data_raw = [];
    $others_shop = null;

    foreach ($merged_shop_data as $d) {
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

    // Get actual transaction category data
    $stmt = $pdo->prepare("
        SELECT s.cat_2, c2.label as label2, SUM(s.price) as total
        FROM {$tables['source']} s
        LEFT JOIN {$tables['cat_2_labels']} c2 ON s.cat_2 = c2.id
        WHERE s.user_id = ? AND s.re_date BETWEEN ? AND ?
        GROUP BY s.cat_2, c2.label
        ORDER BY total DESC
    ");
    $stmt->execute([$user_id, $start_date, $end_date]);
    $category_data_all = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Create a map for merging
    $category_map = [];
    foreach ($category_data_all as $d) {
        $key = $d['cat_2'] . '|' . $d['label2'];
        $category_map[$key] = [
            'cat_2' => $d['cat_2'],
            'label2' => $d['label2'],
            'total' => (float)$d['total']
        ];
    }

    // Get recurring expense instances and aggregate by category
    $recurring_instances = generateRecurringExpenseInstances($pdo, $user_id, $start_date, $end_date);
    foreach ($recurring_instances as $instance) {
        $key = $instance['cat_2'] . '|' . $instance['category_name'];
        if (isset($category_map[$key])) {
            $category_map[$key]['total'] += $instance['price'];
        } else {
            $category_map[$key] = [
                'cat_2' => $instance['cat_2'],
                'label2' => $instance['category_name'],
                'total' => (float)$instance['price']
            ];
        }
    }

    // Convert map to array and sort by total descending
    $merged_category_data = array_values($category_map);
    usort($merged_category_data, function($a, $b) {
        return $b['total'] <=> $a['total'];
    });

    // Return top 10
    return array_slice($merged_category_data, 0, 10);
}

// 日別推移取得
function getDailyData($pdo, $user_id, $start_date, $end_date) {
    // ユーザーID検証
    if (!is_numeric($user_id) || (int)$user_id <= 0) {
        return [];
    }

    $tables = getTableNames();

    // Get actual transaction daily data
    $stmt = $pdo->prepare("
        SELECT re_date, SUM(price) as daily_total
        FROM {$tables['source']}
        WHERE user_id = ? AND re_date BETWEEN ? AND ?
        GROUP BY re_date
        ORDER BY re_date
    ");
    $stmt->execute([$user_id, $start_date, $end_date]);
    $daily_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Create a map for merging
    $daily_map = [];
    foreach ($daily_data as $d) {
        $daily_map[$d['re_date']] = (float)$d['daily_total'];
    }

    // Get recurring expense instances and aggregate by date
    $recurring_instances = generateRecurringExpenseInstances($pdo, $user_id, $start_date, $end_date);
    foreach ($recurring_instances as $instance) {
        $date = $instance['date'];
        if (isset($daily_map[$date])) {
            $daily_map[$date] += $instance['price'];
        } else {
            $daily_map[$date] = (float)$instance['price'];
        }
    }

    // Convert map to array and sort by date
    $result = [];
    foreach ($daily_map as $date => $total) {
        $result[] = [
            're_date' => $date,
            'daily_total' => $total
        ];
    }

    usort($result, function($a, $b) {
        return strcmp($a['re_date'], $b['re_date']);
    });

    return $result;
}

// 期間別推移取得
function getPeriodData($pdo, $user_id, $period_range) {
    // ユーザーID検証
    if (!is_numeric($user_id) || (int)$user_id <= 0) {
        return [];
    }

    $tables = getTableNames();
    $period_is_monthly = $period_range < 60;

    // Calculate date range
    if ($period_is_monthly) {
        $start_date = date('Y-m-01', strtotime("-$period_range months"));
        $end_date = date('Y-m-t'); // Last day of current month
    } else {
        $years_back = intval($period_range / 12);
        $start_date = date('Y-01-01', strtotime("-$years_back years"));
        $end_date = date('Y-12-31'); // Last day of current year
    }

    // Get actual transaction data
    if ($period_is_monthly) {
        $period_query = "
            SELECT
                DATE_FORMAT(s.re_date, '%Y-%m') as period,
                c1.label as shop_name,
                SUM(s.price) as total
            FROM {$tables['source']} s
            LEFT JOIN {$tables['cat_1_labels']} c1 ON s.cat_1 = c1.id
            WHERE s.user_id = ?
                AND s.re_date >= ?
                AND s.re_date <= ?
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
            WHERE s.user_id = ?
                AND s.re_date >= ?
                AND s.re_date <= ?
            GROUP BY YEAR(s.re_date), c1.label
            ORDER BY period, shop_name
        ";
    }

    $stmt = $pdo->prepare($period_query);
    $stmt->execute([$user_id, $start_date, $end_date]);
    $actual_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recurring expense instances for the same period
    $recurring_instances = generateRecurringExpenseInstances($pdo, $user_id, $start_date, $end_date);

    // Aggregate recurring expenses by period and shop
    $recurring_aggregated = [];
    foreach ($recurring_instances as $instance) {
        if ($period_is_monthly) {
            $period = substr($instance['date'], 0, 7); // YYYY-MM
        } else {
            $period = substr($instance['date'], 0, 4); // YYYY
        }

        $shop = $instance['shop_name'];
        $key = $period . '|' . $shop;

        if (!isset($recurring_aggregated[$key])) {
            $recurring_aggregated[$key] = [
                'period' => $period,
                'shop_name' => $shop,
                'total' => 0
            ];
        }

        $recurring_aggregated[$key]['total'] += $instance['price'];
    }

    // Merge actual data and recurring data
    $merged_data = [];

    // Add actual transactions
    foreach ($actual_data as $row) {
        $key = $row['period'] . '|' . $row['shop_name'];
        $merged_data[$key] = $row;
    }

    // Add or merge recurring expenses
    foreach ($recurring_aggregated as $key => $row) {
        if (isset($merged_data[$key])) {
            // Merge: add recurring to actual
            $merged_data[$key]['total'] += $row['total'];
        } else {
            // Add new entry for recurring only
            $merged_data[$key] = $row;
        }
    }

    // Convert to indexed array and sort
    $result = array_values($merged_data);
    usort($result, function($a, $b) {
        $period_cmp = strcmp($a['period'], $b['period']);
        if ($period_cmp !== 0) return $period_cmp;
        return strcmp($a['shop_name'], $b['shop_name']);
    });

    return $result;
}

// 最新取引履歴取得
function getRecentTransactions($pdo, $user_id, $start_date, $end_date, $search_shop, $search_category, $recent_limit) {
    // ユーザーID検証
    if (!is_numeric($user_id) || (int)$user_id <= 0) {
        return [];
    }

    $tables = getTableNames();

    // Get actual transactions
    $recent_sql = "SELECT s.id, s.re_date, c1.label as label1, c2.label as label2, s.price, 'actual' as source
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

    $recent_sql .= " ORDER BY s.re_date DESC, s.id DESC";

    $stmt = $pdo->prepare($recent_sql);
    $stmt->execute($recent_params);
    $actual_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recurring expense instances
    $recurring_instances = generateRecurringExpenseInstances($pdo, $user_id, $start_date, $end_date);

    // Filter recurring instances by search criteria
    $filtered_recurring = [];
    foreach ($recurring_instances as $instance) {
        $include = true;

        if (!empty($search_shop) && $instance['shop_name'] !== $search_shop) {
            $include = false;
        }

        if (!empty($search_category) && $instance['category_name'] !== $search_category) {
            $include = false;
        }

        if ($include) {
            $filtered_recurring[] = [
                'id' => 'recurring_' . $instance['recurring_id'] . '_' . $instance['date'],
                're_date' => $instance['date'],
                'label1' => $instance['shop_name'],
                'label2' => $instance['category_name'],
                'price' => $instance['price'],
                'source' => 'recurring'
            ];
        }
    }

    // Merge and sort by date descending
    $all_transactions = array_merge($actual_transactions, $filtered_recurring);

    usort($all_transactions, function($a, $b) {
        $date_cmp = strcmp($b['re_date'], $a['re_date']);
        if ($date_cmp !== 0) return $date_cmp;

        // For same date, sort by ID (actual transactions first, then recurring)
        if (isset($a['source']) && isset($b['source'])) {
            if ($a['source'] === 'actual' && $b['source'] === 'recurring') return -1;
            if ($a['source'] === 'recurring' && $b['source'] === 'actual') return 1;
        }
        return 0;
    });

    // Apply limit
    return array_slice($all_transactions, 0, (int)$recent_limit);
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

        // Get actual transaction total
        $stmt = $pdo->prepare("SELECT SUM(price) as total FROM {$tables['source']} WHERE user_id = ? AND re_date BETWEEN ? AND ?");
        $stmt->execute([$user_id, $start_date, $end_date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $actual = (float)($result['total'] ?? 0);

        // Add recurring expenses to actual
        $recurring_instances = generateRecurringExpenseInstances($pdo, $user_id, $start_date, $end_date);
        foreach ($recurring_instances as $instance) {
            $actual += $instance['price'];
        }

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

// Get budget progress for a date range with prorated budget calculation
function getBudgetProgressForRange($pdo, $user_id, $start_date, $end_date) {
    // ユーザーID検証
    if (!is_numeric($user_id) || (int)$user_id <= 0) {
        return null;
    }

    try {
        $tables = getTableNames();

        // Parse dates
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);

        // Calculate total budget for the range by aggregating monthly budgets
        $total_budget = 0;
        $current = clone $start;
        $current->modify('first day of this month');

        while ($current <= $end) {
            $year = (int)$current->format('Y');
            $month = (int)$current->format('m');

            // Get monthly budget
            $stmt = $pdo->prepare("SELECT amount FROM {$tables['budgets']} WHERE user_id = ? AND budget_type = 'monthly' AND target_id IS NULL AND target_year = ? AND target_month = ?");
            $stmt->execute([$user_id, $year, $month]);
            $budget = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($budget) {
                $monthly_budget = (float)$budget['amount'];

                // Calculate days in this month that overlap with the filter range
                $month_start = new DateTime($year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01');
                $month_end = new DateTime($month_start->format('Y-m-t'));

                $overlap_start = max($start, $month_start);
                $overlap_end = min($end, $month_end);

                if ($overlap_start <= $overlap_end) {
                    // Calculate prorate
                    $days_in_month = (int)$month_end->format('d');
                    $overlap_days = $overlap_start->diff($overlap_end)->days + 1;
                    $prorated_budget = ($monthly_budget / $days_in_month) * $overlap_days;
                    $total_budget += $prorated_budget;
                }
            }

            $current->modify('first day of next month');
        }

        // If no budget found for any month in range, return null
        if ($total_budget == 0) {
            return null;
        }

        // Get actual transaction total for the range
        $stmt = $pdo->prepare("SELECT SUM(price) as total FROM {$tables['source']} WHERE user_id = ? AND re_date BETWEEN ? AND ?");
        $stmt->execute([$user_id, $start_date, $end_date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $actual = (float)($result['total'] ?? 0);

        // Add recurring expenses to actual
        $recurring_instances = generateRecurringExpenseInstances($pdo, $user_id, $start_date, $end_date);
        foreach ($recurring_instances as $instance) {
            $actual += $instance['price'];
        }

        $percentage = $total_budget > 0 ? round(($actual / $total_budget) * 100, 1) : 0;

        return [
            'budget_amount' => round($total_budget, 0),
            'actual_amount' => $actual,
            'remaining' => round($total_budget - $actual, 0),
            'percentage' => $percentage,
            'alert_level' => $percentage >= 100 ? 'danger' : ($percentage >= 80 ? 'warning' : 'success')
        ];
    } catch (Exception $e) {
        error_log('Budget progress for range fetch error: ' . $e->getMessage());
        return null;
    }
}

// ============================================================
// Advanced Prediction Engine - Machine Learning-like Statistical Methods
// ============================================================

// Helper: Get historical monthly data for the same month across multiple years
function getHistoricalMonthlyData($pdo, $user_id, $target_month, $years_back = 3) {
    if (!is_numeric($user_id) || (int)$user_id <= 0) {
        return [];
    }

    $tables = getTableNames();
    $historical_data = [];
    $current_year = (int)date('Y');

    for ($i = 1; $i <= $years_back; $i++) {
        $year = $current_year - $i;
        $month_start = sprintf('%04d-%02d-01', $year, $target_month);
        $month_end = date('Y-m-t', strtotime($month_start));

        try {
            $stmt = $pdo->prepare("SELECT SUM(price) as total FROM {$tables['source']} WHERE user_id = ? AND re_date BETWEEN ? AND ?");
            $stmt->execute([$user_id, $month_start, $month_end]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total = (float)($result['total'] ?? 0);

            // Add recurring expenses
            $recurring_instances = generateRecurringExpenseInstances($pdo, $user_id, $month_start, $month_end);
            foreach ($recurring_instances as $instance) {
                $total += $instance['price'];
            }

            if ($total > 0) {
                $historical_data[] = [
                    'year' => $year,
                    'month' => $target_month,
                    'total' => $total,
                    'days' => (int)date('t', strtotime($month_start))
                ];
            }
        } catch (Exception $e) {
            error_log("Historical data fetch error for {$year}-{$target_month}: " . $e->getMessage());
        }
    }

    return $historical_data;
}

// Helper: Detect and remove outliers using IQR method
function detectOutliers($data_array) {
    if (count($data_array) < 4) {
        return $data_array; // Not enough data for outlier detection
    }

    $values = array_column($data_array, 'total');
    sort($values);

    $count = count($values);
    $q1_index = floor($count * 0.25);
    $q3_index = floor($count * 0.75);

    $q1 = $values[$q1_index];
    $q3 = $values[$q3_index];
    $iqr = $q3 - $q1;

    $lower_bound = $q1 - (1.5 * $iqr);
    $upper_bound = $q3 + (1.5 * $iqr);

    // Filter out outliers
    return array_filter($data_array, function($item) use ($lower_bound, $upper_bound) {
        return $item['total'] >= $lower_bound && $item['total'] <= $upper_bound;
    });
}

// Helper: Calculate trend coefficient from recent months
function calculateTrendCoefficient($pdo, $user_id, $months_back = 12) {
    if (!is_numeric($user_id) || (int)$user_id <= 0) {
        return 1.0; // Neutral trend
    }

    $tables = getTableNames();
    $monthly_totals = [];

    $current_date = new DateTime();
    for ($i = 1; $i <= $months_back; $i++) {
        $date = clone $current_date;
        $date->modify("-{$i} months");
        $year = (int)$date->format('Y');
        $month = (int)$date->format('m');

        $month_start = sprintf('%04d-%02d-01', $year, $month);
        $month_end = date('Y-m-t', strtotime($month_start));

        try {
            $stmt = $pdo->prepare("SELECT SUM(price) as total FROM {$tables['source']} WHERE user_id = ? AND re_date BETWEEN ? AND ?");
            $stmt->execute([$user_id, $month_start, $month_end]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total = (float)($result['total'] ?? 0);

            // Add recurring expenses
            $recurring_instances = generateRecurringExpenseInstances($pdo, $user_id, $month_start, $month_end);
            foreach ($recurring_instances as $instance) {
                $total += $instance['price'];
            }

            if ($total > 0) {
                $monthly_totals[] = $total;
            }
        } catch (Exception $e) {
            continue;
        }
    }

    if (count($monthly_totals) < 3) {
        return 1.0; // Not enough data
    }

    // Simple linear regression to detect trend
    $n = count($monthly_totals);
    $x = range(1, $n);
    $y = array_reverse($monthly_totals); // Reverse to chronological order

    $sum_x = array_sum($x);
    $sum_y = array_sum($y);
    $sum_xy = 0;
    $sum_xx = 0;

    for ($i = 0; $i < $n; $i++) {
        $sum_xy += $x[$i] * $y[$i];
        $sum_xx += $x[$i] * $x[$i];
    }

    $slope = ($n * $sum_xy - $sum_x * $sum_y) / ($n * $sum_xx - $sum_x * $sum_x);
    $avg_y = $sum_y / $n;

    // Calculate trend coefficient (1.0 = no trend, >1.0 = increasing, <1.0 = decreasing)
    if ($avg_y > 0) {
        $trend_coefficient = 1.0 + ($slope / $avg_y);
        // Limit to reasonable range
        return max(0.8, min(1.2, $trend_coefficient));
    }

    return 1.0;
}

// Helper: Analyze weekday vs weekend spending patterns
function analyzeWeekdayPattern($pdo, $user_id, $months_back = 6) {
    if (!is_numeric($user_id) || (int)$user_id <= 0) {
        return ['weekday_avg' => 0, 'weekend_avg' => 0, 'has_pattern' => false];
    }

    $tables = getTableNames();
    $weekday_totals = [];
    $weekend_totals = [];

    $start_date = date('Y-m-d', strtotime("-{$months_back} months"));
    $end_date = date('Y-m-d');

    try {
        $stmt = $pdo->prepare("SELECT re_date, SUM(price) as daily_total FROM {$tables['source']} WHERE user_id = ? AND re_date BETWEEN ? AND ? GROUP BY re_date");
        $stmt->execute([$user_id, $start_date, $end_date]);
        $daily_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($daily_data as $day) {
            $day_of_week = date('N', strtotime($day['re_date'])); // 1=Monday, 7=Sunday
            $total = (float)$day['daily_total'];

            if ($day_of_week >= 6) { // Saturday or Sunday
                $weekend_totals[] = $total;
            } else {
                $weekday_totals[] = $total;
            }
        }

        $weekday_avg = count($weekday_totals) > 0 ? array_sum($weekday_totals) / count($weekday_totals) : 0;
        $weekend_avg = count($weekend_totals) > 0 ? array_sum($weekend_totals) / count($weekend_totals) : 0;

        // Check if there's a significant pattern (more than 20% difference)
        $has_pattern = false;
        if ($weekday_avg > 0 && $weekend_avg > 0) {
            $diff_ratio = abs($weekend_avg - $weekday_avg) / max($weekday_avg, $weekend_avg);
            $has_pattern = $diff_ratio > 0.2;
        }

        return [
            'weekday_avg' => $weekday_avg,
            'weekend_avg' => $weekend_avg,
            'has_pattern' => $has_pattern
        ];
    } catch (Exception $e) {
        error_log('Weekday pattern analysis error: ' . $e->getMessage());
        return ['weekday_avg' => 0, 'weekend_avg' => 0, 'has_pattern' => false];
    }
}

// Helper: Exponential Smoothing (ETS method)
function exponentialSmoothing($data_points, $alpha = 0.3) {
    if (empty($data_points)) {
        return 0;
    }

    $smoothed = $data_points[0];
    foreach ($data_points as $point) {
        $smoothed = $alpha * $point + (1 - $alpha) * $smoothed;
    }

    return $smoothed;
}

// Helper: Simple ARIMA-like prediction (Auto-Regressive component)
function simpleARIMA($data_points, $order = 2) {
    $n = count($data_points);
    if ($n < $order + 1) {
        return end($data_points);
    }

    // Use last 'order' points for auto-regression
    $recent = array_slice($data_points, -$order);

    // Simple weighted average with more weight on recent data
    $weights = [];
    $weight_sum = 0;
    for ($i = 0; $i < $order; $i++) {
        $weight = $i + 1; // More recent = higher weight
        $weights[] = $weight;
        $weight_sum += $weight;
    }

    $prediction = 0;
    for ($i = 0; $i < $order; $i++) {
        $prediction += $recent[$i] * $weights[$i] / $weight_sum;
    }

    return $prediction;
}

// Helper: Calculate confidence interval based on historical variance
function calculateConfidenceInterval($historical_data, $predicted_value, $confidence_level = 0.95) {
    if (count($historical_data) < 2) {
        // Default to ±10% if not enough data
        $margin = $predicted_value * 0.1;
        return [
            'lower' => max(0, $predicted_value - $margin),
            'upper' => $predicted_value + $margin,
            'margin' => $margin
        ];
    }

    $values = array_column($historical_data, 'total');
    $mean = array_sum($values) / count($values);

    // Calculate standard deviation
    $variance = 0;
    foreach ($values as $value) {
        $variance += pow($value - $mean, 2);
    }
    $std_dev = sqrt($variance / count($values));

    // Z-score for 95% confidence ≈ 1.96
    $z_score = 1.96;
    $margin = $z_score * $std_dev;

    return [
        'lower' => max(0, $predicted_value - $margin),
        'upper' => $predicted_value + $margin,
        'margin' => $margin,
        'std_dev' => $std_dev
    ];
}

// Main: Advanced prediction function with all methods combined
function getPredictedExpense($pdo, $user_id, $year, $month) {
    if (!is_numeric($user_id) || (int)$user_id <= 0) {
        return null;
    }

    try {
        $tables = getTableNames();
        $current_month_start = sprintf('%04d-%02d-01', $year, $month);
        $today = date('Y-m-d');
        $current_month_end = min($today, date('Y-m-t', strtotime($current_month_start)));

        $current_day = (int)date('d');
        $days_in_month = (int)date('t', strtotime($current_month_start));

        // Get current month's actual spending so far
        $stmt = $pdo->prepare("SELECT SUM(price) as total FROM {$tables['source']} WHERE user_id = ? AND re_date BETWEEN ? AND ?");
        $stmt->execute([$user_id, $current_month_start, $current_month_end]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $current_actual = (float)($result['total'] ?? 0);

        // Add recurring expenses from current month
        $recurring_instances = generateRecurringExpenseInstances($pdo, $user_id, $current_month_start, $current_month_end);
        foreach ($recurring_instances as $instance) {
            $current_actual += $instance['price'];
        }

        // PHASE 1: Historical data with outlier removal
        $historical_data = getHistoricalMonthlyData($pdo, $user_id, $month, 3);
        $filtered_data = detectOutliers($historical_data);

        // Calculate weighted average of historical data (more recent = higher weight)
        $weighted_sum = 0;
        $weight_total = 0;
        $historical_values = [];
        foreach ($filtered_data as $index => $data) {
            $weight = count($filtered_data) - $index; // More recent = higher weight
            $weighted_sum += $data['total'] * $weight;
            $weight_total += $weight;
            $historical_values[] = $data['total'];
        }
        $historical_avg = $weight_total > 0 ? $weighted_sum / $weight_total : 0;

        // Get trend coefficient
        $trend_coefficient = calculateTrendCoefficient($pdo, $user_id, 12);

        // PHASE 2: Weekday pattern analysis
        $weekday_pattern = analyzeWeekdayPattern($pdo, $user_id, 6);

        // Calculate remaining weekdays and weekend days
        $remaining_weekdays = 0;
        $remaining_weekend_days = 0;
        for ($day = $current_day + 1; $day <= $days_in_month; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $day_of_week = date('N', strtotime($date));
            if ($day_of_week >= 6) {
                $remaining_weekend_days++;
            } else {
                $remaining_weekdays++;
            }
        }

        // PHASE 3: Multiple prediction methods
        $predictions = [];

        // Method 1: Simple pace-based prediction
        if ($current_day > 0) {
            $daily_avg = $current_actual / $current_day;
            $predictions['simple_pace'] = $current_actual + ($daily_avg * ($days_in_month - $current_day));
        }

        // Method 2: Historical average with trend
        if ($historical_avg > 0) {
            $predictions['historical_trend'] = $historical_avg * $trend_coefficient;
        }

        // Method 3: Weekday-aware prediction
        if ($weekday_pattern['has_pattern'] && $current_day > 0) {
            $current_daily_avg = $current_actual / $current_day;
            $remaining_expense = ($remaining_weekdays * $weekday_pattern['weekday_avg']) +
                                ($remaining_weekend_days * $weekday_pattern['weekend_avg']);

            // Blend current pace with weekday pattern
            $predictions['weekday_aware'] = $current_actual + ($remaining_expense * 0.7 + $current_daily_avg * ($days_in_month - $current_day) * 0.3);
        }

        // Method 4: Exponential Smoothing
        if (!empty($historical_values)) {
            $ets_prediction = exponentialSmoothing($historical_values, 0.3);
            if ($current_day > 0 && $current_day < $days_in_month) {
                // Blend with current pace
                $current_projection = $current_actual * ($days_in_month / $current_day);
                $predictions['exponential_smoothing'] = $ets_prediction * 0.4 + $current_projection * 0.6;
            } else {
                $predictions['exponential_smoothing'] = $ets_prediction;
            }
        }

        // Method 5: ARIMA-like prediction
        if (!empty($historical_values)) {
            $arima_prediction = simpleARIMA($historical_values, 2);
            if ($current_day > 0 && $current_day < $days_in_month) {
                $current_projection = $current_actual * ($days_in_month / $current_day);
                $predictions['arima'] = $arima_prediction * 0.3 + $current_projection * 0.7;
            } else {
                $predictions['arima'] = $arima_prediction;
            }
        }

        // Ensemble: Combine all predictions with weights
        if (empty($predictions)) {
            // Fallback: just use current pace
            $final_prediction = $current_day > 0 ? $current_actual * ($days_in_month / $current_day) : $current_actual;
        } else {
            // Weighted ensemble
            $weights = [
                'simple_pace' => 0.15,
                'historical_trend' => 0.25,
                'weekday_aware' => 0.25,
                'exponential_smoothing' => 0.20,
                'arima' => 0.15
            ];

            $weighted_prediction = 0;
            $total_weight = 0;
            foreach ($predictions as $method => $value) {
                $weight = $weights[$method] ?? 0.2;
                $weighted_prediction += $value * $weight;
                $total_weight += $weight;
            }
            $final_prediction = $total_weight > 0 ? $weighted_prediction / $total_weight : $current_actual;
        }

        // Calculate confidence interval
        $confidence = calculateConfidenceInterval($filtered_data, $final_prediction);

        // Get last year's actual for comparison
        $last_year = $year - 1;
        $last_year_start = sprintf('%04d-%02d-01', $last_year, $month);
        $last_year_end = date('Y-m-t', strtotime($last_year_start));
        $stmt = $pdo->prepare("SELECT SUM(price) as total FROM {$tables['source']} WHERE user_id = ? AND re_date BETWEEN ? AND ?");
        $stmt->execute([$user_id, $last_year_start, $last_year_end]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $last_year_actual = (float)($result['total'] ?? 0);

        $recurring_instances = generateRecurringExpenseInstances($pdo, $user_id, $last_year_start, $last_year_end);
        foreach ($recurring_instances as $instance) {
            $last_year_actual += $instance['price'];
        }

        return [
            'predicted_amount' => round($final_prediction, 0),
            'confidence_lower' => round($confidence['lower'], 0),
            'confidence_upper' => round($confidence['upper'], 0),
            'confidence_margin' => round($confidence['margin'], 0),
            'last_year_actual' => round($last_year_actual, 0),
            'current_actual' => round($current_actual, 0),
            'current_day' => $current_day,
            'days_in_month' => $days_in_month,
            'trend_coefficient' => round($trend_coefficient, 3),
            'methods_used' => array_keys($predictions),
            'method_predictions' => array_map(function($v) { return round($v, 0); }, $predictions)
        ];
    } catch (Exception $e) {
        error_log('Advanced predicted expense fetch error: ' . $e->getMessage());
        return null;
    }
}

// ============================================================
// Recurring Expenses Functions
// ============================================================

// Get all recurring expenses for a user
function getRecurringExpenses($pdo, $user_id, $include_inactive = false) {
    if (!is_numeric($user_id) || (int)$user_id <= 0) {
        return [];
    }

    $tables = getTableNames();
    $query = "
        SELECT
            r.id,
            r.name,
            r.cat_1,
            r.cat_2,
            c1.label as shop_name,
            c2.label as category_name,
            r.price,
            r.day_of_month,
            r.start_date,
            r.end_date,
            r.is_active
        FROM recurring_expenses r
        LEFT JOIN {$tables['cat_1_labels']} c1 ON r.cat_1 = c1.id
        LEFT JOIN {$tables['cat_2_labels']} c2 ON r.cat_2 = c2.id
        WHERE r.user_id = ?
    ";

    if (!$include_inactive) {
        $query .= " AND r.is_active = 1";
    }

    $query .= " ORDER BY r.day_of_month, r.name";

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Recurring expenses fetch error: ' . $e->getMessage());
        return [];
    }
}

// Add a new recurring expense
function addRecurringExpense($pdo, $user_id, $name, $label1, $label2, $price, $day_of_month, $start_date, $end_date = null) {
    if (!is_numeric($user_id) || (int)$user_id <= 0) {
        return ['success' => false, 'message' => 'Invalid user ID'];
    }

    // Validate inputs
    if (empty($name)) {
        return ['success' => false, 'message' => 'Name is required'];
    }
    if (empty($label1) || empty($label2)) {
        return ['success' => false, 'message' => 'Shop and category are required'];
    }
    if (!is_numeric($price) || (int)$price <= 0) {
        return ['success' => false, 'message' => 'Price must be positive'];
    }
    if (!is_numeric($day_of_month) || (int)$day_of_month < 1 || (int)$day_of_month > 31) {
        return ['success' => false, 'message' => 'Day of month must be between 1 and 31'];
    }

    try {
        $tables = getTableNames();

        // Convert label1 to cat_1 ID
        $stmt = $pdo->prepare("SELECT id FROM {$tables['cat_1_labels']} WHERE label = ? AND user_id = ?");
        $stmt->execute([$label1, $user_id]);
        $cat_1_result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Convert label2 to cat_2 ID
        $stmt = $pdo->prepare("SELECT id FROM {$tables['cat_2_labels']} WHERE label = ? AND user_id = ?");
        $stmt->execute([$label2, $user_id]);
        $cat_2_result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cat_1_result || !$cat_2_result) {
            return ['success' => false, 'message' => 'Selected shop or category not found'];
        }

        $cat_1 = $cat_1_result['id'];
        $cat_2 = $cat_2_result['id'];

        $stmt = $pdo->prepare("
            INSERT INTO recurring_expenses
            (user_id, name, cat_1, cat_2, price, day_of_month, start_date, end_date, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");

        $stmt->execute([
            $user_id,
            trim($name),
            $cat_1,
            $cat_2,
            (int)$price,
            (int)$day_of_month,
            $start_date,
            $end_date ?: null
        ]);

        return [
            'success' => true,
            'message' => 'Recurring expense added successfully',
            'id' => $pdo->lastInsertId()
        ];
    } catch (PDOException $e) {
        error_log('Recurring expense add error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to add recurring expense'];
    }
}

// Update a recurring expense
function updateRecurringExpense($pdo, $user_id, $id, $name, $label1, $label2, $price, $day_of_month, $start_date, $end_date = null, $is_active = 1) {
    if (!is_numeric($user_id) || (int)$user_id <= 0) {
        return ['success' => false, 'message' => 'Invalid user ID'];
    }

    // Validate inputs
    if (empty($name)) {
        return ['success' => false, 'message' => 'Name is required'];
    }
    if (empty($label1) || empty($label2)) {
        return ['success' => false, 'message' => 'Shop and category are required'];
    }
    if (!is_numeric($price) || (int)$price <= 0) {
        return ['success' => false, 'message' => 'Price must be positive'];
    }
    if (!is_numeric($day_of_month) || (int)$day_of_month < 1 || (int)$day_of_month > 31) {
        return ['success' => false, 'message' => 'Day of month must be between 1 and 31'];
    }

    try {
        $tables = getTableNames();

        // Convert label1 to cat_1 ID
        $stmt = $pdo->prepare("SELECT id FROM {$tables['cat_1_labels']} WHERE label = ? AND user_id = ?");
        $stmt->execute([$label1, $user_id]);
        $cat_1_result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Convert label2 to cat_2 ID
        $stmt = $pdo->prepare("SELECT id FROM {$tables['cat_2_labels']} WHERE label = ? AND user_id = ?");
        $stmt->execute([$label2, $user_id]);
        $cat_2_result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cat_1_result || !$cat_2_result) {
            return ['success' => false, 'message' => 'Selected shop or category not found'];
        }

        $cat_1 = $cat_1_result['id'];
        $cat_2 = $cat_2_result['id'];

        $stmt = $pdo->prepare("
            UPDATE recurring_expenses
            SET name = ?, cat_1 = ?, cat_2 = ?, price = ?, day_of_month = ?,
                start_date = ?, end_date = ?, is_active = ?
            WHERE id = ? AND user_id = ?
        ");

        $stmt->execute([
            trim($name),
            $cat_1,
            $cat_2,
            (int)$price,
            (int)$day_of_month,
            $start_date,
            $end_date ?: null,
            (int)$is_active,
            (int)$id,
            $user_id
        ]);

        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Recurring expense updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Recurring expense not found or no changes made'];
        }
    } catch (PDOException $e) {
        error_log('Recurring expense update error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update recurring expense'];
    }
}

// Delete a recurring expense
function deleteRecurringExpense($pdo, $user_id, $id) {
    if (!is_numeric($user_id) || (int)$user_id <= 0) {
        return ['success' => false, 'message' => 'Invalid user ID'];
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM recurring_expenses WHERE id = ? AND user_id = ?");
        $stmt->execute([(int)$id, $user_id]);

        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Recurring expense deleted successfully'];
        } else {
            return ['success' => false, 'message' => 'Recurring expense not found'];
        }
    } catch (PDOException $e) {
        error_log('Recurring expense delete error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to delete recurring expense'];
    }
}

// Toggle active status of a recurring expense
function toggleRecurringExpense($pdo, $user_id, $id) {
    if (!is_numeric($user_id) || (int)$user_id <= 0) {
        return ['success' => false, 'message' => 'Invalid user ID'];
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE recurring_expenses
            SET is_active = NOT is_active
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([(int)$id, $user_id]);

        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Recurring expense status updated'];
        } else {
            return ['success' => false, 'message' => 'Recurring expense not found'];
        }
    } catch (PDOException $e) {
        error_log('Recurring expense toggle error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to toggle recurring expense'];
    }
}

// Generate recurring expense instances for a given date range
function generateRecurringExpenseInstances($pdo, $user_id, $start_date, $end_date) {
    if (!is_numeric($user_id) || (int)$user_id <= 0) {
        return [];
    }

    // N+1クエリ対策: キャッシュを使用
    $cache_key = 'user_' . $user_id;
    if (!isset($GLOBALS['recurring_expenses_cache'][$cache_key])) {
        $GLOBALS['recurring_expenses_cache'][$cache_key] = getRecurringExpenses($pdo, $user_id, false);
    }
    $recurring_expenses = $GLOBALS['recurring_expenses_cache'][$cache_key];
    $instances = [];

    foreach ($recurring_expenses as $expense) {
        // Determine the effective start and end dates
        $expense_start = max($expense['start_date'], $start_date);
        $expense_end = $end_date;

        if ($expense['end_date'] !== null) {
            $expense_end = min($expense['end_date'], $end_date);
        }

        // Generate instances for each month in the range
        $current = new DateTime($expense_start);
        $end = new DateTime($expense_end);

        // Start from the first day of the month
        $current->modify('first day of this month');

        while ($current <= $end) {
            // Create an instance for this month
            $year = (int)$current->format('Y');
            $month = (int)$current->format('m');
            $day = min((int)$expense['day_of_month'], (int)$current->format('t')); // Handle month end

            $instance_date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $instance_datetime = new DateTime($instance_date);

            // Check if this instance falls within the range
            $range_start = new DateTime($expense_start);
            $range_end = new DateTime($expense_end);

            if ($instance_datetime >= $range_start && $instance_datetime <= $range_end) {
                // Check if this date is after the recurring expense start date
                $recurring_start = new DateTime($expense['start_date']);
                if ($instance_datetime >= $recurring_start) {
                    $instances[] = [
                        'recurring_id' => $expense['id'],
                        'date' => $instance_date,
                        'name' => $expense['name'],
                        'shop_name' => $expense['shop_name'],
                        'category_name' => $expense['category_name'],
                        'price' => $expense['price'],
                        'cat_1' => $expense['cat_1'],
                        'cat_2' => $expense['cat_2']
                    ];
                }
            }

            // Move to next month
            $current->modify('first day of next month');
        }
    }

    return $instances;
}
