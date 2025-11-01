<?php
// queries.php - データ取得クエリ（Multi-Account対応）

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

// Get predicted expense for current month based on last year's data
function getPredictedExpense($pdo, $user_id, $year, $month) {
    if (!is_numeric($user_id) || (int)$user_id <= 0) {
        return null;
    }

    try {
        $tables = getTableNames();

        // Get last year's same month data
        $last_year = $year - 1;
        $last_year_start = sprintf('%04d-%02d-01', $last_year, $month);
        $last_year_end = date('Y-m-t', strtotime($last_year_start));

        // Get last year's actual for this month
        $stmt = $pdo->prepare("SELECT SUM(price) as total FROM {$tables['source']} WHERE user_id = ? AND re_date BETWEEN ? AND ?");
        $stmt->execute([$user_id, $last_year_start, $last_year_end]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $last_year_actual = (float)($result['total'] ?? 0);

        // Add recurring expenses from last year
        $recurring_instances = generateRecurringExpenseInstances($pdo, $user_id, $last_year_start, $last_year_end);
        foreach ($recurring_instances as $instance) {
            $last_year_actual += $instance['price'];
        }

        // Get current month data up to today
        $current_month_start = sprintf('%04d-%02d-01', $year, $month);
        $today = date('Y-m-d');
        $current_month_end = min($today, date('Y-m-t', strtotime($current_month_start)));

        // Get current month's actual so far
        $stmt = $pdo->prepare("SELECT SUM(price) as total FROM {$tables['source']} WHERE user_id = ? AND re_date BETWEEN ? AND ?");
        $stmt->execute([$user_id, $current_month_start, $current_month_end]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $current_actual = (float)($result['total'] ?? 0);

        // Add recurring expenses from current month
        $recurring_instances = generateRecurringExpenseInstances($pdo, $user_id, $current_month_start, $current_month_end);
        foreach ($recurring_instances as $instance) {
            $current_actual += $instance['price'];
        }

        // Calculate prediction based on proportional calculation
        $current_day = (int)date('d');
        $days_in_month = (int)date('t', strtotime($current_month_start));

        // If we have last year data, use it for prediction
        if ($last_year_actual > 0 && $current_day < $days_in_month) {
            // Proportion: (current_actual / current_day) compared to (last_year_actual / days_in_last_year_month)
            $days_in_last_year_month = (int)date('t', strtotime($last_year_start));
            $last_year_daily_avg = $last_year_actual / $days_in_last_year_month;

            // Predicted: current spending pace applied to remaining days + last year's pace
            if ($current_day > 0) {
                $current_daily_avg = $current_actual / $current_day;
                $predicted = $current_actual + ($current_daily_avg * ($days_in_month - $current_day));
            } else {
                $predicted = $last_year_actual;
            }
        } else {
            // If no last year data, just project current pace
            if ($current_day > 0 && $current_day < $days_in_month) {
                $current_daily_avg = $current_actual / $current_day;
                $predicted = $current_actual + ($current_daily_avg * ($days_in_month - $current_day));
            } else {
                $predicted = $current_actual;
            }
        }

        return [
            'predicted_amount' => round($predicted, 0),
            'last_year_actual' => round($last_year_actual, 0),
            'current_actual' => round($current_actual, 0),
            'current_day' => $current_day,
            'days_in_month' => $days_in_month
        ];
    } catch (Exception $e) {
        error_log('Predicted expense fetch error: ' . $e->getMessage());
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

    $recurring_expenses = getRecurringExpenses($pdo, $user_id, false);
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
