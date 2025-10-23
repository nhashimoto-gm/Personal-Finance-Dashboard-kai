# Personal Finance Dashboard - Multi-Account Architecture

A multi-user personal finance tracking dashboard with complete data isolation using table separation architecture.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-7.4+-blue.svg)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange.svg)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)](https://getbootstrap.com/)

---

## Project Status

**Version**: 1.0.0-alpha
**Status**: Initial Setup / Development Phase
**Architecture**: Table Separation Multi-Tenancy

---

## Overview

This is the multi-user version of Personal Finance Dashboard, designed to support multiple independent user accounts with complete data isolation. Each user gets their own set of database tables, ensuring maximum security and privacy.

### Key Features

- **Multi-User Support**: Independent accounts with secure authentication
- **Complete Data Isolation**: Each user has dedicated database tables
- **User Registration & Login**: Full authentication system with email verification
- **Password Security**: Bcrypt hashing with strong password policies
- **Session Management**: Secure session handling with hijacking prevention
- **CSRF Protection**: Token-based protection for all forms
- **Rate Limiting**: Brute-force attack prevention
- **All Original Features**: Dashboard, transaction entry, master data management

---

## Architecture

### Table Separation Approach

Each user gets their own set of tables:
```
Database: finance_db
├── users (shared)
├── sessions (shared)
├── login_attempts (shared)
├── user_preferences (shared)
├── user_1_source
├── user_1_cat_1_labels
├── user_1_cat_2_labels
├── user_1_view
├── user_2_source
├── user_2_cat_1_labels
├── user_2_cat_2_labels
├── user_2_view
└── ...
```

**Benefits**:
- ✅ Maximum security (complete data separation)
- ✅ Easy user deletion (DROP TABLE)
- ✅ No query interference between users
- ✅ Per-user customization possible

**Suitable for**: Up to 1,000 users

---

## File Structure

```
Personal-Finance-Dashboard-multi-account-architecture/
├── README.md
├── LICENSE
├── .gitignore
├── .env_db.example
├── docs/
│   ├── ARCHITECTURE.md          # Architecture documentation
│   ├── MULTIUSER_DESIGN.md      # Multi-user design specification
│   ├── SETUP_GUIDE.md           # Setup instructions
│   └── MIGRATION_GUIDE.md       # Migration from single-user version
├── database/
│   ├── schema.sql               # Complete database schema
│   ├── sample_data.sql          # Sample data for testing
│   └── migrations/              # Database migration scripts
├── src/
│   ├── auth/                    # Authentication system
│   │   ├── login.php
│   │   ├── register.php
│   │   ├── logout.php
│   │   ├── password_reset.php
│   │   └── verify_email.php
│   ├── core/                    # Core functionality
│   │   ├── config.php
│   │   ├── auth.php
│   │   ├── session_config.php
│   │   ├── table_resolver.php
│   │   ├── security.php
│   │   └── csrf.php
│   ├── functions/               # Business logic
│   │   ├── functions.php
│   │   └── queries.php
│   └── views/                   # View templates
│       ├── dashboard.php
│       ├── entry.php
│       ├── management.php
│       └── user_settings.php
├── public/                      # Public directory (web root)
│   ├── index.php                # Main entry point
│   ├── assets/
│   │   ├── css/
│   │   │   ├── style.css
│   │   │   └── auth.css
│   │   └── js/
│   │       ├── app.js
│   │       └── auth.js
│   └── .htaccess
└── tests/                       # Test files
    ├── AuthTest.php
    └── SecurityTest.php
```

---

## Requirements

- PHP 7.4 or higher
- MySQL 5.7+ / MariaDB 10.2+
- Apache/Nginx web server
- Composer (for dependencies)
- Web browser with JavaScript enabled

---

## Quick Start

### 1. Clone the Repository

```bash
git clone https://github.com/YOUR_USERNAME/Personal-Finance-Dashboard-multi-account-architecture.git
cd Personal-Finance-Dashboard-multi-account-architecture
```

### 2. Create Database

```bash
mysql -u root -p
```

```sql
CREATE DATABASE finance_multiuser_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE finance_multiuser_db;
SOURCE database/schema.sql;
```

### 3. Configure Environment

```bash
cp .env_db.example .env_db
nano .env_db
```

Edit with your credentials:
```ini
DB_HOST=localhost
DB_USERNAME=your_username
DB_PASSWORD=your_password
DB_DATABASE=finance_multiuser_db
```

### 4. Set Permissions

```bash
chmod 600 .env_db
```

### 5. Configure Web Server

Point your web server document root to the `public/` directory.

**Apache example** (`/etc/apache2/sites-available/finance.conf`):
```apache
<VirtualHost *:80>
    ServerName finance.local
    DocumentRoot /path/to/Personal-Finance-Dashboard-multi-account-architecture/public

    <Directory /path/to/Personal-Finance-Dashboard-multi-account-architecture/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 6. Access the Application

```
http://localhost/
```

---

## Initial Setup

1. **Register First User**: Click "Register" and create an account
2. **Verify Email** (if enabled): Check email for verification link
3. **Login**: Use your credentials to login
4. **Start Using**: Access dashboard and start tracking finances

---

## Security Features

### Implemented

- ✅ **Password Hashing**: Bcrypt with cost factor 12
- ✅ **SQL Injection Protection**: PDO prepared statements + table name validation
- ✅ **XSS Protection**: `htmlspecialchars()` on all outputs
- ✅ **CSRF Protection**: Token-based validation
- ✅ **Session Security**: HTTPOnly, Secure, SameSite cookies
- ✅ **Rate Limiting**: Login attempt restrictions (5 attempts per 15 minutes)
- ✅ **Session Hijacking Prevention**: IP and User-Agent validation
- ✅ **Password Policy**: Minimum 8 characters, mixed case, numbers

### Recommended for Production

- [ ] Enable HTTPS (Let's Encrypt)
- [ ] Configure security headers (X-Frame-Options, CSP, etc.)
- [ ] Set up regular database backups
- [ ] Enable email verification
- [ ] Configure error logging (don't display errors to users)
- [ ] Set up monitoring and alerting

---

## Development Roadmap

### Phase 1: Foundation (Weeks 1-2) ✅

- [x] Database schema design
- [x] Authentication system
- [x] Session management
- [x] Table resolver
- [ ] Basic UI (login/register)

### Phase 2: Integration (Weeks 3-4)

- [ ] Migrate existing features
- [ ] Modify queries for multi-user
- [ ] Update all views
- [ ] Testing with multiple users

### Phase 3: Additional Features (Weeks 5-6)

- [ ] Email verification
- [ ] Password reset
- [ ] User settings page
- [ ] Admin dashboard

### Phase 4: Production Ready (Weeks 7-8)

- [ ] Performance optimization
- [ ] Security hardening
- [ ] Documentation completion
- [ ] Deployment preparation

---

## Technology Stack

| Category | Technology |
|----------|-----------|
| **Backend** | PHP 7.4+ with PDO |
| **Database** | MySQL 5.7+ / MariaDB 10.2+ |
| **Frontend** | Bootstrap 5.3 |
| **Charts** | Highcharts |
| **Security** | Bcrypt, PDO, CSRF tokens |
| **Architecture** | Multi-tenancy (table separation) |

---

## Documentation

- [Architecture Overview](docs/ARCHITECTURE.md)
- [Multi-User Design Specification](docs/MULTIUSER_DESIGN.md)
- [Setup Guide](docs/SETUP_GUIDE.md)
- [Migration from Single-User](docs/MIGRATION_GUIDE.md)
- [API Documentation](docs/API.md) (Coming soon)

---

## Differences from Single-User Version

| Feature | Single-User | Multi-User |
|---------|-------------|------------|
| **Authentication** | None | Full system with registration |
| **Data Isolation** | N/A | Complete (separate tables) |
| **Tables per User** | 3 | 4 (source, cat_1, cat_2, view) |
| **Security** | Basic | Enterprise-level |
| **Scalability** | Single user | Up to 1,000 users |

---

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

### Development Setup

```bash
# Clone the repository
git clone https://github.com/YOUR_USERNAME/Personal-Finance-Dashboard-multi-account-architecture.git

# Create a feature branch
git checkout -b feature/your-feature-name

# Make your changes and test thoroughly

# Commit with clear messages
git commit -m "Add: description of changes"

# Push to your fork
git push origin feature/your-feature-name

# Open a Pull Request
```

---

## Testing

### Manual Testing

1. Register multiple users
2. Login with each user
3. Add transactions for each user
4. Verify data isolation (User A cannot see User B's data)
5. Test logout and re-login
6. Test password reset flow

### Automated Testing (Coming Soon)

```bash
vendor/bin/phpunit tests/
```

---

## Performance Considerations

- **Caching**: Implement Redis/Memcached for session storage
- **Indexing**: All tables have appropriate indexes
- **Query Optimization**: Use prepared statements and limit result sets
- **Table Limit**: Recommended maximum 1,000 users (4,000 tables)

---

## Scaling Beyond 1,000 Users

If you exceed 1,000 users, consider migrating to **row-level separation** approach:
- All users share the same tables
- Add `user_id` column to each table
- Filter all queries by `user_id`

See [MIGRATION_GUIDE.md](docs/MIGRATION_GUIDE.md) for details.

---

## License

This project is open source and available under the [MIT License](LICENSE).

---

## Acknowledgments

- Based on [Personal Finance Dashboard](https://github.com/nhashimoto-gm/Personal-Finance-Dashboard)
- [Bootstrap](https://getbootstrap.com/) - UI framework
- [Highcharts](https://www.highcharts.com/) - Chart library
- [PHP Community](https://www.php.net/) - Best practices and security guidelines

---

## Support

- **Issues**: [GitHub Issues](https://github.com/YOUR_USERNAME/Personal-Finance-Dashboard-multi-account-architecture/issues)
- **Discussions**: [GitHub Discussions](https://github.com/YOUR_USERNAME/Personal-Finance-Dashboard-multi-account-architecture/discussions)

---

## Security Disclosure

If you discover a security vulnerability, please email security@example.com instead of using the issue tracker.

---

**Status**: 🚧 Under Development
**Version**: 1.0.0-alpha
**Last Updated**: 2025-10-23

---

[⬆ Back to Top](#personal-finance-dashboard---multi-account-architecture)
