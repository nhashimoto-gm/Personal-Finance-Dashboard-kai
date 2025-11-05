<?php
/**
 * Finance Analytics API
 * 既存のpersonal-finance-dashboardシステムと完全統合
 * セキュアバージョン: 認証とuser_idフィルタリング実装
 */

// 既存の設定ファイルを読み込み
require_once __DIR__ . '/../config.php';

// 認証チェック
if (!isLoggedIn()) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized',
        'message' => 'Authentication required'
    ]);
    exit;
}

// 現在のユーザーIDを取得
$user_id = getCurrentUserId();
if (!$user_id) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'Invalid session',
        'message' => 'User ID not found in session'
    ]);
    exit;
}

// ============================================================
// CORS設定（セキュア版）
// ============================================================
// 環境別のCORS設定を実装
// 本番環境: ホワイトリストのオリジンのみ許可
// 開発環境: 柔軟な設定（環境変数で制御）
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// 環境変数からアプリケーション環境を取得
$appEnv = getenv('APP_ENV') ?: 'development';

// 環境変数から許可するオリジンを取得（カンマ区切り）
// 例: ALLOWED_ORIGINS=https://example.com,https://www.example.com
$allowedOriginsEnv = getenv('ALLOWED_ORIGINS');

// 許可するオリジンのホワイトリスト
$allowedOrigins = [];

if ($allowedOriginsEnv) {
    // 環境変数で指定されている場合
    $allowedOrigins = array_map('trim', explode(',', $allowedOriginsEnv));
} elseif ($appEnv === 'production') {
    // 本番環境: 厳格なホワイトリスト（要変更）
    // ⚠️ 警告: 実際のドメインに変更してください
    $allowedOrigins = [
        'https://yourdomain.com',
        'https://www.yourdomain.com',
        'https://app.yourdomain.com'
    ];
} else {
    // 開発環境: localhost系を許可
    $allowedOrigins = [
        'http://localhost',
        'http://localhost:3000',
        'http://localhost:8000',
        'http://localhost:8080',
        'http://127.0.0.1',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:8000',
        'http://127.0.0.1:8080'
    ];
}

// リクエストのオリジンを取得
$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';

// オリジン検証
$originAllowed = false;

if (!empty($requestOrigin)) {
    // 完全一致チェック
    if (in_array($requestOrigin, $allowedOrigins, true)) {
        $originAllowed = true;
        header('Access-Control-Allow-Origin: ' . $requestOrigin);
    }
    // 開発環境のみ: ワイルドカードパターンマッチ（オプション）
    elseif ($appEnv === 'development') {
        // localhostのポート違いを許可
        if (preg_match('/^http:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/', $requestOrigin)) {
            $originAllowed = true;
            header('Access-Control-Allow-Origin: ' . $requestOrigin);
        }
    }
}

// オリジンが許可されていない場合
if (!$originAllowed && !empty($requestOrigin)) {
    // 本番環境: 403 Forbiddenを返す
    if ($appEnv === 'production') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Forbidden',
            'message' => 'Origin not allowed'
        ]);
        error_log("CORS: Blocked origin - $requestOrigin");
        exit;
    }
    // 開発環境: 警告ログのみ（ブロックしない）
    else {
        error_log("CORS: Warning - Unknown origin: $requestOrigin");
        // 開発環境では許可（デバッグ用）
        header('Access-Control-Allow-Origin: ' . $requestOrigin);
    }
}

// プリフライトリクエストの処理
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    header('Access-Control-Max-Age: 86400'); // 24時間キャッシュ
    exit(0);
}

// データベース接続（既存の関数を使用）
try {
    $pdo = getDatabaseConnection();
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $action = $_GET['action'] ?? 'summary';

    // エンドポイント処理（user_idを渡す）
    switch ($action) {
        case 'summary':
            handleSummary($pdo, $user_id);
            break;

        case 'monthly':
            handleMonthly($pdo, $user_id);
            break;

        case 'yearly':
            handleYearly($pdo, $user_id);
            break;

        case 'shop':
            handleShop($pdo, $user_id);
            break;

        case 'category':
            handleCategory($pdo, $user_id);
            break;

        case 'daily':
            handleDaily($pdo, $user_id);
            break;

        case 'trends':
            handleTrends($pdo, $user_id);
            break;

        case 'period':
            handlePeriod($pdo, $user_id);
            break;

        case 'stats':
            handleStatistics($pdo, $user_id);
            break;

        case 'forecast':
            handleForecast($pdo, $user_id);
            break;

        case 'anomalies':
            handleAnomalies($pdo, $user_id);
            break;

        case 'advanced_stats':
            handleAdvancedStatistics($pdo, $user_id);
            break;

        case 'correlation':
            handleCorrelation($pdo, $user_id);
            break;

        case 'heatmap':
            handleHeatmap($pdo, $user_id);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'action' => $action ?? 'unknown'
    ]);
}

/**
 * サマリー情報取得
 */
function handleSummary($pdo, $user_id) {
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
            WHERE user_id = ? AND re_date BETWEEN ? AND ?
        ");
        $stmt->execute([$user_id, $start_date, $end_date]);
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

        // 比較データの取得
        $currentMonth = date('Y-m');
        $lastMonth = date('Y-m', strtotime('-1 month'));
        $sixMonthsAgo = date('Y-m', strtotime('-6 months'));
        $twelveMonthsAgo = date('Y-m', strtotime('-12 months'));

        // 当月の支出
        $currentStmt = $pdo->prepare("
            SELECT COALESCE(SUM(price), 0) as total
            FROM {$tables['source']}
            WHERE user_id = ? AND DATE_FORMAT(re_date, '%Y-%m') = ?
        ");
        $currentStmt->execute([$user_id, $currentMonth]);
        $currentMonthExpense = $currentStmt->fetch()['total'];

        // 前月の支出
        $lastStmt = $pdo->prepare("
            SELECT COALESCE(SUM(price), 0) as total
            FROM {$tables['source']}
            WHERE user_id = ? AND DATE_FORMAT(re_date, '%Y-%m') = ?
        ");
        $lastStmt->execute([$user_id, $lastMonth]);
        $lastMonthExpense = $lastStmt->fetch()['total'];

        // 6か月前の支出
        $sixStmt = $pdo->prepare("
            SELECT COALESCE(SUM(price), 0) as total
            FROM {$tables['source']}
            WHERE user_id = ? AND DATE_FORMAT(re_date, '%Y-%m') = ?
        ");
        $sixStmt->execute([$user_id, $sixMonthsAgo]);
        $sixMonthsAgoExpense = $sixStmt->fetch()['total'];

        // 12か月前の支出
        $twelveStmt = $pdo->prepare("
            SELECT COALESCE(SUM(price), 0) as total
            FROM {$tables['source']}
            WHERE user_id = ? AND DATE_FORMAT(re_date, '%Y-%m') = ?
        ");
        $twelveStmt->execute([$user_id, $twelveMonthsAgo]);
        $twelveMonthsAgoExpense = $twelveStmt->fetch()['total'];

        // パーセンテージ計算
        $vsLastMonth = $lastMonthExpense > 0 ? (($currentMonthExpense - $lastMonthExpense) / $lastMonthExpense) * 100 : 0;
        $vsSixMonths = $sixMonthsAgoExpense > 0 ? (($currentMonthExpense - $sixMonthsAgoExpense) / $sixMonthsAgoExpense) * 100 : 0;
        $vsTwelveMonths = $twelveMonthsAgoExpense > 0 ? (($currentMonthExpense - $twelveMonthsAgoExpense) / $twelveMonthsAgoExpense) * 100 : 0;

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
                ],
                'comparisons' => [
                    'current_month' => (int)$currentMonthExpense,
                    'last_month' => (int)$lastMonthExpense,
                    'six_months_ago' => (int)$sixMonthsAgoExpense,
                    'twelve_months_ago' => (int)$twelveMonthsAgoExpense,
                    'current_vs_last_month' => round($vsLastMonth, 2),
                    'current_vs_six_months' => round($vsSixMonths, 2),
                    'current_vs_twelve_months' => round($vsTwelveMonths, 2)
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
function handleMonthly($pdo, $user_id) {
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
        WHERE user_id = ? AND re_date BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(re_date, '%Y-%m')
        ORDER BY re_date ASC
    ");
    $stmt->execute([$user_id, $start_date, $end_date]);
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
function handleYearly($pdo, $user_id) {
    $tables = getTableNames();

    $stmt = $pdo->prepare("
        SELECT
            YEAR(re_date) as year,
            SUM(price) as total_expense,
            COUNT(*) as transaction_count,
            COUNT(DISTINCT DATE_FORMAT(re_date, '%Y-%m')) as months_count,
            COUNT(DISTINCT cat_1) as unique_shops,
            ROUND(AVG(price)) as avg_transaction
        FROM {$tables['source']}
        WHERE user_id = ? AND re_date >= '2008-01-01'
        GROUP BY YEAR(re_date)
        ORDER BY year ASC
    ");
    $stmt->execute([$user_id]);
    $data = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
}

/**
 * ショップ別データ取得
 */
function handleShop($pdo, $user_id) {
    $tables = getTableNames();
    $start_date = $_GET['start_date'] ?? '2008-01-01';
    $end_date = $_GET['end_date'] ?? date('Y-m-d');
    $limit = (int)($_GET['limit'] ?? 20);

    // LIMIT値のバリデーション（1-100の範囲）
    $limit = max(1, min(100, $limit));

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
        WHERE s.user_id = ? AND s.re_date BETWEEN ? AND ?
        GROUP BY s.cat_1, c1.label
        ORDER BY total DESC
        LIMIT {$limit}
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $start_date, $end_date]);
    $data = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $data,
        'debug' => [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'limit' => $limit,
            'record_count' => count($data)
        ]
    ]);
}

/**
 * カテゴリ別データ取得
 */
function handleCategory($pdo, $user_id) {
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
        WHERE s.user_id = ? AND s.re_date BETWEEN ? AND ?
        GROUP BY s.cat_2, c2.label
        ORDER BY total DESC
    ");
    $stmt->execute([$user_id, $start_date, $end_date]);
    $data = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
}

/**
 * 日別データ取得
 */
function handleDaily($pdo, $user_id) {
    $tables = getTableNames();
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-d');

    $stmt = $pdo->prepare("
        SELECT
            re_date,
            SUM(price) as daily_total,
            COUNT(*) as transaction_count
        FROM {$tables['source']}
        WHERE user_id = ? AND re_date BETWEEN ? AND ?
        GROUP BY re_date
        ORDER BY re_date ASC
    ");
    $stmt->execute([$user_id, $start_date, $end_date]);
    $data = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
}

/**
 * トレンド分析データ
 */
function handleTrends($pdo, $user_id) {
    $tables = getTableNames();

    // 月別トレンド（全期間）
    $stmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(re_date, '%Y-%m') as month,
            SUM(price) as expense,
            COUNT(*) as count
        FROM {$tables['source']}
        WHERE user_id = ?
        GROUP BY DATE_FORMAT(re_date, '%Y-%m')
        ORDER BY re_date ASC
    ");
    $stmt->execute([$user_id]);
    $monthly = $stmt->fetchAll();

    // カテゴリ別年次推移
    $stmt = $pdo->prepare("
        SELECT
            YEAR(s.re_date) as year,
            c2.label as category,
            SUM(s.price) as total
        FROM {$tables['source']} s
        LEFT JOIN {$tables['cat_2_labels']} c2 ON s.cat_2 = c2.id
        WHERE s.user_id = ?
        GROUP BY YEAR(s.re_date), c2.label
        ORDER BY year, total DESC
    ");
    $stmt->execute([$user_id]);
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
function handlePeriod($pdo, $user_id) {
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
            WHERE s.user_id = ? AND s.re_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
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
            WHERE s.user_id = ? AND s.re_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY YEAR(s.re_date)" . $group_clause . "
            ORDER BY period ASC
        ");
    }

    $stmt->execute([$user_id, $period_range]);
    $data = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
}

/**
 * 統計分析データ
 */
function handleStatistics($pdo, $user_id) {
    try {
        $tables = getTableNames();

        // 曜日別統計
        $stmt = $pdo->prepare("
            SELECT
                DAYNAME(daily.re_date) as day_of_week,
                DAYOFWEEK(daily.re_date) as day_num,
                ROUND(AVG(daily.daily_total)) as avg_expense,
                COUNT(*) as day_count
            FROM (
                SELECT re_date, SUM(price) as daily_total
                FROM {$tables['source']}
                WHERE user_id = ?
                GROUP BY re_date
            ) daily
            GROUP BY DAYOFWEEK(daily.re_date), DAYNAME(daily.re_date)
            ORDER BY day_num
        ");
        $stmt->execute([$user_id]);
        $weekday_stats = $stmt->fetchAll();

        // 月別季節性
        $stmt = $pdo->prepare("
            SELECT
                monthly.month,
                ROUND(AVG(monthly.monthly_total)) as avg_expense,
                COUNT(*) as year_count
            FROM (
                SELECT
                    DATE_FORMAT(re_date, '%Y-%m') as ym,
                    MONTH(re_date) as month,
                    SUM(price) as monthly_total
                FROM {$tables['source']}
                WHERE user_id = ?
                GROUP BY DATE_FORMAT(re_date, '%Y-%m'), MONTH(re_date)
            ) monthly
            GROUP BY monthly.month
            ORDER BY monthly.month
        ");
        $stmt->execute([$user_id]);
        $seasonal_stats = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => [
                'weekday' => $weekday_stats,
                'seasonal' => $seasonal_stats
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Statistics API error: ' . $e->getMessage(),
            'data' => [
                'weekday' => [],
                'seasonal' => []
            ]
        ]);
    }
}

/**
 * 予測分析（時系列予測）
 * 線形回帰と移動平均を使用して将来の支出を予測
 */
function handleForecast($pdo, $user_id) {
    try {
        $tables = getTableNames();
        $months_ahead = (int)($_GET['months'] ?? 6); // デフォルト6ヶ月先まで予測
        $months_ahead = max(1, min(12, $months_ahead)); // 1-12ヶ月の範囲

        // 過去の月次データを取得（最低12ヶ月、できれば24ヶ月）
        $stmt = $pdo->prepare("
            SELECT
                DATE_FORMAT(re_date, '%Y-%m') as month,
                SUM(price) as expense,
                COUNT(*) as transaction_count,
                COUNT(DISTINCT re_date) as active_days
            FROM {$tables['source']}
            WHERE user_id = ? AND re_date >= DATE_SUB(CURDATE(), INTERVAL 24 MONTH)
            GROUP BY DATE_FORMAT(re_date, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt->execute([$user_id]);
        $historical = $stmt->fetchAll();

        if (count($historical) < 3) {
            echo json_encode([
                'success' => false,
                'error' => 'Not enough historical data for forecasting (minimum 3 months required)'
            ]);
            return;
        }

        // 線形回帰の計算
        $n = count($historical);
        $sum_x = 0;
        $sum_y = 0;
        $sum_xy = 0;
        $sum_x2 = 0;

        foreach ($historical as $i => $row) {
            $x = $i; // 時間インデックス
            $y = (float)$row['expense'];
            $sum_x += $x;
            $sum_y += $y;
            $sum_xy += $x * $y;
            $sum_x2 += $x * $x;
        }

        // 傾きと切片の計算
        $slope = ($n * $sum_xy - $sum_x * $sum_y) / ($n * $sum_x2 - $sum_x * $sum_x);
        $intercept = ($sum_y - $slope * $sum_x) / $n;

        // 移動平均の計算（3ヶ月移動平均）
        $ma_period = min(3, count($historical));
        $moving_avg = 0;
        for ($i = count($historical) - $ma_period; $i < count($historical); $i++) {
            $moving_avg += (float)$historical[$i]['expense'];
        }
        $moving_avg = $moving_avg / $ma_period;

        // 予測値の生成
        $predictions = [];
        $current_date = new DateTime();
        $current_date->modify('first day of next month');

        for ($i = 0; $i < $months_ahead; $i++) {
            $x = $n + $i; // 未来の時間インデックス
            $linear_pred = $slope * $x + $intercept;

            // 線形予測と移動平均の加重平均（60%線形、40%移動平均）
            $weighted_pred = $linear_pred * 0.6 + $moving_avg * 0.4;

            // 季節性調整（同じ月の過去平均との比率）
            $target_month = (int)$current_date->format('n');
            $seasonal_factor = calculateSeasonalFactor($pdo, $tables, $user_id, $target_month);
            $adjusted_pred = $weighted_pred * $seasonal_factor;

            $predictions[] = [
                'month' => $current_date->format('Y-m'),
                'predicted_expense' => round($adjusted_pred),
                'linear_prediction' => round($linear_pred),
                'moving_average' => round($moving_avg),
                'seasonal_factor' => round($seasonal_factor, 3),
                'confidence' => calculateConfidence($n, $i)
            ];

            $current_date->modify('+1 month');
        }

        // トレンド情報
        $trend = $slope > 0 ? 'increasing' : ($slope < 0 ? 'decreasing' : 'stable');
        $trend_rate = abs($slope) / ($sum_y / $n) * 100; // パーセンテージ変化率

        echo json_encode([
            'success' => true,
            'data' => [
                'historical' => $historical,
                'predictions' => $predictions,
                'model' => [
                    'type' => 'linear_regression_with_ma',
                    'slope' => round($slope, 2),
                    'intercept' => round($intercept, 2),
                    'moving_average' => round($moving_avg),
                    'trend' => $trend,
                    'trend_rate' => round($trend_rate, 2),
                    'data_points' => $n
                ]
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Forecast API error: ' . $e->getMessage()
        ]);
    }
}

/**
 * 季節性ファクターの計算
 */
function calculateSeasonalFactor($pdo, $tables, $user_id, $target_month) {
    try {
        // 対象月の過去平均
        $stmt = $pdo->prepare("
            SELECT AVG(monthly_total) as month_avg
            FROM (
                SELECT SUM(price) as monthly_total
                FROM {$tables['source']}
                WHERE user_id = ? AND MONTH(re_date) = ?
                AND re_date >= DATE_SUB(CURDATE(), INTERVAL 24 MONTH)
                GROUP BY DATE_FORMAT(re_date, '%Y-%m')
            ) monthly
        ");
        $stmt->execute([$user_id, $target_month]);
        $month_data = $stmt->fetch();

        // 全体平均
        $stmt = $pdo->prepare("
            SELECT AVG(monthly_total) as overall_avg
            FROM (
                SELECT SUM(price) as monthly_total
                FROM {$tables['source']}
                WHERE user_id = ? AND re_date >= DATE_SUB(CURDATE(), INTERVAL 24 MONTH)
                GROUP BY DATE_FORMAT(re_date, '%Y-%m')
            ) monthly
        ");
        $stmt->execute([$user_id]);
        $overall_data = $stmt->fetch();

        if ($overall_data['overall_avg'] > 0 && $month_data['month_avg'] > 0) {
            return (float)$month_data['month_avg'] / (float)$overall_data['overall_avg'];
        }
        return 1.0; // デフォルト値
    } catch (Exception $e) {
        return 1.0;
    }
}

/**
 * 予測信頼度の計算
 */
function calculateConfidence($data_points, $months_ahead) {
    // データポイントが多いほど、近い未来ほど信頼度が高い
    $base_confidence = min(0.95, 0.5 + ($data_points / 48)); // 最大95%
    $decay = pow(0.9, $months_ahead); // 先に行くほど減衰
    return round($base_confidence * $decay, 2);
}

/**
 * 異常検知
 * 標準偏差を使用して通常パターンから外れた支出を検出
 */
function handleAnomalies($pdo, $user_id) {
    try {
        $tables = getTableNames();
        $sensitivity = (float)($_GET['sensitivity'] ?? 2.0); // 標準偏差の倍数
        $days_back = (int)($_GET['days'] ?? 90);

        // 日次データの取得
        $stmt = $pdo->prepare("
            SELECT
                re_date,
                SUM(price) as daily_total,
                COUNT(*) as transaction_count
            FROM {$tables['source']}
            WHERE user_id = ? AND re_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY re_date
            ORDER BY re_date DESC
        ");
        $stmt->execute([$user_id, $days_back]);
        $daily_data = $stmt->fetchAll();

        if (count($daily_data) < 7) {
            echo json_encode([
                'success' => false,
                'error' => 'Not enough data for anomaly detection'
            ]);
            return;
        }

        // 平均と標準偏差の計算
        $total = 0;
        $totals = [];
        foreach ($daily_data as $row) {
            $val = (float)$row['daily_total'];
            $totals[] = $val;
            $total += $val;
        }
        $mean = $total / count($totals);

        $variance = 0;
        foreach ($totals as $val) {
            $variance += pow($val - $mean, 2);
        }
        $variance = $variance / count($totals);
        $std_dev = sqrt($variance);

        // 異常の検出
        $anomalies = [];
        $threshold_upper = $mean + ($sensitivity * $std_dev);
        $threshold_lower = max(0, $mean - ($sensitivity * $std_dev));

        foreach ($daily_data as $row) {
            $amount = (float)$row['daily_total'];
            $z_score = $std_dev > 0 ? ($amount - $mean) / $std_dev : 0;

            if ($amount > $threshold_upper || $amount < $threshold_lower) {
                $anomalies[] = [
                    'date' => $row['re_date'],
                    'amount' => (int)$amount,
                    'transaction_count' => (int)$row['transaction_count'],
                    'deviation' => round($amount - $mean),
                    'z_score' => round($z_score, 2),
                    'type' => $amount > $threshold_upper ? 'high' : 'low',
                    'severity' => abs($z_score) > 3 ? 'critical' : (abs($z_score) > 2.5 ? 'high' : 'moderate')
                ];
            }
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'anomalies' => $anomalies,
                'statistics' => [
                    'mean' => round($mean),
                    'std_dev' => round($std_dev),
                    'threshold_upper' => round($threshold_upper),
                    'threshold_lower' => round($threshold_lower),
                    'total_days' => count($daily_data),
                    'anomaly_count' => count($anomalies),
                    'anomaly_rate' => round(count($anomalies) / count($daily_data) * 100, 2)
                ]
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Anomaly detection error: ' . $e->getMessage()
        ]);
    }
}

/**
 * 高度な統計分析
 */
function handleAdvancedStatistics($pdo, $user_id) {
    try {
        $tables = getTableNames();
        $period = $_GET['period'] ?? 'monthly'; // monthly or daily

        if ($period === 'monthly') {
            // 月次統計
            $stmt = $pdo->prepare("
                SELECT
                    DATE_FORMAT(re_date, '%Y-%m') as period,
                    SUM(price) as total,
                    COUNT(*) as count,
                    AVG(price) as mean,
                    MIN(price) as min,
                    MAX(price) as max
                FROM {$tables['source']}
                WHERE user_id = ? AND re_date >= DATE_SUB(CURDATE(), INTERVAL 24 MONTH)
                GROUP BY DATE_FORMAT(re_date, '%Y-%m')
                ORDER BY period ASC
            ");
            $stmt->execute([$user_id]);
        } else {
            // 日次統計
            $stmt = $pdo->prepare("
                SELECT
                    re_date as period,
                    SUM(price) as total,
                    COUNT(*) as count,
                    AVG(price) as mean,
                    MIN(price) as min,
                    MAX(price) as max
                FROM {$tables['source']}
                WHERE user_id = ? AND re_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                GROUP BY re_date
                ORDER BY re_date ASC
            ");
            $stmt->execute([$user_id]);
        }
        $data = $stmt->fetchAll();

        if (count($data) < 2) {
            echo json_encode([
                'success' => false,
                'error' => 'Not enough data for statistical analysis'
            ]);
            return;
        }

        // 統計計算
        $totals = array_map(function($row) { return (float)$row['total']; }, $data);
        $n = count($totals);
        $sum = array_sum($totals);
        $mean = $sum / $n;

        // 分散と標準偏差
        $variance = 0;
        foreach ($totals as $val) {
            $variance += pow($val - $mean, 2);
        }
        $variance = $variance / $n;
        $std_dev = sqrt($variance);

        // 変動係数（CV）
        $cv = $mean > 0 ? ($std_dev / $mean) * 100 : 0;

        // 四分位数
        sort($totals);
        $q1 = $totals[floor($n * 0.25)];
        $q2 = $totals[floor($n * 0.50)]; // 中央値
        $q3 = $totals[floor($n * 0.75)];
        $iqr = $q3 - $q1;

        // 歪度（Skewness）の簡易計算
        $skewness = 0;
        foreach ($totals as $val) {
            $skewness += pow(($val - $mean) / $std_dev, 3);
        }
        $skewness = $skewness / $n;

        // 信頼区間（95%）
        $z_score = 1.96; // 95%信頼区間
        $std_error = $std_dev / sqrt($n);
        $ci_lower = $mean - ($z_score * $std_error);
        $ci_upper = $mean + ($z_score * $std_error);

        echo json_encode([
            'success' => true,
            'data' => [
                'period_type' => $period,
                'sample_size' => $n,
                'descriptive' => [
                    'mean' => round($mean),
                    'median' => round($q2),
                    'min' => round(min($totals)),
                    'max' => round(max($totals)),
                    'range' => round(max($totals) - min($totals))
                ],
                'dispersion' => [
                    'variance' => round($variance),
                    'std_deviation' => round($std_dev),
                    'coefficient_of_variation' => round($cv, 2),
                    'iqr' => round($iqr)
                ],
                'quartiles' => [
                    'q1' => round($q1),
                    'q2' => round($q2),
                    'q3' => round($q3)
                ],
                'distribution' => [
                    'skewness' => round($skewness, 3),
                    'interpretation' => abs($skewness) < 0.5 ? 'symmetric' : ($skewness > 0 ? 'right_skewed' : 'left_skewed')
                ],
                'confidence_interval_95' => [
                    'lower' => round($ci_lower),
                    'upper' => round($ci_upper),
                    'margin_of_error' => round($z_score * $std_error)
                ],
                'time_series' => $data
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Advanced statistics error: ' . $e->getMessage()
        ]);
    }
}

/**
 * カテゴリ間の相関分析
 */
function handleCorrelation($pdo, $user_id) {
    try {
        $tables = getTableNames();

        // カテゴリ別月次データの取得
        $stmt = $pdo->prepare("
            SELECT
                DATE_FORMAT(s.re_date, '%Y-%m') as month,
                c2.label as category,
                SUM(s.price) as total
            FROM {$tables['source']} s
            LEFT JOIN {$tables['cat_2_labels']} c2 ON s.cat_2 = c2.id
            WHERE s.user_id = ? AND s.re_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            AND c2.label IS NOT NULL
            GROUP BY DATE_FORMAT(s.re_date, '%Y-%m'), c2.label
            ORDER BY month, total DESC
        ");
        $stmt->execute([$user_id]);
        $raw_data = $stmt->fetchAll();

        // データを月×カテゴリの行列に変換
        $matrix = [];
        $categories = [];
        foreach ($raw_data as $row) {
            $month = $row['month'];
            $category = $row['category'];
            $total = (float)$row['total'];

            if (!isset($matrix[$month])) {
                $matrix[$month] = [];
            }
            $matrix[$month][$category] = $total;
            $categories[$category] = true;
        }

        $categories = array_keys($categories);

        // 上位5カテゴリのみに絞る（計算量削減）
        $category_totals = [];
        foreach ($categories as $cat) {
            $category_totals[$cat] = 0;
            foreach ($matrix as $month_data) {
                $category_totals[$cat] += $month_data[$cat] ?? 0;
            }
        }
        arsort($category_totals);
        $top_categories = array_slice(array_keys($category_totals), 0, 5);

        // 相関係数の計算
        $correlations = [];
        for ($i = 0; $i < count($top_categories); $i++) {
            for ($j = $i + 1; $j < count($top_categories); $j++) {
                $cat1 = $top_categories[$i];
                $cat2 = $top_categories[$j];

                $values1 = [];
                $values2 = [];
                foreach ($matrix as $month_data) {
                    $values1[] = $month_data[$cat1] ?? 0;
                    $values2[] = $month_data[$cat2] ?? 0;
                }

                $correlation = calculatePearsonCorrelation($values1, $values2);

                if ($correlation !== null) {
                    $correlations[] = [
                        'category1' => $cat1,
                        'category2' => $cat2,
                        'correlation' => round($correlation, 3),
                        'strength' => abs($correlation) > 0.7 ? 'strong' : (abs($correlation) > 0.4 ? 'moderate' : 'weak'),
                        'direction' => $correlation > 0 ? 'positive' : 'negative'
                    ];
                }
            }
        }

        // 相関の強い順にソート
        usort($correlations, function($a, $b) {
            return abs($b['correlation']) <=> abs($a['correlation']);
        });

        echo json_encode([
            'success' => true,
            'data' => [
                'correlations' => $correlations,
                'categories_analyzed' => $top_categories,
                'total_months' => count($matrix)
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Correlation analysis error: ' . $e->getMessage()
        ]);
    }
}

/**
 * ピアソン相関係数の計算
 */
function calculatePearsonCorrelation($x, $y) {
    $n = count($x);
    if ($n < 2 || $n !== count($y)) return null;

    $sum_x = array_sum($x);
    $sum_y = array_sum($y);
    $sum_xy = 0;
    $sum_x2 = 0;
    $sum_y2 = 0;

    for ($i = 0; $i < $n; $i++) {
        $sum_xy += $x[$i] * $y[$i];
        $sum_x2 += $x[$i] * $x[$i];
        $sum_y2 += $y[$i] * $y[$i];
    }

    $numerator = ($n * $sum_xy) - ($sum_x * $sum_y);
    $denominator = sqrt((($n * $sum_x2) - ($sum_x * $sum_x)) * (($n * $sum_y2) - ($sum_y * $sum_y)));

    if (abs($denominator) < 0.0001) return 0;
    return $numerator / $denominator;
}

/**
 * ヒートマップデータ（曜日×月の支出パターン）
 */
function handleHeatmap($pdo, $user_id) {
    try {
        $tables = getTableNames();
        $type = $_GET['type'] ?? 'weekday_month'; // weekday_month, hour_day, category_month

        if ($type === 'weekday_month') {
            // 曜日×月のヒートマップ
            $stmt = $pdo->prepare("
                SELECT
                    DAYOFWEEK(re_date) as day_of_week,
                    DAYNAME(re_date) as day_name,
                    MONTH(re_date) as month,
                    MONTHNAME(re_date) as month_name,
                    ROUND(AVG(daily_total)) as avg_expense,
                    COUNT(*) as occurrence_count
                FROM (
                    SELECT re_date, SUM(price) as daily_total
                    FROM {$tables['source']}
                    WHERE user_id = ? AND re_date >= DATE_SUB(CURDATE(), INTERVAL 24 MONTH)
                    GROUP BY re_date
                ) daily
                GROUP BY DAYOFWEEK(re_date), DAYNAME(re_date), MONTH(re_date), MONTHNAME(re_date)
                ORDER BY month, day_of_week
            ");
            $stmt->execute([$user_id]);
            $data = $stmt->fetchAll();

            // マトリクス形式に変換
            $matrix = [];
            $max_value = 0;
            foreach ($data as $row) {
                $day = (int)$row['day_of_week'];
                $month = (int)$row['month'];
                $value = (int)$row['avg_expense'];

                if (!isset($matrix[$day])) {
                    $matrix[$day] = array_fill(1, 12, 0);
                }
                $matrix[$day][$month] = $value;
                $max_value = max($max_value, $value);
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'type' => 'weekday_month',
                    'matrix' => $matrix,
                    'max_value' => $max_value,
                    'raw_data' => $data,
                    'labels' => [
                        'x' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                        'y' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
                    ]
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Unsupported heatmap type'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Heatmap data error: ' . $e->getMessage()
        ]);
    }
}
?>
