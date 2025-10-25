<?php
// export.php - CSV/Excelエクスポート機能

// セキュアなセッション設定
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/queries.php';

// データベース接続
$pdo = getDatabaseConnection();

// パラメータ取得
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$search_shop = isset($_GET['search_shop']) ? $_GET['search_shop'] : '';
$search_category = isset($_GET['search_category']) ? $_GET['search_category'] : '';
$export_type = isset($_GET['type']) ? $_GET['type'] : 'transactions';

// CSVヘッダー設定（UTF-8 BOM付き、Excel互換）
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="finance_export_' . date('Y-m-d_His') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// UTF-8 BOMを出力（Excel互換性のため）
echo "\xEF\xBB\xBF";

// 出力バッファリング
$output = fopen('php://output', 'w');

if ($export_type === 'transactions') {
    // トランザクションエクスポート
    $limit = 10000; // 最大10,000件
    $transactions = getRecentTransactions($pdo, $start_date, $end_date, $search_shop, $search_category, $limit);

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
    // サマリーエクスポート
    $summary = getSummary($pdo, $start_date, $end_date);
    $active_days = getActiveDays($pdo, $start_date, $end_date);

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
    // ショップ別サマリー
    $shop_data_result = getShopData($pdo, $start_date, $end_date);
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
    // カテゴリ別サマリー
    $category_data = getCategoryData($pdo, $start_date, $end_date);

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
