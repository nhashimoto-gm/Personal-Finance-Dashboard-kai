# 🚀 Quick Start Guide

Get your Personal Finance Dashboard up and running in 3 simple steps!

## Prerequisites

Before you begin, ensure you have:
- PHP 7.4+ installed
- MySQL 5.7+ or MariaDB 10.2+
- A web server (Apache/Nginx)
- Git (for cloning the repository)

## 3 Steps to Get Started

### Step 1: Install the Application (5 minutes)

```bash
# Clone the repository
git clone https://github.com/nhashimoto-gm/Personal-Finance-Dashboard.git
cd Personal-Finance-Dashboard

# Create the database
mysql -u root -p
```

```sql
CREATE DATABASE finance_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE finance_db;
SOURCE database.sql;
EXIT;
```

### Step 2: Configure Environment (2 minutes)

```bash
# Copy the example environment file
cp .env_db.example .env_db

# Edit with your credentials
nano .env_db  # or use your preferred editor
```

Edit `.env_db` with your database credentials:

```ini
DB_HOST=localhost
DB_USERNAME=your_database_username
DB_PASSWORD=your_database_password
DB_DATABASE=finance_db
```

```bash
# Set proper permissions
chmod 600 .env_db
```

### Step 3: Access the Application (1 minute)

1. **Start your web server** or use PHP's built-in server:
   ```bash
   php -S localhost:8000
   ```

2. **Open your browser** and navigate to:
   ```
   http://localhost:8000
   ```

3. **Create your account**:
   - Click "Register" to create a new account
   - Log in with your credentials
   - Start tracking your finances!

## ✅ Verification Checklist

After installation, verify:

- [ ] Database tables created successfully
- [ ] `.env_db` file configured with correct credentials
- [ ] Application loads without errors
- [ ] Can register a new user account
- [ ] Can log in successfully
- [ ] Dashboard displays correctly
- [ ] Can add a transaction
- [ ] Charts render properly

## 📂 Final Directory Structure

```
Personal-Finance-Dashboard/
├── .env_db                         # Database configuration (configured ✅)
├── .env_db.example                 # Example configuration
├── database.sql                    # Database schema
├── index.php                       # Main entry point
├── config.php                      # Application configuration
├── functions.php                   # Business logic
├── queries.php                     # Database queries
├── translations.php                # Multi-language support
├── view.php                        # Main view template
├── api/
│   └── analytics-api.php          # Analytics API
├── analytics/
│   └── index.html                 # Advanced analytics dashboard
├── assets/
│   ├── css/
│   │   └── style.css              # Stylesheets
│   └── js/
│       └── app.js                 # JavaScript functionality
├── docs/                           # Documentation
└── views/                          # View templates
```

## 🌐 Accessing Your Dashboard

### Local Development
```
http://localhost:8000/
```

### Production Server
```
https://your-domain.com/Personal-Finance-Dashboard/
```

### Advanced Analytics
```
https://your-domain.com/Personal-Finance-Dashboard/analytics/
```

## 🎯 Next Steps

### 1. Security Configuration (Recommended)
- **Change default admin password** (if migrated from single-user)
- **Enable HTTPS** in production
- **Configure rate limiting** (see [RATE_LIMITING.md](./RATE_LIMITING.md))
- **Set up regular backups**

### 2. Customization
- **Theme**: Switch between light and dark mode
- **Language**: Toggle between English and Japanese
- **Budget**: Set monthly budgets for expense tracking
- **Categories**: Add custom shops and categories

### 3. Integration
- **CSV Import**: Bulk import historical transactions
- **Recurring Expenses**: Set up monthly recurring costs
- **Analytics**: Explore the advanced analytics dashboard
- **Reports**: Export data to CSV for external analysis

## 💬 Frequently Asked Questions

### Q: Will this affect my existing data?
**A:** No. This is a fresh installation. If you're migrating data, see [MULTI_ACCOUNT_MIGRATION.md](../MULTI_ACCOUNT_MIGRATION.md).

### Q: Do I need to modify the database?
**A:** No. The `database.sql` script creates all necessary tables and structures automatically.

### Q: Can multiple users use the same installation?
**A:** Yes! The application supports multiple users with isolated data. Each user has their own transactions, budgets, and settings.

### Q: How do I reset my password?
**A:** Currently, password reset must be done via database. Contact your administrator or see the user management documentation.

### Q: The analytics dashboard shows no data
**A:** Analytics require at least some transaction data. Add a few transactions first, then check the analytics page.

## 🔍 Troubleshooting

### Database Connection Error

**Problem**: Can't connect to database

**Solutions**:
1. Verify `.env_db` credentials are correct
2. Ensure MySQL server is running:
   ```bash
   sudo systemctl status mysql
   ```
3. Check database user has proper permissions:
   ```sql
   GRANT ALL PRIVILEGES ON finance_db.* TO 'your_username'@'localhost';
   FLUSH PRIVILEGES;
   ```

### White Screen / No Output

**Problem**: Application shows blank page

**Solutions**:
1. Check PHP error log:
   ```bash
   tail -f /var/log/php_errors.log
   ```
2. Enable error display (development only):
   ```php
   // Add to index.php temporarily
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```
3. Verify all required PHP extensions are installed:
   ```bash
   php -m | grep -E 'pdo|mysql|mbstring'
   ```

### Permission Denied Errors

**Problem**: Can't write to files or directories

**Solutions**:
```bash
# Set proper ownership (adjust user/group as needed)
sudo chown -R www-data:www-data /path/to/Personal-Finance-Dashboard

# Set proper permissions
chmod 755 /path/to/Personal-Finance-Dashboard
chmod 600 .env_db
```

### Charts Not Rendering

**Problem**: Dashboard loads but charts don't appear

**Solutions**:
1. Check browser console for JavaScript errors (F12)
2. Verify Highcharts CDN is accessible
3. Clear browser cache
4. Try a different browser

### API Errors

**Problem**: Analytics API returns errors

**Solutions**:
```bash
# Test API directly
curl http://localhost:8000/api/analytics-api.php?action=summary

# Check API file permissions
chmod 644 api/analytics-api.php

# Verify .env_db path in analytics-api.php
```

## 📱 Mobile Access

The dashboard is fully responsive and works great on mobile devices!

To easily access from your phone:
1. Deploy to a web server with HTTPS
2. Add to your phone's home screen for app-like experience (PWA support coming soon)

## 🎉 You're All Set!

You can now:
- ✅ Track daily expenses
- ✅ View spending trends
- ✅ Manage budgets
- ✅ Analyze financial patterns
- ✅ Export reports

For detailed usage instructions, see [USAGE.md](./USAGE.md).

For advanced analytics features, see [ANALYTICS.md](./ANALYTICS.md).

## 📚 Additional Resources

- **[USAGE.md](./USAGE.md)** - Detailed usage guide
- **[ANALYTICS.md](./ANALYTICS.md)** - Advanced analytics documentation
- **[CONTRIBUTING.md](./CONTRIBUTING.md)** - Contributing guidelines
- **[Main README](../README.md)** - Complete project documentation

## 🆘 Need Help?

- **GitHub Issues**: https://github.com/nhashimoto-gm/Personal-Finance-Dashboard/issues
- **Discussions**: https://github.com/nhashimoto-gm/Personal-Finance-Dashboard/discussions
- **Email**: 94941257+nhashimoto-gm@users.noreply.github.com

---

**Last Updated**: 2025-11-03
**Version**: 2.1
**Estimated Setup Time**: 10 minutes
