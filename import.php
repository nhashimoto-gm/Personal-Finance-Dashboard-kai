<?php
// import.php - CSVインポート処理（セキュア版）

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// 認証チェック
if (!isLoggedIn()) {
    http_response_code(401);
    $_SESSION['import_errors'] = ['Authentication required. Please log in.'];
    header('Location: login.php');
    exit;
}

// 現在のユーザーIDを取得
$user_id = getCurrentUserId();
if (!$user_id) {
    http_response_code(401);
    $_SESSION['import_errors'] = ['Invalid session. Please log in again.'];
    header('Location: login.php');
    exit;
}

// データベース接続
try {
    $pdo = getDatabaseConnection();
} catch (Exception $e) {
    $_SESSION['import_errors'] = ['Database connection error. Please try again later.'];
    header('Location: index.php');
    exit;
}

$errors = [];
$success_count = 0;
$error_count = 0;
$preview_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    // CSRFトークン検証
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token. Please refresh the page and try again.';
    } else {
        // レート制限チェック
        $rateLimitCheck = checkRateLimit('import');
        if (!$rateLimitCheck['allowed']) {
            http_response_code(429);
            $errors[] = $rateLimitCheck['message'];
        } else {
            $file = $_FILES['csv_file'];

            // ファイルエラーチェック
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'File upload error. Please try again.';
            } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB制限
                $errors[] = 'File size too large. Maximum 5MB allowed.';
            } else {
                // ファイルタイプ検証（追加のセキュリティ）
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if ($file_extension !== 'csv') {
                    $errors[] = 'Invalid file type. Only CSV files are allowed.';
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
                        $max_lines = 10000; // 最大10,000行まで

                        while (($data = fgetcsv($handle)) !== false && $line_number < $max_lines) {
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

                            // ショップ名とカテゴリ名の長さ検証
                            if (strlen($shop) > 255 || strlen($category) > 255) {
                                $error_count++;
                                $errors[] = "Line {$line_number}: Shop or category name too long";
                                continue;
                            }

                            // トランザクション追加（正しい関数シグネチャで呼び出し）
                            // addTransaction($pdo, $user_id, $re_date, $price, $label1, $label2)
                            $result = addTransaction($pdo, $user_id, $date, (int)$amount, $shop, $category);

                            if ($result['success']) {
                                $success_count++;
                            } else {
                                $error_count++;
                                $errors[] = "Line {$line_number}: " . $result['message'];
                            }
                        }

                        fclose($handle);

                        if ($line_number >= $max_lines) {
                            $errors[] = "Import stopped at {$max_lines} lines. Please split your file into smaller chunks.";
                        }

                        if ($success_count > 0) {
                            $_SESSION['successMessage'] = "Successfully imported {$success_count} transactions." . ($error_count > 0 ? " {$error_count} errors occurred." : "");
                            header('Location: index.php');
                            exit;
                        }
                    }
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

// POSTリクエスト以外は index.php にリダイレクト
header('Location: index.php');
exit;
