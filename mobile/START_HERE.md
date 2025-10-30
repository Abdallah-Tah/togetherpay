# 🎯 Quick Start - Run TogetherPay with Your ngrok API

## TL;DR - Just Run This

```bash
cd /Users/amohamed/Herd/togetherpay/mobile

flutter run --dart-define=API_BASE=https://georgiann-semiacademic-never.ngrok-free.dev/api/v1
```

---

## ⚠️ IMPORTANT: Fix Backend First!

**Your app will get 401 errors if you don't do this:**

In your Laravel `routes/api.php`, move this ONE line:

```php
Route::get('/requests/by-code/{prCode}', [PaymentRequestController::class, 'showByCode']);
```

**FROM:** Protected routes section (has `middleware('auth:sanctum')`)  
**TO:** Public routes section (no middleware)

**Why?** Members need to view payment requests without logging in.

See [LARAVEL_FIX_REQUIRED.md](LARAVEL_FIX_REQUIRED.md) for detailed instructions.

---

## 🚀 Easy Way - Use the Script

```bash
# Make script executable (only need to do once)
chmod +x run_with_ngrok.sh

# Run it
./run_with_ngrok.sh
```

The script will:
- ✅ Check Flutter is installed
- ✅ Let you choose device
- ✅ Test your API
- ✅ Run the app with correct URL

---

## 🧪 Quick API Test

Test if your backend is ready:

```bash
# Should return 200 OK (not 401)
curl "https://georgiann-semiacademic-never.ngrok-free.dev/api/v1/requests/by-code/PR_TEST" \
  -H "ngrok-skip-browser-warning: true"
```

**If you get 401:** The route is still protected. Fix it first!

---

## 📚 Full Documentation

I created comprehensive docs for you:

| File | What It's For |
|------|---------------|
| **How_To_Run_With_Your_API.md** | Complete run guide with troubleshooting |
| **LARAVEL_FIX_REQUIRED.md** | Fix the backend route (MUST READ) |
| **CURL_TESTS.md** | Test all endpoints manually |
| **test_api.sh** | Automated API testing |
| **run_with_ngrok.sh** | Interactive run script |
| **DOCUMENTATION_INDEX.md** | Index of all docs |

---

## ✅ That's It!

1. Fix the Laravel route (2 minutes)
2. Run the command above (or use the script)
3. Test the app
4. You're done! 🎉

---

## 🆘 Having Issues?

- **401 Errors:** Route not moved to public section
- **CORS Errors:** Add ngrok URL to Laravel CORS config
- **Connection Failed:** ngrok or Laravel not running

See [How_To_Run_With_Your_API.md](How_To_Run_With_Your_API.md) for detailed troubleshooting.

---

**Your API:** https://georgiann-semiacademic-never.ngrok-free.dev/api/v1  
**Your app:** Already correct! Just needs the command above.  
**Your backend:** Needs one route moved. That's all!

