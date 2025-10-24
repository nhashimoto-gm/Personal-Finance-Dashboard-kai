# Rate Limiting Documentation

## Overview

This application implements rate limiting to protect against abuse and ensure fair usage. Rate limiting restricts the number of requests a user can make within a specific time window.

## Configuration

### Legacy PHP Version

**Location**: `config.php`

**Settings**:
- `RATE_LIMIT_REQUESTS`: Maximum number of requests allowed (default: 10)
- `RATE_LIMIT_WINDOW`: Time window in seconds (default: 60 seconds)

**Default**: 10 requests per 60 seconds (1 minute)

### Laravel Version

**Location**: `routes/web.php`

**Middleware**: `throttle:10,1`
- First parameter (10): Maximum number of requests
- Second parameter (1): Time window in minutes

**Default**: 10 requests per 1 minute

## How It Works

### Session-Based Tracking (Legacy PHP)

The rate limiting system uses PHP sessions to track requests:

1. Each request is timestamped and stored in the session
2. Before processing a request, the system checks how many requests have been made in the current time window
3. Old requests (outside the time window) are automatically removed
4. If the limit is exceeded, the request is rejected with an error message

### Implementation Details

**Functions** (in `config.php`):

```php
checkRateLimit($action)
```
- Checks if the current request exceeds the rate limit
- Returns: `['allowed' => bool, 'message' => string, 'retry_after' => int]`

```php
recordRequest($action)
```
- Records a request in the session
- Should be called after successful validation

```php
getRateLimitInfo($action)
```
- Gets current rate limit status (for debugging)
- Returns: `['requests' => int, 'limit' => int, 'remaining' => int, 'reset' => timestamp]`

### Per-Action Limits

Rate limits are tracked separately for each action:
- `add_transaction`: Limit applies to transaction creation
- `add_shop`: Limit applies to shop creation
- `add_category`: Limit applies to category creation

This prevents one type of action from consuming the quota for another.

## User Experience

### When Rate Limit is Exceeded

**Legacy PHP**:
- Error message displayed: "Too many requests. Please try again in X seconds."
- The message shows exactly how many seconds to wait

**Laravel**:
- HTTP 429 (Too Many Requests) response
- Standard Laravel throttle response with retry-after header

### Error Messages

All rate limit errors are displayed in the alert area at the top of the page:

```html
<div class="alert alert-danger">
    Too many requests. Please try again in 45 seconds.
</div>
```

## Security Considerations

### Why Rate Limiting?

1. **Prevents Brute Force Attacks**: Limits automated attempts to submit invalid data
2. **Protects Server Resources**: Prevents a single user from overloading the server
3. **Ensures Fair Usage**: Guarantees all users have equal access to resources
4. **Prevents Data Spam**: Stops malicious users from creating excessive records

### Attack Scenarios Prevented

- **Form Spam**: Automated submission of forms
- **Resource Exhaustion**: Overwhelming the server with requests
- **Database Flooding**: Creating thousands of records rapidly

## Customization

### Changing the Limits

**Legacy PHP** (in `config.php`):

```php
// Allow 20 requests per 2 minutes
define('RATE_LIMIT_REQUESTS', 20);
define('RATE_LIMIT_WINDOW', 120);
```

**Laravel** (in `routes/web.php`):

```php
// Allow 20 requests per 2 minutes
->middleware('throttle:20,2')
```

### Different Limits for Different Actions

**Legacy PHP**:

You can implement action-specific limits by modifying the `checkRateLimit()` function:

```php
function checkRateLimit($action = 'default') {
    // Different limits based on action
    $limits = [
        'add_transaction' => ['requests' => 10, 'window' => 60],
        'add_shop' => ['requests' => 5, 'window' => 60],
        'add_category' => ['requests' => 5, 'window' => 60],
    ];

    $limit = $limits[$action] ?? ['requests' => 10, 'window' => 60];
    // ... rest of implementation
}
```

**Laravel**:

```php
// Transactions: 10 per minute
Route::resource('transactions', TransactionController::class)
    ->middleware('throttle:10,1');

// Shops: 5 per minute
Route::prefix('management/shops')
    ->middleware('throttle:5,1')
    ->group(function () { ... });
```

## Monitoring

### Check Current Rate Limit Status

**Legacy PHP**:

```php
$info = getRateLimitInfo('add_transaction');
echo "Requests: {$info['requests']}/{$info['limit']}\n";
echo "Remaining: {$info['remaining']}\n";
echo "Reset at: " . date('H:i:s', $info['reset']) . "\n";
```

### Logging

Rate limit violations are automatically logged (when configured):

```php
error_log("Rate limit exceeded for action: $action by IP: " . $_SERVER['REMOTE_ADDR']);
```

## Testing

### Manual Testing

1. **Test Normal Usage**:
   - Submit a form multiple times (within limit)
   - Verify all requests succeed

2. **Test Rate Limit**:
   - Submit a form 11 times rapidly
   - The 11th request should be rejected
   - Error message should indicate retry time

3. **Test Reset**:
   - Wait for the time window to expire (60 seconds)
   - Submit again - should succeed

### Automated Testing (Laravel)

```php
public function test_rate_limiting()
{
    // Make 10 requests (should all succeed)
    for ($i = 0; $i < 10; $i++) {
        $response = $this->post('/transactions', [...]);
        $response->assertStatus(200);
    }

    // 11th request should fail
    $response = $this->post('/transactions', [...]);
    $response->assertStatus(429);
}
```

## Troubleshooting

### Issue: Rate limit triggering too quickly

**Solution**: Increase `RATE_LIMIT_REQUESTS` or `RATE_LIMIT_WINDOW`

### Issue: Rate limit not working

**Check**:
1. Sessions are working correctly (`session_start()` called)
2. `checkRateLimit()` is called before processing requests
3. `recordRequest()` is called after validation

### Issue: Rate limit persists after time window

**Solution**: Clear sessions:
```bash
# PHP sessions
rm -rf /tmp/sess_*

# Or restart PHP-FPM
sudo service php-fpm restart
```

### Issue: Different users sharing rate limit

**Problem**: If using IP-based detection on a shared network
**Solution**: Rate limiting is session-based, so each user session has its own limit

## Best Practices

1. **Set Reasonable Limits**: Balance security with user experience
2. **Provide Clear Feedback**: Tell users when and why they're limited
3. **Different Limits for Different Actions**: Critical actions can have stricter limits
4. **Monitor and Adjust**: Review logs and adjust limits as needed
5. **Combine with Authentication**: Rate limiting works best alongside proper authentication

## Related Security Features

This application also implements:
- CSRF Protection (see `config.php`)
- Input Validation (see `functions.php`)
- Secure Session Configuration (see `index.php`)
- Error Logging (see `config.php`)

## References

- [OWASP: Blocking Brute Force Attacks](https://owasp.org/www-community/controls/Blocking_Brute_Force_Attacks)
- [Laravel Throttling Documentation](https://laravel.com/docs/10.x/routing#rate-limiting)
