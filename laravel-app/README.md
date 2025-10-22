# Personal Finance Dashboard - Laravel Edition

A modern, full-featured personal finance tracking dashboard built with **Laravel 10**, featuring a clean MVC architecture, RESTful API, and comprehensive data visualization.

[![Laravel](https://img.shields.io/badge/Laravel-10.x-red.svg)](https://laravel.com/)
[![PHP](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

---

## Overview

This is a **Laravel implementation** of the Personal Finance Dashboard, completely rewritten from the original plain PHP version. It demonstrates best practices in modern PHP development using Laravel's powerful features.

### Key Improvements Over Plain PHP Version

| Feature | Plain PHP | Laravel |
|---------|-----------|---------|
| **Architecture** | Custom MVC-inspired | Laravel MVC |
| **ORM** | Manual PDO queries | Eloquent ORM |
| **Routing** | Manual routing | Laravel Router |
| **Validation** | Manual validation | Laravel Validation |
| **API** | Manual implementation | API Resources |
| **Auth Ready** | None | Laravel Sanctum ready |
| **Testing** | None | PHPUnit integrated |
| **Security** | Manual CSRF | Built-in CSRF |
| **Code Lines** | ~1,500 | ~800 (more readable) |

---

## Features

### Core Functionality

- **Transaction Management**: Full CRUD operations for financial transactions
- **Dashboard Analytics**:
  - Period-based statistics (total, average, count)
  - Shop-wise expense breakdown (pie chart)
  - Category-wise top 10 analysis (bar chart)
  - Daily trend visualization (line charts)
  - Cumulative expense tracking
- **Master Data Management**: Shops and Categories CRUD
- **Multi-language Support**: English and Japanese (easily extensible)
- **Dark Mode**: Theme switcher with persistent preference
- **Responsive Design**: Mobile-first Bootstrap 5 interface

### Laravel-Specific Features

- **RESTful API** (v1.2 roadmap): Complete API endpoints with Laravel Resources
- **Eloquent Relationships**: Clean model relationships and query scopes
- **Blade Templates**: Reusable, modular view components
- **Form Validation**: Server-side validation with error handling
- **Migration System**: Version-controlled database schema
- **Seeder Support**: Sample data generation
- **API-Ready Authentication**: Laravel Sanctum integration ready

---

## Architecture

### Directory Structure

```
laravel-app/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── DashboardController.php      # Main dashboard logic
│   │   │   ├── TransactionController.php    # Transaction CRUD
│   │   │   ├── ShopController.php           # Shop management
│   │   │   ├── CategoryController.php       # Category management
│   │   │   └── Api/                         # API Controllers (v1.2)
│   │   │       ├── TransactionController.php
│   │   │       ├── ShopController.php
│   │   │       └── CategoryController.php
│   │   └── Resources/                       # API Resources
│   │       ├── TransactionResource.php
│   │       ├── ShopResource.php
│   │       └── CategoryResource.php
│   ├── Models/
│   │   ├── Transaction.php                  # Transaction model with scopes
│   │   ├── Shop.php                         # Shop model
│   │   └── Category.php                     # Category model
│   └── Providers/
├── config/                                  # Configuration files
├── database/
│   ├── migrations/                          # Database migrations
│   │   ├── 2024_01_01_000001_create_shops_table.php
│   │   ├── 2024_01_01_000002_create_categories_table.php
│   │   └── 2024_01_01_000003_create_transactions_table.php
│   └── seeders/
│       └── DatabaseSeeder.php               # Sample data seeder
├── public/                                  # Public assets
│   ├── css/
│   └── js/
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   └── app.blade.php                # Main layout template
│   │   ├── dashboard/
│   │   │   └── index.blade.php              # Dashboard view
│   │   └── transactions/
│   │       └── entry.blade.php              # Transaction entry form
│   └── lang/                                # Multi-language files
│       ├── en/
│       │   └── messages.php
│       └── ja/
│           └── messages.php
├── routes/
│   ├── web.php                              # Web routes
│   └── api.php                              # API routes (v1)
└── tests/                                   # Test files
```

### Design Patterns

- **MVC Architecture**: Clean separation of concerns
- **Repository Pattern**: Model scopes for reusable queries
- **Resource Pattern**: API resource transformation
- **Service Provider Pattern**: Laravel's dependency injection
- **Facade Pattern**: Laravel's static interfaces

---

## Installation

### Prerequisites

- PHP 8.1 or higher
- Composer
- MySQL 5.7+ / MariaDB 10.2+
- Node.js & NPM (for asset compilation)

### Step-by-Step Setup

#### 1. Clone the Repository

```bash
git clone https://github.com/YOUR_USERNAME/Personal-Finance-Dashboard.git
cd Personal-Finance-Dashboard/laravel-app
```

#### 2. Install Dependencies

```bash
composer install
npm install
```

#### 3. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=finance_db
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

#### 4. Database Setup

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE finance_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations
php artisan migrate

# Seed sample data (optional)
php artisan db:seed
```

#### 5. Build Assets

```bash
npm run dev
# or for production
npm run build
```

#### 6. Start Development Server

```bash
php artisan serve
```

Visit: `http://localhost:8000`

---

## Database Schema

### Tables

#### `shops` (formerly `cat_1_labels`)
- `id`: Primary key
- `name`: Shop name (unique)
- `created_at`, `updated_at`: Timestamps

#### `categories` (formerly `cat_2_labels`)
- `id`: Primary key
- `name`: Category name (unique)
- `created_at`, `updated_at`: Timestamps

#### `transactions` (formerly `source`)
- `id`: Primary key
- `transaction_date`: Date of transaction
- `shop_id`: Foreign key to shops
- `category_id`: Foreign key to categories
- `amount`: Transaction amount (integer)
- `created_at`, `updated_at`: Timestamps

### Relationships

```php
Transaction belongsTo Shop
Transaction belongsTo Category
Shop hasMany Transactions
Category hasMany Transactions
```

---

## API Documentation

### Base URL

```
http://localhost:8000/api/v1
```

### Endpoints

#### Transactions

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/transactions` | List all transactions (with filters) |
| POST | `/transactions` | Create a transaction |
| GET | `/transactions/{id}` | Get single transaction |
| PUT/PATCH | `/transactions/{id}` | Update transaction |
| DELETE | `/transactions/{id}` | Delete transaction |
| GET | `/transactions/statistics` | Get statistics for date range |

#### Shops

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/shops` | List all shops |
| POST | `/shops` | Create a shop |
| GET | `/shops/{id}` | Get single shop |
| PUT/PATCH | `/shops/{id}` | Update shop |
| DELETE | `/shops/{id}` | Delete shop (if no transactions) |

#### Categories

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/categories` | List all categories |
| POST | `/categories` | Create a category |
| GET | `/categories/{id}` | Get single category |
| PUT/PATCH | `/categories/{id}` | Update category |
| DELETE | `/categories/{id}` | Delete category (if no transactions) |

### Example API Request

```bash
# Get transactions for a date range
curl -X GET "http://localhost:8000/api/v1/transactions?start_date=2024-01-01&end_date=2024-01-31" \
  -H "Accept: application/json"

# Create a transaction
curl -X POST "http://localhost:8000/api/v1/transactions" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "transaction_date": "2024-01-15",
    "shop_id": 1,
    "category_id": 2,
    "amount": 1500
  }'

# Get statistics
curl -X GET "http://localhost:8000/api/v1/transactions/statistics?start_date=2024-01-01&end_date=2024-01-31" \
  -H "Accept: application/json"
```

### API Response Format

```json
{
  "data": [
    {
      "id": 1,
      "transaction_date": "2024-01-15",
      "shop": {
        "id": 1,
        "name": "Supermarket"
      },
      "category": {
        "id": 2,
        "name": "Food"
      },
      "amount": 1500,
      "created_at": "2024-01-15T10:00:00.000000Z",
      "updated_at": "2024-01-15T10:00:00.000000Z"
    }
  ],
  "links": { ... },
  "meta": { ... }
}
```

---

## Eloquent Examples

### Using Model Scopes

```php
// Get transactions within date range
$transactions = Transaction::withinDateRange('2024-01-01', '2024-01-31')
    ->forShop(1)
    ->latest()
    ->get();

// Get shop total spending
$total = Transaction::withinDateRange($startDate, $endDate)
    ->where('shop_id', $shopId)
    ->sum('amount');
```

### Using Relationships

```php
// Eager loading
$transactions = Transaction::with(['shop', 'category'])->get();

// Get all transactions for a shop
$shop = Shop::find(1);
$transactions = $shop->transactions()
    ->whereBetween('transaction_date', [$startDate, $endDate])
    ->get();

// Get shop total spending (model method)
$total = $shop->getTotalSpending('2024-01-01', '2024-01-31');
```

---

## Testing

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter TransactionTest

# With coverage
php artisan test --coverage
```

### Example Test

```php
public function test_can_create_transaction()
{
    $shop = Shop::factory()->create();
    $category = Category::factory()->create();

    $response = $this->post('/api/v1/transactions', [
        'transaction_date' => '2024-01-15',
        'shop_id' => $shop->id,
        'category_id' => $category->id,
        'amount' => 1000,
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('transactions', [
        'shop_id' => $shop->id,
        'amount' => 1000,
    ]);
}
```

---

## Deployment

### Production Checklist

- [ ] Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`
- [ ] Run `composer install --optimize-autoloader --no-dev`
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`
- [ ] Set up proper file permissions (storage and cache directories)
- [ ] Configure web server (Apache/Nginx)
- [ ] Enable HTTPS
- [ ] Set up database backups
- [ ] Configure queue workers (if using queues)
- [ ] Set up monitoring and logging

### Apache Configuration

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /path/to/laravel-app/public

    <Directory /path/to/laravel-app/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/laravel-app/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## Artisan Commands

### Common Commands

```bash
# Database
php artisan migrate                 # Run migrations
php artisan migrate:fresh --seed    # Fresh migration with seeding
php artisan db:seed                 # Run seeders

# Cache
php artisan cache:clear             # Clear application cache
php artisan config:clear            # Clear config cache
php artisan route:clear             # Clear route cache
php artisan view:clear              # Clear view cache

# Development
php artisan serve                   # Start dev server
php artisan tinker                  # Interactive shell
php artisan make:controller NameController  # Create controller
php artisan make:model ModelName    # Create model
php artisan make:migration create_table_name  # Create migration

# Production
php artisan optimize                # Optimize for production
php artisan config:cache            # Cache config
php artisan route:cache             # Cache routes
php artisan view:cache              # Cache views
```

---

## Future Enhancements (Roadmap)

### Version 1.1
- [ ] CSV/Excel export (Laravel Excel package)
- [ ] Import transactions from file
- [ ] Recurring transactions (Laravel Task Scheduling)
- [ ] Budget planning and alerts
- [ ] Email notifications

### Version 1.2
- [x] REST API endpoints (✅ Implemented)
- [x] API Resources (✅ Implemented)
- [ ] User authentication (Laravel Breeze)
- [ ] Multi-user support
- [ ] Per-user data isolation
- [ ] API authentication (Laravel Sanctum)

### Version 2.0
- [ ] Multi-currency support
- [ ] Bank account integration (OAuth)
- [ ] Receipt photo upload (File Storage)
- [ ] Mobile app (PWA)
- [ ] AI-powered insights (OpenAI integration)
- [ ] Predictive analytics
- [ ] Social features (share insights)

---

## Comparison: Plain PHP vs Laravel

### Code Comparison

#### Plain PHP (queries.php)

```php
function getSummary($pdo, $start_date, $end_date) {
    $stmt = $pdo->prepare("
        SELECT SUM(price) as total, COUNT(*) as record_count,
               COUNT(DISTINCT label1) as shop_count
        FROM view1
        WHERE re_date BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date, $end_date]);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);

    return [
        'total' => $summary['total'] ?? 0,
        'record_count' => $summary['record_count'] ?? 0,
        'shop_count' => $summary['shop_count'] ?? 0
    ];
}
```

#### Laravel (DashboardController.php)

```php
private function getSummary(string $startDate, string $endDate): array
{
    $data = Transaction::withinDateRange($startDate, $endDate)
        ->selectRaw('
            SUM(amount) as total,
            COUNT(*) as record_count,
            COUNT(DISTINCT shop_id) as shop_count
        ')
        ->first();

    return [
        'total' => $data->total ?? 0,
        'record_count' => $data->record_count ?? 0,
        'shop_count' => $data->shop_count ?? 0,
    ];
}
```

### Benefits Demonstrated

1. **Type Safety**: Method signatures with types
2. **Cleaner Syntax**: Eloquent's fluent interface
3. **Reusable Scopes**: `withinDateRange()` scope used throughout
4. **No SQL Injection**: Eloquent handles parameterization
5. **Easier Testing**: Can mock Eloquent queries
6. **IDE Support**: Better autocomplete and type hinting

---

## Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Follow PSR-12 coding standards
4. Write tests for new features
5. Run `php artisan test` before committing
6. Commit changes (`git commit -m 'Add amazing feature'`)
7. Push to branch (`git push origin feature/amazing-feature`)
8. Open a Pull Request

---

## License

This project is open source under the [MIT License](LICENSE).

---

## Credits

- **Original Plain PHP Version**: See `../` directory
- **Framework**: [Laravel](https://laravel.com/)
- **Frontend**: [Bootstrap 5](https://getbootstrap.com/)
- **Charts**: [Highcharts](https://www.highcharts.com/)
- **Icons**: [Bootstrap Icons](https://icons.getbootstrap.com/)

---

## Support

- **Issues**: [GitHub Issues](https://github.com/YOUR_USERNAME/Personal-Finance-Dashboard/issues)
- **Documentation**: [Laravel Docs](https://laravel.com/docs)
- **Community**: [Laravel Forum](https://laracasts.com/discuss)

---

**Made with ❤️ using Laravel**

[⬆ Back to Top](#personal-finance-dashboard---laravel-edition)
