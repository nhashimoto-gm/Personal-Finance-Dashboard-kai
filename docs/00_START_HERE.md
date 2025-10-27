# 家計簿分析ダッシュボード - 完全パッケージ

## 📦 パッケージ内容

このパッケージには、既存の`personal-finance-dashboard-public`システムに統合できる、
React + Chart.js による高度な分析ダッシュボードが含まれています。

## 🗂️ ファイル構成

### 📄 メインダッシュボード
```
dashboard.html                    ★推奨★ 最新版・フル機能
├─ 17年間のデータ可視化
├─ 4つのタブ（概要/トレンド/内訳/分析）
├─ 移動平均トレンド分析
├─ 曜日別・季節性パターン分析
└─ デモモード/本番モード切替可能
```

### 🎨 その他のダッシュボード
```
finance-dashboard.html           基本版（シンプル）
advanced-dashboard.html          高度分析版（予測機能付き）
```

### 🔌 API（バックエンド）
```
api/
├── analytics-api.php            ★メインAPI★ 新規実装
│   ├─ 8つのエンドポイント
│   ├─ .env_db 完全対応
│   └─ 既存DB構造対応
│
├── analytics-integration.php    既存queries.php統合版
│   ├─ 既存関数を活用
│   └─ 拡張分析機能
│
└── finance-data.php             汎用API（参考）
```

### 📚 ドキュメント
```
README_ANALYTICS.md              ★詳細ガイド★ 必読
├─ セットアップ手順
├─ 既存システムとの統合方法
├─ カスタマイズガイド
└─ トラブルシューティング

QUICKSTART.md                    3ステップで開始
├─ 1分でセットアップ
└─ チェックリスト付き

README.md                        基本説明
```

### 🗄️ データベース
```
database-schema.sql              サンプルスキーマ（参考）
optimization.sql                 ★パフォーマンス最適化★
├─ インデックス追加
├─ 便利なビュー定義
├─ キャッシュテーブル
└─ 高速化クエリ集
```

## 🚀 今すぐ始める（3ステップ）

### ステップ1: ファイルを配置
```bash
# 既存プロジェクトに移動
cd /path/to/personal-finance-dashboard-public

# ファイルをコピー
cp dashboard.html analytics/index.html
cp api/analytics-api.php api/
```

### ステップ2: 動作確認（デモモード）
```
ブラウザで analytics/index.html を開く
→ サンプルデータで動作確認
```

### ステップ3: 本番モードに切替
```javascript
// analytics/index.html の24行目
const USE_DEMO_DATA = false;  // true → false
```

## 📊 主な機能

### 基本機能
✅ 17年間（2008-2025）のデータ可視化
✅ 月次・年次の収支トレンド
✅ ショップ別・カテゴリ別分析
✅ 期間フィルター（全期間/直近12ヶ月/年別）
✅ レスポンシブデザイン（スマホ対応）

### 高度な分析
✅ 6ヶ月移動平均トレンド
✅ 曜日別平均支出分析
✅ 月別季節性パターン検出
✅ 自動インサイト生成
✅ トップショップ・カテゴリランキング

### パフォーマンス
✅ データベースインデックス最適化
✅ ビューによる高速クエリ
✅ キャッシュテーブル対応
✅ 非同期データ読み込み

## 🎯 統合オプション

### オプションA: 別ページとして運用（推奨）
```
既存: https://your-domain.com/finance/
新規: https://your-domain.com/finance/analytics/
```
**メリット**: 既存システムに影響なし、安全

### オプションB: メニューに統合
```html
<!-- 既存index.phpに追加 -->
<nav>
    <a href="/">入力</a>
    <a href="/analytics/">📊 分析</a>
</nav>
```

### オプションC: タブとして統合
```javascript
// iframeで埋め込み
<iframe src="/analytics/" width="100%" height="800px"></iframe>
```

## 🔧 カスタマイズ例

### 色テーマ変更
```css
.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    /* お好みのカラーに変更 */
}
```

### 統計カード追加
```javascript
<div className="stat-card">
    <div className="stat-label">新しい指標</div>
    <div className="stat-value">¥XXX</div>
</div>
```

### 新しいグラフ追加
```javascript
const MyChart = ({ data }) => {
    // Chart.jsコンポーネント
};
```

## 📈 APIエンドポイント一覧

| エンドポイント | 説明 |
|---|---|
| `?action=summary` | 全期間統計 |
| `?action=monthly` | 月次データ |
| `?action=yearly` | 年次データ |
| `?action=shop` | ショップ別 |
| `?action=category` | カテゴリ別 |
| `?action=daily` | 日別データ |
| `?action=trends` | トレンド分析 |
| `?action=stats` | 統計分析 |

## 🔒 セキュリティ

### Basic認証（推奨）
```apache
# .htaccess
AuthType Basic
AuthName "Analytics"
AuthUserFile /path/.htpasswd
Require valid-user
```

### IPアドレス制限
```php
$allowed_ips = ['your-ip'];
if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
    die('Access denied');
}
```

## 🐛 よくある問題

### データが表示されない
→ `USE_DEMO_DATA = true` でデモモード確認
→ APIのURL確認: `API_BASE_URL`
→ ブラウザコンソール（F12）でエラー確認

### 白い画面
→ JavaScriptエラーを確認（F12 → Console）
→ Chart.js CDNが読み込めているか確認

### 遅い
→ `optimization.sql` でインデックス追加
→ 期間を限定してデータ取得
→ キャッシュテーブル使用

## 📱 動作環境

- **ブラウザ**: Chrome, Firefox, Safari, Edge（最新版）
- **サーバー**: PHP 7.4+, MySQL 5.7+
- **ホスティング**: Lolipop ハイスピードプラン対応
- **デバイス**: PC, タブレット, スマートフォン

## 📚 推奨読む順序

1. **QUICKSTART.md** - まず3分で開始
2. **README_ANALYTICS.md** - 詳細を理解
3. **optimization.sql** - パフォーマンス向上
4. **dashboard.html** - コードをカスタマイズ

## 🎨 デモサイト

デモモード（`USE_DEMO_DATA = true`）で、
実際のデータなしで動作を確認できます。

## 💡 次のステップ

1. ✅ デモモードで動作確認
2. ✅ 本番データベースに接続
3. ✅ カラーテーマをカスタマイズ
4. ✅ Basic認証を設定
5. ✅ 既存システムにリンク追加

## 🆘 サポート

問題が発生した場合：
1. QUICKSTART.md のトラブルシューティング確認
2. README_ANALYTICS.md の詳細ガイド参照
3. ブラウザのコンソールでエラー確認

## 📄 ライセンス

MIT License - 自由にカスタマイズ・商用利用可能

## 🎉 完成！

このパッケージで17年分のデータを美しく可視化できます。
まずはQUICKSTART.mdから始めてください！

---

作成日: 2025-10-27
バージョン: 1.0
互換性: personal-finance-dashboard-public
