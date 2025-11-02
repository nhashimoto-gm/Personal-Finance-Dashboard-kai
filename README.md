# 💰 Personal Finance Dashboard

A comprehensive personal finance tracking dashboard built with PHP, MySQL, Bootstrap, and Highcharts.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-7.4+-blue.svg)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange.svg)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![Highcharts](https://img.shields.io/badge/Highcharts-Latest-00A1E4?logo=highcharts&logoColor=white)](https://www.highcharts.com/)
[![React](https://img.shields.io/badge/React-18-61DAFB?logo=react&logoColor=white)](https://reactjs.org/)
[![Chart.js](https://img.shields.io/badge/Chart.js-4-FF6384?logo=chartdotjs&logoColor=white)](https://www.chartjs.org/)

---

## ✨ Features

### 🔐 User Authentication & Security
- **User Registration** - Secure account creation with email validation
- **User Login/Logout** - Session-based authentication system
- **User Profile Management** - Update username and password
- **User Display in Header** - Show current logged-in user with dropdown menu
- **CSRF Protection** - Token-based protection against cross-site request forgery
- **Rate Limiting** - Protection against brute-force attacks
- **Session Management** - Secure session handling with 30-minute timeout
- **Multi-Account Support** - Each user has isolated data and transactions

### 📊 Dashboard
- **Period Summary Statistics** - Total expenses, daily average, record count, and shop count
- **Shop Expense Breakdown** - Interactive pie chart showing spending by shop
- **Top 10 Categories** - Bar chart displaying top expense categories
- **Daily Expense Trend** - Line chart tracking daily spending
- **Cumulative Expense Trend** - Line chart showing cumulative spending
- **Period Trend Analysis** - Stacked bar chart (12 months/2 years/5 years/10 years views)
- **Transaction History** - Filterable and searchable transaction table with clickable filters

### 📝 Data Entry & Management
- Quick transaction entry form
- **Edit & Delete** - Modify or remove existing transactions
- Shop and category dropdown selection with incremental search
- Input validation and real-time guidance
- **CSV Import** - Bulk import transactions from file
- Success/error message notifications

### ⚙️ Master Management
- Shop list management (add/view)
- Category list management (add/view)
- Easy master data maintenance

### 💰 Budget Management & Forecasting
- **Monthly Budget Planning** - Set and track monthly spending limits
- **Visual Progress Tracking** - Color-coded progress bars and alerts
- **Smart Alerts** - Warning at 80%, danger at 100% of budget
- **Budget vs Actual** - Real-time comparison with remaining balance
- **Advanced Expense Forecasting** - AI-powered statistical prediction using multiple methods:
  - **Historical Analysis** - 3-year weighted average with outlier detection
  - **Trend Detection** - 12-month linear regression for spending trends
  - **Weekday Patterns** - Weekday vs. weekend spending analysis
  - **Exponential Smoothing (ETS)** - Time series smoothing for stable predictions
  - **ARIMA Model** - Auto-regressive integrated moving average
  - **Ensemble Prediction** - Weighted combination of 5 prediction methods
  - **Confidence Intervals** - Statistical range (95% confidence level)
  - **Prediction Details** - Expandable view showing all prediction methods and values

### 🔄 Recurring Expenses
- **Recurring Expense Management** - Track monthly recurring costs (subscriptions, rent, utilities)
- **Automated Calculations** - Auto-include recurring expenses in budget and dashboard
- **Flexible Scheduling** - Set day of month, start date, and optional end date
- **Active/Inactive Toggle** - Temporarily disable recurring expenses
- **Edit & Delete** - Full CRUD operations for recurring expenses

### 📈 Advanced Analytics Dashboard
- **Long-Year Data Visualization** - Comprehensive view from to present
- **Yearly Trend Analysis** - Annual spending patterns with bar charts
- **Monthly Tracking** - Recent 12-month spending trends
- **Shop Breakdown** - Top 10 shops pie chart analysis
- **Category Distribution** - Horizontal bar chart for expense categories
- **Statistical Summary** - Total expenses, monthly average, transaction count
- **Trend Analysis** - Moving averages and seasonal patterns
- **Weekday Analysis** - Spending patterns by day of the week
- **Responsive Charts** - Interactive Chart.js visualizations

### 📤 Data Import/Export
- **CSV Export** - Export transactions, summaries, and analytics
- **Excel Compatible** - UTF-8 BOM encoding for seamless Excel import
- **Bulk Import** - Import multiple transactions from CSV files
- **Validation** - Automatic data validation during import

### 🌐 Additional Features
- **Multi-language Support** - Seamless Japanese/English toggle
- **Dark Mode** - Theme switcher with automatic chart color updates (also available on login screen)
- **Unified Design** - Consistent UI between login and dashboard screens
- **Responsive Design** - Mobile-first, works on all devices
- **Interactive Charts** - Powered by Highcharts with animations
- **Search & Filter** - Click any shop or category to filter transactions
- **CRUD Operations** - Full Create, Read, Update, Delete support

---

## 🏗️ Architecture

This project follows a **modular MVC-inspired architecture** for better maintainability and scalability.

### File Structure
```
Personal-Finance-Dashboard/
├── index.php                 # Main entry point & routing
├── config.php               # Configuration & DB connection
├── functions.php            # Business logic (add/update/delete)
├── queries.php              # Data retrieval queries
├── translations.php         # Multi-language data
├── view.php                 # Main view template
├── database.sql             # Database schema & sample data
├── .env_db.example          # Environment variables template
├── api/
│   └── analytics-api.php   # REST API for analytics data
├── analytics/
│   └── index.html          # Advanced analytics dashboard (React)
├── assets/
│   ├── css/
│   │   └── style.css       # Responsive stylesheets
│   └── js/
│       └── app.js          # Chart rendering & interactivity
├── docs/
│   ├── ANALYTICS.md        # Analytics documentation
│   ├── README_ANALYTICS.md # Analytics integration guide
│   └── USAGE.md            # Detailed usage guide
└── views/
    ├── dashboard.php        # Dashboard view
    ├── entry.php           # Data entry form
    ├── management.php      # Master management
    ├── transactions_table.php  # Transaction history
    └── search_results.php  # Search results display
```

### Design Principles
- **Separation of Concerns** - Logic, data, and presentation are separated
- **DRY (Don't Repeat Yourself)** - Reusable functions and components
- **Single Responsibility** - Each file has one clear purpose
- **Prepared Statements** - SQL injection protection
- **Mobile-First** - Responsive design from the ground up

---

## 🚀 Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7+ / MariaDB 10.2+
- Apache/Nginx web server
- Web browser with JavaScript enabled

### Quick Start

1. **Clone the repository**
```bash
git clone https://github.com/nhashimoto-gm/Personal-Finance-Dashboard.git
cd Personal-Finance-Dashboard
```

2. **Create database**
```bash
mysql -u root -p
```
```sql
CREATE DATABASE finance_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE finance_db;
SOURCE database.sql;
```

3. **Configure environment**
```bash
cp .env_db.example .env_db
nano .env_db  # or use your favorite editor
```

Edit with your credentials:
```ini
DB_HOST=localhost
DB_USERNAME=your_username
DB_PASSWORD=your_password
DB_DATABASE=finance_db
```

4. **Set permissions**
```bash
chmod 600 .env_db
```

5. **Configure web server**

**Apache** - Create `.htaccess`:
```apache
DirectoryIndex index.php
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

**Nginx** - Add to your site config:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
}
```

6. **Access the application**
```
http://localhost/Personal-Finance-Dashboard/
```

### Docker Setup (Alternative)

Coming soon...

---

## 🚢 Deployment

### Shared Hosting Deployment

This application can be deployed to any shared hosting service that supports PHP 7.4+ and MySQL.

#### General Deployment Steps

1. Upload all files to your web server
2. Import `database.sql` into your MySQL database
3. Configure `.env_db` with your database credentials
4. Set proper file permissions (storage directories writable)
5. Point your web server to the root directory containing `index.php`

#### Example: Apache Configuration

```apache
DirectoryIndex index.php
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

---

## 🛠️ Technology Stack

| Category | Technology |
|----------|-----------|
| **Backend** | PHP 7.4+ with PDO |
| **Database** | MySQL 5.7+ / MariaDB 10.2+ |
| **Frontend Framework** | Bootstrap 5.3 |
| **Charts** | Highcharts (main dashboard), Chart.js 4 (analytics) |
| **JavaScript Library** | React 18 (analytics dashboard) |
| **Icons** | Bootstrap Icons |
| **API** | RESTful API for analytics data |
| **Architecture** | MVC-inspired modular design |

---

## 📖 Usage

> 📚 **Detailed Usage Guide**: Please refer to [USAGE.md](./docs/USAGE.md)

### Getting Started

1. **Create an Account**: Navigate to the registration page and create your account
2. **Login**: Use your username/email and password to log in
3. **Access Dashboard**: After login, you'll see your personalized dashboard

### Managing Your Profile

- Click your username in the top-right corner of the dashboard
- Access options to update your username or password
- Log out when finished

### Adding a Transaction
1. Click the **Data Entry** tab
2. Select the transaction date
3. Enter the amount (required, > 0)
4. Choose a shop from the dropdown
5. Select a category
6. Click **Add Transaction**

### Analyzing Expenses
1. Go to the **Dashboard** tab
2. Adjust the date range using the filter
3. View summary statistics in the cards
4. Interact with charts:
   - **Pie Chart**: Click legend items to toggle shops
   - **Bar Chart**: Hover for exact values
   - **Line Charts**: Zoom and pan
5. Click shop badges or category names in the transaction table to filter
6. Switch period views (12mo/2yr/5yr/10yr) for trend analysis

### Using Advanced Analytics
1. Click the **Advanced Analytics** tab or access directly at `/analytics/`
2. View comprehensive statistics:
   - **Overview**: 17-year summary with yearly and monthly trends
   - **Trends**: Detailed monthly spending patterns
   - **Breakdown**: Shop and category distribution analysis
   - **Insights**: Weekday analysis and seasonal patterns
3. Navigate between tabs for different visualization perspectives
4. All charts are interactive and responsive

### Managing Recurring Expenses
1. Navigate to the **Master** tab
2. Scroll to the **Recurring Expenses** section
3. Click **Add Recurring Expense**
4. Enter expense details:
   - Name (e.g., "Netflix", "Rent", "Internet")
   - Shop and Category
   - Monthly amount
   - Day of month (1-31)
   - Start date and optional end date
5. Recurring expenses automatically appear in budget calculations and dashboard

### Managing Master Data
1. Navigate to the **Master** tab
2. Click **Add** button next to Shops or Categories
3. Enter the name in the prompt
4. New entries appear immediately in dropdowns

### Switching Themes/Languages
- **Dark Mode**: Click the moon/sun icon in the navbar
- **Language**: Click the language toggle (EN/JP)

---

## 🎨 Customization

### Adding Custom Charts

Edit `assets/js/app.js`:

```javascript
function renderCustomChart(data) {
    Highcharts.chart('customChart', {
        chart: { type: 'line' },
        title: { text: 'Custom Analysis' },
        series: [{
            name: 'Series 1',
            data: data
        }]
    });
}
```

Add to dashboard view in `views/dashboard.php`:
```html
<div class="col-md-6 mb-4">
    <div class="card">
        <div class="card-body">
            <div id="customChart"></div>
        </div>
    </div>
</div>
```

### Modifying Color Schemes

Edit `assets/css/style.css`:
```css
:root {
    --primary-color: #your-color;
    --chart-height: 450px;
}
```

### Adding New Categories/Shops

**Via UI**: Use the Master Management tab

**Via SQL**:
```sql
INSERT INTO cat_1_labels (label) VALUES ('New Shop');
INSERT INTO cat_2_labels (label) VALUES ('New Category');
```

---

## 🔒 Security

### Implemented Protections
- ✅ **User Authentication**: Session-based authentication with secure login/logout
- ✅ **Password Security**: Bcrypt hashing for all user passwords
- ✅ **SQL Injection**: PDO prepared statements throughout
- ✅ **XSS**: `htmlspecialchars()` on all user-generated outputs
- ✅ **CSRF Protection**: Token-based validation on all POST requests
- ✅ **Rate Limiting**: Protection against brute-force attacks
- ✅ **Session Management**: Secure session handling with 30-minute timeout
- ✅ **Session Configuration**: HttpOnly cookies, SameSite protection, strict mode
- ✅ **Multi-User Isolation**: User-specific data access with query-level filtering
- ✅ **Environment Variables**: Credentials in `.env_db` (gitignored)

### Production Checklist
- [ ] Disable error display (`display_errors = 0`)
- [ ] Enable HTTPS
- [x] Implement CSRF protection
- [x] Add rate limiting
- [x] Set secure session cookies
- [ ] Regular database backups
- [ ] Update dependencies

### Recommended .htaccess Security Headers
```apache
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
Header set Referrer-Policy "strict-origin-when-cross-origin"
```

---

## 📝 Refactoring Story

This codebase was refactored from a **1,600+ line monolithic file** into a clean, modular architecture:

### Before vs After

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| **Files** | 1 file | 17 files | Better organization |
| **Lines/File** | 1,600+ | 100-300 | Easier to navigate |
| **Structure** | Monolithic | Modular | Clear separation |
| **CSS** | Embedded | External | Reusable |
| **JavaScript** | Embedded | External | Cacheable |
| **Maintainability** | Low | High | 5x faster edits |
| **Testability** | None | Easy | Unit testable |

### Key Improvements
- 📁 **Modular Design**: Each file has a single responsibility
- 🔄 **Reusable Components**: Functions can be used across the app
- 🧪 **Testable**: Business logic separated for easy testing
- 📚 **Documented**: Clear structure and naming conventions
- 🚀 **Extensible**: Easy to add new features

---

## 🤝 Contributing

Contributions are welcome! Here's how you can help:

### Ways to Contribute
- 🐛 Report bugs
- 💡 Suggest new features
- 📖 Improve documentation
- 🔧 Submit pull requests
- ⭐ Star the project

### Development Process

1. **Fork** the repository
2. **Clone** your fork:
   ```bash
   git clone https://github.com/nhashimoto-gm/Personal-Finance-Dashboard.git
   ```
3. **Create** a feature branch:
   ```bash
   git checkout -b feature/amazing-feature
   ```
4. **Make** your changes
5. **Test** thoroughly
6. **Commit** with clear messages:
   ```bash
   git commit -m "Add: amazing new feature"
   ```
7. **Push** to your fork:
   ```bash
   git push origin feature/amazing-feature
   ```
8. **Open** a Pull Request

### Code Style Guidelines
- Follow PSR-12 for PHP
- Use meaningful variable names
- Comment complex logic
- Keep functions small and focused
- Write self-documenting code

---

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

```
MIT License

Copyright (c) 2024 NHGM

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

## 🐛 Bug Reports & Feature Requests

### Reporting Bugs

Please include:
- **Description**: Clear description of the bug
- **Steps to Reproduce**: Detailed steps
- **Expected Behavior**: What should happen
- **Actual Behavior**: What actually happens
- **Environment**: PHP version, MySQL version, browser
- **Screenshots**: If applicable

### Requesting Features

Please include:
- **Use Case**: Why this feature is needed
- **Proposed Solution**: How it should work
- **Alternatives Considered**: Other approaches you've thought of

[Create an Issue](https://github.com/nhashimoto-gm/Personal-Finance-Dashboard/issues/new)

---

## 🗺️ Roadmap

### Version 1.1 (✅ Completed)
- [x] **Transaction Edit & Delete** - Modify or remove transactions with inline controls
- [x] **Export to CSV/Excel** - Export transactions and summaries with UTF-8 BOM support
- [x] **Budget Planning & Alerts** - Set monthly budgets with visual progress tracking
- [x] **Import from CSV** - Bulk import transactions with validation
- [x] **Advanced Analytics Dashboard** - React-based analytics with Chart.js visualizations
- [x] **REST API** - Analytics API endpoints for data retrieval
- [x] **17-Year Data Analysis** - Comprehensive historical data visualization
- [x] **Trend & Pattern Analysis** - Moving averages, weekday, and seasonal patterns
- [x] **User Authentication & Multi-User Support** - Secure login system with multi-account isolation
- [x] **Recurring Expenses** - Track and manage monthly recurring costs
- [x] **User Profile Management** - Username and password update functionality
- [x] **Unified Design** - Consistent UI between login and dashboard
- [x] **User Display in Header** - Current user display with dropdown menu
- [x] **AI-Powered Expense Forecasting** - Statistical ML-like prediction engine with ensemble methods

### Version 1.2 (Planned)
- [ ] Transaction memo/notes field
- [ ] Transaction categories hierarchy
- [ ] Advanced filtering options
- [ ] Email notifications for budget alerts
- [ ] PDF/Excel report generation from analytics
- [ ] Two-factor authentication (2FA)
- [ ] Password reset via email

### Version 2.0 (Future)
- [ ] Multi-currency support
- [ ] Bank account integration
- [ ] Receipt photo upload
- [ ] Mobile app (PWA)
- [ ] AI-powered insights and anomaly detection
- [ ] Advanced predictive analytics with external data integration

---

## 📧 Contact & Support

- **Issues**: [GitHub Issues](https://github.com/nhashimoto-gm/Personal-Finance-Dashboard/issues)
- **Discussions**: [GitHub Discussions](https://github.com/nhashimoto-gm/Personal-Finance-Dashboard/discussions)
- **Email**: 94941257+nhashimoto-gm@users.noreply.github.com

---

## 🙏 Acknowledgments

- [Bootstrap](https://getbootstrap.com/) - UI framework
- [Highcharts](https://www.highcharts.com/) - Main dashboard chart library
- [Chart.js](https://www.chartjs.org/) - Analytics dashboard visualization
- [React](https://reactjs.org/) - Analytics dashboard framework
- [Bootstrap Icons](https://icons.getbootstrap.com/) - Icon library

---

## ⭐ Show Your Support

If you find this project useful, please consider:
- ⭐ **Starring** the repository
- 🐦 **Sharing** on social media
- 📝 **Writing** a blog post about it
- 💬 **Telling** your friends

---

**Made with ❤️ by NHGM**

[⬆ Back to Top](#-personal-finance-dashboard)
