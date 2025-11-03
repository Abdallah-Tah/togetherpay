# Production Deployment Quick Start

This guide provides a quick path to getting TogetherPay production-ready.

## Prerequisites Checklist

Before deploying to production, ensure you have:

- [ ] **Domain Name** - e.g., `togetherpay.app`, `api.togetherpay.app`
- [ ] **SSL Certificate** - Let's Encrypt or commercial SSL
- [ ] **Cloud Server** - AWS EC2, DigitalOcean, etc. (2+ cores, 4GB RAM)
- [ ] **PostgreSQL Database** - Managed or self-hosted
- [ ] **Redis Instance** - For cache, queue, sessions
- [ ] **AWS Account** - For S3 (storage) and SES (email)
- [ ] **Payment Provider Accounts** - Cash App, Venmo developer accounts
- [ ] **SMS Service** - Twilio account
- [ ] **Push Notifications** - Firebase project
- [ ] **Apple Developer Account** - For iOS app (if deploying to App Store)
- [ ] **Google Play Developer Account** - For Android app

## Backend Quick Start

### 1. Server Setup (15 minutes)

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-pgsql \
    php8.2-redis php8.2-curl php8.2-mbstring php8.2-xml \
    php8.2-zip php8.2-bcmath php8.2-gd nginx postgresql-client redis-tools

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
```

### 2. Application Deployment (10 minutes)

```bash
# Clone repository
cd /var/www
sudo git clone https://github.com/Abdallah-Tah/togetherpay.git
cd togetherpay

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Configure environment
cp .env.production.example .env
nano .env  # Edit with your values

# Generate key and run migrations
php artisan key:generate
php artisan migrate --force

# Set permissions
sudo chown -R www-data:www-data /var/www/togetherpay
sudo chmod -R 755 /var/www/togetherpay
sudo chmod -R 775 storage bootstrap/cache
```

### 3. Environment Configuration (20 minutes)

Edit `.env` with your production values:

```env
# Core Settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.togetherpay.app

# Database (PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=your-postgres-host
DB_DATABASE=togetherpay_production
DB_USERNAME=togetherpay_user
DB_PASSWORD=your-secure-password

# Redis
REDIS_HOST=your-redis-host
REDIS_PASSWORD=your-redis-password

# AWS (S3 + SES)
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_BUCKET=togetherpay-production
MAIL_MAILER=ses

# Payment Providers
CASHAPP_CLIENT_ID=your-cashapp-id
CASHAPP_CLIENT_SECRET=your-cashapp-secret

# Notifications
TWILIO_ACCOUNT_SID=your-twilio-sid
TWILIO_AUTH_TOKEN=your-twilio-token
FCM_SERVER_KEY=your-fcm-key
```

### 4. Web Server Configuration (10 minutes)

```bash
# Create Nginx config
sudo nano /etc/nginx/sites-available/togetherpay

# Copy configuration from DEPLOYMENT.md
# Link and reload
sudo ln -s /etc/nginx/sites-available/togetherpay /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# Get SSL certificate
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d api.togetherpay.app
```

### 5. Queue Workers & Scheduler (5 minutes)

```bash
# Setup Supervisor for queue workers
sudo nano /etc/supervisor/conf.d/togetherpay-worker.conf
# Copy configuration from DEPLOYMENT.md

sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start togetherpay-worker:*

# Setup cron for scheduler
sudo crontab -e -u www-data
# Add: * * * * * cd /var/www/togetherpay && php artisan schedule:run >> /dev/null 2>&1
```

### 6. Verify Deployment (5 minutes)

```bash
# Test health check
curl https://api.togetherpay.app/api/health

# Expected response:
# {"status":"healthy","timestamp":"...","checks":{...}}

# Check queue workers
sudo supervisorctl status

# Check logs
tail -f storage/logs/laravel.log
```

**Total Backend Setup Time: ~65 minutes**

## Mobile App Quick Start

### Android Setup (30 minutes)

```bash
cd mobile

# 1. Generate release keystore (one-time)
cd android/app
keytool -genkey -v -keystore upload-keystore.jks \
  -keyalg RSA -keysize 2048 -validity 10000 -alias upload

# 2. Configure signing
cd ..
cp key.properties.example key.properties
nano key.properties  # Add your keystore password

# 3. Build release APK/AAB
cd ../..
flutter build appbundle --release \
  --dart-define=API_BASE_URL=https://api.togetherpay.app/api/v1

# 4. Upload to Play Console
# Upload: build/app/outputs/bundle/release/app-release.aab
```

### iOS Setup (45 minutes)

**Requirements:** macOS with Xcode

```bash
cd mobile

# 1. Open in Xcode
open ios/Runner.xcworkspace

# 2. Configure signing in Xcode
# - Select Runner target
# - Go to Signing & Capabilities
# - Select your Team
# - Enable "Automatically manage signing"

# 3. Configure production entitlements
# - Verify Runner-Release.entitlements has production domains
# - Link entitlements in Build Settings

# 4. Build archive
flutter build ios --release \
  --dart-define=API_BASE_URL=https://api.togetherpay.app/api/v1

# 5. In Xcode: Product → Archive → Distribute App
```

### App Links / Universal Links

**For iOS (apple-app-site-association):**

Upload to: `https://togetherpay.app/.well-known/apple-app-site-association`

```json
{
  "applinks": {
    "apps": [],
    "details": [{
      "appID": "YOUR_TEAM_ID.com.togetherpay.togetherpay",
      "paths": ["/p/*", "/pay/*"]
    }]
  }
}
```

**For Android (assetlinks.json):**

Upload to: `https://togetherpay.app/.well-known/assetlinks.json`

```json
[{
  "relation": ["delegate_permission/common.handle_all_urls"],
  "target": {
    "namespace": "android_app",
    "package_name": "com.togetherpay.togetherpay",
    "sha256_cert_fingerprints": ["YOUR_SHA256_FROM_KEYSTORE"]
  }
}]
```

Get SHA-256:
```bash
keytool -list -v -keystore android/app/upload-keystore.jks -alias upload
```

**Total Mobile Setup Time: ~75 minutes**

## Post-Deployment Checklist

### Immediate (First Hour)

- [ ] Verify health check endpoint responds
- [ ] Test user registration flow
- [ ] Test authentication (login/logout)
- [ ] Create a test group
- [ ] Add a test member
- [ ] Generate a test payment request
- [ ] Verify email delivery
- [ ] Test SMS notifications
- [ ] Check error logs
- [ ] Monitor performance metrics

### First Day

- [ ] Set up error tracking (Sentry)
- [ ] Configure uptime monitoring
- [ ] Test payment flow with real accounts
- [ ] Verify webhook delivery
- [ ] Check database backups
- [ ] Review security logs
- [ ] Test rate limiting
- [ ] Verify SSL configuration

### First Week

- [ ] Monitor error rates
- [ ] Check performance metrics
- [ ] Review user feedback
- [ ] Address critical bugs
- [ ] Scale resources if needed
- [ ] Optimize slow queries
- [ ] Update documentation with learnings

## Common Issues & Solutions

### Issue: Health Check Returns 503

**Solution:**
```bash
# Check database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check Redis connection
redis-cli -h your-redis-host ping

# Check logs
tail -f storage/logs/laravel.log
```

### Issue: Queue Workers Not Processing

**Solution:**
```bash
# Check supervisor status
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart togetherpay-worker:*

# Check queue size
php artisan queue:work --once
```

### Issue: Mobile App Can't Connect

**Solution:**
1. Verify API URL is correct in production config
2. Check SSL certificate is valid
3. Verify CORS origins include app domains
4. Check device can reach API endpoint
5. Review mobile app logs

### Issue: Emails Not Sending

**Solution:**
```bash
# Test SES configuration
php artisan tinker
>>> Mail::raw('Test', function($msg) { 
    $msg->to('test@example.com')->subject('Test'); 
});

# Check AWS SES console for bounces
# Verify sender email is verified in SES
```

## Monitoring Setup

### Required Monitoring

1. **Uptime Monitoring** (5 min)
   - Sign up: Pingdom, UptimeRobot, or similar
   - Monitor: `https://api.togetherpay.app/api/health/alive`
   - Alert: Email/SMS on downtime

2. **Error Tracking** (10 min)
   - Sign up: sentry.io
   - Add DSN to `.env`: `SENTRY_LARAVEL_DSN=your-dsn`
   - Deploy updated config

3. **Performance Monitoring** (15 min)
   - Enable in monitoring.php
   - Set up CloudWatch or custom dashboard
   - Track: Response times, database queries, queue size

### Optional Monitoring

- Log aggregation (Papertrail, Loggly)
- APM (New Relic, Datadog)
- Infrastructure monitoring (Prometheus, Grafana)

## Scaling Considerations

### When to Scale

Scale when you consistently see:
- CPU > 70% for 5+ minutes
- Memory > 80%
- Response time > 2 seconds
- Queue backlog > 1000 jobs

### Horizontal Scaling

1. **Add Application Servers**
   - Deploy to multiple servers
   - Add load balancer
   - Use shared Redis/Database

2. **Database Read Replicas**
   - Set up read replica
   - Configure in `.env`: `DB_READ_HOST`

3. **Redis Cluster**
   - Set up Redis Cluster
   - Update connection string

### Vertical Scaling

- Increase server resources (CPU, RAM)
- Optimize database (indexes, queries)
- Add Redis memory
- Enable OPcache

## Cost Estimates

### Minimum Production Setup

| Service | Provider | Monthly Cost |
|---------|----------|--------------|
| Server (2 cores, 4GB) | DigitalOcean | $24 |
| PostgreSQL Database | DigitalOcean | $15 |
| Redis | DigitalOcean | $15 |
| S3 Storage (10GB) | AWS | $0.23 |
| SES Email (10k emails) | AWS | $1 |
| Twilio SMS (1000 SMS) | Twilio | $10 |
| Domain + SSL | Various | $12 |
| **Total** | | **~$77/month** |

### With Monitoring

Add:
- Sentry (10k events) - Free tier
- Uptime monitoring - Free tier
- Total: **~$77/month**

### At Scale (10k users)

Add:
- Larger server (4 cores, 8GB) - $48
- Larger database - $30
- Increased storage/email/SMS - ~$50
- **Total: ~$205/month**

## Success Metrics

Track these metrics to measure production readiness:

- **Uptime:** > 99.9%
- **Response Time:** < 500ms (p95)
- **Error Rate:** < 0.1%
- **Failed Jobs:** < 1%
- **User Satisfaction:** > 4.5/5
- **Time to Recovery:** < 5 minutes

## Support & Resources

### Documentation
- [Deployment Guide](DEPLOYMENT.md) - Detailed deployment instructions
- [Security Guide](SECURITY.md) - Security best practices
- [API Documentation](API_DOCUMENTATION.md) - Complete API reference
- [Production Readiness](PRODUCTION_READINESS.md) - Comprehensive checklist

### Getting Help
- **Email:** ops@togetherpay.app
- **Documentation:** All guides in repository
- **GitHub Issues:** Report bugs and issues

### Community
- **Slack:** #production-ops
- **Forum:** community.togetherpay.app (future)

## Rollback Procedure

If something goes wrong:

```bash
# Quick rollback
cd /var/www/togetherpay
git reset --hard HEAD~1
composer install --no-dev
php artisan migrate:rollback
php artisan config:cache
sudo systemctl reload php8.2-fpm
sudo supervisorctl restart togetherpay-worker:*
```

## Next Steps After Launch

1. **Week 1:** Monitor closely, fix critical bugs
2. **Week 2:** Optimize based on real usage data
3. **Month 1:** Implement user feedback
4. **Month 2:** Plan next features
5. **Quarter 1:** Review and optimize infrastructure

---

**Estimated Total Setup Time: 2-3 hours**

**Production-Ready Status: Backend ✅ | Mobile 🟡 (needs signing)**

Good luck with your launch! 🚀
