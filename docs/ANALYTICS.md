# Analytics Dashboard - 高度な分析ダッシュボード

## 概要

Personal Finance Dashboardに統合された高度な分析ダッシュボードです。17年間（2008-2025）のデータを可視化し、トレンド分析、ショップ別・カテゴリ別の内訳、季節性パターンなどを提供します。

## アクセス方法

メインダッシュボード（index.php）のタブメニューから「高度な分析」をクリックしてアクセスできます。

直接URL: `https://your-domain.com/analytics/index.html`

## 主な機能

### 統計サマリー
- 総支出額（17年間）
- 月平均支出
- 総取引数
- 利用店舗数とカテゴリ数

### ビジュアル分析
- **年別推移**: 2008年から現在までの年間支出の棒グラフ
- **月次推移**: 直近12ヶ月の折れ線グラフ
- **ショップ別内訳**: トップ10店舗の円グラフ
- **カテゴリ別内訳**: カテゴリ別支出の横棒グラフ

## API仕様

### エンドポイント

分析ダッシュボードは以下のAPIエンドポイントを使用します：

| エンドポイント | 説明 | パラメータ |
|-------------|------|-----------|
| `?action=summary` | 全期間統計サマリー | start_date, end_date |
| `?action=monthly` | 月次データ | start_date, end_date |
| `?action=yearly` | 年次集計 | - |
| `?action=shop` | ショップ別データ | limit, start_date, end_date |
| `?action=category` | カテゴリ別データ | start_date, end_date |
| `?action=daily` | 日別データ | start_date, end_date |
| `?action=trends` | トレンド分析 | - |
| `?action=stats` | 統計分析（曜日別、季節性） | - |

### 使用例

```javascript
// サマリー取得
fetch('/api/analytics-api.php?action=summary')
    .then(res => res.json())
    .then(data => console.log(data));

// 直近1年の月次データ
const startDate = '2024-01-01';
const endDate = '2024-12-31';
fetch(\`/api/analytics-api.php?action=monthly&start_date=\${startDate}&end_date=\${endDate}\`)
    .then(res => res.json())
    .then(data => console.log(data));
```

## データベース最適化

大量のデータを高速に処理するために、以下の最適化を実施することを推奨します：

### インデックスの追加

`database-optimization.sql`ファイルを実行してください：

```bash
mysql -u your_username -p your_database < database-optimization.sql
```

主な最適化内容：
- `re_date`, `cat_1`, `cat_2`カラムにインデックス追加
- 複合インデックス（date + category）
- 月次サマリービューの作成
- 曜日別・季節性パターンビューの作成

### ビューの活用

最適化SQLは以下のビューを作成します：

- `v_monthly_summary` - 月次集計
- `v_yearly_summary` - 年次集計  
- `v_shop_summary` - ショップ別集計
- `v_category_summary` - カテゴリ別集計
- `v_weekday_stats` - 曜日別統計
- `v_seasonal_pattern` - 季節性パターン

## カスタマイズ

### 色テーマの変更

`analytics/index.html`のCSSセクションで色を変更できます：

```css
.stat-card {
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
    /* お好みのグラデーションに変更 */
}
```

### グラフの追加

新しいチャートコンポーネントを追加する例：

```javascript
const MyCustomChart = ({ data }) => {
    const chartRef = useRef(null);
    // Chart.jsの設定
    return <div className="chart-container">
        <canvas ref={chartRef}></canvas>
    </div>;
};
```

## トラブルシューティング

### データが表示されない

1. APIが正しく動作しているか確認：
   ```
   https://your-domain.com/api/analytics-api.php?action=summary
   ```

2. ブラウザのコンソール（F12）でエラーを確認

3. `.env_db`ファイルのデータベース設定を確認

### パフォーマンスが遅い

1. `database-optimization.sql`を実行してインデックスを追加
2. 期間を限定してデータを取得（デフォルトは全期間）
3. データベースサーバーのリソースを確認

### 白い画面が表示される

1. JavaScriptコンソールでエラーを確認
2. Chart.js CDNが読み込めているか確認
3. React CDNが読み込めているか確認

## 技術スタック

- **フロントエンド**: React 18, Chart.js 4, Bootstrap 5
- **バックエンド**: PHP 7.4+, PDO
- **データベース**: MySQL 5.7+ / MariaDB
- **デザイン**: Bootstrap 5 Dark Theme

## セキュリティ

analytics/ディレクトリへのアクセスを制限する場合：

### Basic認証の追加

`.htaccess`を`analytics/`ディレクトリに配置：

```apache
AuthType Basic
AuthName "Analytics Dashboard"
AuthUserFile /path/to/.htpasswd
Require valid-user
```

### IPアドレス制限

`api/analytics-api.php`に追加：

```php
$allowed_ips = ['your-ip-address'];
if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
    http_response_code(403);
    die('Access denied');
}
```

## 今後の拡張案

- PDF/Excelレポート生成
- 予算vs実績比較
- カスタムダッシュボード（ウィジェット配置）
- メール通知機能
- モバイルアプリ版

## サポート

問題が発生した場合は、GitHubのIssuesで報告してください。
