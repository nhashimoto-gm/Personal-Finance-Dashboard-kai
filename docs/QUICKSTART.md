# 🚀 クイックスタートガイド

## 3ステップで始める

### ステップ1: ファイルをコピー（1分）

```bash
# 既存プロジェクトに移動
cd /path/to/personal-finance-dashboard-public

# 新しいディレクトリ作成
mkdir -p analytics api

# ファイルをコピー
cp /path/to/dashboard.html analytics/index.html
cp /path/to/analytics-api.php api/
```

### ステップ2: 設定を確認（1分）

`.env_db`ファイルが既にあるので、何も変更不要！

### ステップ3: デモモードで動作確認（1分）

1. `analytics/index.html`をブラウザで開く
2. デモデータで動作を確認
3. 問題なければ本番モードに切り替え：

```javascript
// analytics/index.html の24行目付近
const USE_DEMO_DATA = false;  // true → false に変更
```

## ✅ チェックリスト

- [ ] `analytics/index.html` を配置
- [ ] `api/analytics-api.php` を配置
- [ ] `.env_db` の接続情報が正しい
- [ ] ブラウザでアクセスして動作確認
- [ ] デモモードから本番モードに切り替え

## 📂 最終的なファイル構造

```
personal-finance-dashboard-public/
├── .env_db                    ✅ 既存（そのまま）
├── database.sql               ✅ 既存
├── queries.php                ✅ 既存
├── index.php                  ✅ 既存
├── api/
│   └── analytics-api.php     🆕 追加
└── analytics/
    └── index.html            🆕 追加
```

## 🌐 アクセスURL

**ローカル開発:**
```
http://localhost/finance/analytics/
```

**Lolipopサーバー:**
```
https://your-domain.com/finance/analytics/
```

## 🎯 次にやること

1. **セキュリティ設定**
   - Basic認証を追加
   - IPアドレス制限

2. **カスタマイズ**
   - 色を好みに変更
   - 表示する統計を調整

3. **既存システムと連携**
   - メニューにリンク追加
   - データを共有

## 💬 よくある質問

**Q: 既存のダッシュボードに影響はありますか？**
A: ありません。完全に独立して動作します。

**Q: データベースを変更する必要はありますか？**
A: いいえ。既存のテーブルをそのまま使用します。

**Q: デモデータはどこから来ていますか？**
A: `dashboard.html`内のJavaScriptで自動生成されています。

**Q: 本番モードに切り替えるとエラーが出ます**
A: APIのパスを確認してください。`API_BASE_URL`が正しいか確認。

## 🔍 トラブルシューティング

### データが表示されない
```bash
# APIが動作しているか確認
curl https://your-domain.com/api/analytics-api.php?action=summary
```

### 白い画面が表示される
```
F12 → Console でエラーを確認
```

### MySQLエラーが出る
```php
// .env_db の設定を確認
DB_HOST=mysql000.lolipop.jp
DB_USERNAME=LAA0000000
DB_PASSWORD=your_password
DB_DATABASE=LAA0000000-xxxxxx
```

## 📱 スマホでアクセス

QRコードを生成してスマホからアクセス：
```
https://www.qr-code-generator.com/
→ URLを入力してQRコード生成
```

## 🎉 完了！

これで17年分のデータを美しく可視化できます！
