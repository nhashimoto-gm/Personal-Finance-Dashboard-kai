# GitHub Actions ワークフロー

このディレクトリには、PFDプロジェクトのCI/CDワークフローが含まれています。

## 📋 利用可能なワークフロー

### 1. 🔍 PHP Syntax Check (`php-syntax-check.yml`)

**目的**: 全PHPファイルの構文エラーを検出

**実行タイミング**:
- すべてのブランチへのpush
- すべてのブランチへのPull Request

**処理内容**:
- PHP 7.4での構文チェック
- エラーログのアーティファクト保存

**使用例**:
```bash
# ローカルで同じチェックを実行
find . -name "*.php" -not -path "./vendor/*" -exec php -l {} \;
```

---

### 2. 🎯 Code Quality Check (`code-quality.yml`)

**目的**: コード品質の維持とベストプラクティスの強制

**実行タイミング**:
- すべてのブランチへのpush
- すべてのブランチへのPull Request

**含まれるチェック**:

#### a) PHPStan (静的解析)
- レベル5の静的解析
- 未定義変数の検出
- 型の不一致検出

#### b) PHP_CodeSniffer (コーディング規約)
- PSR-12準拠チェック
- レガシーコード対応の緩和ルール

#### c) PHPCBF (自動整形 - dry-run)
- コード整形の提案

**使用例**:
```bash
# ローカルでPHPStanを実行
composer require --dev phpstan/phpstan
vendor/bin/phpstan analyse

# ローカルでPHPCSを実行
composer require --dev squizlabs/php_codesniffer
vendor/bin/phpcs --standard=PSR12 .
```

---

### 3. 🔒 Security Scan (`security-scan.yml`)

**目的**: セキュリティ脆弱性の早期発見

**実行タイミング**:
- すべてのブランチへのpush
- すべてのブランチへのPull Request
- 毎週月曜日00:00 UTC (定期スキャン)

**含まれるスキャン**:

#### a) Dependency Review
- Pull Request時の依存関係の脆弱性チェック

#### b) PHP Security Checker
- Composer依存関係の既知の脆弱性検出

#### c) TruffleHog OSS
- 機密情報（API Key、パスワード等）の検出

#### d) CodeQL Security Analysis
- JavaScriptコードのセキュリティ解析

#### e) SQL Injection Check
- SQLインジェクションパターンの検出
- 非推奨のmysql_query関数の使用チェック
- Prepared Statementの使用確認

#### f) XSS Vulnerability Check
- エスケープされていない出力の検出

**重要な警告**:
- SQL injection、XSSの警告は必ず確認してください
- 機密情報が検出された場合は直ちに修正が必要です

---

### 4. 🗄️ Database Tests (`database-test.yml`)

**目的**: データベーススキーマとクエリの検証

**実行タイミング**:
- すべてのブランチへのpush
- すべてのブランチへのPull Request

**テスト内容**:

#### a) Schema Validation (MySQL 5.7)
- schema.sqlの構文チェック
- テーブル構造の検証
- インデックスの確認
- JOIN クエリのテスト
- UNIQUE制約のテスト
- 外部キー関係のテスト

#### b) Migration Test (MySQL 8.0)
- MySQL 8.0との互換性確認
- UTF8MB4エンコーディングの検証
- 絵文字サポートのテスト

**使用するデータベース**:
- MySQL 5.7 (本番環境互換)
- MySQL 8.0 (将来の互換性確認)

---

### 5. 🚀 Continuous Integration (`ci.yml`)

**目的**: 複数バージョンでの互換性確認

**実行タイミング**:
- すべてのブランチへのpush
- すべてのブランチへのPull Request

**テストマトリクス**:
| PHP Version | MySQL Version |
|-------------|---------------|
| 7.4         | 5.7           |
| 8.0         | 5.7           |
| 8.1         | 8.0           |
| 8.2         | 8.0           |
| 8.3         | 8.0           |

**テスト項目**:
1. PHP構文チェック（各バージョン）
2. データベース接続テスト
3. PDO Prepared Statementテスト
4. プロジェクト構造の検証
5. 一般的な問題のチェック:
   - 短いPHPタグ (`<?`)
   - eval()の使用
   - ハードコードされた認証情報

---

### 6. 🤖 Dependabot (`dependabot.yml`)

**目的**: 依存関係の自動更新

**更新スケジュール**: 毎週月曜日 09:00

**監視対象**:
- GitHub Actions（ワークフロー内のアクション）
- Composer依存関係（PHP）
- npm依存関係（JavaScript/Bootstrap）

**設定**:
- メジャーバージョン更新は無視（破壊的変更を防ぐ）
- Bootstrapの更新はグループ化
- Pull Request上限: 5件

---

## 🔧 セットアップ方法

### 1. ワークフローの有効化

リポジトリにpushすると自動的に有効化されます:

```bash
git add .github/
git commit -m "Add GitHub Actions workflows"
git push origin your-branch
```

### 2. GitHub Secretsの設定（オプション）

将来的にデプロイメントを追加する場合は、以下のSecretsを設定:

- `FTP_SERVER`: FTPサーバーアドレス
- `FTP_USERNAME`: FTPユーザー名
- `FTP_PASSWORD`: FTPパスワード

設定方法: リポジトリ > Settings > Secrets and variables > Actions

### 3. ブランチ保護ルールの設定（推奨）

メインブランチを保護するため:

1. リポジトリ > Settings > Branches
2. "Add rule" をクリック
3. 以下を有効化:
   - ✅ Require status checks to pass before merging
   - ✅ Require branches to be up to date before merging
   - 必須チェック:
     - `Check PHP Syntax`
     - `PHPStan Static Analysis`
     - `MySQL Schema Validation`

---

## 📊 ワークフローの確認方法

### GitHub UI
1. リポジトリの "Actions" タブを開く
2. 左サイドバーからワークフローを選択
3. 実行履歴とログを確認

### ステータスバッジの追加（オプション）

README.mdに以下を追加してステータスを表示:

```markdown
![PHP Syntax Check](https://github.com/nhashimoto-gm/PFD/actions/workflows/php-syntax-check.yml/badge.svg)
![Code Quality](https://github.com/nhashimoto-gm/PFD/actions/workflows/code-quality.yml/badge.svg)
![Security Scan](https://github.com/nhashimoto-gm/PFD/actions/workflows/security-scan.yml/badge.svg)
![Database Tests](https://github.com/nhashimoto-gm/PFD/actions/workflows/database-test.yml/badge.svg)
![CI](https://github.com/nhashimoto-gm/PFD/actions/workflows/ci.yml/badge.svg)
```

---

## 🐛 トラブルシューティング

### ワークフローが失敗する場合

#### 1. PHP構文エラー
```
Parse error: syntax error, unexpected...
```
**解決**: 該当ファイルの構文を修正

#### 2. データベース接続エラー
```
SQLSTATE[HY000] [2002] Connection refused
```
**原因**: CIのMySQLサービスが起動していない
**解決**: ワークフロー定義のservicesセクションを確認

#### 3. Composer依存関係のエラー
```
Package not found
```
**解決**: composer.jsonに依存関係を追加するか、グローバルインストールを使用

#### 4. セキュリティスキャンの誤検知

**XSS警告の例**:
```php
// 誤検知される可能性
echo $safe_variable; // すでにエスケープ済み
```

**解決**: `htmlspecialchars()` または `h()` を明示的に使用

---

## 📈 今後の拡張予定

- [ ] PHPUnitによるユニットテスト
- [ ] カバレッジレポート
- [ ] パフォーマンステスト
- [ ] E2Eテスト（Selenium/Playwright）
- [ ] 自動デプロイメント（ステージング環境）

---

## 📝 参考リンク

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [PHPStan](https://phpstan.org/)
- [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer)
- [Dependabot](https://docs.github.com/en/code-security/dependabot)
- [CodeQL](https://codeql.github.com/)

---

**最終更新**: 2025-11-03
**メンテナー**: nhashimoto-gm
