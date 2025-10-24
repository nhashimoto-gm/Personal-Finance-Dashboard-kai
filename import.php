<?php
// import.php - CSVインポート処理

// セキュアなセッション設定
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// データベース接続
$pdo = getDatabaseConnection();

$errors = [];
$success_count = 0;
$error_count = 0;
$preview_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    // CSRFトークン検証
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $file = $_FILES['csv_file'];

        // ファイルエラーチェック
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error. Please try again.';
        } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB制限
            $errors[] = 'File size too large. Maximum 5MB allowed.';
        } else {
            // CSVファイルを読み込み
            $handle = fopen($file['tmp_name'], 'r');

            if ($handle === false) {
                $errors[] = 'Could not open CSV file.';
            } else {
                // BOMを削除
                $bom = fread($handle, 3);
                if ($bom !== "\xEF\xBB\xBF") {
                    rewind($handle);
                }

                // ヘッダー行をスキップ
                $header = fgetcsv($handle);

                $line_number = 1;
                while (($data = fgetcsv($handle)) !== false) {
                    $line_number++;

                    // 空行をスキップ
                    if (empty(array_filter($data))) {
                        continue;
                    }

                    // データが4列あることを確認
                    if (count($data) < 4) {
                        $error_count++;
                        $errors[] = "Line {$line_number}: Insufficient columns";
                        continue;
                    }

                    list($date, $shop, $category, $amount) = $data;

                    // バリデーション
                    $date = trim($date);
                    $shop = trim($shop);
                    $category = trim($category);
                    $amount = trim($amount);

                    // 日付検証
                    $date_obj = DateTime::createFromFormat('Y-m-d', $date);
                    if (!$date_obj || $date_obj->format('Y-m-d') !== $date) {
                        $error_count++;
                        $errors[] = "Line {$line_number}: Invalid date format (expected YYYY-MM-DD)";
                        continue;
                    }

                    // 金額検証
                    if (!is_numeric($amount) || (int)$amount <= 0) {
                        $error_count++;
                        $errors[] = "Line {$line_number}: Invalid amount";
                        continue;
                    }

                    // トランザクション追加
                    $result = addTransaction($pdo, $date, (int)$amount, $shop, $category);

                    if ($result['success']) {
                        $success_count++;
                    } else {
                        $error_count++;
                        $errors[] = "Line {$line_number}: " . $result['message'];
                    }
                }

                fclose($handle);

                if ($success_count > 0) {
                    $_SESSION['successMessage'] = "Successfully imported {$success_count} transactions." . ($error_count > 0 ? " {$error_count} errors occurred." : "");
                    header('Location: index.php');
                    exit;
                }
            }
        }
    }
}

// エラーがある場合は戻る
if (!empty($errors)) {
    $_SESSION['import_errors'] = $errors;
    header('Location: index.php');
    exit;
}
