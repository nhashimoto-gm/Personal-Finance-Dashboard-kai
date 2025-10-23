# 新リポジトリ作成ガイド
# Personal_Finance_Dashboard-Multi_Account_Architecture

## GitHub リポジトリ作成設定

### 基本情報

**リポジトリ名**: `Personal_Finance_Dashboard-Multi_Account_Architecture`

**説明 (Description)**:
```
Multi-user personal finance tracking dashboard with table separation architecture. Secure authentication, complete data isolation, and support for up to 1,000 users. Built with PHP, MySQL, and Bootstrap.
```

**公開設定**:
- ✅ **Public** (推奨) - オープンソースプロジェクトとして公開
- または Private - 非公開にする場合

**初期設定**:
- ❌ **Add a README file** - チェックを外す（既に用意済み）
- ❌ **Add .gitignore** - チェックを外す（既に用意済み）
- ✅ **Choose a license** → **MIT License** を選択（または既に用意済みなのでスキップ）

---

## リポジトリ作成後の初期セットアップ

### 1. リポジトリの基本設定

#### About セクション
GitHubのリポジトリページ右上の「⚙️」アイコンをクリック:

**Website**:
```
https://your-domain.com（デプロイ後に追加）
```

**Topics (タグ)** - 以下を追加:
```
php
mysql
personal-finance
finance-tracker
multi-user
multi-tenancy
authentication
bootstrap
dashboard
budgeting
expense-tracker
table-separation
finance-management
```

**Social Preview Image** (オプション):
- スクリーンショットをアップロード（後で追加可能）

---

### 2. ブランチ保護設定

**Settings** → **Branches** → **Add branch protection rule**

**Branch name pattern**: `main`

推奨設定:
- ✅ **Require a pull request before merging**
  - Require approvals: 1（複数人開発の場合）
- ✅ **Require status checks to pass before merging**
- ✅ **Require conversation resolution before merging**
- ❌ **Require signed commits** (オプション)
- ✅ **Include administrators** (管理者も従う)

---

### 3. セキュリティ設定

**Settings** → **Security** → **Code security and analysis**

有効化推奨:
- ✅ **Dependency graph**
- ✅ **Dependabot alerts**
- ✅ **Dependabot security updates**
- ✅ **Secret scanning** (Public リポジトリは自動有効)

---

### 4. Issues設定

**Settings** → **General** → **Features**

- ✅ **Issues** を有効化
- ✅ **Projects** を有効化（プロジェクト管理に使用）
- ✅ **Discussions** を有効化（コミュニティ用）
- ✅ **Wiki** を有効化（追加ドキュメント用）

#### Issue Templates の作成

`.github/ISSUE_TEMPLATE/` ディレクトリに以下を作成:

**bug_report.md**:
```markdown
---
name: Bug Report
about: Create a report to help us improve
title: '[BUG] '
labels: bug
assignees: ''
---

**Describe the bug**
A clear and concise description of what the bug is.

**To Reproduce**
Steps to reproduce the behavior:
1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

**Expected behavior**
A clear and concise description of what you expected to happen.

**Screenshots**
If applicable, add screenshots to help explain your problem.

**Environment:**
 - OS: [e.g. Ubuntu 22.04]
 - PHP Version: [e.g. 8.1]
 - MySQL Version: [e.g. 8.0]
 - Browser: [e.g. Chrome, Safari]

**Additional context**
Add any other context about the problem here.
```

**feature_request.md**:
```markdown
---
name: Feature Request
about: Suggest an idea for this project
title: '[FEATURE] '
labels: enhancement
assignees: ''
---

**Is your feature request related to a problem? Please describe.**
A clear and concise description of what the problem is.

**Describe the solution you'd like**
A clear and concise description of what you want to happen.

**Describe alternatives you've considered**
A clear and concise description of any alternative solutions.

**Additional context**
Add any other context or screenshots about the feature request here.
```

---

### 5. Pull Request Template

`.github/PULL_REQUEST_TEMPLATE.md`:
```markdown
## Description
Brief description of the changes in this PR.

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] I have tested this code locally
- [ ] I have added tests that prove my fix/feature works
- [ ] All existing tests pass

## Checklist
- [ ] My code follows the project's coding standards
- [ ] I have commented my code, particularly in hard-to-understand areas
- [ ] I have updated the documentation accordingly
- [ ] My changes generate no new warnings
- [ ] I have checked for security vulnerabilities

## Related Issues
Closes #(issue number)

## Screenshots (if applicable)
Add screenshots here
```

---

### 6. GitHub Actions (CI/CD)

`.github/workflows/tests.yml`:
```yaml
name: Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: finance_test_db
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
    - uses: actions/checkout@v3

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        extensions: pdo, pdo_mysql, mbstring

    - name: Validate PHP syntax
      run: find . -name "*.php" -exec php -l {} \;

    - name: Import database schema
      run: |
        mysql -h 127.0.0.1 -u root -proot finance_test_db < database/schema.sql

    - name: Run tests (when implemented)
      run: |
        echo "Tests will be added in future"
```

---

### 7. README バッジの追加

README.md の先頭に以下のバッジを追加:

```markdown
[![GitHub release](https://img.shields.io/github/release/YOUR_USERNAME/Personal_Finance_Dashboard-Multi_Account_Architecture.svg)](https://github.com/YOUR_USERNAME/Personal_Finance_Dashboard-Multi_Account_Architecture/releases)
[![GitHub issues](https://img.shields.io/github/issues/YOUR_USERNAME/Personal_Finance_Dashboard-Multi_Account_Architecture.svg)](https://github.com/YOUR_USERNAME/Personal_Finance_Dashboard-Multi_Account_Architecture/issues)
[![GitHub stars](https://img.shields.io/github/stars/YOUR_USERNAME/Personal_Finance_Dashboard-Multi_Account_Architecture.svg)](https://github.com/YOUR_USERNAME/Personal_Finance_Dashboard-Multi_Account_Architecture/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/YOUR_USERNAME/Personal_Finance_Dashboard-Multi_Account_Architecture.svg)](https://github.com/YOUR_USERNAME/Personal_Finance_Dashboard-Multi_Account_Architecture/network)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
```

---

### 8. Contributing ガイドライン

`CONTRIBUTING.md`:
```markdown
# Contributing to Personal Finance Dashboard (Multi-User)

Thank you for considering contributing! We welcome contributions from everyone.

## Getting Started

1. Fork the repository
2. Clone your fork: `git clone https://github.com/YOUR_USERNAME/Personal_Finance_Dashboard-Multi_Account_Architecture.git`
3. Create a feature branch: `git checkout -b feature/your-feature-name`
4. Make your changes
5. Test thoroughly
6. Commit: `git commit -m "Add: your feature description"`
7. Push: `git push origin feature/your-feature-name`
8. Create a Pull Request

## Code Standards

- Follow PSR-12 for PHP code
- Use meaningful variable and function names
- Comment complex logic
- Write self-documenting code
- Add PHPDoc blocks for functions

## Commit Message Format

Use conventional commits:
- `Add: new feature`
- `Fix: bug description`
- `Update: improvement description`
- `Refactor: code refactoring`
- `Docs: documentation update`
- `Test: test addition/modification`

## Testing

- Test your changes locally
- Ensure all existing functionality still works
- Add tests for new features (when test framework is implemented)

## Security

If you discover a security vulnerability, please email security@example.com instead of using the issue tracker.

## Questions?

Feel free to open a discussion or issue if you have questions!
```

---

### 9. Code of Conduct

`CODE_OF_CONDUCT.md`:
```markdown
# Code of Conduct

## Our Pledge

We are committed to providing a welcoming and inspiring community for all.

## Our Standards

Examples of behavior that contributes to a positive environment:
- Being respectful of differing viewpoints
- Gracefully accepting constructive criticism
- Focusing on what is best for the community
- Showing empathy towards other community members

Examples of unacceptable behavior:
- Trolling, insulting/derogatory comments, and personal attacks
- Public or private harassment
- Publishing others' private information without permission
- Other conduct which could reasonably be considered inappropriate

## Enforcement

Instances of abusive behavior may be reported by contacting the project team. All complaints will be reviewed and investigated.

## Attribution

This Code of Conduct is adapted from the Contributor Covenant, version 2.1.
```

---

### 10. プロジェクトの Labels

**Settings** → **Issues** → **Labels**

推奨ラベル:
- `bug` - Something isn't working (赤)
- `enhancement` - New feature or request (青)
- `documentation` - Documentation improvements (紫)
- `good first issue` - Good for newcomers (緑)
- `help wanted` - Extra attention is needed (黄色)
- `security` - Security related (濃い赤)
- `performance` - Performance improvement (オレンジ)
- `database` - Database related (水色)
- `authentication` - Auth system related (茶色)
- `frontend` - Frontend/UI related (ピンク)
- `backend` - Backend/PHP related (灰色)

---

### 11. Milestones の作成

**Issues** → **Milestones**

推奨マイルストーン:
1. **v1.0.0 - MVP**
   - Due date: 2ヶ月後
   - Description: Basic multi-user functionality with authentication

2. **v1.1.0 - Enhanced Features**
   - Due date: 4ヶ月後
   - Description: Email verification, password reset, user settings

3. **v2.0.0 - Advanced Features**
   - Due date: 6ヶ月後
   - Description: API endpoints, admin dashboard, advanced analytics

---

### 12. Releases の準備

最初のリリース作成:

**Releases** → **Create a new release**

- **Tag version**: `v0.1.0-alpha`
- **Release title**: `Initial Alpha Release - Multi-User Architecture`
- **Description**:
```markdown
## Initial Alpha Release

This is the initial alpha release of the multi-user version of Personal Finance Dashboard.

### Features
- ✅ User registration and authentication
- ✅ Secure session management
- ✅ Table separation architecture (up to 1,000 users)
- ✅ CSRF protection
- ✅ Rate limiting
- ✅ Database schema with stored procedures

### What's Next
- [ ] Login/Registration UI
- [ ] Dashboard integration
- [ ] Email verification
- [ ] Password reset functionality

### Installation
See [SETUP_GUIDE.md](docs/SETUP_GUIDE.md) for detailed installation instructions.

**⚠️ Warning**: This is an alpha release. Not recommended for production use.
```

---

## リポジトリの初期化コマンド

```bash
# 新しいディレクトリ作成
mkdir Personal_Finance_Dashboard-Multi_Account_Architecture
cd Personal_Finance_Dashboard-Multi_Account_Architecture

# multiuser-setup からファイルをコピー
cp -r /home/user/Personal-Finance-Dashboard/multiuser-setup/* .
cp -r /home/user/Personal-Finance-Dashboard/multiuser-setup/.gitignore .
cp -r /home/user/Personal-Finance-Dashboard/multiuser-setup/.env_db.example .

# Git初期化
git init
git add .
git commit -m "Initial commit: Multi-user architecture setup

- Complete database schema with table separation design
- Authentication system (registration, login, logout)
- Secure session management with hijacking prevention
- CSRF protection
- Rate limiting (5 attempts per 15 minutes)
- Dynamic table resolver with security validation
- Comprehensive documentation (README, SETUP_GUIDE, ARCHITECTURE)
- MIT License

This release provides the foundation for a secure multi-user personal finance dashboard."

# GitHubリポジトリに接続
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/Personal_Finance_Dashboard-Multi_Account_Architecture.git
git push -u origin main
```

---

## 追加の推奨設定

### README.md にスクリーンショットセクション追加

将来的に追加:
```markdown
## Screenshots

### Login Page
![Login](screenshots/login.png)

### Dashboard
![Dashboard](screenshots/dashboard.png)

### User Registration
![Registration](screenshots/registration.png)
```

### screenshots ディレクトリ作成

```bash
mkdir screenshots
touch screenshots/.gitkeep
```

---

## GitHub Pages 設定（オプション）

ドキュメントサイトを公開する場合:

**Settings** → **Pages**
- **Source**: Deploy from a branch
- **Branch**: main
- **Folder**: /docs

---

## 優先度付きチェックリスト

### 必須（リポジトリ作成時）
- ✅ リポジトリ名: `Personal_Finance_Dashboard-Multi_Account_Architecture`
- ✅ 説明文を追加
- ✅ Public/Private 選択
- ✅ ファイルをプッシュ

### 重要（作成後すぐ）
- ✅ Topics（タグ）を追加
- ✅ README バッジを更新
- ✅ Issue templates を追加
- ✅ CONTRIBUTING.md を追加

### 推奨（1週間以内）
- ✅ ブランチ保護設定
- ✅ GitHub Actions 設定
- ✅ Labels 設定
- ✅ Milestones 作成

### オプション（開発が進んでから）
- ⭕ Code of Conduct 追加
- ⭕ GitHub Pages 有効化
- ⭕ スクリーンショット追加
- ⭕ Wiki 整備

---

**作成日**: 2025-10-23
**対象リポジトリ**: Personal_Finance_Dashboard-Multi_Account_Architecture
**ステータス**: 準備完了 ✅
