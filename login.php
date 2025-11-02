<?php
// login.php - ログインページ
require_once 'config.php';
require_once 'translations.php';

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

// ログイン処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    // CSRF保護
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'Invalid CSRF token. Please try again.';
    } else {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $result = loginUser($pdo, $username, $password);

        if ($result['success']) {
            // ログイン成功 - index.phpにリダイレクト
            header('Location: index.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="ja" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Personal Finance Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bs-body-bg);
        }
        .login-container {
            max-width: 450px;
            width: 100%;
            padding: 20px;
        }
        .login-card {
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            padding: 40px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            color: var(--bs-primary);
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .login-header p {
            color: var(--bs-secondary-color);
            font-size: 14px;
        }
        .login-header .bi-wallet2 {
            font-size: 48px;
            color: var(--bs-primary);
            margin-bottom: 15px;
        }
        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px;
        }
        .btn-login {
            background: var(--bs-primary);
            border: none;
            color: white;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            transition: all 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            opacity: 0.9;
            box-shadow: 0 5px 20px rgba(13, 110, 253, 0.4);
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
            background: var(--bs-border-color);
        }
        .divider span {
            background: var(--bs-body-bg);
            padding: 0 15px;
            position: relative;
            color: var(--bs-secondary-color);
            font-size: 14px;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
        }
        .register-link a {
            color: var(--bs-primary);
            text-decoration: none;
            font-weight: 600;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="d-flex gap-2" style="position: absolute; top: 20px; right: 20px;">
        <button class="btn btn-outline-light" id="langToggle">
            <i class="bi bi-translate"></i> <span id="langLabel">JP</span>
        </button>
        <button class="btn btn-outline-light" id="themeToggle">
            <i class="bi bi-moon-fill" id="themeIcon"></i>
        </button>
    </div>
    <div class="login-container">
        <div class="login-card card">
            <div class="login-header">
                <i class="bi bi-wallet2"></i>
                <h1 data-i18n="loginTitle">Personal Finance Dashboard</h1>
                <p data-i18n="loginSubtitle">ログインしてあなたの財務データにアクセス</p>
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

            <form method="POST" action="login.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="action" value="login">

                <div class="mb-3">
                    <label for="username" class="form-label" data-i18n="loginUsername">ユーザー名またはメールアドレス</label>
                    <input type="text" class="form-control" id="username" name="username"
                           data-i18n-placeholder="loginUsernamePlaceholder" placeholder="username or email@example.com" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label" data-i18n="loginPassword">パスワード</label>
                    <input type="password" class="form-control" id="password" name="password"
                           data-i18n-placeholder="loginPasswordPlaceholder" placeholder="Enter your password" required>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember">
                    <label class="form-check-label" for="remember" data-i18n="loginRememberMe">
                        ログイン状態を保持
                    </label>
                </div>

                <button type="submit" class="btn btn-login" data-i18n="loginButton">
                    ログイン
                </button>
            </form>

        </div>

        <div class="text-center mt-3">
            <p class="text-muted small">
                &copy; <?= date('Y') ?> Personal Finance Dashboard. All rights reserved.
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Translation data
        const translations = <?= json_encode(getTranslations()) ?>;
        let currentLang = localStorage.getItem('language') || 'en';

        // Language switching functionality
        function switchLanguage(lang) {
            currentLang = lang;
            localStorage.setItem('language', lang);

            // Update text content
            document.querySelectorAll('[data-i18n]').forEach(el => {
                const key = el.getAttribute('data-i18n');
                if (translations[lang] && translations[lang][key]) {
                    el.textContent = translations[lang][key];
                }
            });

            // Update placeholders
            document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
                const key = el.getAttribute('data-i18n-placeholder');
                if (translations[lang] && translations[lang][key]) {
                    el.placeholder = translations[lang][key];
                }
            });

            // Update language button label
            document.getElementById('langLabel').textContent = lang === 'en' ? 'JP' : 'EN';
        }

        // Theme toggle functionality
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const htmlElement = document.documentElement;

        // Load saved theme or default to dark
        const savedTheme = localStorage.getItem('theme') || 'dark';
        htmlElement.setAttribute('data-bs-theme', savedTheme);
        updateThemeIcon(savedTheme);

        themeToggle.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            htmlElement.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });

        function updateThemeIcon(theme) {
            if (theme === 'dark') {
                themeIcon.classList.remove('bi-sun-fill');
                themeIcon.classList.add('bi-moon-fill');
            } else {
                themeIcon.classList.remove('bi-moon-fill');
                themeIcon.classList.add('bi-sun-fill');
            }
        }

        // Language toggle event listener
        document.getElementById('langToggle').addEventListener('click', () => {
            switchLanguage(currentLang === 'en' ? 'ja' : 'en');
        });

        // Initialize language on page load
        window.addEventListener('DOMContentLoaded', () => {
            switchLanguage(currentLang);
        });
    </script>
</body>
</html>
