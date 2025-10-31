<?php
// index.php - メインファイル（Multi-Account対応）

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

// 認証チェック - ログインしていない場合はlogin.phpにリダイレクト
requireLogin('login.php');

// セッションタイムアウトチェック（30分）
if (checkSessionTimeout()) {
    header('Location: login.php');
    exit;
}

// 現在のユーザーIDを取得
$user_id = getCurrentUserId();
$current_user = getCurrentUser();

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
                    $user_id,
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
                $result = addShop($pdo, $user_id, $_POST['name']);
                if ($result['success']) {
                    $_SESSION['successMessage'] = $result['message'];
                    header('Location: ' . $_SERVER['REQUEST_URI']);
                    exit;
                } else {
                    $errors[] = $result['message'];
                }
            }
            elseif ($action === 'add_category' && isset($_POST['name'])) {
                $result = addCategory($pdo, $user_id, $_POST['name']);
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
                    $user_id,
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
                $result = deleteTransaction($pdo, $user_id, (int)$_POST['id']);
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
                    $user_id,
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
                $result = deleteBudget($pdo, $user_id, (int)$_POST['id']);
                if ($result['success']) {
                    $_SESSION['successMessage'] = $result['message'];
                    header('Location: ' . $_SERVER['REQUEST_URI']);
                    exit;
                } else {
                    $errors[] = $result['message'];
                }
            }
            elseif ($action === 'add_recurring_expense' && isset($_POST['name'], $_POST['label1'], $_POST['label2'], $_POST['price'], $_POST['day_of_month'], $_POST['start_date'])) {
                $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
                $result = addRecurringExpense(
                    $pdo,
                    $user_id,
                    $_POST['name'],
                    $_POST['label1'],
                    $_POST['label2'],
                    (int)$_POST['price'],
                    (int)$_POST['day_of_month'],
                    $_POST['start_date'],
                    $end_date
                );
                if ($result['success']) {
                    $_SESSION['successMessage'] = $result['message'];
                    header('Location: ' . $_SERVER['REQUEST_URI']);
                    exit;
                } else {
                    $errors[] = $result['message'];
                }
            }
            elseif ($action === 'update_recurring_expense' && isset($_POST['id'], $_POST['name'], $_POST['label1'], $_POST['label2'], $_POST['price'], $_POST['day_of_month'], $_POST['start_date'])) {
                $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
                $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
                $result = updateRecurringExpense(
                    $pdo,
                    $user_id,
                    (int)$_POST['id'],
                    $_POST['name'],
                    $_POST['label1'],
                    $_POST['label2'],
                    (int)$_POST['price'],
                    (int)$_POST['day_of_month'],
                    $_POST['start_date'],
                    $end_date,
                    $is_active
                );
                if ($result['success']) {
                    $_SESSION['successMessage'] = $result['message'];
                    header('Location: ' . $_SERVER['REQUEST_URI']);
                    exit;
                } else {
                    $errors[] = $result['message'];
                }
            }
            elseif ($action === 'toggle_recurring_expense' && isset($_POST['id'])) {
                $result = toggleRecurringExpense($pdo, $user_id, (int)$_POST['id']);
                if ($result['success']) {
                    $_SESSION['successMessage'] = $result['message'];
                    header('Location: ' . $_SERVER['REQUEST_URI']);
                    exit;
                } else {
                    $errors[] = $result['message'];
                }
            }
            elseif ($action === 'delete_recurring_expense' && isset($_POST['id'])) {
                $result = deleteRecurringExpense($pdo, $user_id, (int)$_POST['id']);
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

// データ取得（ユーザー固有）
$summary = getSummary($pdo, $user_id, $start_date, $end_date);
$total = $summary['total'];
$record_count = $summary['record_count'];
$shop_count = $summary['shop_count'];

$active_days = getActiveDays($pdo, $user_id, $start_date, $end_date);

$shop_data_result = getShopData($pdo, $user_id, $start_date, $end_date);
$shop_data_above_4pct = $shop_data_result['above_4pct'];
$shop_data_below_4pct_total = $shop_data_result['below_4pct_total'];
$others_shop = $shop_data_result['others_shop'];

$category_data = getCategoryData($pdo, $user_id, $start_date, $end_date);
$daily_data = getDailyData($pdo, $user_id, $start_date, $end_date);
$period_data = getPeriodData($pdo, $user_id, $period_range);
$recent_transactions = getRecentTransactions($pdo, $user_id, $start_date, $end_date, $search_shop, $search_category, $recent_limit);
$search_results = getSearchResults($pdo, $user_id, $search_shop, $search_category, $search_limit);

$shops = getShops($pdo, $user_id);
$categories = getCategories($pdo, $user_id);
$recurring_expenses = getRecurringExpenses($pdo, $user_id, true);

// 予算データ取得（当月・ユーザー固有）
$current_year = (int)date('Y');
$current_month = (int)date('m');
$budget_progress = getBudgetProgress($pdo, $user_id, $current_year, $current_month);
$all_budgets = getBudgets($pdo, $user_id);

// ビュー読み込み
require_once __DIR__ . '/view.php';
