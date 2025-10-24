# データベース移行ガイド

このガイドでは、既存のテーブル（`source`, `cat_1_labels`, `cat_2_labels`）を保持したまま、Laravel用のテーブル（`transactions`, `shops`, `categories`）を作成する方法を説明します。

## 前提条件

- データベース名: `LAA1547051-kakeidb`
- 既存テーブル: `source`, `cat_1_labels`, `cat_2_labels`, `view1`
- これらの既存テーブルには**一切変更を加えません**

## 1. phpMyAdminでテーブルを作成

### 手順

1. phpMyAdminにログイン
2. 左側のデータベース一覧から **`LAA1547051-kakeidb`** をクリック
3. 上部メニューの「SQL」タブをクリック
4. 以下のSQLをコピー&ペースト
5. 「実行」ボタンをクリック

### 実行SQL

```sql
USE `LAA1547051-kakeidb`;

-- 外部キー制約があるため、子テーブルから順に削除（再実行時のみ必要）
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS shops;

-- 1. shopsテーブルの作成（cat_1_labelsのデータを複製）
CREATE TABLE shops (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY shops_name_unique (name),
    KEY shops_name_index (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- cat_1_labelsからshopsへデータをコピー
INSERT INTO shops (id, name, created_at, updated_at)
SELECT id, label, NOW(), NOW()
FROM cat_1_labels;

-- 2. categoriesテーブルの作成（cat_2_labelsのデータを複製）
CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY categories_name_unique (name),
    KEY categories_name_index (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- cat_2_labelsからcategoriesへデータをコピー
INSERT INTO categories (id, name, created_at, updated_at)
SELECT id, label, NOW(), NOW()
FROM cat_2_labels;

-- 3. transactionsテーブルの作成（sourceのデータを複製）
CREATE TABLE transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_date DATE NOT NULL,
    shop_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    amount INT NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    KEY transactions_transaction_date_index (transaction_date),
    KEY transactions_shop_id_index (shop_id),
    KEY transactions_category_id_index (category_id),
    CONSTRAINT transactions_shop_id_foreign FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE RESTRICT,
    CONSTRAINT transactions_category_id_foreign FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- sourceからtransactionsへデータをコピー（整合性のあるデータのみ）
INSERT INTO transactions (id, transaction_date, shop_id, category_id, amount, created_at, updated_at)
SELECT s.id, s.re_date, s.cat_1, s.cat_2, s.price, NOW(), NOW()
FROM source s
INNER JOIN cat_1_labels c1 ON s.cat_1 = c1.id
INNER JOIN cat_2_labels c2 ON s.cat_2 = c2.id;

-- 完了メッセージとコピーされたレコード数
SELECT
    'Laravel用テーブル作成完了' AS Status,
    (SELECT COUNT(*) FROM shops) AS shops_count,
    (SELECT COUNT(*) FROM categories) AS categories_count,
    (SELECT COUNT(*) FROM transactions) AS transactions_count,
    (SELECT COUNT(*) FROM source) AS source_original_count,
    (SELECT COUNT(*) FROM source) - (SELECT COUNT(*) FROM transactions) AS skipped_records;
```

### 結果の確認

実行後、以下のようなメッセージが表示されます：

| Status | shops_count | categories_count | transactions_count | source_original_count | skipped_records |
|--------|-------------|------------------|--------------------|-----------------------|-----------------|
| Laravel用テーブル作成完了 | XX | XX | XX | XX | 0 |

`skipped_records` が0であれば、すべてのデータが正常にコピーされています。

## 2. Laravel環境設定

### .envファイルの作成

`laravel-app` ディレクトリに `.env` ファイルを作成します：

```bash
cd laravel-app
cp .env.example .env
```

### データベース接続情報の設定

`.env` ファイルを開き、以下の設定を確認・修正します：

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=LAA1547051-kakeidb
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

**重要**: `DB_USERNAME` と `DB_PASSWORD` を実際の接続情報に変更してください。

### アプリケーションキーの生成

```bash
php artisan key:generate
```

## 3. テーブル構造の対応表

| 既存テーブル | Laravel テーブル | 説明 |
|-------------|------------------|------|
| `cat_1_labels` | `shops` | ショップマスタ |
| `cat_2_labels` | `categories` | カテゴリマスタ |
| `source` | `transactions` | 取引データ |

### カラムの対応表

#### shops テーブル
| 既存カラム (cat_1_labels) | Laravel カラム (shops) |
|--------------------------|------------------------|
| `id` | `id` |
| `label` | `name` |
| - | `created_at` (新規) |
| - | `updated_at` (新規) |

#### categories テーブル
| 既存カラム (cat_2_labels) | Laravel カラム (categories) |
|---------------------------|----------------------------|
| `id` | `id` |
| `label` | `name` |
| - | `created_at` (新規) |
| - | `updated_at` (新規) |

#### transactions テーブル
| 既存カラム (source) | Laravel カラム (transactions) |
|--------------------|-------------------------------|
| `id` | `id` |
| `re_date` | `transaction_date` |
| `cat_1` | `shop_id` |
| `cat_2` | `category_id` |
| `price` | `amount` |
| - | `created_at` (新規) |
| - | `updated_at` (新規) |

## 4. データの同期について

### 重要な注意点

- **既存テーブル**と**Laravelテーブル**は独立しています
- 片方のテーブルを更新しても、もう片方には自動反映されません
- 運用時は**どちらか一方のテーブルのみを使用**してください

### 推奨運用方法

1. **Laravel経由でのみデータ操作を行う**（推奨）
   - `transactions`, `shops`, `categories` テーブルを使用
   - Eloquent ORMで安全にデータ操作
   - 既存テーブル（`source`等）は参照のみ

2. **既存システムを継続使用する場合**
   - `source`, `cat_1_labels`, `cat_2_labels` を使用
   - Laravelテーブルは定期的に再作成

## 5. Laravel Eloquentモデルの使用例

### 取引データの取得

```php
use App\Models\Transaction;

// 全取引を取得
$transactions = Transaction::with(['shop', 'category'])->get();

// 日付範囲で取得
$transactions = Transaction::whereBetween('transaction_date', ['2024-01-01', '2024-12-31'])->get();

// ショップ名とカテゴリ名も含めて取得
foreach ($transactions as $transaction) {
    echo $transaction->transaction_date;
    echo $transaction->shop->name;  // リレーション
    echo $transaction->category->name;  // リレーション
    echo $transaction->amount;
}
```

## 6. トラブルシューティング

### エラー: Cannot add or update a child row

**原因**: `source` テーブルに、`cat_1_labels` や `cat_2_labels` に存在しないIDが含まれています。

**解決**: 上記のSQLでは `INNER JOIN` を使用しているため、整合性のあるデータのみがコピーされます。`skipped_records` で何件スキップされたか確認できます。

### エラー: 重複キーエラー

**原因**: テーブルがすでに存在しています。

**解決**: SQLの最初にある `DROP TABLE IF EXISTS` で既存テーブルを削除してから再作成します。

### データベース接続エラー

**チェック項目**:
1. `.env` ファイルの `DB_USERNAME` と `DB_PASSWORD` が正しいか
2. データベース名が `LAA1547051-kakeidb` になっているか
3. MySQLサーバーが起動しているか

## 7. 参考資料

- [Laravel データベース: 入門](https://laravel.com/docs/database)
- [Laravel Eloquent ORM](https://laravel.com/docs/eloquent)
- [Laravel マイグレーション](https://laravel.com/docs/migrations)
