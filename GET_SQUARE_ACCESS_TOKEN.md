# How to Get Your Square Sandbox Access Token

## Where to Find It

1. **Go to Square Developer Dashboard**
   - URL: https://developer.squareup.com/apps
   - Log in with your Square developer account

2. **Select Your Application**
   - Click on your application name
   - (If you don't have one, click "+" to create a new application)

3. **Navigate to Credentials**
   - Click **"Credentials"** in the left sidebar
   - Make sure you're in **"Sandbox"** mode (toggle at top-right)

4. **Find Sandbox Access Token**
   You'll see several credentials:

   ```
   ┌─────────────────────────────────────────────────┐
   │  Sandbox Credentials                             │
   ├─────────────────────────────────────────────────┤
   │                                                  │
   │  Application ID:                                 │
   │  sandbox-sq0idb-8ZG94llx8SQtLCB9T9f_bA          │
   │                                                  │
   │  Application Secret:  [Show]                     │
   │  sandbox-sq0csb-s4kpODp0ZUucQvKMK...            │
   │                                                  │
   │  ⭐ Sandbox Access Token:  [Show]  ← THIS ONE! │
   │  EAAAl_abcdefghijklmnopqrstuvwxyz...            │
   │  (Long token, 60-100+ characters)                │
   │                                                  │
   │  Location IDs:                                   │
   │  • Main Location: LXXXXXXXXXXX                   │
   │                                                  │
   └─────────────────────────────────────────────────┘
   ```

5. **Click "Show" next to Sandbox Access Token**
   - The token will be revealed
   - Click the copy icon to copy it

6. **Copy the Location ID**
   - Also copy one of the Location IDs shown
   - You'll need this too

## What You Currently Have

✅ **Sandbox Application ID**
```
sandbox-sq0idb-8ZG94llx8SQtLCB9T9f_bA
```

✅ **Sandbox Application Secret**
```
sandbox-sq0csb-s4kpODp0ZUucQvKMKhBdpEQHkNSTIAs7WeS_6CaEb90
```

❌ **Sandbox Access Token** (NEED THIS!)
```
Currently: yEAAAl4YRs9djONdfEAkmOourp_y5tP1ncA4Ls_FviBHCl_ExAlqGAfvDvDbt72l8
Looks like: Truncated or invalid

Should be: EAAAl_... (60-100+ characters)
```

❌ **Location ID** (NEED THIS TOO!)
```
Currently: (empty)
Should be: Something like "LXXXXXXXXXXX" or "L1234567890ABC"
```

## How to Update .env

Once you have both values, update your `.env` file:

```bash
# Current (INVALID)
SQUARE_ACCESS_TOKEN=yEAAAl4YRs9djONdfEAkmOourp_y5tP1ncA4Ls_FviBHCl_ExAlqGAfvDvDbt72l8
SQUARE_LOCATION_ID=

# Updated (VALID)
SQUARE_ACCESS_TOKEN=EAAAl_your_full_60_plus_character_token_here
SQUARE_LOCATION_ID=L1234567890ABC
```

## Quick Test After Update

```bash
# Clear config cache
php artisan config:clear

# Test the access token
php artisan test:real-square-invoice abdallahtmohamed86@gmail.com +12074097887 --force
```

If you see:
- ✅ "Square customer created: CXXXXXXX" → Access token is valid!
- ✅ "Square invoice created: inv_XXXXXXX" → Success!
- ❌ "401 UNAUTHORIZED" → Token still invalid

## Why Application Secret Doesn't Work

The **Application Secret** is used for:
- OAuth authorization flows
- Generating access tokens from authorization codes
- Server-to-server authentication setup

The **Access Token** is used for:
- Direct API calls to Square
- Creating customers, invoices, payments
- What we need for testing!

## Alternative: Use OAuth Flow

If you can't find the Sandbox Access Token, you can use the OAuth flow:

1. Start the Laravel dev server:
   ```bash
   php artisan serve
   ```

2. Visit:
   ```
   http://localhost:8000/square/oauth
   ```

3. Click "Connect Square Account"
4. Authorize with your sandbox account
5. The access token will be saved automatically

But for quick testing, it's easier to just copy the Sandbox Access Token from the dashboard!

## Still Can't Find It?

If you're having trouble locating the Sandbox Access Token:

1. **Check if you're in Sandbox mode**
   - Look for a toggle that says "Sandbox" / "Production"
   - Make sure it's set to "Sandbox"

2. **Look for "Access Token" section**
   - It might be labeled as:
     - "Sandbox Access Token"
     - "Personal Access Token"
     - "Access Token (Sandbox)"

3. **Try the "Show" button**
   - The token is usually hidden by default
   - Click "Show" or an eye icon to reveal it

4. **Generate a new one**
   - There might be a "Generate" or "Regenerate" button
   - Click it to create a fresh token

---

## Next Steps

1. Get Sandbox Access Token from Square Dashboard
2. Get Location ID from same page
3. Update `.env` with both values
4. Run test command
5. Check your email for Square invoice
6. Click payment link
7. See Cash App payment request on your phone!
8. Confirm payment in Cash App
9. SUCCESS! 🎉
