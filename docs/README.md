# 家計簿分析ダッシュボード - セットアップガイド

## 📋 概要
2008年からの17年間の家計簿データをReact + Chart.jsで可視化する分析ダッシュボードです。

## 🎯 主な機能

### 基本分析
- ✅ 月次・年次の収支トレンドグラフ
- ✅ カテゴリ別支出の円グラフ
- ✅ 17年間の長期トレンド比較
- ✅ 期間フィルター（全期間/直近12ヶ月/年別）
- ✅ 貯蓄率の自動計算

### 高度な機能（追加可能）
- 🔄 季節性パターン分析
- 📊 予算vs実績比較
- 🎯 目標設定と進捗管理
- 📈 支出予測（機械学習）
- 📤 データエクスポート（CSV/PDF）

## 🚀 クイックスタート

### 1. デモ版を確認
`finance-dashboard.html` をブラウザで開くだけで動作します。
サンプルデータで動作を確認できます。

### 2. 実際のデータベースに接続

#### Lolipopでの設定手順

**A. データベース情報を確認**
1. Lolipopユーザー専用ページにログイン
2. サーバーの管理・設定 → データベース
3. 接続情報をメモ：
   - ホスト名: `mysql000.lolipop.jp`
   - データベース名: `LAA0000000-xxxxxx`
   - ユーザー名: `LAA0000000`
   - パスワード: `********`

**B. APIファイルの設定**
`api/finance-data.php` を編集：

```php
$db_host = 'mysql000.lolipop.jp'; // 実際のホスト名
$db_name = 'LAA0000000-xxxxxx';    // データベース名
$db_user = 'LAA0000000';            // ユーザー名
$db_pass = 'your_password';         // パスワード
```

**C. テーブル構造の確認と調整**

あなたの既存のテーブル構造に合わせてSQLクエリを修正してください。

想定しているテーブル構造例：
```sql
CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    date DATE NOT NULL,
    category VARCHAR(50),
    amount DECIMAL(10, 2),
    type ENUM('income', 'expense'),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

既存のテーブル構造が異なる場合、`finance-data.php` のSQL文を修正してください。

### 3. ファイルのアップロード

Lolipopサーバーに以下の構造でアップロード：

```
public_html/
├── finance/
│   ├── index.html (finance-dashboard.html をリネーム)
│   └── api/
│       └── finance-data.php
```

FTPソフト（FileZilla等）またはLolipopのファイルマネージャーを使用。

### 4. HTMLファイルの修正

`finance-dashboard.html` の以下の部分を有効化：

**現在（デモ用）：**
```javascript
useEffect(() => {
    const sampleData = generateSampleData();
    setData(sampleData);
}, []);
```

**本番用に変更：**
```javascript
useEffect(() => {
    // 月次データ取得
    fetch('/api/finance-data.php?action=monthly')
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                setMonthlyData(result.data);
            }
        });
    
    // カテゴリデータ取得
    fetch('/api/finance-data.php?action=categories')
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                setCategoryData(result.data);
            }
        });
}, []);
```

## 📊 既存システムとの統合

### GitHub リポジトリとの連携
現在運用中の `personal-finance-dashboard-public` との統合方法：

**オプション1: 別システムとして運用**
- 分析専用ダッシュボードとして独立運用
- 既存の入力システムはそのまま使用
- データベースを共有

**オプション2: 統合する**
- 既存システムに分析機能を追加
- React コンポーネントとして組み込み

## 🛠️ カスタマイズ

### カテゴリの変更
`generateSampleData()` 関数内の配列を編集：
```javascript
const categories = ['食費', '住居費', '光熱費', ...];
```

### 色のカスタマイズ
```javascript
backgroundColor: [
    '#FF6384', // 色コードを変更
    '#36A2EB',
    // ...
]
```

### 追加機能の実装

**1. 予算管理機能**
```javascript
const budgets = {
    '食費': 50000,
    '住居費': 100000,
    // ...
};
```

**2. CSVエクスポート**
```javascript
const exportToCSV = () => {
    const csv = filteredData.map(row => 
        `${row.date},${row.income},${row.expense}`
    ).join('\n');
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'finance-data.csv';
    a.click();
};
```

## 🔒 セキュリティ

### 推奨設定
1. **Basic認証の設定**
   - Lolipopの管理画面で .htaccess を設定
   - パスワード保護

2. **APIのセキュリティ**
   ```php
   // IPアドレス制限
   $allowed_ips = ['あなたのIP'];
   if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
       die('Access denied');
   }
   ```

3. **SQLインジェクション対策**
   - プリペアドステートメントを使用（実装済み）

## 📱 レスポンシブ対応
Tailwind CSSでモバイル対応済み：
- スマートフォン
- タブレット
- デスクトップ

## 🐛 トラブルシューティング

### データが表示されない
1. ブラウザの開発者ツール（F12）でエラー確認
2. `api/finance-data.php` に直接アクセスしてJSON確認
3. データベース接続情報を再確認

### チャートが表示されない
1. Chart.js のCDNが読み込めているか確認
2. コンソールエラーを確認

### Lolipopでの動作が遅い
1. データを集約（月次サマリーテーブル作成）
2. インデックスを追加
3. キャッシュを実装

## 📚 技術スタック
- **フロントエンド**: React 18, Chart.js 4, Tailwind CSS
- **バックエンド**: PHP 7.4+, MySQL 5.7+
- **ホスティング**: Lolipop ハイスピードプラン

## 🎨 今後の拡張案
- [ ] PDF/Excelレポート生成
- [ ] メール通知機能
- [ ] 予算アラート
- [ ] 複数アカウント対応
- [ ] AI による支出予測
- [ ] スマホアプリ版

## 💡 質問・サポート
何か問題があれば、以下を確認してください：
1. データベーステーブル構造
2. エラーログ
3. ブラウザコンソール

## 📄 ライセンス
MIT License - ご自由にカスタマイズしてください！
