# Quick API Test Commands

Test your Laravel API endpoints directly with these curl commands.

## Setup

```bash
# Set your API base URL
export API_BASE="https://georgiann-semiacademic-never.ngrok-free.dev/api/v1"
```

---

## 🧪 Test Public Endpoints (No Auth)

### Test Login
```bash
curl -X POST "$API_BASE/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password"
  }' | jq
```

**Expected:** `{"token": "1|abc123..."}`

---

### Test Registration
```bash
curl -X POST "$API_BASE/auth/register" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Test User",
    "email": "test'$(date +%s)'@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }' | jq
```

**Expected:** `{"token": "1|def456..."}`

---

### ⚠️ Test Payment Request by Code (CRITICAL - Should Work Without Auth)
```bash
curl -X GET "$API_BASE/requests/by-code/PR_TEST" \
  -H "Accept: application/json" \
  -H "ngrok-skip-browser-warning: true" | jq
```

**Expected HTTP Status:** `200 OK`
**Expected Response:**
```json
{
  "id": 123,
  "pr_code": "PR_TEST",
  "code": "PR_TEST",
  "amount_display": "$50.00",
  "deep_link": "https://cash.app/$Owner/50.00",
  "group": {
    "name": "Group Name",
    "owner": {
      "name": "Owner Name",
      "cash_tag": "$Owner"
    }
  }
}
```

**If you get 401 Unauthorized:** The route is still protected. Move it to public section!

---

### Test Device Registration
```bash
curl -X POST "$API_BASE/devices/register" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "fcm_token": "test_fcm_token_'$(date +%s)'",
    "platform": "ios"
  }' | jq
```

**Expected:** `{"message": "Device registered"}`

---

### Test Mark Payment
```bash
curl -X POST "$API_BASE/requests/by-code/PR_TEST/mark" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "evidence_url": null,
    "evidence_type": null
  }' | jq
```

**Expected:** `{"message": "Payment marked"}`

---

## 🔒 Test Protected Endpoints (Auth Required)

### Get Auth Token First
```bash
# Login and save token
export AUTH_TOKEN=$(curl -s -X POST "$API_BASE/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password"
  }' | jq -r '.token')

echo "Token: $AUTH_TOKEN"
```

---

### Test Get Groups
```bash
curl -X GET "$API_BASE/groups" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Accept: application/json" \
  -H "ngrok-skip-browser-warning: true" | jq
```

**Expected:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Group Name",
      "owner": { "name": "Owner" }
    }
  ]
}
```

---

### Test Get Payment Requests
```bash
curl -X GET "$API_BASE/payment-requests" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Accept: application/json" \
  -H "ngrok-skip-browser-warning: true" | jq
```

**Expected:**
```json
{
  "data": [
    {
      "id": 123,
      "code": "PR_2025-10_G1_M1_abc",
      "status": "sent",
      "amount_display": "$50.00"
    }
  ]
}
```

---

### Test Get User Profile
```bash
curl -X GET "$API_BASE/auth/me" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Accept: application/json" \
  -H "ngrok-skip-browser-warning: true" | jq
```

**Expected:**
```json
{
  "id": 1,
  "name": "Test User",
  "email": "test@example.com"
}
```

---

### Test Logout
```bash
curl -X POST "$API_BASE/auth/logout" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -H "Accept: application/json" | jq
```

**Expected:** `{"message": "Logged out"}`

---

## 🔍 Troubleshooting

### ngrok Browser Warning
Add this header to skip ngrok's browser warning:
```bash
-H "ngrok-skip-browser-warning: true"
```

### CORS Errors
Make sure your Laravel `config/cors.php` includes:
```php
'supports_credentials' => true,
'allowed_origins' => ['https://*.ngrok-free.dev'],
```

### 401 Unauthorized on /requests/by-code
This means the route is still protected. Fix: Move it to public routes section.

### 404 Not Found
Check that:
1. Laravel server is running
2. ngrok is forwarding correctly
3. You're using the correct URL with `/api/v1` prefix

### Connection Refused
Make sure ngrok is running:
```bash
ngrok http 8000 --host-header=rewrite
```

---

## ✅ Success Criteria

All these commands should return **200 OK** status:

- ✅ POST /auth/login
- ✅ POST /auth/register
- ✅ POST /devices/register
- ✅ **GET /requests/by-code/{prCode}** ← Most important!
- ✅ POST /requests/by-code/{prCode}/mark
- ✅ GET /groups (with auth)
- ✅ GET /payment-requests (with auth)

If `/requests/by-code/{prCode}` returns 401, your mobile app won't work properly!

