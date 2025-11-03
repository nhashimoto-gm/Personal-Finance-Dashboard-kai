# Analytics Dashboard - Setup Guide

## 📋 Overview

The Analytics Dashboard provides advanced data visualization for your financial data using React and Chart.js, supporting historical data analysis spanning multiple years.

## 🎯 Key Features

### Basic Analysis
- ✅ Monthly and yearly trend charts
- ✅ Category breakdown pie charts
- ✅ Long-term trend comparison
- ✅ Period filters (all-time/last 12 months/by year)
- ✅ Automatic savings rate calculation

### Advanced Features
- 🔄 Seasonal pattern analysis
- 📊 Budget vs actual comparison
- 🎯 Goal setting and progress tracking
- 📈 Expense prediction (statistical methods)
- 📤 Data export (CSV/Excel)

## 🚀 Quick Start

### 1. Prerequisites

Ensure you have the following installed:
- PHP 7.4 or higher
- MySQL 5.7+ / MariaDB 10.2+
- Web server (Apache/Nginx)
- Modern web browser with JavaScript enabled

### 2. Database Configuration

#### A. Configure Environment Variables

Edit your `.env_db` file with your database credentials:

```ini
DB_HOST=localhost
DB_USERNAME=your_username
DB_PASSWORD=your_password
DB_DATABASE=finance_db
```

#### B. Verify Table Structure

The analytics system expects the following table structure:

```sql
-- Main transactions table
CREATE TABLE source (
    id INT PRIMARY KEY AUTO_INCREMENT,
    re_date DATE NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    cat_1 VARCHAR(255),  -- Shop
    cat_2 VARCHAR(255),  -- Category
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Shop labels
CREATE TABLE cat_1_labels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    label VARCHAR(255) NOT NULL,
    user_id INT
);

-- Category labels
CREATE TABLE cat_2_labels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    label VARCHAR(255) NOT NULL,
    user_id INT
);
```

### 3. File Structure

Deploy the analytics dashboard with the following structure:

```
Personal-Finance-Dashboard/
├── analytics/
│   └── index.html          # Analytics dashboard
├── api/
│   └── analytics-api.php   # Backend API
├── .env_db                 # Database configuration
└── database.sql            # Database schema
```

### 4. API Configuration

The analytics API (`api/analytics-api.php`) automatically reads configuration from `.env_db`. Ensure the file path is correct:

```php
// The API will look for .env_db in the parent directory
// Adjust the path if your structure is different
```

### 5. Enable Production Mode

By default, the analytics dashboard runs in demo mode. To connect to your actual database:

Edit `analytics/index.html` and change:

```javascript
// Line ~24
const USE_DEMO_DATA = false;  // Change from true to false
```

## 📊 Integration Options

### Option 1: Standalone Dashboard (Recommended)
- Run analytics as a separate page
- Main app: `https://your-domain.com/`
- Analytics: `https://your-domain.com/analytics/`
- **Benefit**: No impact on existing system

### Option 2: Menu Integration
Add a link in your main navigation:

```html
<!-- In index.php -->
<nav>
    <a href="/">Dashboard</a>
    <a href="/analytics/">📊 Analytics</a>
</nav>
```

### Option 3: Tab Integration
Embed as an iframe in your existing dashboard:

```javascript
<iframe src="/analytics/" width="100%" height="800px"></iframe>
```

## 🛠️ Customization

### Color Theme

Customize the gradient colors in `analytics/index.html`:

```css
.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    /* Change to your preferred colors */
}
```

### Chart Colors

Modify chart colors in the JavaScript section:

```javascript
backgroundColor: [
    '#FF6384',  // Change these hex color codes
    '#36A2EB',
    '#FFCE56',
    // ...
]
```

### Adding Custom Features

#### 1. Budget Management
```javascript
const budgets = {
    'Groceries': 50000,
    'Rent': 100000,
    // Add your budget categories
};
```

#### 2. CSV Export
```javascript
const exportToCSV = () => {
    const csv = filteredData.map(row =>
        `${row.date},${row.amount},${row.category}`
    ).join('\n');

    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'financial-data.csv';
    a.click();
};
```

## 🔒 Security

### Recommended Security Measures

1. **Basic Authentication**
   Create `.htaccess` in the `analytics/` directory:
   ```apache
   AuthType Basic
   AuthName "Analytics Dashboard"
   AuthUserFile /path/to/.htpasswd
   Require valid-user
   ```

2. **IP Address Restriction**
   Add to `api/analytics-api.php`:
   ```php
   $allowed_ips = ['your.ip.address'];
   if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
       http_response_code(403);
       die('Access denied');
   }
   ```

3. **SQL Injection Prevention**
   - Use prepared statements (already implemented)
   - Validate all user inputs
   - Sanitize outputs with `htmlspecialchars()`

## 📱 Responsive Design

The analytics dashboard is fully responsive and works on:
- 📱 Smartphones (320px+)
- 💻 Tablets (768px+)
- 🖥️ Desktops (1024px+)

## 🐛 Troubleshooting

### Data Not Displaying

1. **Check API Response**
   ```bash
   curl https://your-domain.com/api/analytics-api.php?action=summary
   ```

2. **Verify Database Connection**
   - Check `.env_db` credentials
   - Ensure database server is running
   - Verify user permissions

3. **Check Browser Console**
   - Press F12 to open Developer Tools
   - Look for errors in the Console tab
   - Check Network tab for failed API requests

### Charts Not Rendering

1. **Verify CDN Access**
   - Check if Chart.js CDN is accessible
   - Check if React CDN is accessible
   - Look for CORS errors

2. **JavaScript Errors**
   - Open browser console (F12)
   - Check for syntax errors
   - Verify all dependencies loaded

### Performance Issues

1. **Add Database Indexes**
   ```sql
   CREATE INDEX idx_date ON source(re_date);
   CREATE INDEX idx_user_date ON source(user_id, re_date);
   CREATE INDEX idx_shop ON source(cat_1);
   CREATE INDEX idx_category ON source(cat_2);
   ```

2. **Limit Date Range**
   ```javascript
   // Fetch only recent data
   fetch('/api/analytics-api.php?action=monthly&start_date=2024-01-01')
   ```

3. **Implement Caching**
   ```php
   // Cache API responses
   $cache_file = "cache/summary_" . date('Y-m-d') . ".json";
   if (file_exists($cache_file) && (time() - filemtime($cache_file) < 3600)) {
       echo file_get_contents($cache_file);
       exit;
   }
   ```

## 📚 Technology Stack

- **Frontend**: React 18, Chart.js 4, Bootstrap 5
- **Backend**: PHP 7.4+, PDO
- **Database**: MySQL 5.7+ / MariaDB 10.2+
- **Architecture**: RESTful API with JSON responses

## 🎨 Future Enhancements

- [ ] PDF/Excel report generation
- [ ] Email notifications
- [ ] Budget alerts
- [ ] Multi-account support
- [ ] AI-powered expense prediction
- [ ] Mobile app (PWA)

## 💡 Support

If you encounter issues:
1. Check database table structure matches expected schema
2. Review error logs (PHP error log and browser console)
3. Verify API endpoint responses
4. Check [GitHub Issues](https://github.com/nhashimoto-gm/Personal-Finance-Dashboard/issues)

## 📄 License

MIT License - Feel free to customize and use commercially!

---

**Last Updated**: 2025-11-03
**Version**: 2.1
**For more information**: See [ANALYTICS.md](./ANALYTICS.md) for detailed analytics documentation
