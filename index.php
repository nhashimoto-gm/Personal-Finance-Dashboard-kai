<?php
// index.php - メインファイル

// セキュアなセッション設定
session_start([
    'cookie_httponly' => true,  // JavaScriptからのアクセスを防止
    'cookie_samesite' => 'Lax', // CSRF保護
    'use_strict_mode' => true,  // セッションIDの厳格な検証
]);
header('Content-Type: text/html; charset=utf-8');

// 必要なファイルを読み込み
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/queries.php';
require_once __DIR__ . '/translations.php';

// データベース接続
$pdo = getDatabaseConnection();

$errors = [];
$successMessage = "";

// POST処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // CSRFトークン検証
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $action = $_POST['action'];

        // レート制限チェック
        $rateLimitCheck = checkRateLimit($action);
        if (!$rateLimitCheck['allowed']) {
            $errors[] = $rateLimitCheck['message'];
        } else {
            // レート制限OK - リクエストを記録
            recordRequest($action);

            // 各アクションの処理
            if ($action === 'add_transaction' && isset($_POST['re_date'], $_POST['price'], $_POST['label1'], $_POST['label2'])) {
                $result = addTransaction(
                    $pdo,
                    $_POST['re_date'],
                    (int)$_POST['price'],
                    trim($_POST['label1']),
                    trim($_POST['label2'])
                );

                if ($result['success']) {
                    $_SESSION['successMessage'] = $result['message'];
                    $_SESSION['form_tab'] = 'entry';
                    $_SESSION['form_re_date'] = $result['data']['re_date'];
                    $_SESSION['form_label1'] = $result['data']['label1'];
                    $_SESSION['form_label2'] = $result['data']['label2'];
                    header('Location: ' . $_SERVER['REQUEST_URI']);
                    exit;
                } else {
                    $errors[] = $result['message'];
                }
            }
            elseif ($action === 'add_shop' && isset($_POST['name'])) {
                $result = addShop($pdo, $_POST['name']);
                if ($result['success']) {
                    $_SESSION['successMessage'] = $result['message'];
                    header('Location: ' . $_SERVER['REQUEST_URI']);
                    exit;
                } else {
                    $errors[] = $result['message'];
                }
            }
            elseif ($action === 'add_category' && isset($_POST['name'])) {
                $result = addCategory($pdo, $_POST['name']);
                if ($result['success']) {
                    $_SESSION['successMessage'] = $result['message'];
                    header('Location: ' . $_SERVER['REQUEST_URI']);
                    exit;
                } else {
                    $errors[] = $result['message'];
                }
            }
            elseif ($action === 'update_transaction' && isset($_POST['id'], $_POST['re_date'], $_POST['price'], $_POST['label1'], $_POST['label2'])) {
                $result = updateTransaction(
                    $pdo,
                    (int)$_POST['id'],
                    $_POST['re_date'],
                    (int)$_POST['price'],
                    trim($_POST['label1']),
                    trim($_POST['label2'])
                );

                if ($result['success']) {
                    $_SESSION['successMessage'] = $result['message'];
                    header('Location: ' . $_SERVER['REQUEST_URI']);
                    exit;
                } else {
                    $errors[] = $result['message'];
                }
            }
            elseif ($action === 'delete_transaction' && isset($_POST['id'])) {
                $result = deleteTransaction($pdo, (int)$_POST['id']);
                if ($result['success']) {
                    $_SESSION['successMessage'] = $result['message'];
                    header('Location: ' . $_SERVER['REQUEST_URI']);
                    exit;
                } else {
                    $errors[] = $result['message'];
                }
            }
            elseif ($action === 'set_budget' && isset($_POST['budget_type'], $_POST['target_year'], $_POST['target_month'], $_POST['amount'])) {
                $target_id = isset($_POST['target_id']) && $_POST['target_id'] !== '' ? (int)$_POST['target_id'] : null;
                $result = setBudget(
                    $pdo,
                    $_POST['budget_type'],
                    $target_id,
                    (int)$_POST['target_year'],
                    (int)$_POST['target_month'],
                    (int)$_POST['amount']
                );

                if ($result['success']) {
                    $_SESSION['successMessage'] = $result['message'];
                    header('Location: ' . $_SERVER['REQUEST_URI']);
                    exit;
                } else {
                    $errors[] = $result['message'];
                }
            }
            elseif ($action === 'delete_budget' && isset($_POST['id'])) {
                $result = deleteBudget($pdo, (int)$_POST['id']);
                if ($result['success']) {
                    $_SESSION['successMessage'] = $result['message'];
                    header('Location: ' . $_SERVER['REQUEST_URI']);
                    exit;
                } else {
                    $errors[] = $result['message'];
                }
            }
        }
    }
}

// セッションからメッセージ取得
if (isset($_SESSION['successMessage'])) {
    $successMessage = $_SESSION['successMessage'];
    unset($_SESSION['successMessage']);
}

// パラメータ取得
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$period_range = isset($_GET['period_range']) ? $_GET['period_range'] : '12';
$search_shop = isset($_GET['search_shop']) ? $_GET['search_shop'] : '';
$search_category = isset($_GET['search_category']) ? $_GET['search_category'] : '';
$search_limit = isset($_GET['search_limit']) ? $_GET['search_limit'] : '100';
$recent_limit = isset($_GET['recent_limit']) ? $_GET['recent_limit'] : '20';

// データ取得
$summary = getSummary($pdo, $start_date, $end_date);
$total = $summary['total'];
$record_count = $summary['record_count'];
$shop_count = $summary['shop_count'];

$active_days = getActiveDays($pdo, $start_date, $end_date);

$shop_data_result = getShopData($pdo, $start_date, $end_date);
$shop_data_above_4pct = $shop_data_result['above_4pct'];
$shop_data_below_4pct_total = $shop_data_result['below_4pct_total'];
$others_shop = $shop_data_result['others_shop'];

$category_data = getCategoryData($pdo, $start_date, $end_date);
$daily_data = getDailyData($pdo, $start_date, $end_date);
$period_data = getPeriodData($pdo, $period_range);
$recent_transactions = getRecentTransactions($pdo, $start_date, $end_date, $search_shop, $search_category, $recent_limit);
$search_results = getSearchResults($pdo, $search_shop, $search_category, $search_limit);

$shops = getShops($pdo);
$categories = getCategories($pdo);

// 予算データ取得（当月）
$current_year = (int)date('Y');
$current_month = (int)date('m');
$budget_progress = getBudgetProgress($pdo, $current_year, $current_month);
$all_budgets = getBudgets($pdo);

// ビュー読み込み
require_once __DIR__ . '/view.php';
