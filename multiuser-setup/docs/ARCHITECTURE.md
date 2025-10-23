# Architecture Overview - Multi-User Personal Finance Dashboard

## Table Separation Multi-Tenancy Architecture

### Design Philosophy

This application uses a **table separation** approach for multi-tenancy, where each user gets their own set of database tables. This provides:

- **Maximum data isolation**: Complete separation between users
- **Enhanced security**: No risk of data leakage between users
- **Easy user deletion**: Simply drop the user's tables
- **Per-user customization**: Possible to customize schema per user in future

---

## System Architecture

```
┌─────────────────────────────────────────┐
│          Web Browser (Client)           │
└───────────────┬─────────────────────────┘
                │ HTTPS
                ▼
┌─────────────────────────────────────────┐
│       Web Server (Apache/Nginx)         │
│              public/                    │
└───────────────┬─────────────────────────┘
                │
                ▼
┌─────────────────────────────────────────┐
│        Application Layer (PHP)          │
│  ┌───────────────────────────────────┐  │
│  │  Router (index.php)               │  │
│  └─────────────┬─────────────────────┘  │
│                │                         │
│  ┌─────────────▼─────────────────────┐  │
│  │  Authentication Layer              │  │
│  │  - Session Management              │  │
│  │  - CSRF Protection                 │  │
│  │  - User Validation                 │  │
│  └─────────────┬─────────────────────┘  │
│                │                         │
│  ┌─────────────▼─────────────────────┐  │
│  │  Table Resolver                    │  │
│  │  - Prefix Validation               │  │
│  │  - Table Name Generation           │  │
│  └─────────────┬─────────────────────┘  │
│                │                         │
│  ┌─────────────▼─────────────────────┐  │
│  │  Business Logic                    │  │
│  │  - Functions & Queries             │  │
│  └─────────────┬─────────────────────┘  │
└────────────────┼──────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│          MySQL Database                 │
│  ┌───────────────────────────────────┐  │
│  │  Shared Tables                    │  │
│  │  - users                          │  │
│  │  - sessions                       │  │
│  │  - login_attempts                 │  │
│  │  - user_preferences               │  │
│  └───────────────────────────────────┘  │
│  ┌───────────────────────────────────┐  │
│  │  User 1 Tables                    │  │
│  │  - user_1_source                  │  │
│  │  - user_1_cat_1_labels            │  │
│  │  - user_1_cat_2_labels            │  │
│  │  - user_1_view                    │  │
│  └───────────────────────────────────┘  │
│  ┌───────────────────────────────────┐  │
│  │  User 2 Tables                    │  │
│  │  - user_2_source                  │  │
│  │  - user_2_cat_1_labels            │  │
│  │  - user_2_cat_2_labels            │  │
│  │  - user_2_view                    │  │
│  └───────────────────────────────────┘  │
└─────────────────────────────────────────┘
```

---

## Core Components

### 1. Configuration Layer (`src/core/config.php`)

- Loads environment variables from `.env_db`
- Establishes database connection
- Provides utility functions (redirect, asset, config)
- Error handling configuration

### 2. Session Management (`src/core/session_config.php`)

- Secure session configuration
- Session hijacking prevention (IP + User-Agent validation)
- Session timeout handling (30 minutes)
- Flash message system

### 3. Authentication (`src/core/auth.php`)

- User registration with table creation
- Login with rate limiting (5 attempts per 15 minutes)
- Password hashing (bcrypt, cost 12)
- Session management
- Authentication checks

### 4. CSRF Protection (`src/core/csrf.php`)

- Token generation and validation
- Automatic form field generation
- POST request validation

### 5. Table Resolver (`src/core/table_resolver.php`)

- Dynamic table name resolution
- Prefix validation (security-critical)
- Table existence checks
- User table creation/deletion
- Table statistics

---

## Data Flow

### User Registration Flow

```
1. User submits registration form
   ↓
2. Validate input (username, email, password)
   ↓
3. Check if user already exists
   ↓
4. Hash password (bcrypt)
   ↓
5. Generate table prefix (user_N)
   ↓
6. Begin database transaction
   ↓
7. Insert into users table
   ↓
8. Insert into user_preferences table
   ↓
9. Call create_user_tables() stored procedure
   ↓
10. Call insert_sample_data() stored procedure
   ↓
11. Commit transaction
   ↓
12. Redirect to login
```

### User Login Flow

```
1. User submits login form
   ↓
2. Check rate limiting (IP-based)
   ↓
3. Query users table (username or email)
   ↓
4. Verify password with password_verify()
   ↓
5. Record login attempt
   ↓
6. Regenerate session ID
   ↓
7. Set session variables:
   - user_id
   - username
   - table_prefix
   - logged_in
   - ip_address
   - user_agent
   ↓
8. Save session to database
   ↓
9. Update last_login timestamp
   ↓
10. Redirect to dashboard
```

### Data Access Flow

```
1. User accesses dashboard
   ↓
2. Check authentication
   ↓
3. Validate session (timeout, hijacking)
   ↓
4. Get table_prefix from session
   ↓
5. Validate table prefix
   ↓
6. Generate table names
   ↓
7. Execute queries on user's tables
   ↓
8. Return results
```

---

## Security Layers

### Layer 1: Transport Security
- HTTPS (TLS 1.2+)
- Secure cookies (HTTPOnly, Secure, SameSite)

### Layer 2: Session Security
- Session ID regeneration
- Session timeout (30 minutes)
- IP address validation
- User-Agent validation

### Layer 3: Authentication
- Password hashing (bcrypt, cost 12)
- Rate limiting (5 attempts per 15 minutes)
- Account lockout

### Layer 4: Authorization
- Require authentication for protected pages
- Validate table_prefix before database access

### Layer 5: Input Validation
- CSRF token validation
- SQL injection prevention (prepared statements)
- XSS prevention (htmlspecialchars)
- Table name validation (regex + database check)

### Layer 6: Output Encoding
- HTML entity encoding
- JSON encoding for API responses

---

## Database Schema

### Shared Tables

#### users
- User account information
- Unique username and email
- Password hash
- Table prefix (unique)
- Email verification status
- Last login timestamp

#### sessions
- Active session tracking
- Foreign key to users table
- IP address and user agent
- Last activity timestamp

#### login_attempts
- Login attempt logging
- Rate limiting enforcement
- Security monitoring

#### user_preferences
- User settings (language, theme, etc.)
- Foreign key to users table

### User-Specific Tables

Each user gets 4 tables:

#### {prefix}_source
- Transaction records
- Date, shop, category, amount
- Optional memo field

#### {prefix}_cat_1_labels
- Shop master data
- Label, sort order, active status

#### {prefix}_cat_2_labels
- Category master data
- Label, sort order, active status

#### {prefix}_view
- Joined view of transactions with labels
- Read-only, automatically updated

---

## Scalability Considerations

### Current Design Limits

- **Recommended maximum**: 1,000 users (4,000 tables)
- **Hard MySQL limit**: ~64,000 tables (16,000 users)

### Performance Optimizations

1. **Caching**
   - Session storage in Redis/Memcached
   - Master data caching
   - Query result caching

2. **Indexing**
   - All foreign keys indexed
   - Composite indexes on frequently queried columns
   - Full-text search on labels (if needed)

3. **Connection Pooling**
   - Persistent database connections
   - Connection reuse

### Migration Path

When approaching 1,000 users, migrate to **row-level separation**:

- Consolidate all user tables into shared tables
- Add `user_id` column to each table
- Migrate data: `INSERT INTO transactions SELECT user_id, * FROM user_N_source`
- Update queries to filter by `user_id`

---

## File Structure

```
Personal-Finance-Dashboard-multi-account-architecture/
├── README.md
├── .gitignore
├── .env_db.example
├── docs/
│   ├── ARCHITECTURE.md (this file)
│   ├── SETUP_GUIDE.md
│   └── MIGRATION_GUIDE.md
├── database/
│   ├── schema.sql
│   └── migrations/
├── src/
│   ├── core/
│   │   ├── config.php
│   │   ├── session_config.php
│   │   ├── auth.php
│   │   ├── csrf.php
│   │   └── table_resolver.php
│   ├── functions/
│   │   ├── functions.php
│   │   └── queries.php
│   └── views/
│       └── (view templates)
├── public/
│   ├── index.php
│   ├── .htaccess
│   └── assets/
│       ├── css/
│       └── js/
└── tests/
```

---

## Error Handling

### Production Mode (`APP_ENV=production`)
- Display user-friendly error messages
- Log errors to file
- Do not expose stack traces

### Development Mode (`APP_ENV=development`)
- Display detailed error messages
- Show stack traces
- Enable debugging tools

---

## Monitoring & Logging

### Key Metrics to Monitor

1. **Security Events**
   - Failed login attempts
   - CSRF token failures
   - Session hijacking attempts

2. **Performance Metrics**
   - Average page load time
   - Database query time
   - Number of active sessions

3. **Usage Statistics**
   - Total users
   - Total transactions
   - Active users (daily/weekly/monthly)

### Log Files

- Application log: `/var/log/finance_dashboard.log`
- Apache/Nginx access log
- MySQL slow query log

---

## Future Enhancements

### Phase 1
- Admin dashboard
- Email verification
- Password reset
- User settings page

### Phase 2
- API endpoints (RESTful)
- Mobile app support
- Two-factor authentication

### Phase 3
- Multi-currency support
- Budgeting features
- Reporting enhancements
- Data export (CSV, PDF)

---

**Version**: 1.0.0
**Last Updated**: 2025-10-23
