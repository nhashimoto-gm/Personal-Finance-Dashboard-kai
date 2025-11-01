<?php
/**
 * パスワード変更ページ
 */

require_once __DIR__ . '/config.php';

// 認証チェック
requireLogin();

$errors = [];
$successMessage = '';
$current_user = getCurrentUser();

// POSTリクエスト処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF トークン検証
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // 入力検証
        if (empty($currentPassword)) {
            $errors[] = '現在のパスワードを入力してください';
        }
        if (empty($newPassword)) {
            $errors[] = '新しいパスワードを入力してください';
        } elseif (strlen($newPassword) < 8) {
            $errors[] = '新しいパスワードは8文字以上である必要があります';
        }
        if ($newPassword !== $confirmPassword) {
            $errors[] = '新しいパスワードと確認用パスワードが一致しません';
        }

        if (empty($errors)) {
            try {
                $pdo = getDatabaseConnection();

                // changePassword 関数を使用
                $result = changePassword($pdo, $current_user['id'], $currentPassword, $newPassword);

                if ($result['success']) {
                    $successMessage = $result['message'];
                } else {
                    $errors[] = $result['message'];
                }
            } catch (Exception $e) {
                error_log("Password change error: " . $e->getMessage());
                $errors[] = 'パスワードの変更に失敗しました';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>パスワード変更 - Personal Finance Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">
                <i class="bi bi-wallet2"></i>
                <span onclick="window.location.href='index.php';" style="cursor: pointer;">
                    Personal Finance Dashboard
                </span>
            </span>
            <a href="index.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-arrow-left"></i> 戻る
            </a>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="bi bi-key"></i> パスワード変更</h4>
                    </div>
                    <div class="card-body">
                        <!-- メッセージ表示 -->
                        <?php if (!empty($successMessage)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($successMessage) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        <?php foreach ($errors as $error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endforeach; ?>

                        <form method="POST" action="update-password.php">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                            <div class="mb-3">
                                <label for="username" class="form-label">ユーザー名</label>
                                <input type="text" class="form-control" id="username" value="<?= htmlspecialchars($current_user['username']) ?>" disabled>
                            </div>

                            <div class="mb-3">
                                <label for="current_password" class="form-label">現在のパスワード <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label">新しいパスワード <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="new_password" name="new_password"
                                       minlength="8"
                                       title="パスワードは8文字以上である必要があります"
                                       required>
                                <div class="form-text">パスワードは8文字以上である必要があります</div>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">新しいパスワード（確認） <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                       minlength="8"
                                       required>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> 変更する
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> キャンセル
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // パスワード確認のクライアント側バリデーション
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('新しいパスワードと確認用パスワードが一致しません');
                return false;
            }
        });
    </script>
</body>
</html>
