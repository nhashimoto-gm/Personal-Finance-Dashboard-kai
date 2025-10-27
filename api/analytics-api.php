<?php
/**
 * Finance Analytics API
 * 既存のpersonal-finance-dashboardシステムと完全統合
 */

// 既存の設定ファイルを読み込み
require_once __DIR__ . '/../config.php';

// CORS設定
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// データベース接続（既存の関数を使用）
try {
    $pdo = getDatabaseConnection();
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $action = $_GET['action'] ?? 'summary';

    // エンドポイント処理
    switch ($action) {
        case 'summary':
            handleSummary($pdo);
            break;

        case 'monthly':
            handleMonthly($pdo);
            break;

        case 'yearly':
            handleYearly($pdo);
            break;

        case 'shop':
            handleShop($pdo);
            break;

        case 'category':
            handleCategory($pdo);
            break;

        case 'daily':
            handleDaily($pdo);
            break;

        case 'trends':
            handleTrends($pdo);
            break;

        case 'period':
            handlePeriod($pdo);
            break;

        case 'stats':
            handleStatistics($pdo);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'action' => $action ?? 'unknown'
    ]);
}

/**
 * サマリー情報取得
 */
function handleSummary($pdo) {
    try {
        $tables = getTableNames();
        $start_date = $_GET['start_date'] ?? '2008-01-01';
        $end_date = $_GET['end_date'] ?? date('Y-m-d');

        // 全期間統計
        $stmt = $pdo->prepare("
            SELECT
                MIN(re_date) as earliest_date,
                MAX(re_date) as latest_date,
                SUM(price) as total_expense,
                COUNT(*) as total_transactions,
                COUNT(DISTINCT re_date) as active_days,
                COUNT(DISTINCT cat_1) as unique_shops,
                COUNT(DISTINCT cat_2) as unique_categories,
                ROUND(AVG(price)) as avg_transaction
            FROM {$tables['source']}
            WHERE re_date BETWEEN ? AND ?
        ");
        $stmt->execute([$start_date, $end_date]);
        $summary = $stmt->fetch();

        if (!$summary || !$summary['earliest_date']) {
            echo json_encode([
                'success' => false,
                'error' => 'No data found in database'
            ]);
            return;
        }

        // 月数計算
        $start = new DateTime($summary['earliest_date']);
        $end = new DateTime($summary['latest_date']);
        $interval = $start->diff($end);
        $total_months = ($interval->y * 12) + $interval->m + 1;

        echo json_encode([
            'success' => true,
            'data' => [
                'period' => [
                    'start' => $summary['earliest_date'],
                    'end' => $summary['latest_date'],
                    'total_months' => $total_months,
                    'total_years' => round($total_months / 12, 1)
                ],
                'totals' => [
                    'expense' => (int)$summary['total_expense'],
                    'transactions' => (int)$summary['total_transactions'],
                    'active_days' => (int)$summary['active_days']
                ],
                'averages' => [
                    'monthly_expense' => round($summary['total_expense'] / $total_months),
                    'daily_expense' => round($summary['total_expense'] / $summary['active_days']),
                    'transaction_amount' => (int)$summary['avg_transaction']
                ],
                'diversity' => [
                    'unique_shops' => (int)$summary['unique_shops'],
                    'unique_categories' => (int)$summary['unique_categories']
                ]
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Summary API error: ' . $e->getMessage()
        ]);
    }
}

/**
 * 月次データ取得
 */
function handleMonthly($pdo) {
    $tables = getTableNames();
    $start_date = $_GET['start_date'] ?? '2008-01-01';
    $end_date = $_GET['end_date'] ?? date('Y-m-d');

    $stmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(re_date, '%Y-%m') as month,
            YEAR(re_date) as year,
            MONTH(re_date) as month_num,
            SUM(price) as expense,
            COUNT(*) as transaction_count,
            COUNT(DISTINCT re_date) as active_days,
            ROUND(AVG(price)) as avg_transaction,
            MAX(price) as max_transaction,
            MIN(price) as min_transaction
        FROM {$tables['source']}
        WHERE re_date BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(re_date, '%Y-%m')
        ORDER BY re_date ASC
    ");
    $stmt->execute([$start_date, $end_date]);
    $data = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $data,
        'count' => count($data)
    ]);
}

/**
 * 年次データ取得
 */
function handleYearly($pdo) {
    $tables = getTableNames();

    $stmt = $pdo->query("
        SELECT
            YEAR(re_date) as year,
            SUM(price) as total_expense,
            COUNT(*) as transaction_count,
            COUNT(DISTINCT DATE_FORMAT(re_date, '%Y-%m')) as months_count,
            COUNT(DISTINCT cat_1) as unique_shops,
            ROUND(AVG(price)) as avg_transaction
        FROM {$tables['source']}
        WHERE re_date >= '2008-01-01'
        GROUP BY YEAR(re_date)
        ORDER BY year ASC
    ");
    $data = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
}

/**
 * ショップ別データ取得
 */
function handleShop($pdo) {
    $tables = getTableNames();
    $start_date = $_GET['start_date'] ?? '2008-01-01';
    $end_date = $_GET['end_date'] ?? date('Y-m-d');
    $limit = (int)($_GET['limit'] ?? 20);

    // LIMIT値のバリデーション（1-100の範囲）
    $limit = max(1, min(100, $limit));

    // デバッグ: テーブル名とパラメータを確認
    error_log("Shop API Debug - Tables: " . json_encode($tables));
    error_log("Shop API Debug - Params: start_date={$start_date}, end_date={$end_date}, limit={$limit}");

    // まずデータ件数を確認
    $countStmt = $pdo->prepare("SELECT COUNT(*) as count FROM {$tables['source']} WHERE re_date BETWEEN ? AND ?");
    $countStmt->execute([$start_date, $end_date]);
    $count = $countStmt->fetch();
    error_log("Shop API Debug - Source records in date range: " . $count['count']);

    // LIMIT句はバインドパラメータではなく直接埋め込む（整数として検証済み）
    $sql = "
        SELECT
            s.cat_1,
            c1.label as shop_name,
            SUM(s.price) as total,
            COUNT(*) as transaction_count,
            ROUND(AVG(s.price)) as avg_amount,
            MIN(s.re_date) as first_purchase,
            MAX(s.re_date) as last_purchase
        FROM {$tables['source']} s
        LEFT JOIN {$tables['cat_1_labels']} c1 ON s.cat_1 = c1.id
        WHERE s.re_date BETWEEN ? AND ?
        GROUP BY s.cat_1, c1.label
        ORDER BY total DESC
        LIMIT {$limit}
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_date, $end_date]);
    $data = $stmt->fetchAll();

    error_log("Shop API Debug - Result count: " . count($data));
    if (count($data) > 0) {
        error_log("Shop API Debug - First record: " . json_encode($data[0]));
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'debug' => [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'limit' => $limit,
            'record_count' => count($data),
            'source_records' => $count['count']
        ]
    ]);
}

/**
 * カテゴリ別データ取得
 */
function handleCategory($pdo) {
    $tables = getTableNames();
    $start_date = $_GET['start_date'] ?? '2008-01-01';
    $end_date = $_GET['end_date'] ?? date('Y-m-d');

    $stmt = $pdo->prepare("
        SELECT
            s.cat_2,
            c2.label as category_name,
            SUM(s.price) as total,
            COUNT(*) as transaction_count,
            ROUND(AVG(s.price)) as avg_amount
        FROM {$tables['source']} s
        LEFT JOIN {$tables['cat_2_labels']} c2 ON s.cat_2 = c2.id
        WHERE s.re_date BETWEEN ? AND ?
        GROUP BY s.cat_2, c2.label
        ORDER BY total DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $data = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
}

/**
 * 日別データ取得
 */
function handleDaily($pdo) {
    $tables = getTableNames();
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-d');

    $stmt = $pdo->prepare("
        SELECT
            re_date,
            SUM(price) as daily_total,
            COUNT(*) as transaction_count
        FROM {$tables['source']}
        WHERE re_date BETWEEN ? AND ?
        GROUP BY re_date
        ORDER BY re_date ASC
    ");
    $stmt->execute([$start_date, $end_date]);
    $data = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
}

/**
 * トレンド分析データ
 */
function handleTrends($pdo) {
    $tables = getTableNames();

    // 月別トレンド（全期間）
    $stmt = $pdo->query("
        SELECT
            DATE_FORMAT(re_date, '%Y-%m') as month,
            SUM(price) as expense,
            COUNT(*) as count
        FROM {$tables['source']}
        GROUP BY DATE_FORMAT(re_date, '%Y-%m')
        ORDER BY re_date ASC
    ");
    $monthly = $stmt->fetchAll();

    // カテゴリ別年次推移
    $stmt = $pdo->query("
        SELECT
            YEAR(s.re_date) as year,
            c2.label as category,
            SUM(s.price) as total
        FROM {$tables['source']} s
        LEFT JOIN {$tables['cat_2_labels']} c2 ON s.cat_2 = c2.id
        GROUP BY YEAR(s.re_date), c2.label
        ORDER BY year, total DESC
    ");
    $category_yearly = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => [
            'monthly' => $monthly,
            'category_yearly' => $category_yearly
        ]
    ]);
}

/**
 * 期間別推移（月次/年次）
 */
function handlePeriod($pdo) {
    $tables = getTableNames();
    $period_range = (int)($_GET['months'] ?? 12);
    $group_by_shop = $_GET['group_by_shop'] ?? false;

    if ($period_range < 60) {
        // 月次集計
        $group_clause = $group_by_shop ? ", c1.label" : "";
        $stmt = $pdo->prepare("
            SELECT
                DATE_FORMAT(s.re_date, '%Y-%m') as period,
                " . ($group_by_shop ? "c1.label as shop_name," : "") . "
                SUM(s.price) as total
            FROM {$tables['source']} s
            " . ($group_by_shop ? "LEFT JOIN {$tables['cat_1_labels']} c1 ON s.cat_1 = c1.id" : "") . "
            WHERE s.re_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY DATE_FORMAT(s.re_date, '%Y-%m')" . $group_clause . "
            ORDER BY period ASC
        ");
    } else {
        // 年次集計
        $group_clause = $group_by_shop ? ", c1.label" : "";
        $stmt = $pdo->prepare("
            SELECT
                YEAR(s.re_date) as period,
                " . ($group_by_shop ? "c1.label as shop_name," : "") . "
                SUM(s.price) as total
            FROM {$tables['source']} s
            " . ($group_by_shop ? "LEFT JOIN {$tables['cat_1_labels']} c1 ON s.cat_1 = c1.id" : "") . "
            WHERE s.re_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY YEAR(s.re_date)" . $group_clause . "
            ORDER BY period ASC
        ");
    }

    $stmt->execute([$period_range]);
    $data = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
}

/**
 * 統計分析データ
 */
function handleStatistics($pdo) {
    $tables = getTableNames();

    // 曜日別統計
    $stmt = $pdo->query("
        SELECT
            DAYNAME(re_date) as day_of_week,
            DAYOFWEEK(re_date) as day_num,
            ROUND(AVG(daily_total)) as avg_expense,
            COUNT(*) as day_count
        FROM (
            SELECT re_date, SUM(price) as daily_total
            FROM {$tables['source']}
            GROUP BY re_date
        ) daily
        GROUP BY DAYNAME(re_date), DAYOFWEEK(re_date)
        ORDER BY day_num
    ");
    $weekday_stats = $stmt->fetchAll();

    // 月別季節性
    $stmt = $pdo->query("
        SELECT
            MONTH(re_date) as month,
            ROUND(AVG(monthly_total)) as avg_expense,
            COUNT(*) as year_count
        FROM (
            SELECT
                DATE_FORMAT(re_date, '%Y-%m') as ym,
                MONTH(re_date) as month,
                SUM(price) as monthly_total
            FROM {$tables['source']}
            GROUP BY DATE_FORMAT(re_date, '%Y-%m')
        ) monthly
        GROUP BY MONTH(re_date)
        ORDER BY month
    ");
    $seasonal_stats = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => [
            'weekday' => $weekday_stats,
            'seasonal' => $seasonal_stats
        ]
    ]);
}
?>
