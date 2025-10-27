# マルチアカウント対応移行ガイド

Personal Finance Dashboardをマルチアカウント対応にするための包括的な移行ガイドです。

## 概要

このアップデートにより、Personal Finance Dashboardが複数ユーザーに対応し、各ユーザーが独自のデータを安全に管理できるようになります。

### 主な変更点

1. **ユーザー認証システム**の追加
   - ユーザー登録とログイン機能
   - セッション管理とタイムアウト（30分）
   - パスワードハッシュ化（bcrypt）

2. **データベーススキーマの変更**
   - `users`テーブルの追加
   - 全テーブルに`user_id`カラムを追加
   - ユーザー固有の制約とインデックス

3. **データアイソレーション**
   - 全てのクエリにuser_idフィルタリングを追加
   - ユーザー間のデータ完全分離
   - 行レベルのセキュリティ

## 移行手順

### ステップ1: データベースのバックアップ

**重要:** 移行を開始する前に、必ずデータベースの完全バックアップを作成してください。

```bash
# MySQLのバックアップ例
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql
```

### ステップ2: マイグレーションSQLの実行

提供された`migration-multi-account.sql`を実行します：

```bash
mysql -u username -p database_name < migration-multi-account.sql
```

#### マイグレーションが実行する内容

1. **usersテーブルの作成**
   ```sql
   CREATE TABLE users (
     id INT AUTO_INCREMENT PRIMARY KEY,
     username VARCHAR(255) NOT NULL UNIQUE,
     email VARCHAR(255) NOT NULL UNIQUE,
     password_hash VARCHAR(255) NOT NULL,
     full_name VARCHAR(255),
     is_active TINYINT(1) DEFAULT 1,
     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
   );
   ```

2. **既存テーブルへのuser_id追加**
   - `source` (トランザクション)
   - `cat_1_labels` (ショップ)
   - `cat_2_labels` (カテゴリ)
   - `budgets` (予算)
   - `monthly_summary_cache` (キャッシュ)

3. **デフォルトユーザーの作成**
   - ユーザー名: `admin`
   - メールアドレス: `admin@example.com`
   - パスワード: `admin123` (**必ず変更してください！**)

4. **既存データの移行**
   - 全ての既存データをデフォルトユーザーに紐付け

5. **外部キー制約の追加**
   - ユーザー削除時のカスケード削除設定

6. **ビューの再作成**
   - user_idフィルタリングを含む全ビューの更新

### ステップ3: アプリケーションファイルの更新

以下のファイルが更新されています：

#### 新規ファイル
- `login.php` - ログインページ
- `register.php` - ユーザー登録ページ
- `logout.php` - ログアウト処理
- `migration-multi-account.sql` - データベースマイグレーション

#### 更新されたファイル
- `config.php` - 認証関数の追加
- `functions.php` - 全関数にuser_idパラメータ追加
- `queries.php` - 全クエリにuser_idフィルタリング追加
- `index.php` - 認証チェックとuser_id受け渡し

### ステップ4: 動作確認

1. **ログインテスト**
   ```
   URL: http://your-domain/login.php
   ユーザー名: admin
   パスワード: admin123
   ```

2. **データアクセスの確認**
   - ダッシュボードで既存データが表示されることを確認
   - トランザクションの追加・編集・削除が正常に動作することを確認

3. **新規ユーザー登録テスト**
   ```
   URL: http://your-domain/register.php
   ```
   - 新しいアカウントを作成
   - 別のブラウザまたはシークレットモードでログイン
   - データが分離されていることを確認

### ステップ5: セキュリティ設定

#### 1. デフォルトパスワードの変更

管理者アカウントでログイン後、必ずパスワードを変更してください：

```php
// データベースから直接変更する場合
$newPassword = 'your_secure_password';
$hash = password_hash($newPassword, PASSWORD_DEFAULT);
UPDATE users SET password_hash = '$hash' WHERE username = 'admin';
```

#### 2. HTTPS の設定

本番環境では必ずHTTPSを使用してください：

```php
// config.phpに追加（任意）
if ($_SERVER['HTTPS'] !== 'on' && getenv('APP_ENV') === 'production') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}
```

#### 3. セッションセキュリティの確認

`php.ini`または`.htaccess`で以下を確認：

```ini
session.cookie_secure = 1  # HTTPSのみ
session.cookie_httponly = 1
session.use_strict_mode = 1
```

## 新機能

### ユーザー認証API

#### ユーザー登録
```php
$result = registerUser($pdo, $username, $email, $password, $fullName);
```

#### ログイン
```php
$result = loginUser($pdo, $username, $password);
```

#### ログアウト
```php
logoutUser();
```

#### 認証確認
```php
if (isLoggedIn()) {
    $userId = getCurrentUserId();
    $user = getCurrentUser();
}
```

#### ページ保護
```php
requireLogin('login.php');  // 未ログイン時にリダイレクト
```

### セッションタイムアウト

デフォルトで30分間の非アクティブ後、自動ログアウトします：

```php
// タイムアウト時間を変更（秒単位）
checkSessionTimeout(3600);  // 1時間
```

## データ構造の変更

### 更新前
```sql
SELECT * FROM source WHERE re_date BETWEEN '2024-01-01' AND '2024-12-31';
```

### 更新後
```sql
SELECT * FROM source
WHERE user_id = ? AND re_date BETWEEN '2024-01-01' AND '2024-12-31';
```

全てのクエリに`user_id`フィルタが追加されています。

## トラブルシューティング

### 問題: ログイン後にデータが表示されない

**原因**: 既存データがデフォルトユーザーに紐付けられていない

**解決策**:
```sql
-- 既存データの確認
SELECT DISTINCT user_id FROM source;

-- user_idがNULLの場合、デフォルトユーザーに紐付け
SET @admin_id = (SELECT id FROM users WHERE username = 'admin');
UPDATE source SET user_id = @admin_id WHERE user_id IS NULL;
```

### 問題: ショップ/カテゴリの重複エラー

**原因**: ユーザー固有の制約が正しく設定されていない

**解決策**:
```sql
-- 制約の確認
SHOW CREATE TABLE cat_1_labels;

-- 必要に応じて制約を再作成
ALTER TABLE cat_1_labels
  DROP INDEX label,
  ADD UNIQUE KEY unique_label_per_user (user_id, label);
```

### 問題: セッションタイムアウトが早すぎる

**原因**: サーバーのPHP設定

**解決策**:
```php
// config.phpに追加
ini_set('session.gc_maxlifetime', 3600);  // 1時間
ini_set('session.cookie_lifetime', 3600);
```

## パフォーマンス最適化

### インデックスの追加

マイグレーションスクリプトには既に含まれていますが、追加で最適化する場合：

```sql
-- 複合インデックス
ALTER TABLE source ADD INDEX idx_user_date (user_id, re_date);
ALTER TABLE source ADD INDEX idx_user_cat1 (user_id, cat_1);
ALTER TABLE source ADD INDEX idx_user_cat2 (user_id, cat_2);

-- テーブル解析
ANALYZE TABLE users, source, cat_1_labels, cat_2_labels, budgets;
```

### キャッシュテーブルの更新

```sql
-- キャッシュのクリアと再生成
TRUNCATE TABLE monthly_summary_cache;

-- 各ユーザーごとにキャッシュを再生成
INSERT INTO monthly_summary_cache (user_id, year, month, ...)
SELECT user_id, YEAR(re_date), MONTH(re_date), ...
FROM source
GROUP BY user_id, YEAR(re_date), MONTH(re_date);
```

## ロールバック手順

万が一、元の状態に戻す必要がある場合：

### 1. バックアップからの復元
```bash
mysql -u username -p database_name < backup_YYYYMMDD_HHMMSS.sql
```

### 2. 旧バージョンのコードに戻す
```bash
git revert HEAD
# または
git checkout <previous-commit-hash>
```

## セキュリティベストプラクティス

1. **パスワードポリシー**
   - 最低8文字
   - 英数字と記号の組み合わせを推奨

2. **定期的なパスワード変更**
   - 管理者は3ヶ月ごとにパスワード変更を推奨

3. **アカウントロック**
   - 5回連続ログイン失敗でアカウントロック（今後の機能追加候補）

4. **監査ログ**
   - `audit_log`テーブルで重要操作を記録（オプション機能）

5. **HTTPSの強制**
   - 本番環境では必須

## サポートとフィードバック

問題が発生した場合は、以下の情報を含めて報告してください：

1. PHPバージョン: `php -v`
2. MySQLバージョン: `mysql --version`
3. エラーメッセージ（`error.log`から）
4. 実行したSQL（該当する場合）

## 今後の拡張予定

- [ ] パスワードリセット機能
- [ ] メール認証
- [ ] 2要素認証（2FA）
- [ ] ユーザー権限管理（管理者/一般ユーザー）
- [ ] チーム/グループ機能
- [ ] データエクスポート（ユーザー固有）
- [ ] データインポート（ユーザー固有）

---

**最終更新**: <?= date('Y-m-d') ?>

**バージョン**: 1.3.0 (Multi-Account Support)
