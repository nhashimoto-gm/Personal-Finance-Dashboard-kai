# Setup Guide - Personal Finance Dashboard (Multi-User)

Complete setup instructions for the multi-user version of Personal Finance Dashboard.

---

## Table of Contents

1. [System Requirements](#system-requirements)
2. [Installation Steps](#installation-steps)
3. [Database Setup](#database-setup)
4. [Web Server Configuration](#web-server-configuration)
5. [First User Registration](#first-user-registration)
6. [Troubleshooting](#troubleshooting)

---

## System Requirements

### Minimum Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7+ or MariaDB 10.2+
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **RAM**: 512MB minimum, 1GB recommended
- **Storage**: 100MB + (grows with user data)

### PHP Extensions Required

```bash
php -m | grep -E 'pdo|pdo_mysql|mbstring|openssl|json'
```

Required extensions:
- `pdo`
- `pdo_mysql`
- `mbstring`
- `openssl`
- `json`
- `session`

Install missing extensions (Ubuntu/Debian):
```bash
sudo apt-get install php-mysql php-mbstring php-json
```

---

## Installation Steps

### Step 1: Download the Code

#### Option A: Clone from GitHub

```bash
git clone https://github.com/YOUR_USERNAME/Personal-Finance-Dashboard-multi-account-architecture.git
cd Personal-Finance-Dashboard-multi-account-architecture
```

#### Option B: Download ZIP

1. Download ZIP from GitHub
2. Extract to your web server directory
3. Navigate to the directory

---

### Step 2: Database Setup

#### Create Database

```bash
mysql -u root -p
```

```sql
CREATE DATABASE finance_multiuser_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### Create Database User (Recommended)

```sql
CREATE USER 'finance_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON finance_multiuser_db.* TO 'finance_user'@'localhost';
FLUSH PRIVILEGES;
```

#### Import Schema

```bash
mysql -u finance_user -p finance_multiuser_db < database/schema.sql
```

#### Verify Tables

```sql
USE finance_multiuser_db;
SHOW TABLES;
```

Expected output:
```
+-------------------------------+
| Tables_in_finance_multiuser_db|
+-------------------------------+
| users                         |
| sessions                      |
| login_attempts                |
| user_preferences              |
+-------------------------------+
```

---

### Step 3: Environment Configuration

#### Copy Environment Template

```bash
cp .env_db.example .env_db
```

#### Edit Configuration

```bash
nano .env_db
```

**Required settings**:
```ini
# Database Connection
DB_HOST=localhost
DB_USERNAME=finance_user
DB_PASSWORD=your_strong_password
DB_DATABASE=finance_multiuser_db

# Application Settings
APP_ENV=production
APP_DEBUG=false

# Security (generate random strings)
SESSION_SECRET=random_32_character_string_here
CSRF_SECRET=another_random_32_character_string

# Email Settings (optional, for verification)
MAIL_ENABLED=false
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your_email@example.com
MAIL_PASSWORD=your_email_password
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Finance Dashboard"
```

#### Generate Random Secrets

```bash
# For SESSION_SECRET and CSRF_SECRET
php -r "echo bin2hex(random_bytes(16)) . PHP_EOL;"
```

#### Set File Permissions

```bash
chmod 600 .env_db
```

---

### Step 4: Web Server Configuration

#### Apache Configuration

**Option A: VirtualHost (Recommended)**

Create `/etc/apache2/sites-available/finance.conf`:

```apache
<VirtualHost *:80>
    ServerName finance.local
    ServerAlias www.finance.local

    DocumentRoot /var/www/Personal-Finance-Dashboard-multi-account-architecture/public

    <Directory /var/www/Personal-Finance-Dashboard-multi-account-architecture/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/finance_error.log
    CustomLog ${APACHE_LOG_DIR}/finance_access.log combined
</VirtualHost>
```

Enable the site:
```bash
sudo a2ensite finance
sudo a2enmod rewrite
sudo systemctl reload apache2
```

Add to `/etc/hosts`:
```
127.0.0.1    finance.local
```

**Option B: .htaccess in public directory**

The `public/.htaccess` file is already configured:

```apache
DirectoryIndex index.php

RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
```

---

#### Nginx Configuration

Create `/etc/nginx/sites-available/finance`:

```nginx
server {
    listen 80;
    server_name finance.local;

    root /var/www/Personal-Finance-Dashboard-multi-account-architecture/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
}
```

Enable the site:
```bash
sudo ln -s /etc/nginx/sites-available/finance /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

### Step 5: File Permissions

Set appropriate permissions:

```bash
# Make sure web server can read files
sudo chown -R www-data:www-data /var/www/Personal-Finance-Dashboard-multi-account-architecture

# Ensure .env_db is secure
chmod 600 .env_db

# Public directory should be readable
chmod 755 public
chmod 644 public/*
```

---

## First User Registration

### Access the Application

Open your browser and navigate to:
```
http://finance.local/
```

Or if using localhost:
```
http://localhost/Personal-Finance-Dashboard-multi-account-architecture/public/
```

### Register First User

1. Click **"Register"** button
2. Fill in registration form:
   - **Username**: Unique username (3-20 characters)
   - **Email**: Valid email address
   - **Password**: Strong password (8+ characters, mixed case, numbers)
   - **Confirm Password**: Re-enter password
3. Click **"Create Account"**
4. Upon successful registration:
   - User tables are automatically created (`user_1_source`, etc.)
   - Sample data is inserted (optional)
   - Redirect to login page

### First Login

1. Enter your **username/email** and **password**
2. Click **"Login"**
3. You will be redirected to the dashboard

---

## Testing the Installation

### Verify Multi-User Functionality

1. **Register a second user** (use different browser or incognito mode)
2. **Login as User 1**, add some transactions
3. **Logout**, then **Login as User 2**
4. **Verify**: User 2 should NOT see User 1's transactions
5. Add transactions for User 2
6. **Logout**, **Login as User 1** again
7. **Verify**: User 1's data is intact and User 2's data is not visible

### Check Database Tables

```sql
USE finance_multiuser_db;
SHOW TABLES;
```

After 2 users registered, you should see:
```
users
sessions
login_attempts
user_preferences
user_1_source
user_1_cat_1_labels
user_1_cat_2_labels
user_2_source
user_2_cat_2_labels
user_2_cat_2_labels
```

---

## Troubleshooting

### Issue: Database Connection Error

**Error**: `Database connection error: Access denied`

**Solution**:
1. Check `.env_db` credentials
2. Verify MySQL user has privileges:
   ```sql
   SHOW GRANTS FOR 'finance_user'@'localhost';
   ```
3. Test connection manually:
   ```bash
   mysql -u finance_user -p finance_multiuser_db
   ```

---

### Issue: 404 Page Not Found

**Solution**:
1. Check Apache/Nginx configuration
2. Ensure `mod_rewrite` is enabled (Apache):
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```
3. Verify DocumentRoot points to `public/` directory

---

### Issue: Session Not Working

**Error**: Login successful but immediately logged out

**Solution**:
1. Check PHP session configuration:
   ```php
   <?php
   phpinfo();
   // Look for session.save_path
   ?>
   ```
2. Ensure session directory is writable:
   ```bash
   sudo chmod 1733 /var/lib/php/sessions
   ```
3. Check `.env_db` for correct `SESSION_SECRET`

---

### Issue: CSRF Token Mismatch

**Error**: `Invalid CSRF token`

**Solution**:
1. Clear browser cookies
2. Verify `CSRF_SECRET` is set in `.env_db`
3. Check that forms include CSRF token:
   ```php
   <?= csrfField() ?>
   ```

---

### Issue: Cannot Create User Tables

**Error**: `Table creation failed`

**Solution**:
1. Verify database user has `CREATE` privilege:
   ```sql
   GRANT CREATE ON finance_multiuser_db.* TO 'finance_user'@'localhost';
   ```
2. Check MySQL error log:
   ```bash
   sudo tail -f /var/log/mysql/error.log
   ```

---

### Issue: Email Verification Not Working

**Solution**:
1. Check `MAIL_ENABLED=true` in `.env_db`
2. Verify SMTP credentials
3. Test email sending manually
4. For development, disable email verification temporarily:
   ```ini
   MAIL_ENABLED=false
   ```

---

## Security Checklist

Before going to production, ensure:

- [ ] `.env_db` has `APP_DEBUG=false`
- [ ] `.env_db` permissions are `600`
- [ ] Strong database password is set
- [ ] HTTPS is enabled (Let's Encrypt)
- [ ] Security headers are configured
- [ ] Error display is disabled in `php.ini`:
  ```ini
  display_errors = Off
  log_errors = On
  error_log = /var/log/php/error.log
  ```
- [ ] Regular database backups are configured
- [ ] File upload directory (if any) is outside web root

---

## Next Steps

1. **Customize**: Edit `public/assets/css/style.css` for branding
2. **Configure Email**: Set up SMTP for password reset
3. **Add Admin User**: Create first admin account
4. **Regular Backups**: Set up automated database backups
5. **Monitor**: Set up logging and monitoring

---

## Need Help?

- **Documentation**: Check [docs/](../docs/) folder
- **Issues**: [GitHub Issues](https://github.com/YOUR_USERNAME/Personal-Finance-Dashboard-multi-account-architecture/issues)
- **Community**: [GitHub Discussions](https://github.com/YOUR_USERNAME/Personal-Finance-Dashboard-multi-account-architecture/discussions)

---

**Last Updated**: 2025-10-23
