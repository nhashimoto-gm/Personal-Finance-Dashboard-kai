# Database Migrations

This directory contains SQL migration scripts to upgrade your existing database.

## How to Apply Migrations

### For Existing Databases

If you already have a Personal Finance Dashboard database and need to add new tables or features, run the migration scripts in order:

```bash
mysql -u your_username -p your_database_name < migrations/001_add_budgets_table.sql
```

Or from MySQL prompt:

```sql
USE your_database_name;
SOURCE migrations/001_add_budgets_table.sql;
```

### For New Installations

If you're setting up a fresh database, use the main `database.sql` file instead:

```bash
mysql -u your_username -p your_database_name < database.sql
```

## Available Migrations

### 001_add_budgets_table.sql
- **Purpose**: Adds the `budgets` table for budget management functionality
- **Date**: Version 1.1
- **Features**: Monthly budget tracking, category budgets, shop budgets

## Migration Order

Migrations must be applied in numerical order:
1. 001_add_budgets_table.sql

## Verifying Migrations

After running a migration, verify the table exists:

```sql
SHOW TABLES LIKE 'budgets';
DESCRIBE budgets;
```

## Troubleshooting

### Error: "An error occurred while setting the budget"
This error typically means the `budgets` table doesn't exist in your database. Run the migration:

```bash
mysql -u your_username -p your_database_name < migrations/001_add_budgets_table.sql
```

### Error: "Table 'budgets' already exists"
The `CREATE TABLE IF NOT EXISTS` statement will skip creation if the table already exists. This is safe and expected.

## Need Help?

If you encounter any issues with migrations, please:
1. Check your database credentials in `.env_db`
2. Verify you have the correct permissions
3. Review the MySQL error log
4. Open an issue on GitHub
