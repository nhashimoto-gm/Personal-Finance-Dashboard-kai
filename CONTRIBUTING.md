# Contributing to Personal Finance Dashboard

First off, thank you for considering contributing to Personal Finance Dashboard! 🎉

## 📋 Table of Contents

- [Code of Conduct](#code-of-conduct)
- [How Can I Contribute?](#how-can-i-contribute)
- [Development Setup](#development-setup)
- [Coding Standards](#coding-standards)
- [Commit Guidelines](#commit-guidelines)
- [Pull Request Process](#pull-request-process)

## 📜 Code of Conduct

This project follows a Code of Conduct. By participating, you are expected to uphold this code.

- Be respectful and inclusive
- Welcome newcomers
- Focus on constructive feedback
- Assume good intentions

## 🤝 How Can I Contribute?

### Reporting Bugs

Before creating a bug report:
- Check the [existing issues](https://github.com/YOUR_USERNAME/Personal-Finance-Dashboard/issues)
- Use the latest version
- Check if it's already fixed in `main` branch

When creating a bug report, include:
- **Clear title**: Describe the issue in the title
- **Description**: Detailed description of the bug
- **Steps to Reproduce**: Step-by-step instructions
- **Expected Behavior**: What should happen
- **Actual Behavior**: What actually happens
- **Environment**: OS, PHP version, MySQL version, browser
- **Screenshots**: If applicable
- **Error Messages**: Full error messages if any

### Suggesting Enhancements

Enhancement suggestions include:
- New features
- Improvements to existing features
- Performance improvements
- Better error messages

When suggesting enhancements:
- Use a clear and descriptive title
- Provide detailed description of the enhancement
- Explain why this would be useful
- Include mockups or examples if applicable

### Contributing Code

We love code contributions! Here are types of contributions we welcome:
- Bug fixes
- New features
- Performance improvements
- Documentation improvements
- Code refactoring
- Test coverage improvements

## 🛠️ Development Setup

### Prerequisites

- PHP 7.4+
- MySQL 5.7+ or MariaDB 10.2+
- Composer (optional, for future dependencies)
- Git

### Setup Steps

1. **Fork the repository**

2. **Clone your fork**
   ```bash
   git clone https://github.com/YOUR_USERNAME/Personal-Finance-Dashboard.git
   cd Personal-Finance-Dashboard
   ```

3. **Create database**
   ```bash
   mysql -u root -p
   ```
   ```sql
   CREATE DATABASE finance_db_dev;
   USE finance_db_dev;
   SOURCE database.sql;
   ```

4. **Configure environment**
   ```bash
   cp .env_db.example .env_db
   ```
   Edit `.env_db` with your development credentials

5. **Start development server**
   ```bash
   php -S localhost:8000
   ```

6. **Access application**
   ```
   http://localhost:8000
   ```

## 📐 Coding Standards

### PHP Standards

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding style
- Use meaningful variable names
- Keep functions small and focused (< 50 lines)
- Use type hints when possible
- Always use prepared statements for database queries

**Example:**
```php
// Good
function addTransaction(PDO $pdo, string $date, int $price): array {
    // Implementation
}

// Bad
function add($p, $d, $pr) {
    // Implementation
}
```

### JavaScript Standards

- Use ES6+ syntax
- Use `const` and `let`, avoid `var`
- Use arrow functions when appropriate
- Comment complex logic
- Keep functions pure when possible

**Example:**
```javascript
// Good
const calculateTotal = (items) => {
    return items.reduce((sum, item) => sum + item.price, 0);
};

// Bad
function calc(x) {
    var t = 0;
    for(var i=0;i<x.length;i++){t+=x[i].price;}
    return t;
}
```

### CSS Standards

- Use Bootstrap utility classes when possible
- Follow BEM naming for custom classes
- Group related properties together
- Use CSS variables for theming

**Example:**
```css
/* Good */
.card__header--primary {
    background-color: var(--primary-color);
    padding: 1rem;
}

/* Bad */
.ch1 {
    background-color: blue;
    padding: 16px;
}
```

### Database Standards

- Always use prepared statements
- Use meaningful table and column names
- Add indexes for frequently queried columns
- Include comments for complex queries

**Example:**
```php
// Good
$stmt = $pdo->prepare("
    SELECT id, re_date, price 
    FROM source 
    WHERE re_date BETWEEN ? AND ?
    ORDER BY re_date DESC
");
$stmt->execute([$startDate, $endDate]);

// Bad
$result = mysqli_query($conn, "SELECT * FROM source WHERE re_date >= '$start'");
```

## 💬 Commit Guidelines

### Commit Message Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

- **feat**: New feature
- **fix**: Bug fix
- **docs**: Documentation changes
- **style**: Code style changes (formatting, etc.)
- **refactor**: Code refactoring
- **test**: Adding or updating tests
- **chore**: Maintenance tasks

### Examples

```bash
feat(dashboard): add export to CSV functionality

- Add CSV export button to dashboard
- Implement server-side CSV generation
- Include all visible transactions in export

Closes #123
```

```bash
fix(entry): validate price input correctly

Price field now properly rejects negative values
and non-numeric input.

Fixes #456
```

### Best Practices

- Use present tense ("add feature" not "added feature")
- Use imperative mood ("move cursor to..." not "moves cursor to...")
- Limit first line to 72 characters
- Reference issues and pull requests when relevant
- Explain *what* and *why*, not *how*

## 🔄 Pull Request Process

### Before Submitting

1. **Test your changes thoroughly**
   - Test in multiple browsers
   - Test mobile responsive design
   - Check both light and dark modes
   - Test with different data scenarios

2. **Update documentation**
   - Update README.md if needed
   - Add inline comments for complex code
   - Update database.sql if schema changed

3. **Check code quality**
   - Follow coding standards
   - Remove debug code and console.logs
   - No commented-out code
   - No TODO comments (create issues instead)

### Creating the Pull Request

1. **Push to your fork**
   ```bash
   git push origin feature/your-feature-name
   ```

2. **Create pull request on GitHub**
   - Use a clear, descriptive title
   - Describe what changes you made
   - Explain why these changes are needed
   - Link related issues
   - Add screenshots if UI changed

3. **Pull Request Template**
   ```markdown
   ## Description
   Brief description of changes
   
   ## Type of Change
   - [ ] Bug fix
   - [ ] New feature
   - [ ] Breaking change
   - [ ] Documentation update
   
   ## Testing
   Describe how you tested your changes
   
   ## Screenshots (if applicable)
   Add screenshots here
   
   ## Checklist
   - [ ] Code follows project style guidelines
   - [ ] Self-review completed
   - [ ] Comments added for complex code
   - [ ] Documentation updated
   - [ ] No new warnings generated
   - [ ] Tested on multiple browsers
   - [ ] Tested mobile responsive design
   ```

### After Submitting

- Respond to code review feedback
- Make requested changes promptly
- Keep the PR focused and small
- Be patient and respectful

### Review Process

1. Maintainers will review your PR
2. They may request changes
3. Once approved, it will be merged
4. Your contribution will be acknowledged

## 🎯 Priority Areas

We especially welcome contributions in these areas:

- **Testing**: Unit tests, integration tests
- **Documentation**: Tutorials, examples, API docs
- **Accessibility**: WCAG compliance improvements
- **Performance**: Optimization opportunities
- **Security**: Security enhancements
- **Internationalization**: New language translations

## 🌟 Recognition

Contributors will be:
- Listed in README.md
- Mentioned in release notes
- Given credit in commit messages

## ❓ Questions?

- Create an [issue](https://github.com/YOUR_USERNAME/Personal-Finance-Dashboard/issues)
- Start a [discussion](https://github.com/YOUR_USERNAME/Personal-Finance-Dashboard/discussions)
- Email: your.email@example.com

## 📚 Resources

- [PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)
- [Conventional Commits](https://www.conventionalcommits.org/)
- [How to Write a Git Commit Message](https://chris.beams.io/posts/git-commit/)

---

Thank you for contributing! 🙏