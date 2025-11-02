# Email Notification Setup Guide

## ✅ Your Current Configuration

Your email is already configured and ready to use!

**SMTP Provider:** NameCheap Private Email (mail.privateemail.com)
**From Address:** support@djib-payroll.com
**From Name:** TogetherPay (or your APP_NAME)

---

## 📧 Email Features Configured

### 1. Payment Request Notifications
- **Subject:** 💰 Payment Due: {Group Name}
- **Content:** Beautiful HTML email with amount, due date, and payment link
- **Action Button:** "Pay Now" → Opens Smart Pay page
- **Includes:** Instructions to mark as paid after payment

### 2. Email Template Preview
```
Hi {Member Name}! 👋

Your share for {Group Name} is now due.

💵 Amount: ${XX.XX}
📅 Due Date: {Date}

[Pay Now Button]

After paying, click "I've Sent the Payment" on the payment page.

Questions? Reply to this email.

Thank you!
```

---

## 🧪 Testing Your Email

### Step 1: Send a Test Email

Run this command to send a test payment notification:

```bash
php artisan test:payment-notification your-email@example.com
```

**Example:**
```bash
php artisan test:payment-notification ikram@example.com
```

### Step 2: What Happens

The command will:
1. ✅ Create test data (user, group, billing cycle, payment request)
2. ✅ Send email notification to the specified address
3. ✅ Show payment link in console
4. ✅ Ask if you want to cleanup test data

### Step 3: Check Your Inbox

Look for an email with:
- **Subject:** 💰 Payment Due: Test Group - Family Phone Plan
- **From:** support@djib-payroll.com
- **Amount:** $50.00
- **Payment Link:** Click "Pay Now" to see Smart Pay page

---

## 🔍 Troubleshooting

### If Email Doesn't Send

**Check 1: Verify .env Configuration**
```bash
php artisan config:clear
php artisan config:cache
```

**Check 2: Test SMTP Connection**
```bash
php artisan tinker
```
Then in Tinker:
```php
Mail::raw('Test email', function ($message) {
    $message->to('your-email@example.com')
            ->subject('Test Email from TogetherPay');
});
```

**Check 3: Check Laravel Logs**
```bash
tail -f storage/logs/laravel.log
```

### Common Issues

**Issue:** "Connection refused"
- **Solution:** Check MAIL_PORT (465 for SSL, 587 for TLS)
- **Current:** 465 with SSL ✅

**Issue:** "Authentication failed"
- **Solution:** Verify MAIL_USERNAME and MAIL_PASSWORD
- **Check:** Make sure password is correct (no extra spaces)

**Issue:** "Email not received"
- **Solution:** Check spam folder
- **Solution:** Try sending to a different email address

---

## 📬 Queue Configuration (Optional)

For production, you should queue notifications to avoid blocking:

### Step 1: Configure Queue Driver

**Option A: Database Queue** (Recommended for small-medium apps)
```bash
# .env
QUEUE_CONNECTION=database

# Create jobs table
php artisan queue:table
php artisan migrate

# Run queue worker
php artisan queue:work
```

**Option B: Redis Queue** (Better for high traffic)
```bash
# .env
QUEUE_CONNECTION=redis

# Run queue worker
php artisan queue:work redis
```

### Step 2: Keep Queue Worker Running (Production)

**Using Supervisor:**
```bash
sudo apt-get install supervisor
```

Create `/etc/supervisor/conf.d/togetherpay-worker.conf`:
```ini
[program:togetherpay-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/worker.log
stopwaitsecs=3600
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start togetherpay-worker:*
```

---

## 🚀 Using Notifications in Code

### Send Payment Request Notification

```php
use App\Notifications\PaymentRequestNotification;

// When creating a payment request
$member->user->notify(new PaymentRequestNotification($paymentRequest));
```

### Send to Multiple Members

```php
use Illuminate\Support\Facades\Notification;

$members = $group->activeMembers()->with('user')->get();

Notification::send(
    $members->pluck('user'),
    new PaymentRequestNotification($paymentRequest)
);
```

### Queue Notification

```php
// Already queued by default (implements ShouldQueue)
$user->notify(new PaymentRequestNotification($paymentRequest));
```

---

## 📊 Monitoring Emails

### Check Failed Jobs

```bash
# List failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry {id}

# Retry all failed jobs
php artisan queue:retry all
```

### Check Database Notifications

```bash
php artisan tinker
```
```php
// Check notifications table
DB::table('notifications')->latest()->take(10)->get();
```

---

## 🎨 Customizing Email Template

The email template is located at:
`app/Notifications/PaymentRequestNotification.php`

### Customize Subject

```php
public function toMail(object $notifiable): MailMessage
{
    return (new MailMessage)
        ->subject("🎯 Your Custom Subject: {$group->name}")
        // ...
}
```

### Customize Content

```php
return (new MailMessage)
    ->subject("💰 Payment Due: {$group->name}")
    ->greeting("Hello {$notifiable->name}!")
    ->line("Your custom message here.")
    ->line("**Amount:** \${$amount}")
    ->action('Pay Now', $payLink)
    ->salutation('Best regards, Team TogetherPay');
```

### Add Custom Logo

Create `resources/views/vendor/mail/html/header.blade.php`:
```blade
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img src="https://your-domain.com/logo.png" class="logo" alt="TogetherPay Logo">
@else
{{ $slot }}
@endif
</a>
</td>
</tr>
```

Publish mail views:
```bash
php artisan vendor:publish --tag=laravel-mail
```

---

## 📝 Email Best Practices

### 1. Test Before Production
- Always send test emails before going live
- Check spam score: https://www.mail-tester.com/
- Test on multiple email clients (Gmail, Outlook, Apple Mail)

### 2. Monitor Deliverability
- Check bounce rates
- Monitor spam complaints
- Keep email list clean (remove bounces)

### 3. Comply with Regulations
- Include unsubscribe link (for marketing emails)
- Add physical address in footer
- Follow CAN-SPAM Act guidelines

### 4. Performance
- Use queue for all notifications
- Set reasonable retry attempts (3-5 max)
- Monitor queue size and processing time

---

## ✅ Quick Test Checklist

Before deploying to production:

- [ ] Send test email using `php artisan test:payment-notification`
- [ ] Verify email appears in inbox (not spam)
- [ ] Click "Pay Now" button → Opens Smart Pay page
- [ ] Check email formatting on mobile device
- [ ] Verify "Reply-To" works correctly
- [ ] Test queue worker is processing jobs
- [ ] Check failed jobs queue is empty
- [ ] Review Laravel logs for errors

---

## 🆘 Need Help?

### Email Not Sending?

1. Run the test command with verbose output:
   ```bash
   php artisan test:payment-notification your@email.com -vvv
   ```

2. Check Laravel logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. Verify SMTP credentials with your provider (NameCheap)

4. Test with a simple email:
   ```bash
   php artisan tinker
   Mail::raw('Test', fn($m) => $m->to('test@example.com')->subject('Test'));
   ```

### Still Having Issues?

Contact your email provider (NameCheap) to ensure:
- SMTP is enabled for your account
- Port 465 is not blocked
- Your IP is not blacklisted
- Daily sending limits are not exceeded

---

## 🎉 You're All Set!

Your email notifications are configured and ready to use!

**Next Steps:**
1. Test email: `php artisan test:payment-notification your@email.com`
2. Check inbox and click "Pay Now"
3. Verify Smart Pay page loads correctly
4. Deploy to production! 🚀
