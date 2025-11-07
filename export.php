<?php
// export.php - CSV/Excelエクスポート機能（セキュア版）

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/queries.php';

// 認証チェック
if (!isLoggedIn()) {
    http_response_code(401);
    die('Unauthorized: Authentication required');
}

// 現在のユーザーIDを取得
$user_id = getCurrentUserId();
if (!$user_id) {
    http_response_code(401);
    die('Unauthorized: Invalid session');
}

// レート制限チェック
$rateLimitCheck = checkRateLimit('export');
if (!$rateLimitCheck['allowed']) {
    http_response_code(429);
    if (isset($rateLimitCheck['retry_after'])) {
        header('Retry-After: ' . $rateLimitCheck['retry_after']);
    }
    die($rateLimitCheck['message']);
}

// データベース接続
try {
    $pdo = getDatabaseConnection();
} catch (Exception $e) {
    http_response_code(500);
    die('Database connection error');
}

// パラメータ取得と検証
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$search_shop = isset($_GET['search_shop']) ? $_GET['search_shop'] : '';
$search_category = isset($_GET['search_category']) ? $_GET['search_category'] : '';
$export_type = isset($_GET['type']) ? $_GET['type'] : 'transactions';

// 日付フォーマット検証
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
    http_response_code(400);
    die('Invalid date format');
}

// 日付範囲検証
if (strtotime($start_date) > strtotime($end_date)) {
    http_response_code(400);
    die('Start date must be before end date');
}

// エクスポートタイプ検証
$valid_types = ['transactions', 'summary', 'shop_summary', 'category_summary'];
if (!in_array($export_type, $valid_types)) {
    http_response_code(400);
    die('Invalid export type');
}

// CSVヘッダー設定（UTF-8 BOM付き、Excel互換）
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="finance_export_' . date('Y-m-d_His') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// UTF-8 BOMを出力（Excel互換性のため）
echo "\xEF\xBB\xBF";

// 出力バッファリング
$output = fopen('php://output', 'w');

if (!$output) {
    http_response_code(500);
    die('Failed to open output stream');
}

if ($export_type === 'transactions') {
    // トランザクションエクスポート（user_idフィルタリング付き）
    $limit = 10000; // 最大10,000件
    $transactions = getRecentTransactions($pdo, $user_id, $start_date, $end_date, $search_shop, $search_category, $limit);

    // CSVヘッダー
    fputcsv($output, ['Date', 'Shop', 'Category', 'Amount']);

    // データ行
    foreach ($transactions as $t) {
        fputcsv($output, [
            $t['re_date'],
            $t['label1'],
            $t['label2'],
            $t['price']
        ]);
    }
} elseif ($export_type === 'summary') {
    // サマリーエクスポート（user_idフィルタリング付き）
    $summary = getSummary($pdo, $user_id, $start_date, $end_date);
    $active_days = getActiveDays($pdo, $user_id, $start_date, $end_date);

    // CSVヘッダー
    fputcsv($output, ['Metric', 'Value']);

    // サマリーデータ
    fputcsv($output, ['Period Start', $start_date]);
    fputcsv($output, ['Period End', $end_date]);
    fputcsv($output, ['Total Expenses', $summary['total']]);
    fputcsv($output, ['Transaction Count', $summary['record_count']]);
    fputcsv($output, ['Unique Shops', $summary['shop_count']]);
    fputcsv($output, ['Active Days', $active_days]);
    fputcsv($output, ['Daily Average', $active_days > 0 ? round($summary['total'] / $active_days, 2) : 0]);

} elseif ($export_type === 'shop_summary') {
    // ショップ別サマリー（user_idフィルタリング付き）
    $shop_data_result = getShopData($pdo, $user_id, $start_date, $end_date);
    $shop_data_above_4pct = $shop_data_result['above_4pct'];

    // CSVヘッダー
    fputcsv($output, ['Shop', 'Total Amount']);

    // データ行
    foreach ($shop_data_above_4pct as $shop) {
        fputcsv($output, [
            $shop['label1'],
            $shop['total']
        ]);
    }

} elseif ($export_type === 'category_summary') {
    // カテゴリ別サマリー（user_idフィルタリング付き）
    $category_data = getCategoryData($pdo, $user_id, $start_date, $end_date);

    // CSVヘッダー
    fputcsv($output, ['Category', 'Total Amount']);

    // データ行
    foreach ($category_data as $cat) {
        fputcsv($output, [
            $cat['label2'],
            $cat['total']
        ]);
    }
}

fclose($output);
exit;
