# Production Deployment Checklist

This checklist ensures your TogetherPay application is ready for production deployment.

## ✅ Pre-Deployment Checklist

### 1. Environment Configuration

- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Generate new `APP_KEY` for production
- [ ] Configure production database credentials
  - [ ] `DB_CONNECTION=pgsql`
  - [ ] `DB_HOST` set to production database
  - [ ] `DB_DATABASE` configured
  - [ ] `DB_USERNAME` configured
  - [ ] `DB_PASSWORD` set securely
- [ ] Configure Redis for queues and cache
  - [ ] `QUEUE_CONNECTION=redis`
  - [ ] `CACHE_STORE=redis`
  - [ ] `REDIS_HOST` configured
  - [ ] `REDIS_PASSWORD` set securely
- [ ] Set `SESSION_DRIVER=redis` for better performance
- [ ] Configure production URL
  - [ ] `APP_URL=https://www.togetherpay.app`
  - [ ] `SANCTUM_STATEFUL_DOMAINS=www.togetherpay.app`
  - [ ] `SESSION_DOMAIN=.togetherpay.app`

### 2. Email Configuration

- [ ] Configure AWS SES or email provider
  - [ ] `MAIL_MAILER=ses`
  - [ ] `AWS_ACCESS_KEY_ID` configured
  - [ ] `AWS_SECRET_ACCESS_KEY` configured
  - [ ] `AWS_DEFAULT_REGION` configured
  - [ ] `MAIL_FROM_ADDRESS` set
  - [ ] `MAIL_FROM_NAME` set
- [ ] Test email sending works
- [ ] Verify email deliverability (not spam)

### 3. Square Integration

- [ ] Switch to production Square environment
  - [ ] `SQUARE_ENVIRONMENT=production`
  - [ ] `SQUARE_CLIENT_ID` (production app)
  - [ ] `SQUARE_CLIENT_SECRET` (production app)
  - [ ] `SQUARE_OAUTH_CALLBACK_URL=https://www.togetherpay.app/oauth/square/callback`
- [ ] Configure webhook signing key
  - [ ] `SQUARE_WEBHOOK_SIGNING_KEY` (from Square dashboard)
  - [ ] `SQUARE_WEBHOOK_ENDPOINT=https://www.togetherpay.app/webhooks/square`
- [ ] Register webhook endpoint in Square dashboard
  - [ ] invoice.payment_made
  - [ ] subscription.charge.succeeded
  - [ ] subscription.charge.failed
  - [ ] payment.updated
- [ ] Test Square OAuth flow in production
- [ ] Test Square webhook signature verification

### 4. Storage & File Uploads

- [ ] Configure S3 for file storage
  - [ ] `FILESYSTEM_DISK=s3`
  - [ ] `AWS_BUCKET` configured
  - [ ] `AWS_ACCESS_KEY_ID` configured
  - [ ] `AWS_SECRET_ACCESS_KEY` configured
  - [ ] `AWS_DEFAULT_REGION` configured
- [ ] Test file uploads work
- [ ] Configure S3 bucket CORS policy
- [ ] Set up S3 lifecycle policies for exports

### 5. Security

- [ ] SSL certificate installed and valid
- [ ] Force HTTPS (configure web server)
- [ ] Review and update CORS settings
- [ ] Configure rate limiting appropriately
- [ ] Set secure session cookie settings
  - [ ] `SESSION_SECURE_COOKIE=true`
  - [ ] `SESSION_HTTP_ONLY=true`
  - [ ] `SESSION_SAME_SITE=lax`
- [ ] Review and lock down firewall rules
- [ ] Disable unnecessary ports
- [ ] Configure fail2ban or similar
- [ ] Set up SSH key-only authentication

### 6. Database

- [ ] Run fresh migrations on production database
  ```bash
  php artisan migrate --force
  ```
- [ ] Verify all tables created correctly
- [ ] Set up automated database backups
- [ ] Test database backup restoration
- [ ] Configure connection pooling (pgbouncer for PostgreSQL)
- [ ] Optimize database indexes
- [ ] Set up database monitoring

### 7. Queue Workers

- [ ] Configure Supervisor for queue workers
  ```bash
  sudo cp supervisor-togetherpay-worker.conf /etc/supervisor/conf.d/
  sudo cp supervisor-togetherpay-scheduler.conf /etc/supervisor/conf.d/
  sudo supervisorctl reread
  sudo supervisorctl update
  sudo supervisorctl start togetherpay-worker:*
  sudo supervisorctl start togetherpay-scheduler:*
  ```
- [ ] Verify workers are running
  ```bash
  sudo supervisorctl status
  ```
- [ ] Test queue job processing
- [ ] Set up Laravel Horizon (optional but recommended)
  - [ ] `composer require laravel/horizon`
  - [ ] `php artisan horizon:publish`
  - [ ] Configure dashboard authentication

### 8. Scheduler

- [ ] Add Laravel scheduler to crontab
  ```bash
  * * * * * cd /home/togetherpay/htdocs/www.togetherpay.app && php artisan schedule:run >> /dev/null 2>&1
  ```
- [ ] Verify cron job is running
  ```bash
  crontab -l
  ```
- [ ] Test scheduled jobs execute correctly

### 9. Performance Optimization

- [ ] Run production optimizations
  ```bash
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  php artisan event:cache
  composer install --no-dev --optimize-autoloader
  npm run build
  ```
- [ ] Enable OPcache in PHP
- [ ] Configure Redis memory limits
- [ ] Set up CDN for static assets (optional)
- [ ] Configure gzip compression on web server

### 10. Monitoring & Logging

- [ ] Configure application logging
  - [ ] `LOG_CHANNEL=stack`
  - [ ] Set up log rotation
  - [ ] Configure log retention policy
- [ ] Set up error tracking (Sentry, Bugsnag, etc.)
  ```bash
  composer require sentry/sentry-laravel
  ```
- [ ] Configure uptime monitoring
- [ ] Set up performance monitoring (New Relic, AppSignal, etc.)
- [ ] Monitor queue depth
- [ ] Monitor database connections
- [ ] Set up alerts for:
  - [ ] Application errors
  - [ ] Failed jobs
  - [ ] High queue depth
  - [ ] Database connection issues
  - [ ] Disk space warnings

### 11. Testing in Production

- [ ] Run smoke tests after deployment
  ```bash
  php artisan test --testsuite=Feature
  ```
- [ ] Test critical user flows:
  - [ ] User registration
  - [ ] Group creation
  - [ ] Member invitation
  - [ ] Payment request creation
  - [ ] Payment confirmation
  - [ ] Square OAuth connection
  - [ ] Square webhook processing
- [ ] Verify email notifications work
- [ ] Test mobile app integration (if deployed)

### 12. Backup & Recovery

- [ ] Database automated backups configured
- [ ] Test database restoration process
- [ ] Application code backup strategy
- [ ] Redis data persistence configured
- [ ] Document recovery procedures
- [ ] Store backups off-site (different region)

### 13. Documentation

- [ ] Update API documentation
- [ ] Document deployment process
- [ ] Create runbook for common issues
- [ ] Document emergency procedures
- [ ] Update environment variable documentation

---

## 🚀 Deployment Steps

1. **Backup Current State**
   ```bash
   # Backup database
   pg_dump -U togetherpay togetherpay > backup-$(date +%Y%m%d-%H%M%S).sql

   # Backup code
   tar -czf code-backup-$(date +%Y%m%d-%H%M%S).tar.gz /home/togetherpay/htdocs/www.togetherpay.app
   ```

2. **Pull Latest Code**
   ```bash
   cd /home/togetherpay/htdocs/www.togetherpay.app
   git pull origin main
   ```

3. **Install Dependencies**
   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci
   npm run build
   ```

4. **Run Migrations**
   ```bash
   php artisan migrate --force
   ```

5. **Clear & Cache**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan event:cache
   ```

6. **Restart Services**
   ```bash
   sudo supervisorctl restart togetherpay-worker:*
   sudo service php8.2-fpm restart  # Adjust PHP version as needed
   ```

7. **Verify Deployment**
   ```bash
   php artisan about
   curl -I https://www.togetherpay.app/health
   ```

---

## 🔍 Post-Deployment Verification

- [ ] Application loads without errors
- [ ] Health check endpoint responds: `/health`
- [ ] Queue workers processing jobs
- [ ] Scheduler running correctly
- [ ] Database queries executing
- [ ] Redis cache working
- [ ] Email sending functional
- [ ] Square webhooks being received
- [ ] SSL certificate valid
- [ ] No console errors in browser
- [ ] Mobile API endpoints responding

---

## 🆘 Rollback Plan

If issues occur:

1. **Immediate Rollback**
   ```bash
   git checkout <previous-commit-hash>
   composer install
   php artisan migrate:rollback
   php artisan config:cache
   sudo supervisorctl restart togetherpay-worker:*
   ```

2. **Database Rollback**
   ```bash
   psql -U togetherpay togetherpay < backup-YYYYMMDD-HHMMSS.sql
   ```

3. **Notify Team**
   - Alert team of rollback
   - Document issue
   - Schedule post-mortem

---

## 📊 Monitoring Checklist (First 24 Hours)

- [ ] Monitor error logs every 2 hours
- [ ] Check queue depth every hour
- [ ] Verify webhook processing
- [ ] Monitor database performance
- [ ] Check user registration flow
- [ ] Monitor payment request creation
- [ ] Verify email delivery rates
- [ ] Check Square integration success rates

---

## 🔐 Security Final Checks

- [ ] No `.env` file committed to git
- [ ] No sensitive data in logs
- [ ] Database credentials rotated
- [ ] API keys valid and secure
- [ ] Remove test/debug endpoints
- [ ] Verify CSRF protection enabled
- [ ] Check XSS protection enabled
- [ ] SQL injection protection verified

---

## 📝 Sign-Off

- [ ] Technical lead approval
- [ ] QA testing complete
- [ ] Staging environment verified
- [ ] Deployment window scheduled
- [ ] Team notified
- [ ] On-call engineer assigned

**Deployed by:** ___________________
**Date:** ___________________
**Version:** ___________________
**Git Commit:** ___________________

---

**Notes:**

This checklist is based on Laravel best practices and the specific architecture of the TogetherPay application. Adjust as needed for your infrastructure.
