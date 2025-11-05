# 📦 データベースバックアップガイド

Personal Finance Dashboard のデータベース自動バックアップシステムの設定と使用方法です。

---

## 📋 目次

1. [概要](#概要)
2. [バックアップスクリプトの設定](#バックアップスクリプトの設定)
3. [手動バックアップの実行](#手動バックアップの実行)
4. [自動バックアップの設定（Cron）](#自動バックアップの設定cron)
5. [バックアップからの復元](#バックアップからの復元)
6. [バックアップ戦略](#バックアップ戦略)
7. [トラブルシューティング](#トラブルシューティング)

---

## 概要

このシステムには2つのバックアップスクリプトが含まれています：

| スクリプト | 目的 | 使用頻度 |
|-----------|------|----------|
| `backup-database.sh` | データベースの自動バックアップ | 毎日（Cron推奨） |
| `restore-database.sh` | バックアップからの復元 | 必要時のみ |

### バックアップの種類

1. **日次バックアップ** - 毎日作成、7日分保持
2. **週次バックアップ** - 毎週日曜日、4週分保持
3. **月次バックアップ** - 毎月1日、12ヶ月分保持

---

## バックアップスクリプトの設定

### 1. 基本設定

`backup-database.sh` の設定セクションを編集：

```bash
# バックアップ保存先（変更推奨）
BACKUP_DIR="/var/backups/mysql/finance_dashboard"

# 保持期間
DAILY_RETENTION=7      # 日次: 7日分
WEEKLY_RETENTION=28    # 週次: 4週分
MONTHLY_RETENTION=365  # 月次: 12ヶ月分
```

### 2. バックアップディレクトリの作成

```bash
# rootまたはsudo権限で実行
sudo mkdir -p /var/backups/mysql/finance_dashboard/{daily,weekly,monthly}
sudo chown -R www-data:www-data /var/backups/mysql/finance_dashboard
sudo chmod 700 /var/backups/mysql/finance_dashboard
```

または、ホームディレクトリを使用する場合：

```bash
mkdir -p ~/backups/finance_dashboard/{daily,weekly,monthly}
chmod 700 ~/backups/finance_dashboard

# backup-database.sh内のBACKUP_DIRを変更
BACKUP_DIR="$HOME/backups/finance_dashboard"
```

### 3. スクリプトの権限設定

```bash
chmod +x backup-database.sh
chmod +x restore-database.sh
```

---

## 手動バックアップの実行

### 基本的な使用方法

```bash
# バックアップの実行
./backup-database.sh

# ログ出力を確認しながら実行
./backup-database.sh 2>&1 | tee backup.log
```

### 実行例

```bash
$ ./backup-database.sh
[2025-11-05 02:00:01] ==========================================
[2025-11-05 02:00:01] Database Backup Started
[2025-11-05 02:00:01] Database: finance_db
[2025-11-05 02:00:01] Host: localhost
[2025-11-05 02:00:01] ==========================================
[2025-11-05 02:00:02] Creating daily backup...
[2025-11-05 02:00:05] Daily backup completed: /var/backups/mysql/finance_dashboard/daily/finance_db_20251105_020001.sql.gz (Size: 245K)
[2025-11-05 02:00:05] Cleaning up old backups...
[2025-11-05 02:00:05] Daily backups retained: 7
[2025-11-05 02:00:05] Weekly backups retained: 4
[2025-11-05 02:00:05] Monthly backups retained: 12
[2025-11-05 02:00:05] Verifying backup integrity...
[2025-11-05 02:00:05] Backup integrity verified: OK
[2025-11-05 02:00:05] ==========================================
[2025-11-05 02:00:05] Database Backup Completed Successfully
[2025-11-05 02:00:05] Total backup directory size: 1.8M
[2025-11-05 02:00:05] ==========================================
```

---

## 自動バックアップの設定（Cron）

### Cronジョブの追加

```bash
# cronエディタを開く
crontab -e

# 毎日午前2時に実行（推奨）
0 2 * * * /path/to/Personal-Finance-Dashboard/backup-database.sh >> /var/log/mysql-backup.log 2>&1

# 毎日午前3時に実行
0 3 * * * /path/to/Personal-Finance-Dashboard/backup-database.sh >> /var/log/mysql-backup.log 2>&1

# 12時間ごとに実行（午前2時と午後2時）
0 2,14 * * * /path/to/Personal-Finance-Dashboard/backup-database.sh >> /var/log/mysql-backup.log 2>&1
```

### Cron設定例

#### 開発環境向け（毎日1回）
```bash
# 毎日午前2時
0 2 * * * cd /home/user/Personal-Finance-Dashboard && ./backup-database.sh >> /var/log/mysql-backup.log 2>&1
```

#### 本番環境向け（1日2回）
```bash
# 午前2時と午後2時
0 2,14 * * * cd /var/www/html/Personal-Finance-Dashboard && ./backup-database.sh >> /var/log/mysql-backup.log 2>&1
```

### Cron設定の確認

```bash
# 現在のcronジョブを確認
crontab -l

# cronサービスの状態確認
sudo systemctl status cron    # Ubuntu/Debian
sudo systemctl status crond   # CentOS/RHEL
```

### ログの確認

```bash
# バックアップログの確認
tail -f /var/log/mysql-backup.log

# 最近のバックアップ履歴
tail -50 /var/log/mysql-backup.log
```

---

## バックアップからの復元

### 1. 利用可能なバックアップを確認

```bash
# 引数なしで実行すると、利用可能なバックアップを表示
./restore-database.sh
```

出力例：
```
Available backups:
-------------------

Daily backups:
/var/backups/mysql/finance_dashboard/daily/finance_db_20251105_020001.sql.gz
/var/backups/mysql/finance_dashboard/daily/finance_db_20251104_020001.sql.gz
/var/backups/mysql/finance_dashboard/daily/finance_db_20251103_020001.sql.gz

Weekly backups:
/var/backups/mysql/finance_dashboard/weekly/finance_db_week_20251103_020001.sql.gz

Monthly backups:
/var/backups/mysql/finance_dashboard/monthly/finance_db_month_20251101_020001.sql.gz
```

### 2. バックアップからの復元

```bash
# バックアップファイルを指定して復元
./restore-database.sh /var/backups/mysql/finance_dashboard/daily/finance_db_20251105_020001.sql.gz
```

### 3. 復元時の確認プロセス

スクリプトは以下の確認を行います：

1. **警告の表示**
   ```
   WARNING: DATABASE RESTORE OPERATION
   This operation will:
     1. DROP all existing tables in the database
     2. Restore data from the backup file
     3. ALL CURRENT DATA WILL BE LOST
   ```

2. **1回目の確認**
   ```
   Are you sure you want to proceed? Type 'yes' to continue:
   ```

3. **2回目の確認（データベース名の入力）**
   ```
   This is your LAST CHANCE. Type the database name 'finance_db' to confirm:
   ```

### 4. 安全機能

- **自動セーフティバックアップ**: 復元前に現在のデータベースをバックアップ
- **整合性チェック**: バックアップファイルの破損チェック
- **自動ロールバック**: 復元失敗時に自動的に元の状態に戻す

---

## バックアップ戦略

### 推奨バックアップスケジュール

| 環境 | 頻度 | 保持期間 | 備考 |
|------|------|----------|------|
| **開発環境** | 毎日1回（午前2時） | 日次:7日 | 軽量バックアップ |
| **ステージング** | 毎日2回（午前2時、午後2時） | 日次:14日、週次:4週 | 中程度の保護 |
| **本番環境** | 毎日2回 + オフサイト | 日次:30日、週次:8週、月次:12ヶ月 | 完全な保護 |

### データ保護のベストプラクティス

1. **3-2-1ルール**
   - データを **3つ** のコピーで保管
   - **2種類** の異なるメディアに保存
   - **1つ** はオフサイト（クラウドなど）

2. **定期的な復元テスト**
   ```bash
   # テスト環境で復元テストを実施（月1回推奨）
   ./restore-database.sh /path/to/backup.sql.gz
   ```

3. **バックアップの監視**
   - Cronログの定期確認
   - バックアップファイルサイズの異常チェック
   - ディスク容量の監視

---

## オプション機能

### 1. S3へのバックアップアップロード

`backup-database.sh`の設定を変更：

```bash
# S3アップロードを有効化
ENABLE_S3_UPLOAD=true
S3_BUCKET="your-backup-bucket"
S3_PATH="backups/mysql/finance/"
```

AWS CLIのインストールと設定：

```bash
# AWS CLIのインストール
sudo apt install awscli

# AWS認証情報の設定
aws configure
# AWS Access Key ID: [your-access-key]
# AWS Secret Access Key: [your-secret-key]
# Default region name: ap-northeast-1
# Default output format: json

# S3バケットの作成
aws s3 mb s3://your-backup-bucket

# テストアップロード
aws s3 cp test.txt s3://your-backup-bucket/test.txt
```

### 2. Slack通知の設定

```bash
# Slack通知を有効化
ENABLE_SLACK_NOTIFICATION=true
SLACK_WEBHOOK_URL="https://hooks.slack.com/services/YOUR/WEBHOOK/URL"
```

Slack Webhookの作成：
1. Slack Appを作成: https://api.slack.com/apps
2. Incoming Webhooksを有効化
3. Webhook URLをコピー

---

## トラブルシューティング

### エラー: .env_db file not found

**原因**: `.env_db`ファイルが存在しない

**解決方法**:
```bash
cp .env_db.example .env_db
nano .env_db  # データベース設定を編集
chmod 600 .env_db
```

### エラー: Failed to create backup directory

**原因**: ディレクトリ作成の権限がない

**解決方法**:
```bash
# ディレクトリを手動で作成
sudo mkdir -p /var/backups/mysql/finance_dashboard/{daily,weekly,monthly}

# 権限を付与
sudo chown -R $USER:$USER /var/backups/mysql/finance_dashboard

# または、ホームディレクトリを使用
# backup-database.sh内のBACKUP_DIRを変更
BACKUP_DIR="$HOME/backups/finance_dashboard"
```

### エラー: mysqldump failed

**原因**: データベース接続エラーまたは認証エラー

**解決方法**:
```bash
# 接続テスト
mysql -h [DB_HOST] -u [DB_USERNAME] -p[DB_PASSWORD] [DB_DATABASE] -e "SHOW TABLES;"

# .env_dbの設定を確認
cat .env_db

# mysqldumpの権限確認
mysql -h [DB_HOST] -u [DB_USERNAME] -p -e "SHOW GRANTS;"
```

### バックアップファイルが大きすぎる

**解決方法**:

1. **古いデータのアーカイブ**
   ```sql
   -- 古いトランザクションを別テーブルに移動
   CREATE TABLE source_archive LIKE source;
   INSERT INTO source_archive SELECT * FROM source WHERE date < '2020-01-01';
   DELETE FROM source WHERE date < '2020-01-01';
   ```

2. **圧縮率の向上**
   ```bash
   # gzipの代わりにxzを使用（より高圧縮）
   mysqldump ... | xz > backup.sql.xz
   ```

### Cronジョブが実行されない

**確認事項**:

```bash
# 1. Cronサービスが起動しているか確認
sudo systemctl status cron

# 2. Cronログを確認
grep CRON /var/log/syslog

# 3. スクリプトのパスが正しいか確認
which mysqldump

# 4. スクリプトに実行権限があるか確認
ls -l backup-database.sh

# 5. 環境変数をフルパスで指定
0 2 * * * cd /full/path/to/Personal-Finance-Dashboard && /usr/bin/bash ./backup-database.sh
```

---

## セキュリティのベストプラクティス

### 1. ファイル権限

```bash
# バックアップディレクトリの権限
chmod 700 /var/backups/mysql/finance_dashboard

# .env_dbファイルの権限
chmod 600 .env_db

# スクリプトの権限
chmod 700 backup-database.sh restore-database.sh
```

### 2. バックアップの暗号化

**GPGを使用した暗号化**:

```bash
# バックアップを暗号化
mysqldump ... | gzip | gpg -c --cipher-algo AES256 > backup.sql.gz.gpg

# 復号化
gpg -d backup.sql.gz.gpg | gunzip | mysql ...
```

### 3. オフサイトバックアップ

- AWS S3
- Google Cloud Storage
- Azure Blob Storage
- 外部FTPサーバー

---

## まとめ

✅ **完了チェックリスト**

- [ ] `backup-database.sh`の設定を完了
- [ ] バックアップディレクトリを作成
- [ ] 手動バックアップのテスト実行
- [ ] Cronジョブの設定
- [ ] 復元テストの実施
- [ ] ログ確認の自動化
- [ ] オフサイトバックアップの設定（推奨）
- [ ] 監視・アラートの設定（推奨）

---

## 関連ドキュメント

- [README.md](README.md) - メインドキュメント
- [MIGRATION_GUIDE.md](MIGRATION_GUIDE.md) - データベース移行ガイド
- [.env_db.example](.env_db.example) - 環境設定の例

---

**作成日**: 2025-11-05
**バージョン**: 1.0
**著者**: Personal Finance Dashboard Team
