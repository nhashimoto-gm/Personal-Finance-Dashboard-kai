<?php
// register.php - ユーザー登録ページ
require_once 'config.php';

// セッション開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 既にログイン済みの場合はindex.phpにリダイレクト
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pdo = getDatabaseConnection();
$error = '';
$success = '';

// 登録処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    // CSRF保護
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'Invalid CSRF token. Please try again.';
    } else {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $full_name = $_POST['full_name'] ?? '';

        // パスワード確認
        if ($password !== $password_confirm) {
            $error = 'Passwords do not match';
        } else {
            $result = registerUser($pdo, $username, $email, $password, $full_name);

            if ($result['success']) {
                $success = 'Registration successful! You can now log in.';
                // 自動ログイン
                $loginResult = loginUser($pdo, $username, $password);
                if ($loginResult['success']) {
                    header('Location: index.php');
                    exit;
                }
            } else {
                $error = $result['message'];
            }
        }
    }
}

$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Personal Finance Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 0;
        }
        .register-container {
            max-width: 500px;
            width: 100%;
            padding: 20px;
        }
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
        }
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-header h1 {
            color: #667eea;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .register-header p {
            color: #6c757d;
            font-size: 14px;
        }
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        .form-control {
            border-radius: 8px;
            border: 1px solid #dee2e6;
            padding: 12px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            transition: transform 0.2s;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .divider {
            text-align: center;
            margin: 20px 0;
            position: relative;
        }
        .divider::before {
            content: "";
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: #dee2e6;
        }
        .divider span {
            background: white;
            padding: 0 15px;
            position: relative;
            color: #6c757d;
            font-size: 14px;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        .password-requirements {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h1>アカウント作成</h1>
                <p>Personal Finance Dashboardへようこそ</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill"></i>
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="action" value="register">

                <div class="mb-3">
                    <label for="username" class="form-label">ユーザー名 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="username" name="username"
                           placeholder="3-50 characters, alphanumeric and underscore only"
                           pattern="[a-zA-Z0-9_]{3,50}" required autofocus>
                    <div class="password-requirements">
                        3〜50文字の英数字とアンダースコアのみ
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">メールアドレス <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email"
                           placeholder="email@example.com" required>
                </div>

                <div class="mb-3">
                    <label for="full_name" class="form-label">フルネーム（任意）</label>
                    <input type="text" class="form-control" id="full_name" name="full_name"
                           placeholder="Your full name">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">パスワード <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="Enter your password" minlength="8" required>
                    <div class="password-requirements">
                        最低8文字以上
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password_confirm" class="form-label">パスワード確認 <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm"
                           placeholder="Confirm your password" minlength="8" required>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="terms" required>
                    <label class="form-check-label" for="terms">
                        利用規約とプライバシーポリシーに同意します
                    </label>
                </div>

                <button type="submit" class="btn btn-register">
                    アカウント作成
                </button>
            </form>

            <div class="divider">
                <span>または</span>
            </div>

            <div class="login-link">
                <p>既にアカウントをお持ちですか? <a href="login.php">ログイン</a></p>
            </div>
        </div>

        <div class="text-center mt-3">
            <p class="text-white-50 small">
                &copy; <?= date('Y') ?> Personal Finance Dashboard. All rights reserved.
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // パスワード一致チェック
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;

            if (password !== passwordConfirm) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
        });
    </script>
</body>
</html>
