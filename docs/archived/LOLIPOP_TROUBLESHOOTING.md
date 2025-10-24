# ロリポップデプロイ後の設定ガイド

## 現在の状況
- ✅ Laravel動作確認済み：`https://www.*********.cloud/Personal-Finance-Dashboard/laravel-app/public/`
- ❌ 短縮URL未設定：`https://www.*********.cloud/` → 404エラー

## SSH接続での作業手順

### Step 1: .htaccessファイルの作成

```bash
# SSH接続
ssh あなたのアカウント@ssh.lolipop.jp -p 2222

# ウェブルートに移動
cd ~/web

# 既存の.htaccessを確認（存在する場合はバックアップ）
ls -la .htaccess
# 存在する場合
cp .htaccess .htaccess.backup

# 新しい.htaccessを作成
cat > .htaccess << 'EOF'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Personal-Finance-Dashboard/laravel-app/public にリダイレクト
    RewriteCond %{REQUEST_URI} !^/Personal-Finance-Dashboard/laravel-app/public/
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ /Personal-Finance-Dashboard/laravel-app/public/$1 [L]
</IfModule>
EOF

# 確認
cat .htaccess
```

### Step 2: Laravel側の.htaccessを確認

```bash
cd ~/web/Personal-Finance-Dashboard/laravel-app/public

# .htaccessが存在するか確認
ls -la .htaccess

# 内容を確認
cat .htaccess
```

もし存在しない場合、作成：

```bash
cat > .htaccess << 'EOF'
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
EOF
```

### Step 3: Laravelのキャッシュをクリア

```bash
cd ~/web/Personal-Finance-Dashboard/laravel-app

php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
composer dump-autoload
```

### Step 4: アクセステスト

ブラウザで以下にアクセス：

1. `https://www.*********.cloud/` → Laravelのスタートページが表示されるはず
2. `https://www.*********.cloud/Personal-Finance-Dashboard/laravel-app/public/` → こちらも動作するはず

---

## それでも404が出る場合

### 代替案1: index.phpでリダイレクト

`~/web/index.php` を作成：

```bash
cd ~/web
cat > index.php << 'EOF'
<?php
header('Location: /Personal-Finance-Dashboard/laravel-app/public/');
exit;
EOF
```

### 代替案2: publicフォルダを直接webルートに配置

```bash
cd ~/web

# Laravel publicの内容をwebルートにコピー
cp -r Personal-Finance-Dashboard/laravel-app/public/* .

# index.phpのパスを修正
nano index.php
```

`index.php` の以下の2行を変更：

変更前：
```php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
```

変更後：
```php
require __DIR__.'/Personal-Finance-Dashboard/laravel-app/vendor/autoload.php';
$app = require_once __DIR__.'/Personal-Finance-Dashboard/laravel-app/bootstrap/app.php';
```

---

## 確認コマンド

```bash
# 現在のディレクトリ構造を確認
cd ~/web
pwd
ls -la

# .htaccessの内容を確認
cat .htaccess

# Laravelのpublicディレクトリを確認
ls -la Personal-Finance-Dashboard/laravel-app/public/
```

---

## エラーログの確認

```bash
# Laravelのログ
tail -50 ~/web/Personal-Finance-Dashboard/laravel-app/storage/logs/laravel.log

# Apacheのエラーログ（ロリポップの管理画面から確認）
```

---

これらの手順を試してみて、結果を教えてください。特に：

1. どの方法を試したか
2. `.htaccess` の内容
3. エラーメッセージ（あれば）
