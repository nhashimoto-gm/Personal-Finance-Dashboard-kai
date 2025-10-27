<?php
/**
 * Finance Analytics API
 * 既存のpersonal-finance-dashboard-publicシステムと互換性あり
 */

// CORS設定
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 設定ファイル読み込み
function loadEnv($file = '.env_db') {
    if (!file_exists($file)) {
        $file = '.env_db.example';
    }
    
    $config = parse_ini_file($file);
    return $config;
}

// テーブル名取得
function getTableNames() {
    global $config;
    return [
        'source' => $config['DB_TABLE_SOURCE'] ?? 'source',
        'shop' => $config['DB_TABLE_SHOP'] ?? 'cat_1_labels',
        'category' => $config['DB_TABLE_CATEGORY'] ?? 'cat_2_labels',
        'view1' => $config['DB_VIEW_MAIN'] ?? 'view1',
        'budgets' => $config['DB_TABLE_BUDGETS'] ?? 'budgets'
    ];
}

// データベース接続
function getConnection() {
    global $config;
    
    try {
        $pdo = new PDO(
            "mysql:host={$config['DB_HOST']};dbname={$config['DB_DATABASE']};charset=utf8mb4",
            $config['DB_USERNAME'],
            $config['DB_PASSWORD'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database connection failed',
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

$config = loadEnv();
$pdo = getConnection();
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

/**
 * サマリー情報取得
 */
function handleSummary($pdo) {
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
    
    $stmt = $pdo->prepare("
        SELECT 
            v.cat_1,
            v.label1 as shop_name,
            SUM(v.price) as total,
            COUNT(*) as transaction_count,
            ROUND(AVG(v.price)) as avg_amount,
            MIN(v.re_date) as first_purchase,
            MAX(v.re_date) as last_purchase
        FROM {$tables['view1']} v
        WHERE v.re_date BETWEEN ? AND ?
        GROUP BY v.cat_1, v.label1
        ORDER BY total DESC
        LIMIT ?
    ");
    $stmt->execute([$start_date, $end_date, $limit]);
    $data = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $data
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
            v.cat_2,
            v.label2 as category_name,
            SUM(v.price) as total,
            COUNT(*) as transaction_count,
            ROUND(AVG(v.price)) as avg_amount
        FROM {$tables['view1']} v
        WHERE v.re_date BETWEEN ? AND ?
        GROUP BY v.cat_2, v.label2
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
            YEAR(re_date) as year,
            label2 as category,
            SUM(price) as total
        FROM {$tables['view1']}
        GROUP BY YEAR(re_date), label2
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
        $group_clause = $group_by_shop ? ", label1" : "";
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(re_date, '%Y-%m') as period,
                " . ($group_by_shop ? "label1 as shop_name," : "") . "
                SUM(price) as total
            FROM {$tables['view1']}
            WHERE re_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY DATE_FORMAT(re_date, '%Y-%m')" . $group_clause . "
            ORDER BY period ASC
        ");
    } else {
        // 年次集計
        $group_clause = $group_by_shop ? ", label1" : "";
        $stmt = $pdo->prepare("
            SELECT 
                YEAR(re_date) as period,
                " . ($group_by_shop ? "label1 as shop_name," : "") . "
                SUM(price) as total
            FROM {$tables['view1']}
            WHERE re_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY YEAR(re_date)" . $group_clause . "
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
    
    // カテゴリ別成長率
    $stmt = $pdo->query("
        WITH yearly_category AS (
            SELECT 
                YEAR(re_date) as year,
                label2 as category,
                SUM(price) as total
            FROM {$tables['view1']}
            GROUP BY YEAR(re_date), label2
        )
        SELECT 
            y1.category,
            y1.year,
            y1.total as current_year,
            y2.total as previous_year,
            ROUND(((y1.total - y2.total) / y2.total * 100), 2) as growth_rate
        FROM yearly_category y1
        LEFT JOIN yearly_category y2 
            ON y1.category = y2.category 
            AND y1.year = y2.year + 1
        WHERE y2.total IS NOT NULL
        ORDER BY y1.year DESC, y1.total DESC
    ");
    $growth_rates = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'weekday' => $weekday_stats,
            'seasonal' => $seasonal_stats,
            'growth' => $growth_rates
        ]
    ]);
}
?>
