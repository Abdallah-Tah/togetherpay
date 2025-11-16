# Mobile App Registration Debugging Guide

## Issue: Registration Failed

The mobile app shows "Registration failed" when trying to create an account.

### Current Findings:

1. ✅ App installed successfully on device A059
2. ✅ App connects to production API: `https://www.togetherpay.app/api/v1`
3. ❌ Registration endpoint not receiving JSON data properly
4. ❌ API returns validation errors even though data is sent

### Diagnostic Steps:

## 1. Check if Backend is Receiving Requests

From your **backend server** (SSH into production), check Laravel logs:

```bash
# View recent logs
tail -f storage/logs/laravel.log

# Or if using PM2/Forge/similar
pm2 logs
```

Try to register again from the app and watch the logs to see what the request looks like.

## 2. Test API Directly from Terminal

From your Mac:

```bash
# Test with curl
curl -v -X POST https://www.togetherpay.app/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"Password123!","password_confirmation":"Password123!"}'
```

**Expected:** Should return 201 with user and token
**Actual:** Returns 422 with "field is required" errors

This means the server is NOT receiving the JSON body.

## 3. Possible Causes:

### A. Reverse Proxy Stripping Request Body

If you're using **Nginx**, **Apache**, or a **CDN like Cloudflare**, they might be modifying requests.

**Check Nginx config:**
```nginx
# Ensure these are set in your nginx config
client_max_body_size 10M;
proxy_set_header Content-Type $content_type;
proxy_pass_request_body on;
```

### B. Laravel Not Reading JSON Input

**Check if `php://input` is accessible:**

Add this temporary route to test (in `routes/api.php`):

```php
Route::post('/test-json', function (Request $request) {
    return response()->json([
        'all' => $request->all(),
        'json' => $request->json()->all(),
        'input' => file_get_contents('php://input'),
        'content_type' => $request->header('Content-Type'),
    ]);
});
```

Then test:
```bash
curl -X POST https://www.togetherpay.app/api/v1/test-json \
  -H "Content-Type: application/json" \
  -d '{"test":"data"}'
```

### C. ModSecurity or Firewall Blocking

Some WAFs (Web Application Firewalls) block JSON POST requests.

**Check:**
- Cloudflare WAF rules
- Server firewall logs
- ModSecurity logs (if enabled)

### D. PHP Configuration

**Check php.ini:**
```ini
post_max_size = 10M
upload_max_filesize = 10M
```

## 4. Quick Workaround - Test on Local Backend

While debugging production, you can test the mobile app against your local backend:

**Terminal 1 - Start local backend:**
```bash
cd /Users/amohamed/Herd/togetherpay
php artisan serve --host=0.0.0.0 --port=8000
```

**Terminal 2 - Get your Mac's local IP:**
```bash
ifconfig | grep "inet " | grep -v 127.0.0.1
# Look for something like: inet 192.168.1.XXX
```

**Rebuild mobile app with local API:**
```bash
cd mobile
flutter build apk --dart-define=API_BASE=http://192.168.1.XXX:8000/api/v1 --debug
flutter install
```

This will let you test registration while debugging production.

## 5. Enable Debug Mode in Mobile App

Rebuild with debug logging enabled:

```bash
cd mobile
flutter build apk --dart-define=DEBUG=true --debug
flutter install
```

Then view logs while testing:
```bash
flutter logs
```

You'll see the exact API requests and responses.

## 6. Check Production Server Logs

### Laravel Logs:
```bash
# On production server
tail -f storage/logs/laravel.log
```

### Nginx Logs:
```bash
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log
```

### PHP-FPM Logs:
```bash
tail -f /var/log/php8.3-fpm.log
```

## 7. Temporary Fix - Add Logging to RegisterRequest

Edit `app/Http/Requests/API/RegisterRequest.php`:

```php
public function rules(): array
{
    // Temporary debug logging
    \Log::info('Registration request received', [
        'all' => $this->all(),
        'json' => $this->json()->all(),
        'content_type' => $this->header('Content-Type'),
        'method' => $this->method(),
    ]);

    return [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        'phone' => ['nullable', 'string', 'max:20'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ];
}
```

Deploy this, try to register from the app, then check logs to see what's being received.

## 8. Check CORS Configuration

Make sure CORS is allowing your mobile app:

**File:** `config/cors.php`

```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'], // Or specify your domains
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

## 9. Test with Postman/Insomnia

Download [Postman](https://www.postman.com/) or [Insomnia](https://insomnia.rest/) and test the registration endpoint:

**POST** `https://www.togetherpay.app/api/v1/auth/register`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body (JSON):**
```json
{
  "name": "Test User",
  "email": "test@example.com",
  "password": "Password123!",
  "password_confirmation": "Password123!"
}
```

If this works but curl doesn't, it's a server configuration issue.

## 10. Most Likely Solutions:

### Solution A: Update Nginx Configuration

If you're using Nginx, update your site config:

```nginx
server {
    listen 80;
    server_name togetherpay.app www.togetherpay.app;

    root /path/to/togetherpay/public;
    index index.php;

    client_max_body_size 10M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;

        # Ensure request body is passed
        fastcgi_pass_request_body on;
        fastcgi_param CONTENT_TYPE $content_type;
        fastcgi_param CONTENT_LENGTH $content_length;
    }
}
```

Then restart Nginx:
```bash
sudo nginx -t
sudo systemctl restart nginx
```

### Solution B: Check if Cloudflare is Blocking

If using Cloudflare:

1. Go to Cloudflare Dashboard
2. Navigate to Security → WAF
3. Check if any rules are blocking POST requests
4. Temporarily set Security Level to "Essentially Off" for testing
5. Check Firewall Events log

### Solution C: Verify PHP is Reading JSON

SSH into production and test PHP:

```bash
# Create test file
echo '<?php echo file_get_contents("php://input");' > /tmp/test.php

# Test it
echo '{"test":"data"}' | php /tmp/test.php
# Should output: {"test":"data"}
```

## Next Steps:

1. **SSH into your production server**
2. **Tail the Laravel logs:** `tail -f storage/logs/laravel.log`
3. **Try to register from the app** and watch the logs
4. **Check what data the server receives**
5. **Report back with log output**

The issue is definitely on the production server side, not the mobile app, since the same curl request fails.

---

**Temporary Solution:** Use local backend for testing while debugging production:

```bash
# Terminal 1
php artisan serve --host=0.0.0.0

# Terminal 2
cd mobile
flutter build apk --dart-define=API_BASE=http://YOUR_MAC_IP:8000/api/v1 --debug
flutter install
```

This will let you continue testing the app functionality while fixing production.
