#!/bin/bash
################################################################################
# Personal Finance Dashboard - Database Restore Script
################################################################################
# このスクリプトはバックアップからデータベースを復元します
#
# 使用方法:
#   1. chmod +x restore-database.sh
#   2. ./restore-database.sh [バックアップファイルパス]
#
# 例:
#   ./restore-database.sh /var/backups/mysql/finance_dashboard/daily/finance_db_20250101_020000.sql.gz
################################################################################

# ============================================================
# 設定セクション
# ============================================================

# .env_dbファイルのパス
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="${SCRIPT_DIR}/.env_db"

# 色付きメッセージ用
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# ============================================================
# 環境変数の読み込み
# ============================================================

if [ ! -f "$ENV_FILE" ]; then
    echo -e "${RED}ERROR: .env_db file not found at: $ENV_FILE${NC}"
    exit 1
fi

# .env_dbファイルから設定を読み込む
while IFS='=' read -r key value; do
    [[ $key =~ ^[[:space:]]*# ]] && continue
    [[ -z $key ]] && continue
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
    echo -e "${RED}ERROR: Database credentials not properly configured in .env_db${NC}"
    exit 1
fi

# ============================================================
# バックアップファイルの確認
# ============================================================

if [ -z "$1" ]; then
    echo -e "${RED}ERROR: Backup file path not specified${NC}"
    echo ""
    echo "Usage: $0 <backup_file_path>"
    echo ""
    echo "Example:"
    echo "  $0 /var/backups/mysql/finance_dashboard/daily/finance_db_20250101_020000.sql.gz"
    echo ""
    echo "Available backups:"
    echo "-------------------"

    # 利用可能なバックアップファイルをリスト表示
    BACKUP_DIR="/var/backups/mysql/finance_dashboard"
    if [ -d "$BACKUP_DIR" ]; then
        echo ""
        echo "Daily backups:"
        find "$BACKUP_DIR/daily" -name "*.sql.gz" -type f -printf "%T@ %p\n" 2>/dev/null | sort -rn | head -n 5 | cut -d' ' -f2-
        echo ""
        echo "Weekly backups:"
        find "$BACKUP_DIR/weekly" -name "*.sql.gz" -type f -printf "%T@ %p\n" 2>/dev/null | sort -rn | head -n 3 | cut -d' ' -f2-
        echo ""
        echo "Monthly backups:"
        find "$BACKUP_DIR/monthly" -name "*.sql.gz" -type f -printf "%T@ %p\n" 2>/dev/null | sort -rn | head -n 3 | cut -d' ' -f2-
    fi
    exit 1
fi

BACKUP_FILE="$1"

if [ ! -f "$BACKUP_FILE" ]; then
    echo -e "${RED}ERROR: Backup file not found: $BACKUP_FILE${NC}"
    exit 1
fi

# ファイルが.gzファイルかどうかチェック
if [[ "$BACKUP_FILE" == *.gz ]]; then
    IS_COMPRESSED=true
else
    IS_COMPRESSED=false
fi

# ============================================================
# 警告と確認
# ============================================================

echo -e "${YELLOW}=========================================="
echo "WARNING: DATABASE RESTORE OPERATION"
echo -e "==========================================${NC}"
echo ""
echo "Database: ${GREEN}$DB_DATABASE${NC}"
echo "Host: ${GREEN}$DB_HOST${NC}"
echo "Backup file: ${GREEN}$BACKUP_FILE${NC}"
echo ""
echo -e "${RED}This operation will:"
echo "  1. DROP all existing tables in the database"
echo "  2. Restore data from the backup file"
echo "  3. ALL CURRENT DATA WILL BE LOST"
echo -e "${NC}"

# ファイルサイズと作成日時を表示
FILE_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
FILE_DATE=$(date -r "$BACKUP_FILE" '+%Y-%m-%d %H:%M:%S')
echo "Backup file size: $FILE_SIZE"
echo "Backup created: $FILE_DATE"
echo ""

# 確認プロンプト
read -p "Are you sure you want to proceed? Type 'yes' to continue: " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo -e "${YELLOW}Restore operation cancelled.${NC}"
    exit 0
fi

# 二重確認
read -p "This is your LAST CHANCE. Type the database name '$DB_DATABASE' to confirm: " CONFIRM_DB

if [ "$CONFIRM_DB" != "$DB_DATABASE" ]; then
    echo -e "${YELLOW}Database name mismatch. Restore operation cancelled.${NC}"
    exit 0
fi

# ============================================================
# バックアップの整合性チェック
# ============================================================

echo ""
echo "Verifying backup integrity..."

if [ "$IS_COMPRESSED" = true ]; then
    if gzip -t "$BACKUP_FILE" 2>/dev/null; then
        echo -e "${GREEN}✓ Backup integrity verified${NC}"
    else
        echo -e "${RED}ERROR: Backup file is corrupted${NC}"
        exit 1
    fi
fi

# ============================================================
# 現在のデータベースのバックアップ（安全策）
# ============================================================

echo ""
echo "Creating safety backup of current database..."

SAFETY_BACKUP="/tmp/${DB_DATABASE}_pre_restore_$(date +%Y%m%d_%H%M%S).sql.gz"

mysqldump \
    --host="$DB_HOST" \
    --user="$DB_USERNAME" \
    --password="$DB_PASSWORD" \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    "$DB_DATABASE" 2>/dev/null | gzip > "$SAFETY_BACKUP"

if [ ${PIPESTATUS[0]} -eq 0 ]; then
    echo -e "${GREEN}✓ Safety backup created: $SAFETY_BACKUP${NC}"
else
    echo -e "${YELLOW}WARNING: Failed to create safety backup${NC}"
    read -p "Continue anyway? (yes/no): " CONTINUE
    if [ "$CONTINUE" != "yes" ]; then
        exit 1
    fi
fi

# ============================================================
# データベースのリストア
# ============================================================

echo ""
echo "Starting database restore..."

if [ "$IS_COMPRESSED" = true ]; then
    # 圧縮ファイルを解凍しながらリストア
    gunzip < "$BACKUP_FILE" | mysql \
        --host="$DB_HOST" \
        --user="$DB_USERNAME" \
        --password="$DB_PASSWORD" \
        "$DB_DATABASE"
else
    # 非圧縮ファイルをそのままリストア
    mysql \
        --host="$DB_HOST" \
        --user="$DB_USERNAME" \
        --password="$DB_PASSWORD" \
        "$DB_DATABASE" < "$BACKUP_FILE"
fi

# リストアの成否チェック
if [ $? -eq 0 ]; then
    echo -e "${GREEN}"
    echo "=========================================="
    echo "DATABASE RESTORE COMPLETED SUCCESSFULLY"
    echo "=========================================="
    echo -e "${NC}"
    echo "Database: $DB_DATABASE"
    echo "Restored from: $BACKUP_FILE"
    echo ""
    echo "Safety backup is available at:"
    echo "  $SAFETY_BACKUP"
    echo ""
    echo -e "${YELLOW}You can delete the safety backup after verifying the restore:${NC}"
    echo "  rm $SAFETY_BACKUP"
else
    echo -e "${RED}"
    echo "=========================================="
    echo "DATABASE RESTORE FAILED"
    echo "=========================================="
    echo -e "${NC}"
    echo ""
    echo -e "${YELLOW}Attempting to restore from safety backup...${NC}"

    # 安全バックアップから復元を試みる
    if [ -f "$SAFETY_BACKUP" ]; then
        gunzip < "$SAFETY_BACKUP" | mysql \
            --host="$DB_HOST" \
            --user="$DB_USERNAME" \
            --password="$DB_PASSWORD" \
            "$DB_DATABASE"

        if [ $? -eq 0 ]; then
            echo -e "${GREEN}✓ Successfully restored from safety backup${NC}"
        else
            echo -e "${RED}ERROR: Failed to restore from safety backup${NC}"
            echo "Manual intervention required."
        fi
    fi

    exit 1
fi

# ============================================================
# リストア後の検証
# ============================================================

echo ""
echo "Verifying restored database..."

# テーブル数をカウント
TABLE_COUNT=$(mysql \
    --host="$DB_HOST" \
    --user="$DB_USERNAME" \
    --password="$DB_PASSWORD" \
    --batch \
    --skip-column-names \
    -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$DB_DATABASE'" 2>/dev/null)

if [ -n "$TABLE_COUNT" ] && [ "$TABLE_COUNT" -gt 0 ]; then
    echo -e "${GREEN}✓ Database contains $TABLE_COUNT tables${NC}"
else
    echo -e "${YELLOW}WARNING: Database appears to be empty${NC}"
fi

# ユーザーテーブルのレコード数をチェック（存在する場合）
USER_COUNT=$(mysql \
    --host="$DB_HOST" \
    --user="$DB_USERNAME" \
    --password="$DB_PASSWORD" \
    --batch \
    --skip-column-names \
    -e "SELECT COUNT(*) FROM users" "$DB_DATABASE" 2>/dev/null)

if [ -n "$USER_COUNT" ]; then
    echo -e "${GREEN}✓ Users table contains $USER_COUNT records${NC}"
fi

echo ""
echo -e "${GREEN}Restore operation completed!${NC}"

exit 0
