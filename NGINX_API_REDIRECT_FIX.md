# Nginx API Redirect Fix Guide

## Current Issue

API requests to `/api/v1/*` return **HTTP 302 redirect** to homepage instead of JSON responses.

**Example**:
```bash
curl -X POST https://www.togetherpay.app/api/v1/auth/login
# Returns: 302 redirect to https://www.togetherpay.app
# Expected: 422 JSON validation error
```

---

## Root Causes (Varnish is OFF)

Since Varnish is already disabled, the redirect is coming from one of these:

1. **Nginx rewrite rules** forcing www or HTTPS
2. **Laravel TrustProxies middleware** misconfiguration
3. **APP_URL mismatch** in `.env`
4. **Route cache** including incorrect routes

---

## Solution 1: Check Nginx Configuration (Most Likely)

### Problem: Nginx redirecting non-www to www

If your Nginx config has:
```nginx
server {
    listen 443 ssl http2;
    server_name togetherpay.app;  # Without www
    return 301 https://www.togetherpay.app$request_uri;  # Redirects ALL requests
}
```

**This redirects API calls before they reach Laravel!**

### Fix: Exclude API from redirect

```nginx
server {
    listen 443 ssl http2;
    server_name togetherpay.app;

    # Redirect everything EXCEPT /api to www version
    location / {
        if ($uri !~ ^/api) {
            return 301 https://www.togetherpay.app$request_uri;
        }
        # If it IS /api, process normally
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass 127.0.0.1:{{php_fpm_port}};
    }
}
```

**OR better: Handle API on both domains**

```nginx
server {
    listen 443 ssl http2;
    server_name togetherpay.app www.togetherpay.app;
    root /home/togetherpay/htdocs/www.togetherpay.app/public;

    index index.php index.html;

    # API routes - no redirect
    location /api {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Web routes - redirect togetherpay.app → www.togetherpay.app
    location / {
        if ($host = 'togetherpay.app') {
            return 301 https://www.togetherpay.app$request_uri;
        }
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass 127.0.0.1:{{php_fpm_port}};
    }
}
```

---

## Solution 2: Fix Laravel TrustProxies Middleware

### Check: `app/Http/Middleware/TrustProxies.php`

```php
protected $proxies = '*';  // Trust all proxies

protected $headers =
    Request::HEADER_X_FORWARDED_FOR |
    Request::HEADER_X_FORWARDED_HOST |
    Request::HEADER_X_FORWARDED_PORT |
    Request::HEADER_X_FORWARDED_PROTO;
```

If you DON'T have a proxy (Varnish is off), you might not need this middleware or need to configure it differently.

### Option A: Remove from global middleware

In `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    // Don't apply TrustProxies globally if no proxy
    $middleware->remove(\App\Http\Middleware\TrustProxies::class);
})
```

### Option B: Set proxies to null

```php
protected $proxies = null;  // Don't trust any proxies
```

---

## Solution 3: Verify APP_URL in .env

### Check production `.env`:

```bash
grep "^APP_URL=" .env
```

**Should be**:
```env
APP_URL=https://www.togetherpay.app
```

**NOT**:
```env
APP_URL=https://togetherpay.app  # ❌ Wrong if using www
APP_URL=http://...                # ❌ Wrong (not HTTPS)
```

After changing, clear config cache:
```bash
php artisan config:clear
php artisan config:cache
```

---

## Solution 4: Check API Routes Registration

### Verify routes are registered:

```bash
php artisan route:list --path=api
```

Should show:
```
POST   api/v1/auth/login ......... api.login
POST   api/v1/auth/register ...... api.register
GET    api/v1/groups ............. api.groups.index
```

If routes are missing, check `routes/api.php` is being loaded in `bootstrap/app.php`:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',  // ← Make sure this exists
    commands: __DIR__.'/../routes/console.php',
)
```

After fixing, rebuild route cache:
```bash
php artisan route:clear
php artisan route:cache
```

---

## Diagnostic Steps (Run on Production)

### 1. Upload and run diagnostic script

```bash
# SSH into production
ssh root@ubuntu-16gb-hel1-1

# Navigate to project
cd /home/togetherpay/htdocs/www.togetherpay.app

# Upload fix-nginx-api-redirect.sh from local machine
chmod +x fix-nginx-api-redirect.sh
./fix-nginx-api-redirect.sh
```

### 2. Check Nginx config for redirects

```bash
# Find Nginx config file
grep -r "togetherpay.app" /etc/nginx/sites-enabled/

# Look for redirect rules
grep -A 5 "return 301" /etc/nginx/sites-enabled/togetherpay.app
```

### 3. Test API with verbose output

```bash
# Test with full headers
curl -X POST https://www.togetherpay.app/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{"email":"test@example.com","password":"password"}' \
  -v 2>&1 | grep -E "(HTTP|Location|<)"
```

**Expected (working)**:
```
< HTTP/2 422
< content-type: application/json
{"message":"The email field is required. ..."}
```

**Current (broken)**:
```
< HTTP/2 302
< Location: https://www.togetherpay.app
```

---

## Quick Fix (Most Common Issue)

**If Nginx is redirecting non-www to www**, add this to your Nginx config:

```nginx
server {
    listen 443 ssl http2;
    server_name togetherpay.app www.togetherpay.app;
    root /home/togetherpay/htdocs/www.togetherpay.app/public;

    # Critical: API must NOT redirect
    location /api {
        try_files $uri $uri/ /index.php?$query_string;

        location ~ \.php$ {
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            fastcgi_pass 127.0.0.1:9000;  # Adjust port if needed
        }
    }

    # Web routes can redirect to www
    location / {
        # Only redirect web requests to www (not API)
        if ($host = 'togetherpay.app') {
            return 301 https://www.togetherpay.app$request_uri;
        }
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass 127.0.0.1:9000;
    }
}
```

After updating:
```bash
# Test config
nginx -t

# Reload Nginx
systemctl reload nginx

# Test API
curl -X POST https://www.togetherpay.app/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"test@test.com","password":"password"}'
```

---

## Expected Result After Fix

```bash
curl -X POST https://www.togetherpay.app/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{}'
```

**Should return**:
```json
{
  "message": "The email field is required. (and 1 more error)",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  }
}
```

**HTTP Status: 422** (NOT 302!)

---

## Test Mobile App

After fixing Nginx:

1. Open TogetherPay mobile app
2. Try to login with valid credentials
3. **Should work!** ✅

---

## Next Steps After This Fix

1. ✅ Cache fix (completed)
2. ✅ Nginx API redirect fix (this guide)
3. 🔐 **Critical security fixes** (next priority)
4. 📊 Performance optimizations
5. 🧪 Add test coverage

See `PRODUCTION_FIX_GUIDE.md` for complete roadmap.
