# ロリポップデプロイ - クイックスタート

このガイドは、できるだけ早くロリポップにデプロイしたい方向けの簡潔な手順です。

## 📋 事前準備チェックリスト

- [ ] ロリポップのプラン: スタンダード以上
- [ ] PHPバージョン: 8.1以上に設定済み
- [ ] SSH接続: 有効化済み
- [ ] データベース: `LAA1547051-kakeidb` 作成済み
- [ ] Git リポジトリ: アクセス可能

## 🚀 デプロイ手順（3ステップ）

### Step 1: SSH接続

```bash
ssh あなたのアカウント@サーバー名 -p 2222
```

例:
```bash
ssh LAA1547051@ssh000.lolipop.jp -p 2222
```

### Step 2: Composerのインストール（初回のみ）

```bash
curl -sS https://getcomposer.org/installer | php
mkdir -p ~/bin
mv composer.phar ~/bin/composer
chmod +x ~/bin/composer
echo 'export PATH="$HOME/bin:$PATH"' >> ~/.bashrc
source ~/.bashrc
```

### Step 3: プロジェクトのデプロイ

```bash
cd ~/web
git clone https://github.com/nhashimoto-gm/Personal-Finance-Dashboard.git
cd Personal-Finance-Dashboard/laravel-app
bash deploy-lolipop.sh
```

デプロイスクリプトが以下を自動実行します：
1. Composerパッケージのインストール
2. .envファイルの作成（編集を求められます）
3. アプリケーションキーの生成
4. ストレージリンクの作成
5. パーミッション設定
6. キャッシュの生成

---

## ⚙️ .envファイルの編集

デプロイスクリプトの途中で `.env` ファイルを編集します：

```bash
nano .env
```

**編集箇所**:

```env
APP_URL=https://あなたのドメイン.com

DB_HOST=mysql000.lolipop.jp  # ロリポップ管理画面で確認
DB_DATABASE=LAA1547051-kakeidb
DB_USERNAME=LAA1547051
DB_PASSWORD=データベースのパスワード  # ここを実際のパスワードに変更
```

保存: `Ctrl + O` → `Enter` → `Ctrl + X`

---

## 🌐 公開ディレクトリの設定

### 方法1: .htaccessでリダイレクト（簡単）

```bash
cd ~/web
nano .htaccess
```

以下を記述：

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^/Personal-Finance-Dashboard/laravel-app/public/
    RewriteRule ^(.*)$ Personal-Finance-Dashboard/laravel-app/public/$1 [L]
</IfModule>
```

### 方法2: publicフォルダの移動（確実）

```bash
cd ~/web/Personal-Finance-Dashboard/laravel-app
cp -r public/* ~/web/
cd ~/web
```

`index.php` を編集：

```bash
nano index.php
```

以下の2行を変更：

```php
require __DIR__.'/Personal-Finance-Dashboard/laravel-app/vendor/autoload.php';
$app = require_once __DIR__.'/Personal-Finance-Dashboard/laravel-app/bootstrap/app.php';
```

---

## 🗄️ データベースのセットアップ

### phpMyAdminでテーブル作成

1. ロリポップのユーザー専用ページにログイン
2. 「データベース」→「phpMyAdmin」を開く
3. `LAA1547051-kakeidb` を選択
4. 「SQL」タブをクリック
5. `docs/DATABASE_MIGRATION_GUIDE.md` に記載のSQLを実行

---

## ✅ 動作確認

ブラウザで以下にアクセス：

```
https://あなたのドメイン.com
```

### エラーが出た場合

```bash
# ログを確認
tail -f ~/web/Personal-Finance-Dashboard/laravel-app/storage/logs/laravel.log

# キャッシュをクリア
cd ~/web/Personal-Finance-Dashboard/laravel-app
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## 🔄 更新手順

コードを更新した場合：

```bash
ssh あなたのアカウント@サーバー名 -p 2222
cd ~/web/Personal-Finance-Dashboard
git pull origin main
cd laravel-app
composer install --optimize-autoloader --no-dev
php artisan migrate
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 📚 詳細ドキュメント

詳しい説明やトラブルシューティングは以下を参照：

- **詳細なデプロイガイド**: `docs/LOLIPOP_DEPLOYMENT_GUIDE.md`
- **データベース移行ガイド**: `docs/DATABASE_MIGRATION_GUIDE.md`

---

## 🆘 よくある質問

### Q: Composerがメモリ不足で失敗する

```bash
php -d memory_limit=-1 ~/bin/composer install --optimize-autoloader --no-dev
```

### Q: 500 Internal Server Error が出る

1. パーミッション確認:
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```

2. .env確認:
   ```bash
   cd ~/web/Personal-Finance-Dashboard/laravel-app
   cat .env | grep DB_
   ```

3. ログ確認:
   ```bash
   tail -50 storage/logs/laravel.log
   ```

### Q: データベースに接続できない

ロリポップの管理画面で以下を確認：
- データベースホスト名
- データベースユーザー名
- データベースパスワード

---

これで完了です！問題が解決しない場合は、`docs/LOLIPOP_DEPLOYMENT_GUIDE.md` を参照してください。
