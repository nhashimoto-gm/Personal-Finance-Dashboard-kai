#!/bin/bash

# ============================================================
# リポジトリ移行スクリプト
# Personal Finance Dashboard を新しいパブリックリポジトリに移行
# ============================================================

set -e  # エラーが発生したら停止

echo "=================================================="
echo "リポジトリ移行スクリプト"
echo "=================================================="
echo ""

# 色の定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 新しいリポジトリのURLを入力
echo -e "${YELLOW}新しいリポジトリのURLを入力してください（例: https://github.com/username/new-repo.git）${NC}"
read -p "URL: " NEW_REPO_URL

if [ -z "$NEW_REPO_URL" ]; then
    echo -e "${RED}エラー: リポジトリURLが入力されていません${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}新しいリポジトリURL: $NEW_REPO_URL${NC}"
echo ""

# Gitユーザー情報を入力
echo -e "${YELLOW}Gitコミット用のユーザー情報を入力してください${NC}"
read -p "名前 (例: Your Name): " GIT_USER_NAME
read -p "メールアドレス (例: you@example.com): " GIT_USER_EMAIL

if [ -z "$GIT_USER_NAME" ] || [ -z "$GIT_USER_EMAIL" ]; then
    echo -e "${RED}エラー: 名前とメールアドレスの両方を入力してください${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}Git ユーザー名: $GIT_USER_NAME${NC}"
echo -e "${GREEN}Git メールアドレス: $GIT_USER_EMAIL${NC}"
echo ""

# 確認
echo -e "${YELLOW}警告: このスクリプトは以下の操作を行います:${NC}"
echo "  1. 現在のプロジェクトをバックアップ"
echo "  2. .git ディレクトリを削除（開発履歴を初期化）"
echo "  3. 新しいgitリポジトリとして初期化"
echo "  4. すべてのファイルを新しいリポジトリにコミット"
echo "  5. 新しいリモートリポジトリにプッシュ"
echo ""
read -p "続行しますか？ (yes/no): " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo -e "${RED}キャンセルされました${NC}"
    exit 0
fi

# 現在のディレクトリを保存
CURRENT_DIR=$(pwd)
PROJECT_NAME=$(basename "$CURRENT_DIR")
BACKUP_DIR="${CURRENT_DIR}_backup_$(date +%Y%m%d_%H%M%S)"

# ステップ1: バックアップ
echo ""
echo -e "${GREEN}ステップ 1/5: プロジェクトをバックアップしています...${NC}"
cd ..
cp -r "$PROJECT_NAME" "$BACKUP_DIR"
echo -e "${GREEN}✓ バックアップ完了: $BACKUP_DIR${NC}"

# ステップ2: .git ディレクトリを削除
echo ""
echo -e "${GREEN}ステップ 2/5: .git ディレクトリを削除しています...${NC}"
cd "$CURRENT_DIR"
rm -rf .git
echo -e "${GREEN}✓ .git ディレクトリを削除しました${NC}"

# ステップ3: 新しいgitリポジトリとして初期化
echo ""
echo -e "${GREEN}ステップ 3/5: 新しいgitリポジトリとして初期化しています...${NC}"
git init
# ローカルリポジトリ用にGitユーザー情報を設定
git config user.name "$GIT_USER_NAME"
git config user.email "$GIT_USER_EMAIL"
echo -e "${GREEN}✓ gitリポジトリを初期化しました${NC}"

# ステップ4: すべてのファイルを追加してコミット
echo ""
echo -e "${GREEN}ステップ 4/5: ファイルを追加してコミットしています...${NC}"
git add .
git commit -m "Initial commit: Personal Finance Dashboard

Personal Finance Dashboard - A web-based application for tracking income and expenses

Features:
- Transaction management (income/expense tracking)
- Category-based organization
- Monthly/yearly summaries with charts
- CSV export functionality
- Responsive design for mobile and desktop

Tech Stack:
- Frontend: HTML, CSS, JavaScript, Chart.js
- Backend: PHP
- Database: MySQL
- Server: Apache"

# mainブランチにリネーム
git branch -M main
echo -e "${GREEN}✓ 初期コミットを作成しました${NC}"

# ステップ5: リモートリポジトリに接続してプッシュ
echo ""
echo -e "${GREEN}ステップ 5/5: 新しいリモートリポジトリにプッシュしています...${NC}"
git remote add origin "$NEW_REPO_URL"

# プッシュを試行（最大5回リトライ）
MAX_RETRIES=5
RETRY_COUNT=0
WAIT_TIME=2

while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
    if git push -u origin main; then
        echo -e "${GREEN}✓ プッシュに成功しました${NC}"
        break
    else
        RETRY_COUNT=$((RETRY_COUNT + 1))
        if [ $RETRY_COUNT -lt $MAX_RETRIES ]; then
            echo -e "${YELLOW}プッシュに失敗しました。${WAIT_TIME}秒後にリトライします... (${RETRY_COUNT}/${MAX_RETRIES})${NC}"
            sleep $WAIT_TIME
            WAIT_TIME=$((WAIT_TIME * 2))
        else
            echo -e "${RED}エラー: プッシュに失敗しました。以下を確認してください:${NC}"
            echo "  - 新しいリポジトリが正しく作成されているか"
            echo "  - リポジトリURLが正しいか"
            echo "  - GitHubへの認証が設定されているか"
            echo ""
            echo -e "${YELLOW}手動でプッシュする場合:${NC}"
            echo "  git push -u origin main"
            exit 1
        fi
    fi
done

# 完了メッセージ
echo ""
echo "=================================================="
echo -e "${GREEN}移行が完了しました！${NC}"
echo "=================================================="
echo ""
echo "新しいリポジトリ: $NEW_REPO_URL"
echo "バックアップ: $BACKUP_DIR"
echo ""
echo -e "${YELLOW}次のステップ:${NC}"
echo "1. GitHubで新しいリポジトリがパブリックになっていることを確認"
echo "2. README.md などを必要に応じて更新"
echo "3. バックアップが不要になったら削除: rm -rf $BACKUP_DIR"
echo ""
