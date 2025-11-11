# Test Real Square Invoice with Cash App Pay

This guide shows you how to use the `test:real-square-invoice` command to create and send a real Square invoice that can be paid via Cash App.

## Prerequisites

- Your Square account must be connected in the app
- You need access to an email to receive the invoice
- Have the Cash App mobile app installed on your phone (for testing Cash App Pay)

## Usage

### Basic Usage
```bash
php artisan test:real-square-invoice <member_email> [member_name] [member_phone]
```

### Examples

**Simple - Just provide email:**
```bash
php artisan test:real-square-invoice john@example.com
```

**With member details:**
```bash
php artisan test:real-square-invoice john@example.com "John Smith" "+12065551234"
```

**Skip confirmation (auto-confirm):**
```bash
php artisan test:real-square-invoice john@example.com --force
```

**Use specific owner (if you have multiple connected accounts):**
```bash
php artisan test:real-square-invoice john@example.com "John Smith" "+12065551234" --owner_id=5
```

## What It Does

1. **Finds your connected Square account** - Uses your merchant credentials
2. **Creates a test group** - Sets up auto_square billing mode
3. **Creates a member** - Uses the email you provide
4. **Creates a billing cycle** - Due 7 days from now
5. **Creates a Square customer** - Registers member in Square
6. **Generates an invoice** - $5.00 with Cash App Pay enabled
7. **Publishes the invoice** - Square sends email notification

## What Happens Next

### You Receive Email
- Square sends invoice email to the member email address
- Email includes: Invoice details, amount, and "Pay with Cash App" button

### Member Pays via Cash App
1. Receive email from Square
2. Click the payment link or "Pay with Cash App" button
3. Cash App app opens on your phone
4. Shows: "$5.00 payment request"
5. Swipe to confirm payment
6. Payment completes instantly ✓

### Behind the Scenes
- Square sends webhook: `invoice.payment_made`
- Our app receives webhook and confirms payment
- Payment status updates to `auto_verified` automatically
- Group owner is notified

## Monitor in Real-Time

Watch the logs while testing:

```bash
php artisan pail
```

You'll see:
```
[INFO] Square API: Creating customer ...
[INFO] Square API: Customer created successfully ...
[INFO] Square API: Creating order ...
[INFO] Square API: Order created successfully ...
[INFO] Square API: Creating invoice ...
[INFO] Square API: Invoice created successfully ...
[INFO] Square API: Publishing invoice ...
[INFO] Square API: Invoice published successfully ...
[INFO] Created payment request for invoice ...
```

When payment is received:
```
[INFO] Square webhook received {"event_type":"invoice.payment_made",...}
[INFO] Payment request marked as auto_verified ...
```

## REST API (No SDK)

This command uses the REST API directly - **no Square SDK dependency**!

- Uses Laravel's HTTP client
- Direct calls to Square REST API v2025-01-23
- Cleaner, lighter implementation
- Full control over requests and responses

## Test Data

Each test creates:
- **Invoice Amount**: $5.00
- **Billing Cycle**: 7 days from now
- **Payment Method**: Cash App Pay (+ Card)
- **Delivery Method**: Email notification

## Troubleshooting

### "No user with connected Square account found!"
- Go to app settings
- Click "Connect Square Account"
- Complete the OAuth authorization
- Run the command again

### "Owner is missing Square location ID!"
- Reconnect your Square account
- The location ID should be fetched during OAuth
- Contact support if the issue persists

### Invoice not received
- Check spam/promotions folder in email
- Square emails come from Square, Inc
- Wait a few minutes for email delivery
- Check the email address is correct

### Can't pay via Cash App
- Make sure you have Cash App installed
- Ensure Cash App is linked to your bank/card
- Try the payment link again after a minute
- Check your Cash App balance

## Example Output

```
╔════════════════════════════════════════════════════════════════╗
║     Creating REAL Square Invoice with Cash App Pay             ║
╚════════════════════════════════════════════════════════════════╝

✓ Found owner with Square connection
  Name: Abdallah Mohamed
  Email: abdal_cascad@hotmail.com
  Merchant ID: ML0NA3AEKMYRY
  Location ID: LSM2Z5XZ8GFFC

Step 1: Creating test group...
✓ Group created: Square Invoice Test - 15:39:21

Step 2: Setting up member...
✓ Member created
  Name: John Smith
  Email: john@example.com
  Phone: +12065551234

Step 3: Creating billing cycle...
✓ Billing cycle created
  Cycle ID: 22
  Due Date: Nov 18, 2025

Step 4: Creating Square customer for member...
✓ Square customer created
  Customer ID: FP7RP487MVQ6QDK1CAWR33WH6W

Step 5: Creating Square Invoice with Cash App Pay...
✓ Invoice created and published!
  Invoice ID: inv:0-ChAdJ0Ei26jeLs0enbX7xRF_EPsM

╔════════════════════════════════════════════════════════════════╗
║                    ✓ SUCCESS!                                  ║
╚════════════════════════════════════════════════════════════════╝

📧 Email Notification:
  John Smith will receive an email at:
  john@example.com
  with a Square invoice and Cash App Pay button

💳 Cash App Payment:
  1. Member receives email with payment link
  2. Clicks the link or taps "Pay with Cash App"
  3. Cash App opens with $5.00 payment request
  4. Swipes to confirm payment
  5. Payment completes instantly

✓ Webhook Confirmation:
  When payment is received:
  • Square sends webhook: invoice.payment_made
  • Our app auto-marks payment as auto_verified
  • Notification sent to group owner
```

## Key Improvements

✅ **REST API Only** - No Square SDK dependency
✅ **Uses Real Account** - Works with your connected Square account
✅ **Flexible Input** - Accepts any email/phone for testing
✅ **Clear Output** - Step-by-step progress and next steps
✅ **Webhook Ready** - Auto-confirms when payment received
✅ **Cash App Integration** - Full Cash App Pay support

Happy testing! 🎉
