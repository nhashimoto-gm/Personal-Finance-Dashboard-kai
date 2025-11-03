# Personal Finance Dashboard - Documentation Overview

## 📦 Welcome

Welcome to the Personal Finance Dashboard documentation! This comprehensive guide will help you get started and make the most of your financial tracking system.

## 📚 Documentation Structure

### Getting Started (Essential Reading)
- **[QUICKSTART.md](./QUICKSTART.md)** - Get up and running in 3 steps (⏱️ 3 minutes)
- **[USAGE.md](./USAGE.md)** - Detailed usage guide for all features
- **[README.md](../README.md)** - Main project README with overview and installation

### Advanced Features
- **[ANALYTICS.md](./ANALYTICS.md)** - Advanced analytics dashboard documentation
- **[README_ANALYTICS.md](./README_ANALYTICS.md)** - Analytics integration guide
- **[RATE_LIMITING.md](./RATE_LIMITING.md)** - Rate limiting and security features

### Migration & Deployment
- **[MIGRATION_GUIDE.md](../MIGRATION_GUIDE.md)** - Repository migration guide
- **[MULTI_ACCOUNT_MIGRATION.md](../MULTI_ACCOUNT_MIGRATION.md)** - Multi-user migration guide

### Development
- **[CONTRIBUTING.md](./CONTRIBUTING.md)** - Contribution guidelines for developers
- **[APP_DIAGRAM.md](./APP_DIAGRAM.md)** - Application architecture diagrams

## 🎯 Quick Navigation by Role

### 👤 For End Users
Start here to learn how to use the application:
1. [QUICKSTART.md](./QUICKSTART.md) - Quick setup
2. [USAGE.md](./USAGE.md) - How to use all features
3. [ANALYTICS.md](./ANALYTICS.md) - Advanced analytics

### 🚀 For Administrators
Deploying or migrating the application:
1. [Main README](../README.md) - Installation guide
2. [MIGRATION_GUIDE.md](../MIGRATION_GUIDE.md) - Repository migration
3. [MULTI_ACCOUNT_MIGRATION.md](../MULTI_ACCOUNT_MIGRATION.md) - Multi-user setup
4. [RATE_LIMITING.md](./RATE_LIMITING.md) - Security configuration

### 👩‍💻 For Developers
Contributing to the project:
1. [CONTRIBUTING.md](./CONTRIBUTING.md) - How to contribute
2. [APP_DIAGRAM.md](./APP_DIAGRAM.md) - Architecture overview
3. [Main README](../README.md) - Technical stack

## ✨ Key Features Overview

### 🔐 Security & Authentication
- User registration and login
- Multi-account support with data isolation
- CSRF protection and rate limiting
- Session management with timeout

### 📊 Dashboard & Analytics
- Real-time expense tracking
- Interactive charts (Highcharts & Chart.js)
- Period-based filtering and analysis
- Budget vs actual comparison

### 💰 Financial Management
- Transaction management (CRUD operations)
- Budget planning with visual progress tracking
- Recurring expense tracking
- CSV import/export

### 📈 Advanced Analytics
- 17-year historical data visualization
- Trend analysis with moving averages
- Weekday and seasonal pattern detection
- Shop and category breakdown analysis

### 🌐 User Experience
- Multi-language support (English/Japanese)
- Dark mode with automatic chart theming
- Responsive design (mobile, tablet, desktop)
- Real-time input validation

## 🚀 Quick Start Guide

### For New Users
```bash
# 1. Install the application (see main README.md)
git clone https://github.com/nhashimoto-gm/Personal-Finance-Dashboard.git
cd Personal-Finance-Dashboard

# 2. Set up database
mysql -u root -p < database.sql

# 3. Configure environment
cp .env_db.example .env_db
# Edit .env_db with your credentials

# 4. Access the application
http://localhost/Personal-Finance-Dashboard/
```

### For Existing Users
See [USAGE.md](./USAGE.md) for detailed instructions on:
- Adding transactions
- Managing budgets
- Using analytics
- Exporting data

## 🔗 External Resources

- **GitHub Repository**: https://github.com/nhashimoto-gm/Personal-Finance-Dashboard
- **Issues**: https://github.com/nhashimoto-gm/Personal-Finance-Dashboard/issues
- **Discussions**: https://github.com/nhashimoto-gm/Personal-Finance-Dashboard/discussions

## 📞 Support

If you encounter any issues:
1. Check the relevant documentation section above
2. Search existing [GitHub Issues](https://github.com/nhashimoto-gm/Personal-Finance-Dashboard/issues)
3. Create a new issue with detailed information

## 📄 License

This project is licensed under the MIT License - see [LICENSE](../LICENSE) file for details.

---

**Last Updated**: 2025-11-03
**Version**: 2.1
**Compatibility**: PHP 7.4+, MySQL 5.7+
