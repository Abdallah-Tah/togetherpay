# Square 401 Authentication Error - Debugging Guide

## Current Status

✅ **FIXED**: URL issue - now correctly using sandbox URL
❌ **INVESTIGATING**: 401 authentication error when services use the token

## The New Issue

The initial token verification works:
```
✓ Square API reachable and token looks valid.
  Environment: sandbox
  Base URL: https://connect.squareupsandbox.com
```

But when `SquareCustomerService` and `SquareInvoiceService` try to use the token, they get:
```
401 UNAUTHORIZED
"This request could not be authorized."
```

## What's Happening

1. **Test command** (line 36): Hardcoded token
   ```php
   $squareToken = 'EAAAl4YRs9djONdfEAkmOourp_y5tP1ncA4Ls_FviBHCl_ExAlqGAfvDvDbt72l8';
   ```

2. **Initial verification** (line 68-78): Uses token directly ✅
   ```php
   Http::withHeaders([
       'Authorization' => 'Bearer ' . $squareToken,
   ])->get($baseUrl . '/v2/locations');
   ```

3. **Store encrypted** (line 118): Encrypts before saving
   ```php
   'square_access_token' => encrypt($squareToken),
   ```

4. **Services retrieve** (SquareClientBuilder line 36): Decrypts from DB
   ```php
   $accessToken = decrypt($user->square_access_token);
   ```

## Possible Causes

### 1. **Encryption/Decryption Mismatch**
The encrypted value might not be decrypting correctly.

### 2. **Model Not Refreshed**
The `$owner` object might have stale data and needs `$owner->refresh()`.

### 3. **Token Format Issue**
The decrypted token might have extra spaces, newlines, or other characters.

### 4. **Square SDK Issue**
The SquareClient constructor might be modifying the token somehow.

## Changes Applied for Debugging

### 1. Added Encryption Verification (TestRealSquareInvoice.php)
```php
// After creating owner, test encryption/decryption
$decryptedToken = decrypt($owner->square_access_token);
$tokenMatch = $decryptedToken === $squareToken;
$this->line("  Decrypted token matches: " . ($tokenMatch ? 'YES ✓' : 'NO ✗'));
```

### 2. Added Logging (SquareClientBuilder.php)
```php
logger()->info('SquareClientBuilder::buildForUser', [
    'user_id' => $user->id,
    'encrypted_length' => strlen($user->square_access_token),
    'decrypted_length' => strlen($accessToken),
    'environment' => $environment,
    'token_preview' => substr($accessToken, 0, 20) . '...',
]);
```

### 3. Added Model Refresh (TestRealSquareInvoice.php)
```php
// Before using owner in services
$owner->refresh();
```

## Next Steps

### Run the test again:
```bash
php artisan test:real-square-invoice abdallahtmohamed86@gmail.com +12074097887 --force
```

### Look for in the output:

1. **Encryption test results:**
   ```
   Encrypted token length: [number]
   Decrypted token matches: YES ✓  OR  NO ✗
   Decrypted token: EAAAl4YRs9djONdfEAkm...
   ```

2. **Check Laravel logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

   Look for:
   ```
   SquareClientBuilder::buildForUser
   - user_id: [number]
   - encrypted_length: [number]
   - decrypted_length: [should be 84 for your token]
   - token_preview: EAAAl4YRs9djONdfEAkm...
   ```

## Expected Token Length

Your token: `EAAAl4YRs9djONdfEAkmOourp_y5tP1ncA4Ls_FviBHCl_ExAlqGAfvDvDbt72l8`

Length: 84 characters

After decryption, it should still be 84 characters.

## If Token Match = NO

The encryption/decryption is failing. Possible fixes:
1. Store token unencrypted for testing
2. Check APP_KEY in .env hasn't changed
3. Use a different encryption method

## If Token Match = YES but Still 401

The issue is with how the Square SDK is using the token. Possible fixes:
1. The token might have expired
2. The token might not have the required permissions
3. There might be whitespace issues (`trim()` the token)
4. The Square SDK might be using the wrong environment

## Quick Fix to Test

Try storing the token unencrypted temporarily:

```php
// In TestRealSquareInvoice.php line 118
'square_access_token' => $squareToken, // No encrypt()
```

And in SquareClientBuilder.php line 36:
```php
$accessToken = $user->square_access_token; // No decrypt()
```

If this works, the issue is definitely with encryption/decryption.
