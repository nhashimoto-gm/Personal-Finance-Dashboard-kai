<?php
/**
 * ユーザー名更新ユーティリティ
 *
 * 使用方法:
 *   php update-username.php <current_username> <new_username>
 *
 * 例:
 *   php update-username.php user1 tanaka
 *   php update-username.php hiromi hiromi_tanaka
 */

require_once __DIR__ . '/config.php';

// コマンドライン引数をチェック
if ($argc < 3) {
    echo "使用方法: php update-username.php <current_username> <new_username>\n";
    echo "例: php update-username.php user1 tanaka\n";
    exit(1);
}

$currentUsername = $argv[1];
$newUsername = $argv[2];

// 新しいユーザー名の検証（3-50文字、英数字とアンダースコアのみ）
if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $newUsername)) {
    echo "エラー: ユーザー名は3〜50文字の英数字とアンダースコアのみ使用できます\n";
    exit(1);
}

try {
    $pdo = getDatabaseConnection();

    // 現在のユーザーが存在するか確認
    $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE username = ?");
    $stmt->execute([$currentUsername]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "エラー: ユーザー '{$currentUsername}' が見つかりません\n";
        exit(1);
    }

    // 新しいユーザー名が既に使用されていないか確認
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$newUsername]);
    if ($stmt->fetch()) {
        echo "エラー: ユーザー名 '{$newUsername}' は既に使用されています\n";
        exit(1);
    }

    // ユーザー名を更新
    $stmt = $pdo->prepare("UPDATE users SET username = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$newUsername, $user['id']]);

    echo "成功: ユーザー名を '{$currentUsername}' から '{$newUsername}' に変更しました\n";
    echo "メールアドレス: {$user['email']}\n";
    echo "\n";
    echo "⚠️  注意: 次回ログイン時は新しいユーザー名を使用してください\n";
    echo "   ユーザー名: {$newUsername}\n";
    echo "   パスワード: (変更なし)\n";

} catch (PDOException $e) {
    echo "エラー: " . $e->getMessage() . "\n";
    exit(1);
}
