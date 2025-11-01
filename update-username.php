<?php
/**
 * ユーザー名変更ページ
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/translations.php';

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
        $newUsername = trim($_POST['new_username'] ?? '');

        // 新しいユーザー名の検証
        if (empty($newUsername)) {
            $errors[] = '新しいユーザー名を入力してください';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $newUsername)) {
            $errors[] = 'ユーザー名は3〜50文字の英数字とアンダースコアのみ使用できます';
        } else {
            try {
                $pdo = getDatabaseConnection();

                // 新しいユーザー名が既に使用されていないか確認
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $stmt->execute([$newUsername, $current_user['id']]);
                if ($stmt->fetch()) {
                    $errors[] = "ユーザー名 '{$newUsername}' は既に使用されています";
                } else {
                    // ユーザー名を更新
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$newUsername, $current_user['id']]);

                    // セッション情報を更新
                    $_SESSION['username'] = $newUsername;

                    $successMessage = "ユーザー名を '{$newUsername}' に変更しました";
                    $current_user['username'] = $newUsername;
                }
            } catch (PDOException $e) {
                error_log("Username update error: " . $e->getMessage());
                $errors[] = 'ユーザー名の変更に失敗しました';
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
    <meta name="csrf-token" content="<?= generateCsrfToken() ?>">
    <title data-i18n="changeUsername">ユーザー名変更</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">
                <i class="bi bi-wallet2"></i>
                <span data-i18n="title" onclick="window.location.href='index.php';" style="cursor: pointer;">
                    Personal Finance Dashboard
                </span>
            </span>
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-light btn-sm me-2" id="langToggle">
                    <i class="bi bi-translate"></i> <span id="langLabel">JP</span>
                </button>
                <button class="btn btn-outline-light btn-sm" id="themeToggle">
                    <i class="bi bi-moon-fill" id="themeIcon"></i>
                </button>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="bi bi-pencil-square"></i>
                            <span data-i18n="changeUsername">ユーザー名変更</span>
                        </h4>
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

                        <form method="POST" action="update-username.php">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                            <div class="mb-3">
                                <label for="current_username" class="form-label" data-i18n="currentUsername">現在のユーザー名</label>
                                <input type="text" class="form-control" id="current_username" value="<?= htmlspecialchars($current_user['username']) ?>" disabled>
                            </div>

                            <div class="mb-3">
                                <label for="new_username" class="form-label">
                                    <span data-i18n="newUsername">新しいユーザー名</span> <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="new_username" name="new_username"
                                       pattern="[a-zA-Z0-9_]{3,50}"
                                       title="3〜50文字の英数字とアンダースコアのみ使用できます"
                                       required>
                                <div class="form-text" data-i18n="usernameRules">3〜50文字の英数字とアンダースコアのみ使用できます</div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> <span data-i18n="changeButton">変更する</span>
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> <span data-i18n="cancel">キャンセル</span>
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
        // 翻訳データ
        const translations = <?= json_encode(getTranslations()) ?>;
        let currentLang = 'en';

        // 言語切り替え
        function switchLanguage(lang) {
            currentLang = lang;
            document.querySelectorAll('[data-i18n]').forEach(el => {
                const key = el.getAttribute('data-i18n');
                if (translations[lang] && translations[lang][key]) {
                    el.textContent = translations[lang][key];
                }
            });
            document.getElementById('langLabel').textContent = lang === 'en' ? 'JP' : 'EN';

            // Update page title
            document.title = (translations[lang] && translations[lang]['changeUsername']) || 'Change Username';
        }

        // ページ読み込み時
        window.addEventListener('load', () => {
            switchLanguage('en');
        });

        // 言語切り替えボタン
        document.getElementById('langToggle').addEventListener('click', () => {
            switchLanguage(currentLang === 'en' ? 'ja' : 'en');
        });

        // テーマ切り替え
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const html = document.documentElement;

        themeToggle.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-bs-theme', newTheme);
            themeIcon.className = newTheme === 'dark' ? 'bi bi-moon-fill' : 'bi bi-sun-fill';
            localStorage.setItem('theme', newTheme);
        });

        // ページ読み込み時にテーマを復元
        const savedTheme = localStorage.getItem('theme') || 'dark';
        html.setAttribute('data-bs-theme', savedTheme);
        themeIcon.className = savedTheme === 'dark' ? 'bi bi-moon-fill' : 'bi bi-sun-fill';
    </script>
</body>
</html>
