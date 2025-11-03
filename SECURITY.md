# Security Guidelines

This document outlines security best practices and guidelines for the TogetherPay application.

## Table of Contents

1. [Authentication & Authorization](#authentication--authorization)
2. [Data Protection](#data-protection)
3. [API Security](#api-security)
4. [Infrastructure Security](#infrastructure-security)
5. [Dependency Management](#dependency-management)
6. [Incident Response](#incident-response)
7. [Compliance](#compliance)

## Authentication & Authorization

### Password Security

**Requirements:**
- Minimum 12 characters
- Mix of uppercase, lowercase, numbers, and special characters
- Not in common password lists
- Hashed using bcrypt with 12 rounds minimum

**Implementation:**
```php
// Laravel Fortify configuration
'bcrypt' => [
    'rounds' => env('BCRYPT_ROUNDS', 12),
],
```

### API Authentication (Laravel Sanctum)

**Token Management:**
- Tokens expire after configured lifetime (default: 525,600 minutes = 1 year)
- Tokens are stored hashed in database
- Revoke tokens on logout
- Support for multiple concurrent sessions

**Best Practices:**
```php
// Generate token with abilities
$token = $user->createToken('mobile-app', ['read', 'write'])->plainTextToken;

// Check abilities
if ($request->user()->tokenCan('write')) {
    // Authorized
}

// Revoke tokens
$request->user()->currentAccessToken()->delete(); // Current token
$request->user()->tokens()->delete(); // All tokens
```

### Two-Factor Authentication

TogetherPay uses Laravel Fortify's 2FA implementation:

- Time-based One-Time Passwords (TOTP)
- Recovery codes for backup access
- Optional SMS verification via Twilio

**Enable in `.env`:**
```env
FORTIFY_FEATURES=two-factor-authentication
```

### Role-Based Access Control (RBAC)

Implemented using Spatie Laravel Permission:

**Roles:**
- `owner` - Full access to group management
- `member` - View payments, mark as paid
- `admin` - Platform administration (future)

**Permissions:**
- `view-group`
- `manage-group`
- `add-members`
- `remove-members`
- `create-payment-requests`
- `mark-payment-paid`

**Usage:**
```php
// Assign role
$user->assignRole('owner');

// Check permission
if ($user->can('manage-group')) {
    // Authorized
}

// Middleware
Route::middleware(['auth:sanctum', 'permission:manage-group'])->group(function () {
    // Protected routes
});
```

## Data Protection

### Encryption at Rest

**Sensitive Data:**
- Payment account details (encrypted using Laravel encryption)
- OAuth tokens (encrypted)
- Personal identification information

**Implementation:**
```php
// Model casting
protected $casts = [
    'payment_account_details' => 'encrypted:array',
    'oauth_token' => 'encrypted',
];
```

### Encryption in Transit

**TLS Configuration:**
- TLS 1.2+ required
- Strong cipher suites only
- HSTS enabled with 1-year max-age
- Certificate pinning in mobile apps (production)

**Nginx Configuration:**
```nginx
ssl_protocols TLSv1.2 TLSv1.3;
ssl_ciphers HIGH:!aNULL:!MD5;
ssl_prefer_server_ciphers on;
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
```

### Data Minimization

**Principles:**
- Collect only necessary information
- Delete payment requests after 90 days (retention policy)
- Anonymize data when user accounts are deleted
- Regular cleanup of expired sessions and tokens

### Personal Data Handling

**GDPR/CCPA Compliance:**
- User data export (JSON format)
- Right to be forgotten (data deletion)
- Consent management
- Data breach notification procedures

**Implementation:**
```php
// Export user data
public function exportData(User $user): array
{
    return [
        'profile' => $user->only(['name', 'email', 'phone']),
        'groups' => $user->groups()->with('members')->get(),
        'payment_requests' => $user->paymentRequests()->get(),
        'activity_log' => Activity::forSubject($user)->get(),
    ];
}

// Delete user data
public function deleteUserData(User $user): void
{
    DB::transaction(function () use ($user) {
        // Anonymize or delete related data
        $user->paymentRequests()->update(['user_id' => null]);
        $user->groups()->delete();
        $user->activityLogs()->delete();
        $user->delete();
    });
}
```

## API Security

### Rate Limiting

**Configuration:**
```php
// bootstrap/app.php
$middleware->throttleApi('api', limit: env('RATE_LIMIT_API', 60));

// Custom rate limits
Route::middleware(['throttle:5,1'])->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);
});
```

**Limits:**
- Authentication endpoints: 5 requests per minute
- API endpoints: 60 requests per minute
- Webhook endpoints: No limit (validated via signature)

### Input Validation

**Form Request Validation:**
```php
class CreateGroupRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'billing_day' => ['required', 'integer', 'between:1,28'],
            'cadence' => ['required', 'in:monthly,four_week'],
            'amount' => ['required', 'numeric', 'min:1', 'max:10000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Sanitize input
        $this->merge([
            'name' => strip_tags($this->name),
        ]);
    }
}
```

### SQL Injection Prevention

**Always use Eloquent ORM or parameterized queries:**
```php
// Good - Eloquent
User::where('email', $email)->first();

// Good - Query Builder with bindings
DB::table('users')->where('email', '=', $email)->first();

// Bad - Raw SQL (avoid unless necessary)
DB::select("SELECT * FROM users WHERE email = '$email'"); // NEVER DO THIS

// If raw SQL is necessary, use bindings
DB::select('SELECT * FROM users WHERE email = ?', [$email]);
```

### XSS Prevention

**Blade Templating (Auto-escaping):**
```blade
<!-- Good - Escaped -->
{{ $user->name }}

<!-- Only when absolutely necessary and data is trusted -->
{!! $trustedHtml !!}
```

**API Responses:**
```php
// Ensure proper Content-Type header
return response()->json($data, 200, [
    'Content-Type' => 'application/json',
]);
```

### CSRF Protection

**Web Routes:**
- Automatically protected via `web` middleware
- CSRF token validated on POST, PUT, PATCH, DELETE

**API Routes:**
- Excluded from CSRF (using `api` middleware)
- Protected via Sanctum tokens instead

### CORS Configuration

**Production Configuration:**
```php
// config/cors.php
'allowed_origins' => [
    'https://togetherpay.app',
    'https://www.togetherpay.app',
],
'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
'supports_credentials' => true,
```

### Webhook Security

**Cash App Webhooks:**
```php
public function handle(Request $request)
{
    // Verify signature
    $signature = $request->header('X-CashApp-Signature');
    $payload = $request->getContent();
    
    $expectedSignature = hash_hmac(
        'sha256',
        $payload,
        config('services.cashapp.webhook_secret')
    );
    
    if (!hash_equals($expectedSignature, $signature)) {
        abort(403, 'Invalid signature');
    }
    
    // Process webhook
}
```

**Idempotency:**
```php
// Use idempotency keys to prevent duplicate processing
$idempotencyKey = $request->header('Idempotency-Key');

if (ProcessedWebhook::where('idempotency_key', $idempotencyKey)->exists()) {
    return response()->json(['status' => 'already_processed']);
}

// Process and store
```

## Infrastructure Security

### Environment Variables

**Never commit sensitive values:**
```bash
# .gitignore
.env
.env.production
*.key
*.pem
*.jks
key.properties
```

**Use strong, unique values:**
- APP_KEY: 32-character random string (generated via `php artisan key:generate`)
- Database passwords: 20+ characters, random
- API keys: Rotate regularly
- JWT secrets: 256-bit random values

### File Permissions

**Production Server:**
```bash
# Application files
chown -R www-data:www-data /var/www/togetherpay
chmod -R 755 /var/www/togetherpay

# Writable directories
chmod -R 775 /var/www/togetherpay/storage
chmod -R 775 /var/www/togetherpay/bootstrap/cache

# Environment file
chmod 600 /var/www/togetherpay/.env
```

### Security Headers

Implemented via `SecurityHeaders` middleware:

- `X-Frame-Options: DENY` - Prevent clickjacking
- `X-Content-Type-Options: nosniff` - Prevent MIME sniffing
- `X-XSS-Protection: 1; mode=block` - XSS protection (legacy)
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Strict-Transport-Security: max-age=31536000` - Force HTTPS
- `Content-Security-Policy` - Restrict resource loading
- `Permissions-Policy` - Limit browser features

### Database Security

**PostgreSQL Configuration:**
```sql
-- Create dedicated user with limited privileges
CREATE USER togetherpay_user WITH ENCRYPTED PASSWORD 'strong-password';
GRANT CONNECT ON DATABASE togetherpay_production TO togetherpay_user;
GRANT USAGE ON SCHEMA public TO togetherpay_user;
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO togetherpay_user;

-- Enable SSL
ALTER SYSTEM SET ssl = on;
```

**Connection Security:**
```env
# Use SSL for database connections
DB_SSL_MODE=require
DB_SSL_CA=/path/to/ca-cert.pem
```

### Redis Security

**Authentication:**
```bash
# redis.conf
requirepass your-strong-redis-password
```

**Network Isolation:**
- Bind to internal network only
- Use firewall rules to restrict access
- Consider Redis Sentinel for failover

### Backup Security

**Encrypted Backups:**
```bash
#!/bin/bash
# Backup with encryption
pg_dump togetherpay_production | gzip | openssl enc -aes-256-cbc -salt -k "$BACKUP_ENCRYPTION_KEY" > backup.sql.gz.enc

# Upload to S3 with server-side encryption
aws s3 cp backup.sql.gz.enc s3://togetherpay-backups/ --sse AES256
```

**Backup Access Control:**
- Restrict S3 bucket access via IAM policies
- Enable versioning for backup retention
- Regular restore testing

## Dependency Management

### Vulnerability Scanning

**Composer (PHP):**
```bash
# Check for known vulnerabilities
composer audit

# Update dependencies
composer update --with-all-dependencies
```

**npm (JavaScript):**
```bash
# Check for vulnerabilities
npm audit

# Fix automatically
npm audit fix

# Update dependencies
npm update
```

### Dependency Review

**Before Adding New Dependencies:**
1. Check package popularity and maintenance status
2. Review security advisories
3. Verify package integrity (checksums)
4. Review source code for suspicious activity
5. Use official packages when available

**Automated Scanning:**
- GitHub Dependabot alerts enabled
- Trivy security scanner in CI/CD
- Regular dependency updates

### Lock Files

**Always commit lock files:**
- `composer.lock` (PHP)
- `package-lock.json` (Node.js)
- `pubspec.lock` (Flutter)

This ensures consistent dependency versions across environments.

## Incident Response

### Detection

**Monitoring:**
- Error tracking via Sentry
- Log monitoring (Laravel logs, Nginx logs)
- Performance monitoring
- Security alerts (failed login attempts, rate limit violations)

**Alerting:**
- Slack notifications for critical errors
- Email alerts for security events
- PagerDuty integration for on-call rotation

### Response Procedures

**Security Incident:**
1. **Identify** - Confirm incident scope and impact
2. **Contain** - Isolate affected systems
3. **Investigate** - Analyze logs, identify attack vector
4. **Remediate** - Apply patches, update credentials
5. **Recover** - Restore services, verify functionality
6. **Document** - Write incident report, update procedures
7. **Notify** - Inform affected users if required (GDPR/CCPA)

**Data Breach Response:**
1. Assess scope of data exposure
2. Notify affected users within 72 hours (GDPR requirement)
3. Report to regulatory authorities
4. Offer credit monitoring if financial data exposed
5. Implement additional security measures

### Communication

**Security Contacts:**
- Security Email: security@togetherpay.app
- Bug Bounty Program: (Future) security.txt disclosure

**Responsible Disclosure:**
- Acknowledge receipt within 24 hours
- Provide status updates every 7 days
- Fix critical issues within 30 days
- Credit researchers appropriately

## Compliance

### GDPR (European Union)

**Requirements:**
- ✅ Data processing lawful basis
- ✅ Privacy policy available
- ✅ User consent for data collection
- ✅ Right to access data
- ✅ Right to be forgotten
- ✅ Data portability
- ✅ Breach notification (72 hours)
- ✅ Data Protection Officer (if required)

### CCPA (California)

**Requirements:**
- ✅ Privacy notice
- ✅ Right to know data collected
- ✅ Right to delete data
- ✅ Right to opt-out of data sales (N/A - we don't sell data)
- ✅ Non-discrimination

### PCI DSS (Payment Card Industry)

**Current Status:**
- ✅ Not storing card data directly (using Cash App/Venmo APIs)
- ✅ Secure transmission (TLS 1.2+)
- ✅ Access controls
- ✅ Logging and monitoring

**Future Consideration:**
If storing payment methods, full PCI DSS compliance required.

### SOC 2 (Future)

For enterprise customers, consider SOC 2 Type II certification:
- Security
- Availability
- Processing Integrity
- Confidentiality
- Privacy

## Security Checklist

### Development

- [ ] Code reviews required for all changes
- [ ] Security linter enabled (Pint, ESLint)
- [ ] Secrets never committed to version control
- [ ] Environment-specific configurations
- [ ] Input validation on all user input
- [ ] Output escaping in templates
- [ ] Parameterized database queries
- [ ] Secure dependencies (no known vulnerabilities)

### Testing

- [ ] Security tests for authentication
- [ ] Authorization tests for all endpoints
- [ ] Input validation tests
- [ ] XSS protection tests
- [ ] CSRF protection tests
- [ ] SQL injection tests
- [ ] Rate limiting tests
- [ ] Penetration testing (annually)

### Deployment

- [ ] TLS certificates configured
- [ ] Security headers enabled
- [ ] File permissions correct
- [ ] Database credentials secured
- [ ] API keys secured
- [ ] Debug mode disabled
- [ ] Error reporting to logs only (not displayed)
- [ ] Backups configured and encrypted
- [ ] Monitoring and alerting configured

### Operations

- [ ] Regular dependency updates
- [ ] Security patches applied promptly
- [ ] Access logs reviewed regularly
- [ ] Failed login attempts monitored
- [ ] Backup restoration tested
- [ ] Incident response plan documented
- [ ] Security training for team members
- [ ] Annual security audit

## Resources

### External References

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [NIST Cybersecurity Framework](https://www.nist.gov/cyberframework)
- [CIS Controls](https://www.cisecurity.org/controls/)

### Internal Documentation

- [Deployment Guide](DEPLOYMENT.md)
- [API Documentation](API_DOCUMENTATION.md)
- [Privacy Policy](docs/PRIVACY_POLICY.md)
- [Terms of Service](docs/TERMS_OF_SERVICE.md)

## Contact

For security issues, contact: security@togetherpay.app

**PGP Key:** (Future - add public key)
