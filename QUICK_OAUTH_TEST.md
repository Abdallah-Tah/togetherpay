# Quick OAuth Test to Get Valid Access Token

Since the test account access token isn't working, let's use the OAuth flow to get a proper access token.

## Option 1: Use Square Developer Dashboard Sandbox Access Token

1. Go to https://developer.squareup.com/apps
2. Click your application
3. Click **"Credentials"** in left sidebar (NOT OAuth)
4. Toggle to **"Sandbox"** mode at top
5. Find section labeled **"Sandbox Access Token"**
6. Click "Show" button
7. Copy the FULL token
8. Paste it in `.env` as `SQUARE_ACCESS_TOKEN`

## Option 2: If You Can't Find Sandbox Access Token

The test account tokens from the OAuth page have limited permissions. You might need to:

1. Create a real Square sandbox seller account at https://squareupsandbox.com
2. Log in to that account
3. Then use OAuth flow to authorize your app

## Why the Current Token Isn't Working

The token `EAAAl4YRs9djONdfEAkmOourp_y5tP1ncA4Ls_FviBHCl_ExAlqGAfvDvDbt72l8` appears to be from an OAuth test account, which has these limitations:

- ❌ Limited API permissions
- ❌ May be expired
- ❌ May not have access to Customers/Invoices APIs
- ❌ Designed for testing OAuth flows, not direct API calls

## What We Need

A **Sandbox Access Token** that:
- ✅ Has full API permissions
- ✅ Doesn't expire
- ✅ Can create customers and invoices
- ✅ Is your application's main token

## Next Steps

1. Check the **Credentials** page for "Sandbox Access Token"
2. If not found, you may need to create a Square sandbox account first
3. Once you have the correct token, update `.env` and run test again
