# リポジトリ移行ガイド

このガイドでは、Personal Finance Dashboard を新しいパブリックリポジトリに移行する手順を説明します。

## 事前準備

### 1. GitHubで新しいリポジトリを作成

1. GitHubにログイン
2. 右上の「+」から「New repository」を選択
3. 以下の設定を行う：
   - **Repository name**: 任意の名前（例: `personal-finance-dashboard`）
   - **Description**: Personal Finance Dashboard - Web-based income and expense tracker
   - **Visibility**: **Public**（パブリック）を選択
   - **Initialize this repository**: すべてチェックを外す（README、.gitignore、licenseを追加しない）
4. 「Create repository」をクリック
5. 表示されるリポジトリURLをコピー（例: `https://github.com/username/personal-finance-dashboard.git`）

### 2. 必要なファイルの確認

移行前に以下を確認してください：

- [ ] `.env_db` や機密情報を含むファイルが `.gitignore` に含まれているか
- [ ] パスワードやAPIキーなどの機密情報がコード内にハードコードされていないか
- [ ] 不要なファイル（ログファイル、一時ファイルなど）が `.gitignore` に含まれているか

## 移行手順

### ステップ1: スクリプトに実行権限を付与

```bash
chmod +x migrate-repository.sh
```

### ステップ2: スクリプトを実行

```bash
./migrate-repository.sh
```

### ステップ3: 新しいリポジトリURLを入力

スクリプトが起動したら、GitHubで作成した新しいリポジトリのURLを入力してください。

```
URL: https://github.com/username/personal-finance-dashboard.git
```

### ステップ4: 確認して実行

スクリプトが実行する操作を確認し、`yes` と入力して続行します。

## スクリプトが実行する操作

1. **バックアップ作成**: 現在のプロジェクトを `Personal-Finance-Dashboard_backup_YYYYMMDD_HHMMSS` という名前でバックアップ
2. **.git ディレクトリ削除**: 既存の開発履歴を削除
3. **新規初期化**: 新しいgitリポジトリとして初期化
4. **初期コミット**: すべてのファイルを1つのクリーンなコミットとして追加
5. **プッシュ**: 新しいリモートリポジトリにプッシュ

## トラブルシューティング

### プッシュが失敗する場合

**エラー: `authentication failed`**
- GitHubの認証情報が設定されているか確認
- Personal Access Token (PAT) を使用する場合は、tokenに `repo` 権限があるか確認

**エラー: `repository not found`**
- リポジトリURLが正しいか確認
- GitHubで新しいリポジトリが正しく作成されているか確認

**手動でプッシュする方法:**
```bash
git push -u origin main
```

### リモートURLを変更する場合

```bash
git remote set-url origin https://github.com/username/new-repo.git
git push -u origin main
```

## 移行後の確認事項

- [ ] GitHubで新しいリポジトリにアクセスできるか
- [ ] すべてのファイルが正しくプッシュされているか
- [ ] リポジトリが「Public」になっているか
- [ ] README.mdの内容が適切か（必要に応じて更新）
- [ ] LICENSEファイルが含まれているか

## バックアップの削除

移行が成功し、新しいリポジトリが正常に動作することを確認したら、バックアップを削除できます：

```bash
# バックアップディレクトリを削除
rm -rf ../Personal-Finance-Dashboard_backup_*
```

⚠️ **警告**: バックアップを削除する前に、必ず新しいリポジトリが正常に動作することを確認してください。

## 元のリポジトリについて

移行後、元のプライベートリポジトリ（`nhashimoto-gm/Personal-Finance-Dashboard`）は以下のいずれかを選択できます：

1. **保持**: アーカイブとして保持（GitHubでアーカイブ設定を推奨）
2. **削除**: 完全に削除する

GitHubでリポジトリをアーカイブする方法：
1. 元のリポジトリの Settings へ移動
2. 下部の「Danger Zone」セクションで「Archive this repository」をクリック

## サポート

問題が発生した場合は、以下を確認してください：

1. バックアップディレクトリが作成されているか
2. GitHubの新しいリポジトリが正しく作成されているか
3. Git の認証情報が正しく設定されているか

必要に応じて、バックアップから復元できます：

```bash
# バックアップから復元
cd ..
rm -rf Personal-Finance-Dashboard
cp -r Personal-Finance-Dashboard_backup_YYYYMMDD_HHMMSS Personal-Finance-Dashboard
cd Personal-Finance-Dashboard
```
