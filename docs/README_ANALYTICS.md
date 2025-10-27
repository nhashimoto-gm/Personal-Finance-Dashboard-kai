# 家計簿分析ダッシュボード - 統合ガイド

## 📋 概要
既存の`personal-finance-dashboard-public`システムと完全互換性のある、React + Chart.jsによる高度な分析ダッシュボードです。

## 🎯 特徴

### 既存システムとの互換性
- ✅ 同じデータベース構造を使用
- ✅ 既存の`.env_db`設定ファイルをそのまま利用
- ✅ `source`、`cat_1_labels`、`cat_2_labels`テーブルに対応
- ✅ `view1`ビューを活用
- ✅ 既存のクエリ関数（`queries.php`）と互換性あり

### 新機能
- 📊 17年間のデータを可視化
- 📈 移動平均によるトレンド分析
- 🥧 ショップ別・カテゴリ別の詳細分析
- 📅 曜日別・月別の季節性パターン分析
- 💡 自動インサイト生成
- 📱 完全レスポンシブデザイン

## 🚀 セットアップ手順

### ステップ1: ファイル配置

既存のプロジェクト構造に以下のファイルを追加：

```
personal-finance-dashboard-public/
├── .env_db                          # 既存（そのまま使用）
├── database.sql                     # 既存
├── queries.php                      # 既存
├── api/
│   └── analytics-api.php           # ★新規追加
├── analytics/
│   └── index.html                  # ★新規追加（dashboard.htmlをリネーム）
└── README_ANALYTICS.md             # このファイル
```

### ステップ2: 設定ファイル確認

既存の`.env_db`ファイルがそのまま使えます：

```ini
; Application environment: development or production
APP_ENV=development

; Database configuration
DB_HOST=mysql000.lolipop.jp          ; Lolipopのホスト
DB_USERNAME=LAA0000000               ; ユーザー名
DB_PASSWORD=your_password            ; パスワード
DB_DATABASE=LAA0000000-xxxxxx        ; データベース名

; Database table names - 既存の設定をそのまま使用
DB_TABLE_SOURCE=source
DB_TABLE_SHOP=cat_1_labels
DB_TABLE_CATEGORY=cat_2_labels
DB_VIEW_MAIN=view1
DB_TABLE_BUDGETS=budgets
```

### ステップ3: APIファイルの設置

1. `analytics-api.php`を`api/`ディレクトリに配置
2. `.env_db`ファイルのパスを確認（同階層にあるか確認）

```php
// analytics-api.php 内で自動的に.env_dbを読み込みます
function loadEnv($file = '.env_db') {
    if (!file_exists($file)) {
        $file = '../.env_db';  // 必要に応じてパスを調整
    }
    return parse_ini_file($file);
}
```

### ステップ4: HTMLファイルの設定

`dashboard.html`を`analytics/index.html`として配置し、以下を編集：

**デモモードから本番モードへ切り替え：**

```javascript
// 行24付近
const API_BASE_URL = '/api/analytics-api.php';  // APIのパスを確認
const USE_DEMO_DATA = false;  // false に変更して実データを使用
```

### ステップ5: アップロード（Lolipopサーバー）

FTPまたはファイルマネージャーで以下をアップロード：

```
public_html/
└── finance/                    # 既存ディレクトリ
    ├── .env_db                # 既存
    ├── api/
    │   └── analytics-api.php  # 新規追加
    └── analytics/
        └── index.html         # 新規追加
```

### ステップ6: アクセス

ブラウザで以下にアクセス：
```
https://your-domain.com/finance/analytics/
```

## 📊 API エンドポイント

### 利用可能なエンドポイント

| エンドポイント | 説明 | パラメータ |
|---|---|---|
| `?action=summary` | 全期間の統計サマリー | `start_date`, `end_date` |
| `?action=monthly` | 月次データ | `start_date`, `end_date` |
| `?action=yearly` | 年次データ | - |
| `?action=shop` | ショップ別データ | `limit`, `start_date`, `end_date` |
| `?action=category` | カテゴリ別データ | `start_date`, `end_date` |
| `?action=daily` | 日別データ | `start_date`, `end_date` |
| `?action=trends` | トレンド分析 | - |
| `?action=stats` | 統計分析 | - |

### 使用例

```javascript
// サマリー取得
fetch('/api/analytics-api.php?action=summary')
    .then(res => res.json())
    .then(data => console.log(data));

// 2024年の月次データ取得
fetch('/api/analytics-api.php?action=monthly&start_date=2024-01-01&end_date=2024-12-31')
    .then(res => res.json())
    .then(data => console.log(data));

// トップ10ショップ取得
fetch('/api/analytics-api.php?action=shop&limit=10')
    .then(res => res.json())
    .then(data => console.log(data));
```

## 🎨 カスタマイズ

### カラーテーマの変更

`dashboard.html`のCSSセクション：

```css
/* メインカラー変更 */
.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    /* お好みのグラデーションに変更 */
}

body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    /* 背景色を変更 */
}
```

### グラフの色変更

```javascript
// ショップ別グラフの色
backgroundColor: [
    '#FF6384', '#36A2EB', '#FFCE56', // お好みの色に変更
    // ...
]
```

### 表示する統計カードの追加

```javascript
// OverviewTab コンポーネント内
<div className="stat-card">
    <div className="stat-label">新しい統計</div>
    <div className="stat-value">¥XXX</div>
    <div className="stat-label">説明</div>
</div>
```

## 🔧 既存システムとの統合オプション

### オプション1: 別ページとして運用（推奨）

- 既存ダッシュボード: `https://your-domain.com/finance/`
- 分析ダッシュボード: `https://your-domain.com/finance/analytics/`
- メリット: 既存システムに影響なし

### オプション2: 既存システムに統合

既存の`index.php`に分析ページへのリンクを追加：

```html
<nav>
    <a href="/">ホーム</a>
    <a href="/analytics/">📊 詳細分析</a>  <!-- 追加 -->
</nav>
```

### オプション3: タブとして統合

既存システムのタブに分析機能を追加：

```javascript
// 既存のタブに追加
<button onclick="loadAnalytics()">📊 分析</button>

function loadAnalytics() {
    // iframeで読み込み
    document.getElementById('content').innerHTML = 
        '<iframe src="/analytics/" width="100%" height="800px"></iframe>';
}
```

## 📱 レスポンシブ対応

- スマートフォン（320px〜）
- タブレット（768px〜）
- デスクトップ（1024px〜）

すべてのデバイスで最適化された表示。

## 🔒 セキュリティ

### Basic認証の追加（推奨）

`.htaccess`を`analytics/`ディレクトリに配置：

```apache
AuthType Basic
AuthName "Analytics Dashboard"
AuthUserFile /home/your-path/.htpasswd
Require valid-user
```

パスワードファイル生成：
```bash
htpasswd -c .htpasswd username
```

### IPアドレス制限

`analytics-api.php`に追加：

```php
// 先頭に追加
$allowed_ips = ['あなたのIPアドレス'];
if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
    http_response_code(403);
    die('Access denied');
}
```

## 🐛 トラブルシューティング

### データが表示されない

1. **APIレスポンスを確認**
   ```
   https://your-domain.com/api/analytics-api.php?action=summary
   ```
   ブラウザで直接アクセスしてJSONが返ることを確認

2. **ブラウザのコンソールを確認**
   - F12キーで開発者ツールを開く
   - Consoleタブでエラーメッセージを確認

3. **データベース接続を確認**
   ```php
   // analytics-api.phpに一時的に追加してテスト
   var_dump($config);
   var_dump($pdo);
   ```

### グラフが表示されない

1. **Chart.jsのCDNが読み込めているか確認**
   - ブラウザのネットワークタブでChart.jsの読み込みを確認

2. **コンソールエラーを確認**
   - JavaScriptエラーがないか確認

### Lolipopでの動作が遅い

1. **データベースのインデックスを確認**
   ```sql
   SHOW INDEX FROM source;
   ```

2. **期間を限定してクエリ**
   ```javascript
   // 直近2年のみ取得
   fetch('/api/analytics-api.php?action=monthly&start_date=2023-01-01')
   ```

3. **月次サマリーテーブルの活用**（オプション）
   ```sql
   -- 集計テーブルを作成してパフォーマンス向上
   CREATE TABLE monthly_summary AS
   SELECT 
       DATE_FORMAT(re_date, '%Y-%m') as month,
       SUM(price) as total,
       COUNT(*) as count
   FROM source
   GROUP BY DATE_FORMAT(re_date, '%Y-%m');
   ```

## 📈 パフォーマンス最適化

### 大量データの場合

1. **ページネーション実装**
   ```php
   // analytics-api.php に追加
   $page = (int)($_GET['page'] ?? 1);
   $per_page = 100;
   $offset = ($page - 1) * $per_page;
   
   $sql .= " LIMIT $per_page OFFSET $offset";
   ```

2. **キャッシング実装**
   ```php
   // 日次統計をキャッシュ
   $cache_file = "cache/summary_" . date('Y-m-d') . ".json";
   if (file_exists($cache_file)) {
       echo file_get_contents($cache_file);
       exit;
   }
   ```

3. **非同期データ読み込み**
   ```javascript
   // 必要なデータから順次読み込み
   useEffect(() => {
       loadSummary();    // 最初
       loadMonthly();    // 次
       loadShops();      // 最後
   }, []);
   ```

## 📚 技術スタック

- **フロントエンド**: React 18, Chart.js 4, Tailwind CSS
- **バックエンド**: PHP 7.4+, PDO
- **データベース**: MySQL 5.7+ / MariaDB
- **ホスティング**: Lolipop ハイスピードプラン対応

## 🎓 開発者向け情報

### コンポーネント構造

```
FinanceAnalyticsDashboard (メイン)
├── OverviewTab
│   ├── YearlyTrendChart
│   └── RecentMonthsChart
├── TrendsTab
│   └── MonthlyTrendChart
├── BreakdownTab
│   ├── ShopBreakdownCard
│   └── CategoryBreakdownCard
└── InsightsTab
    ├── InsightCards
    ├── WeekdayAnalysis
    └── SeasonalAnalysis
```

### 新しいタブの追加方法

```javascript
// 1. タブボタンを追加
<button className={`tab-btn ${activeTab === 'newtab' ? 'active' : ''}`}
    onClick={() => setActiveTab('newtab')}>🆕 新機能</button>

// 2. タブコンテンツを追加
{activeTab === 'newtab' && <NewTab data={data} />}

// 3. コンポーネントを定義
const NewTab = ({ data }) => {
    return (
        <div className="card">
            <h3>新しい機能</h3>
            {/* コンテンツ */}
        </div>
    );
};
```

## 💡 今後の拡張案

- [ ] PDF/Excelレポート生成
- [ ] 予算vs実績の比較機能
- [ ] カスタムダッシュボード（ウィジェット配置）
- [ ] データのインポート/エクスポート機能
- [ ] モバイルアプリ版
- [ ] リアルタイム通知
- [ ] 複数アカウント対応
- [ ] AI支出予測

## 📞 サポート

質問や問題がある場合：
1. ブラウザのコンソールでエラーを確認
2. APIレスポンスを直接確認
3. データベース接続情報を再確認

## 📄 ライセンス

MIT License - ご自由にカスタマイズしてください
