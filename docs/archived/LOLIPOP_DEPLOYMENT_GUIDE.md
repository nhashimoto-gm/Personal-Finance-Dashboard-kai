# ロリポップへのLaravelデプロイガイド

このガイドでは、ロリポップレンタルサーバーに Personal Finance Dashboard (Laravel版) をデプロイする手順を説明します。

## 前提条件

### 必須要件

- ✅ **ロリポップのプラン**: スタンダードプラン以上（ハイスピード推奨）
- ✅ **PHPバージョン**: 8.1 以上
- ✅ **SSH接続**: 有効化されていること
- ✅ **データベース**: MySQL (LAA1547051-kakeidb)

### 確認方法

#### 1. プランの確認
ロリポップのユーザー専用ページ → 契約情報 で確認

#### 2. PHPバージョンの確認・変更
1. ユーザー専用ページにログイン
2. 「サーバーの管理・設定」→「PHP設定」
3. 対象ドメインのPHPバージョンを **8.1以上** に変更

#### 3. SSH接続の有効化
1. ユーザー専用ページ → 「サーバーの管理・設定」→「SSH」
2. 「SSHを有効にする」をクリック
3. 接続情報をメモ：
   - サーバー
   - アカウント
   - パスワード
   - ポート番号（通常2222）

---

## デプロイ手順

### Phase 1: ローカルでの準備

#### 1. 依存パッケージのインストール（ローカル）

```bash
cd /home/user/Personal-Finance-Dashboard/laravel-app
composer install --optimize-autoloader --no-dev
```

#### 2. .envファイルの作成

```bash
cp .env.example .env
```

`.env` ファイルを編集：

```env
APP_NAME="Personal Finance Dashboard"
APP_ENV=production
APP_KEY=base64:XXXXXXXXXX  # 後でサーバー側で生成
APP_DEBUG=false
APP_URL=https://あなたのドメイン.com

DB_CONNECTION=mysql
DB_HOST=mysql000.lolipop.jp  # ロリポップのMySQLサーバー
DB_PORT=3306
DB_DATABASE=LAA1547051-kakeidb
DB_USERNAME=LAA1547051  # データベースユーザー名
DB_PASSWORD=your_database_password  # データベースパスワード
```

**重要**:
- `APP_DEBUG=false` にすること（本番環境）
- データベース接続情報はロリポップのユーザー専用ページで確認

#### 3. キャッシュの最適化（後でサーバー側で実行）

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

### Phase 2: ロリポップへのアップロード

#### 方法A: SSH + Git（推奨）

##### 1. SSHでロリポップに接続

```bash
ssh アカウント名@サーバー名 -p 2222
```

例:
```bash
ssh LAA1547051@ssh000.lolipop.jp -p 2222
```

##### 2. Composerのインストール（初回のみ）

```bash
cd ~
curl -sS https://getcomposer.org/installer | php
mkdir -p ~/bin
mv composer.phar ~/bin/composer
chmod +x ~/bin/composer
echo 'export PATH="$HOME/bin:$PATH"' >> ~/.bashrc
source ~/.bashrc
```

Composerがインストールされたか確認：
```bash
composer --version
```

##### 3. Gitでプロジェクトをクローン

```bash
cd ~/web
git clone https://github.com/nhashimoto-gm/Personal-Finance-Dashboard.git
cd Personal-Finance-Dashboard/laravel-app
```

##### 4. Composerで依存パッケージをインストール

```bash
composer install --optimize-autoloader --no-dev
```

##### 5. .envファイルの設定

```bash
cp .env.example .env
nano .env  # または vi .env
```

データベース情報を正しく設定してください。

##### 6. アプリケーションキーの生成

```bash
php artisan key:generate
```

##### 7. ストレージリンクの作成

```bash
php artisan storage:link
```

##### 8. パーミッションの設定

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

---

#### 方法B: FTPでアップロード

##### 1. ローカルで準備

```bash
# ローカル環境で実行
cd /home/user/Personal-Finance-Dashboard/laravel-app
composer install --optimize-autoloader --no-dev
```

##### 2. FTPソフトで接続

- **FTPソフト**: FileZilla, WinSCP, Cyberduck など
- **接続情報**: ロリポップのユーザー専用ページで確認
  - FTPSサーバー
  - FTPSアカウント
  - FTPSパスワード

##### 3. ファイルのアップロード

以下のフォルダ・ファイルをアップロード：

```
laravel-app/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/  ← これが重要
├── resources/
├── routes/
├── storage/
├── vendor/  ← composer installで生成されたもの
├── artisan
├── composer.json
├── composer.lock
└── .env  ← 本番用に編集したもの
```

**アップロード先**: `/web/laravel-app/`

##### 4. パーミッション設定（FTPソフトで）

以下のディレクトリのパーミッションを `775` に設定：
- `storage/` とその全サブディレクトリ
- `bootstrap/cache/`

---

### Phase 3: ロリポップの公開フォルダ設定

#### ドキュメントルートの変更

ロリポップでは、Laravelの `public` フォルダを公開ディレクトリにする必要があります。

##### オプション1: .htaccessでリダイレクト（簡単）

`/web/.htaccess` を作成：

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ laravel-app/public/$1 [L]
</IfModule>
```

##### オプション2: シンボリックリンク（推奨）

SSH接続して実行：

```bash
cd ~/web
ln -s ~/web/laravel-app/public ./public_html
```

**注意**: ロリポップではシンボリックリンクに制限がある場合があります。

##### オプション3: publicフォルダの内容を移動

```bash
cd ~/web/laravel-app/public
cp -r * ~/web/
cd ~/web
```

`index.php` を編集して、パスを修正：

```php
require __DIR__.'/laravel-app/vendor/autoload.php';
$app = require_once __DIR__.'/laravel-app/bootstrap/app.php';
```

---

### Phase 4: データベースのセットアップ

#### 1. データベースの確認

ロリポップのユーザー専用ページ → サーバーの管理・設定 → データベース

- データベース名: `LAA1547051-kakeidb`
- 接続先（ホスト名）: `mysqlXXX.lolipop.jp`
- ユーザー名: `LAA1547051`

#### 2. テーブルの作成

phpMyAdmin にアクセス（ロリポップのユーザー専用ページからリンクあり）

`DATABASE_MIGRATION_GUIDE.md` に記載されているSQLを実行してテーブルを作成。

または、SSH経由でマイグレーション実行：

```bash
cd ~/web/laravel-app
php artisan migrate
```

---

### Phase 5: 動作確認

#### 1. ブラウザでアクセス

```
https://あなたのドメイン.com
```

#### 2. エラーが出る場合

##### エラー: 500 Internal Server Error

**原因と対処**:

1. **ログの確認**
   ```bash
   tail -f ~/web/laravel-app/storage/logs/laravel.log
   ```

2. **.envファイルの確認**
   - `APP_KEY` が設定されているか
   - データベース接続情報が正しいか

3. **パーミッションの確認**
   ```bash
   chmod -R 775 ~/web/laravel-app/storage
   chmod -R 775 ~/web/laravel-app/bootstrap/cache
   ```

4. **Composerの再実行**
   ```bash
   cd ~/web/laravel-app
   composer install --optimize-autoloader --no-dev
   ```

##### エラー: データベース接続エラー

`.env` ファイルのデータベース設定を再確認：

```env
DB_HOST=mysql000.lolipop.jp  # ロリポップの管理画面で確認
DB_DATABASE=LAA1547051-kakeidb
DB_USERNAME=LAA1547051
DB_PASSWORD=正しいパスワード
```

---

## Phase 6: 最適化とセキュリティ

### 本番環境の最適化

SSH接続して実行：

```bash
cd ~/web/laravel-app

# キャッシュの生成
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Composerの最適化
composer dump-autoload --optimize
```

### セキュリティチェックリスト

- ✅ `APP_DEBUG=false` になっているか
- ✅ `APP_ENV=production` になっているか
- ✅ `.env` ファイルが公開ディレクトリの外にあるか
- ✅ `storage/` と `bootstrap/cache/` のパーミッションが適切か（775）
- ✅ 不要なファイル（`.git`, `tests/`, `README.md` など）を削除したか

---

## トラブルシューティング

### よくある問題

#### 1. Composerがメモリ不足で失敗

```bash
php -d memory_limit=-1 ~/bin/composer install --optimize-autoloader --no-dev
```

#### 2. シンボリックリンクが作成できない

ロリポップではシンボリックリンクに制限があります。
→ オプション3（publicフォルダの移動）を使用してください。

#### 3. PHPバージョンが古い

ユーザー専用ページ → PHP設定 → バージョンを8.1以上に変更

#### 4. SSHで接続できない

- スタンダードプラン以上か確認
- SSH設定が有効になっているか確認
- ポート番号は 2222 を使用

---

## 更新手順

アプリケーションを更新する場合：

### SSH経由（推奨）

```bash
ssh アカウント名@サーバー名 -p 2222
cd ~/web/Personal-Finance-Dashboard
git pull origin main
cd laravel-app
composer install --optimize-autoloader --no-dev
php artisan migrate
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### FTP経由

1. 変更したファイルのみアップロード
2. SSH接続してキャッシュクリア：
   ```bash
   php artisan cache:clear
   php artisan config:cache
   ```

---

## ロリポップ特有の制約

### 注意点

1. **メモリ制限**: 共有ホスティングのため、メモリに制限があります
2. **プロセス制限**: 長時間実行されるプロセスは制限されます
3. **Cronジョブ**: 最短5分間隔（スタンダードプランの場合）
4. **ファイル数制限**: 大量のファイルがある場合、制限に引っかかる可能性

### 推奨事項

- **キャッシュを活用**: config:cache, route:cache を必ず実行
- **不要なファイルは削除**: tests/, .git/ などは本番環境では不要
- **ログローテーション**: storage/logs/ が肥大化しないよう定期的に削除

---

## 参考リンク

- [ロリポップ公式: SSH接続](https://lolipop.jp/manual/user/ssh/)
- [ロリポップ公式: PHP設定](https://lolipop.jp/manual/user/php-setting/)
- [Laravel公式: デプロイ](https://laravel.com/docs/10.x/deployment)

---

## サポート

問題が発生した場合は、以下を確認してください：

1. `storage/logs/laravel.log` のエラーログ
2. ロリポップのエラーログ（ユーザー専用ページから確認可能）
3. PHPのバージョンとプラン
