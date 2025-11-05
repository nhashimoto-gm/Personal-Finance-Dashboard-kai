#!/bin/bash
################################################################################
# Personal Finance Dashboard - Database Backup Script
################################################################################
# このスクリプトはデータベースを自動的にバックアップします
#
# 使用方法:
#   1. chmod +x backup-database.sh
#   2. ./backup-database.sh
#
# Cronジョブでの自動化:
#   毎日午前2時に実行する場合:
#   0 2 * * * /path/to/backup-database.sh >> /var/log/mysql-backup.log 2>&1
################################################################################

# ============================================================
# 設定セクション
# ============================================================

# バックアップディレクトリ（絶対パス推奨）
BACKUP_DIR="/var/backups/mysql/finance_dashboard"

# .env_dbファイルのパス（このスクリプトと同じディレクトリ）
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="${SCRIPT_DIR}/.env_db"

# 保持期間（日数）
DAILY_RETENTION=7      # 日次バックアップを7日分保持
WEEKLY_RETENTION=28    # 週次バックアップを4週分保持
MONTHLY_RETENTION=365  # 月次バックアップを12ヶ月分保持

# バックアップファイル名のプレフィックス
BACKUP_PREFIX="finance_db"

# ログ出力の有効化
ENABLE_LOGGING=true

# S3へのアップロード（オプション）
ENABLE_S3_UPLOAD=false
S3_BUCKET=""
S3_PATH="backups/mysql/"

# Slackへの通知（オプション）
ENABLE_SLACK_NOTIFICATION=false
SLACK_WEBHOOK_URL=""

# ============================================================
# 環境変数の読み込み
# ============================================================

if [ ! -f "$ENV_FILE" ]; then
    echo "ERROR: .env_db file not found at: $ENV_FILE"
    echo "Please create .env_db file with database credentials."
    exit 1
fi

# .env_dbファイルから設定を読み込む
# 形式: KEY=VALUE
while IFS='=' read -r key value; do
    # コメント行と空行をスキップ
    [[ $key =~ ^[[:space:]]*# ]] && continue
    [[ -z $key ]] && continue

    # 前後の空白を削除
    key=$(echo "$key" | xargs)
    value=$(echo "$value" | xargs)

    case "$key" in
        DB_HOST) DB_HOST="$value" ;;
        DB_USERNAME) DB_USERNAME="$value" ;;
        DB_PASSWORD) DB_PASSWORD="$value" ;;
        DB_DATABASE) DB_DATABASE="$value" ;;
    esac
done < "$ENV_FILE"

# 必須変数のチェック
if [ -z "$DB_HOST" ] || [ -z "$DB_USERNAME" ] || [ -z "$DB_PASSWORD" ] || [ -z "$DB_DATABASE" ]; then
    echo "ERROR: Database credentials not properly configured in .env_db"
    echo "Required: DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DATABASE"
    exit 1
fi

# ============================================================
# 関数定義
# ============================================================

# ログ出力関数
log_message() {
    if [ "$ENABLE_LOGGING" = true ]; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
    fi
}

# エラーメッセージ
error_exit() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: $1" >&2

    # Slack通知（エラー時）
    if [ "$ENABLE_SLACK_NOTIFICATION" = true ] && [ -n "$SLACK_WEBHOOK_URL" ]; then
        curl -X POST -H 'Content-type: application/json' \
            --data "{\"text\":\"🚨 Database Backup Failed: $1\"}" \
            "$SLACK_WEBHOOK_URL" 2>/dev/null
    fi

    exit 1
}

# Slack通知（成功時）
notify_slack() {
    if [ "$ENABLE_SLACK_NOTIFICATION" = true ] && [ -n "$SLACK_WEBHOOK_URL" ]; then
        curl -X POST -H 'Content-type: application/json' \
            --data "{\"text\":\"✅ Database Backup Completed: $1\"}" \
            "$SLACK_WEBHOOK_URL" 2>/dev/null
    fi
}

# S3へのアップロード
upload_to_s3() {
    local file=$1
    if [ "$ENABLE_S3_UPLOAD" = true ] && [ -n "$S3_BUCKET" ]; then
        log_message "Uploading to S3: $S3_BUCKET/$S3_PATH"
        if command -v aws &> /dev/null; then
            aws s3 cp "$file" "s3://$S3_BUCKET/$S3_PATH$(basename $file)" && \
                log_message "S3 upload successful" || \
                log_message "WARNING: S3 upload failed"
        else
            log_message "WARNING: AWS CLI not installed. Skipping S3 upload."
        fi
    fi
}

# ============================================================
# メインバックアップ処理
# ============================================================

log_message "=========================================="
log_message "Database Backup Started"
log_message "Database: $DB_DATABASE"
log_message "Host: $DB_HOST"
log_message "=========================================="

# バックアップディレクトリの作成
mkdir -p "$BACKUP_DIR/daily" || error_exit "Failed to create daily backup directory"
mkdir -p "$BACKUP_DIR/weekly" || error_exit "Failed to create weekly backup directory"
mkdir -p "$BACKUP_DIR/monthly" || error_exit "Failed to create monthly backup directory"

# 現在の日時
DATE=$(date +%Y%m%d_%H%M%S)
DAY_OF_WEEK=$(date +%u)  # 1=月曜日, 7=日曜日
DAY_OF_MONTH=$(date +%d)

# バックアップファイル名
DAILY_BACKUP="${BACKUP_DIR}/daily/${BACKUP_PREFIX}_${DATE}.sql.gz"
WEEKLY_BACKUP="${BACKUP_DIR}/weekly/${BACKUP_PREFIX}_week_${DATE}.sql.gz"
MONTHLY_BACKUP="${BACKUP_DIR}/monthly/${BACKUP_PREFIX}_month_${DATE}.sql.gz"

# ============================================================
# 日次バックアップ（毎日）
# ============================================================

log_message "Creating daily backup..."

# mysqldumpの実行
mysqldump \
    --host="$DB_HOST" \
    --user="$DB_USERNAME" \
    --password="$DB_PASSWORD" \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --add-drop-table \
    --quick \
    --lock-tables=false \
    "$DB_DATABASE" 2>/dev/null | gzip > "$DAILY_BACKUP"

# バックアップの成否チェック
if [ ${PIPESTATUS[0]} -eq 0 ] && [ -s "$DAILY_BACKUP" ]; then
    BACKUP_SIZE=$(du -h "$DAILY_BACKUP" | cut -f1)
    log_message "Daily backup completed: $DAILY_BACKUP (Size: $BACKUP_SIZE)"
else
    error_exit "mysqldump failed for daily backup"
fi

# S3にアップロード
upload_to_s3 "$DAILY_BACKUP"

# ============================================================
# 週次バックアップ（日曜日のみ）
# ============================================================

if [ "$DAY_OF_WEEK" -eq 7 ]; then
    log_message "Creating weekly backup (Sunday)..."
    cp "$DAILY_BACKUP" "$WEEKLY_BACKUP"
    log_message "Weekly backup created: $WEEKLY_BACKUP"
    upload_to_s3 "$WEEKLY_BACKUP"
fi

# ============================================================
# 月次バックアップ（毎月1日のみ）
# ============================================================

if [ "$DAY_OF_MONTH" -eq 01 ]; then
    log_message "Creating monthly backup (1st of month)..."
    cp "$DAILY_BACKUP" "$MONTHLY_BACKUP"
    log_message "Monthly backup created: $MONTHLY_BACKUP"
    upload_to_s3 "$MONTHLY_BACKUP"
fi

# ============================================================
# 古いバックアップの削除
# ============================================================

log_message "Cleaning up old backups..."

# 日次バックアップの削除（7日より古いもの）
find "$BACKUP_DIR/daily" -name "${BACKUP_PREFIX}_*.sql.gz" -mtime +$DAILY_RETENTION -delete
DAILY_COUNT=$(find "$BACKUP_DIR/daily" -name "${BACKUP_PREFIX}_*.sql.gz" | wc -l)
log_message "Daily backups retained: $DAILY_COUNT"

# 週次バックアップの削除（28日より古いもの）
find "$BACKUP_DIR/weekly" -name "${BACKUP_PREFIX}_week_*.sql.gz" -mtime +$WEEKLY_RETENTION -delete
WEEKLY_COUNT=$(find "$BACKUP_DIR/weekly" -name "${BACKUP_PREFIX}_week_*.sql.gz" | wc -l)
log_message "Weekly backups retained: $WEEKLY_COUNT"

# 月次バックアップの削除（365日より古いもの）
find "$BACKUP_DIR/monthly" -name "${BACKUP_PREFIX}_month_*.sql.gz" -mtime +$MONTHLY_RETENTION -delete
MONTHLY_COUNT=$(find "$BACKUP_DIR/monthly" -name "${BACKUP_PREFIX}_month_*.sql.gz" | wc -l)
log_message "Monthly backups retained: $MONTHLY_COUNT"

# ============================================================
# バックアップ検証
# ============================================================

log_message "Verifying backup integrity..."

# gzipファイルの整合性チェック
if gzip -t "$DAILY_BACKUP" 2>/dev/null; then
    log_message "Backup integrity verified: OK"
else
    error_exit "Backup integrity check failed: corrupted gzip file"
fi

# ============================================================
# 完了通知
# ============================================================

TOTAL_SIZE=$(du -sh "$BACKUP_DIR" | cut -f1)
log_message "=========================================="
log_message "Database Backup Completed Successfully"
log_message "Total backup directory size: $TOTAL_SIZE"
log_message "=========================================="

# Slack通知（成功）
notify_slack "Database: $DB_DATABASE, Size: $BACKUP_SIZE, Total: $TOTAL_SIZE"

exit 0
