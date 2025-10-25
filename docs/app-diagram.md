# 個人家計簿ダッシュボード - アプリケーション構造図

## アプリケーション全体構造

```mermaid
graph TB
    A[index.php<br/>メインエントリーポイント] --> B[view.php<br/>メインビューテンプレート]

    B --> C[ナビゲーションバー]
    B --> D[メッセージアラート]
    B --> E[4つのタブインターフェース]
    B --> F[編集モーダル]

    C --> C1[ロゴ/タイトル]
    C --> C2[言語切替<br/>日本語/英語]
    C --> C3[テーマ切替<br/>ダーク/ライト]

    E --> G[Tab 1: ダッシュボード]
    E --> H[Tab 2: データ入力]
    E --> I[Tab 3: 予算管理]
    E --> J[Tab 4: マスタ管理]

    style A fill:#e1f5ff
    style B fill:#fff3e0
    style E fill:#f3e5f5
    style G fill:#c8e6c9
    style H fill:#ffccbc
    style I fill:#b3e5fc
    style J fill:#f8bbd0
```

## Tab 1: ダッシュボード画面 (dashboard.php)

```mermaid
graph TB
    D[ダッシュボード] --> D1[期間選択セクション]
    D --> D2[サマリーカード]
    D --> D3[予算進捗バー]
    D --> D4[5つのチャート]
    D --> D5[取引履歴テーブル]
    D --> D6[検索結果セクション]

    D1 --> D1A[開始日/終了日<br/>日付選択]
    D1 --> D1B[リセットボタン]
    D1 --> D1C[エクスポートボタン]

    D2 --> D2A[総支出額]
    D2 --> D2B[1日平均額]
    D2 --> D2C[レコード数]
    D2 --> D2D[店舗数]

    D3 --> D3A[予算達成率表示]
    D3 --> D3B[残高/超過額表示]
    D3 --> D3C[アラート<br/>緑80%未満/黄80-99%/赤100%以上]

    D4 --> D4A[店舗別支出<br/>円グラフ]
    D4 --> D4B[カテゴリTop10<br/>棒グラフ]
    D4 --> D4C[日別支出推移<br/>折れ線グラフ]
    D4 --> D4D[累積支出<br/>折れ線グラフ]
    D4 --> D4E[期間比較<br/>積み上げ棒グラフ<br/>12ヶ月/2年/5年/10年]

    D5 --> D5A[日付/金額/店舗/カテゴリ]
    D5 --> D5B[編集ボタン]
    D5 --> D5C[削除ボタン]
    D5 --> D5D[表示件数選択<br/>20/100/500/1000件]

    D6 --> D6A[店舗フィルター<br/>クリック可能バッジ]
    D6 --> D6B[カテゴリフィルター<br/>クリック可能バッジ]
    D6 --> D6C[アクティブフィルター表示]

    style D fill:#c8e6c9
    style D4 fill:#fff9c4
    style D5 fill:#e1bee7
```

## Tab 2: データ入力画面 (entry.php)

```mermaid
graph TB
    E[データ入力] --> E1[取引入力フォーム]
    E --> E2[入力ガイドカード]

    E1 --> E1A[日付入力<br/>HTML5 dateピッカー]
    E1 --> E1B[金額入力<br/>通貨フォーマット]
    E1 --> E1C[店舗選択<br/>ドロップダウン]
    E1 --> E1D[カテゴリ選択<br/>ドロップダウン]
    E1 --> E1E[登録ボタン]

    E2 --> E2A[入力バリデーション<br/>ヒント表示]

    style E fill:#ffccbc
    style E1 fill:#fff3e0
```

## Tab 3: 予算管理画面 (budget.php)

```mermaid
graph TB
    B[予算管理] --> B1[予算入力フォーム]
    B --> B2[予算一覧テーブル]

    B1 --> B1A[年入力<br/>2000-2100]
    B1 --> B1B[月入力<br/>1-12]
    B1 --> B1C[予算額入力]
    B1 --> B1D[予算タイプ選択<br/>月次]
    B1 --> B1E[設定ボタン]

    B2 --> B2A[年月表示]
    B2 --> B2B[予算額表示]
    B2 --> B2C[削除ボタン]

    style B fill:#b3e5fc
    style B1 fill:#e1f5fe
```

## Tab 4: マスタ管理画面 (management.php)

```mermaid
graph TB
    M[マスタ管理] --> M1[店舗管理カード]
    M --> M2[カテゴリ管理カード]

    M1 --> M1A[追加ボタン<br/>プロンプト入力]
    M1 --> M1B[登録済み店舗一覧]

    M2 --> M2A[追加ボタン<br/>プロンプト入力]
    M2 --> M2B[登録済みカテゴリ一覧]

    style M fill:#f8bbd0
    style M1 fill:#fce4ec
    style M2 fill:#fce4ec
```

## 編集モーダル

```mermaid
graph TB
    Modal[編集モーダル] --> Modal1[取引情報フォーム]

    Modal1 --> Modal1A[日付フィールド]
    Modal1 --> Modal1B[金額フィールド]
    Modal1 --> Modal1C[店舗選択]
    Modal1 --> Modal1D[カテゴリ選択]
    Modal1 --> Modal1E[更新ボタン]
    Modal1 --> Modal1F[キャンセルボタン]

    style Modal fill:#d1c4e9
```

## バックエンド処理フロー

```mermaid
graph LR
    UI[ユーザーインターフェース] --> Action[POSTアクション]

    Action --> A1[add_transaction]
    Action --> A2[add_shop]
    Action --> A3[add_category]
    Action --> A4[update_transaction]
    Action --> A5[delete_transaction]
    Action --> A6[set_budget]
    Action --> A7[delete_budget]

    A1 --> F[functions.php<br/>ビジネスロジック]
    A2 --> F
    A3 --> F
    A4 --> F
    A5 --> F
    A6 --> F
    A7 --> F

    F --> DB[(MySQL/MariaDB<br/>データベース)]

    UI --> Q[GETパラメータ<br/>検索/フィルター]
    Q --> Queries[queries.php<br/>データ取得]
    Queries --> DB

    DB --> View[view.php<br/>表示更新]

    style UI fill:#e1f5ff
    style F fill:#fff3e0
    style Queries fill:#c8e6c9
    style DB fill:#ffccbc
```

## データフロー

```mermaid
graph TB
    User[ユーザー] --> Input[データ入力/操作]

    Input --> CSRF[CSRFトークン検証]
    CSRF --> RateLimit[レート制限チェック]
    RateLimit --> Validate[入力バリデーション]
    Validate --> Process[データ処理]

    Process --> CRUD[CRUD操作]
    CRUD --> DB[(データベース)]

    DB --> Query[データ取得]
    Query --> Charts[チャート生成<br/>Highcharts]
    Query --> Tables[テーブル表示]
    Query --> Stats[統計計算]

    Charts --> Display[画面表示]
    Tables --> Display
    Stats --> Display

    Display --> User

    style Input fill:#e1f5ff
    style Process fill:#fff3e0
    style DB fill:#ffccbc
    style Display fill:#c8e6c9
```

## 主要機能一覧

### データ管理機能
- ✅ 取引データの追加・編集・削除
- ✅ 店舗マスタの管理
- ✅ カテゴリマスタの管理
- ✅ CSV インポート/エクスポート

### 分析・可視化機能
- ✅ 期間別サマリー統計
- ✅ 店舗別支出分析（円グラフ）
- ✅ カテゴリ別分析（棒グラフ）
- ✅ 日別支出推移（折れ線グラフ）
- ✅ 期間比較分析（12ヶ月/2年/5年/10年）
- ✅ 予算vs実績トラッキング

### 予算管理機能
- ✅ 月次予算設定
- ✅ リアルタイム予算進捗追跡
- ✅ 3段階アラートシステム（緑/黄/赤）
- ✅ 残高・達成率表示

### フィルター・検索機能
- ✅ 日付範囲フィルター
- ✅ 店舗別フィルター
- ✅ カテゴリ別フィルター
- ✅ 表示件数選択（20/100/500/1000件）

### 多言語・テーマ機能
- ✅ 日本語/英語切替
- ✅ ダーク/ライトモード
- ✅ チャートの自動色調整

## 技術スタック

| レイヤー | 技術 |
|---------|------|
| フロントエンド | Bootstrap 5.3, HTML5, CSS3, JavaScript |
| チャート | Highcharts.js |
| バックエンド | PHP 7.4+ |
| データベース | MySQL 5.7+ / MariaDB |
| セキュリティ | CSRF トークン, レート制限, プリペアドステートメント |

## ファイル構成

```
/Personal-Finance-Dashboard/
├── index.php              # メインエントリーポイント
├── view.php               # メインビューテンプレート
├── config.php             # データベース接続・セキュリティ設定
├── functions.php          # ビジネスロジック（CRUD操作）
├── queries.php            # データ取得関数
├── translations.php       # 多言語文字列（日/英）
├── export.php             # CSV エクスポート
├── import.php             # CSV インポート
├── database.sql           # データベーススキーマ
├── assets/
│   ├── css/style.css     # カスタムスタイル
│   └── js/app.js         # チャート描画・UI インタラクション
└── views/
    ├── dashboard.php      # ダッシュボード画面
    ├── entry.php          # データ入力画面
    ├── budget.php         # 予算管理画面
    ├── management.php     # マスタ管理画面
    ├── transactions_table.php  # 取引履歴テーブル
    └── search_results.php      # 検索結果表示
```
