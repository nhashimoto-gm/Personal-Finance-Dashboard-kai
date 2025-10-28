<?php
/**
 * パスワード更新ユーティリティ
 *
 * 使用方法:
 *   php update-password.php <username> <new_password>
 *
 * 例:
 *   php update-password.php user1 mynewpassword123
 *   php update-password.php hiromi newpassword456
 */

require_once __DIR__ . '/config.php';

// コマンドライン引数をチェック
if ($argc < 3) {
    echo "使用方法: php update-password.php <username> <new_password>\n";
    echo "例: php update-password.php user1 mynewpassword123\n";
    exit(1);
}

$username = $argv[1];
$newPassword = $argv[2];

// パスワードの強度チェック
if (strlen($newPassword) < 8) {
    echo "エラー: パスワードは8文字以上である必要があります\n";
    exit(1);
}

try {
    $pdo = getDatabaseConnection();

    // ユーザーが存在するか確認
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "エラー: ユーザー '{$username}' が見つかりません\n";
        exit(1);
    }

    // パスワードをハッシュ化
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    // パスワードを更新
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE username = ?");
    $stmt->execute([$passwordHash, $username]);

    echo "成功: ユーザー '{$username}' のパスワードを更新しました\n";
    echo "新しいパスワードでログインしてください\n";

} catch (PDOException $e) {
    echo "エラー: " . $e->getMessage() . "\n";
    exit(1);
}
