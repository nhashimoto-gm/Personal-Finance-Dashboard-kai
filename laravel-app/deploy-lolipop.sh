#!/bin/bash
#
# ロリポップデプロイスクリプト
#
# SSH接続後に実行してください:
# bash deploy-lolipop.sh
#

echo "=========================================="
echo "Personal Finance Dashboard - ロリポップデプロイ"
echo "=========================================="
echo ""

# カレントディレクトリの確認
if [ ! -f "artisan" ]; then
    echo "エラー: laravel-appディレクトリで実行してください"
    exit 1
fi

# Composerの確認
if ! command -v composer &> /dev/null; then
    echo "エラー: Composerがインストールされていません"
    echo "以下のコマンドでインストールしてください:"
    echo ""
    echo "  curl -sS https://getcomposer.org/installer | php"
    echo "  mkdir -p ~/bin"
    echo "  mv composer.phar ~/bin/composer"
    echo "  chmod +x ~/bin/composer"
    echo "  echo 'export PATH=\"\$HOME/bin:\$PATH\"' >> ~/.bashrc"
    echo "  source ~/.bashrc"
    echo ""
    exit 1
fi

echo "Step 1: Composerパッケージのインストール..."
composer install --optimize-autoloader --no-dev

if [ $? -ne 0 ]; then
    echo "エラー: Composerのインストールに失敗しました"
    exit 1
fi

echo ""
echo "Step 2: .envファイルの確認..."
if [ ! -f ".env" ]; then
    echo ".envファイルが見つかりません"
    echo ".env.lolipop をコピーして作成します..."
    cp .env.lolipop .env
    echo ""
    echo "重要: .envファイルを編集してデータベース情報を設定してください:"
    echo "  nano .env"
    echo ""
    read -p ".envファイルを編集しましたか? (y/n): " -n 1 -r
    echo ""
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "デプロイを中止します。.envファイルを編集してから再実行してください。"
        exit 1
    fi
fi

echo ""
echo "Step 3: アプリケーションキーの生成..."
php artisan key:generate

echo ""
echo "Step 4: ストレージリンクの作成..."
php artisan storage:link

echo ""
echo "Step 5: パーミッションの設定..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache

echo ""
echo "Step 6: キャッシュの生成..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo "Step 7: Composerの最適化..."
composer dump-autoload --optimize

echo ""
echo "=========================================="
echo "デプロイ完了！"
echo "=========================================="
echo ""
echo "次の手順:"
echo "1. ブラウザでサイトにアクセスして動作確認"
echo "2. データベースのマイグレーション（必要に応じて）:"
echo "   php artisan migrate"
echo ""
echo "トラブルシューティング:"
echo "- ログの確認: tail -f storage/logs/laravel.log"
echo "- キャッシュのクリア: php artisan cache:clear"
echo ""
