# Production Deployment Guide

This guide provides comprehensive instructions for deploying TogetherPay to production environments.

## Prerequisites

### Required Services
- PostgreSQL 14+ (primary database)
- Redis 6+ (cache, queue, sessions)
- AWS S3 (file storage)
- AWS SES (email delivery)
- Domain with SSL certificate

### Required Credentials
- Database credentials
- Redis connection details
- AWS access keys (S3, SES)
- Payment provider API keys (Cash App, Venmo)
- Twilio credentials (SMS notifications)
- Firebase credentials (push notifications)

## Initial Setup

### 1. Server Requirements

**Minimum Server Specifications:**
- CPU: 2 cores
- RAM: 4GB
- Storage: 20GB SSD
- Ubuntu 22.04 LTS or similar

**Required Software:**
```bash
# PHP 8.2 with required extensions
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common \
    php8.2-pgsql php8.2-redis php8.2-curl php8.2-mbstring \
    php8.2-xml php8.2-zip php8.2-bcmath php8.2-gd php8.2-intl

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js 22+ (for asset building)
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs

# Nginx
sudo apt install -y nginx

# PostgreSQL Client
sudo apt install -y postgresql-client

# Supervisor (for queue workers)
sudo apt install -y supervisor
```

### 2. Application Deployment

**Clone Repository:**
```bash
cd /var/www
sudo git clone https://github.com/Abdallah-Tah/togetherpay.git
sudo chown -R www-data:www-data togetherpay
cd togetherpay
```

**Install Dependencies:**
```bash
# PHP dependencies
composer install --no-dev --optimize-autoloader

# Node dependencies and build assets
npm ci
npm run build
```

**Configure Environment:**
```bash
# Copy production environment file
cp .env.production.example .env

# Generate application key
php artisan key:generate

# Edit .env with production values
nano .env
```

**Required Environment Variables:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.togetherpay.app

DB_CONNECTION=pgsql
DB_HOST=your-postgres-host
DB_DATABASE=togetherpay_production
DB_USERNAME=togetherpay_user
DB_PASSWORD=your-secure-password

REDIS_HOST=your-redis-host
REDIS_PASSWORD=your-redis-password

AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_BUCKET=togetherpay-production

MAIL_MAILER=ses
MAIL_FROM_ADDRESS=noreply@togetherpay.app
```

**Set Permissions:**
```bash
sudo chown -R www-data:www-data /var/www/togetherpay
sudo chmod -R 755 /var/www/togetherpay
sudo chmod -R 775 /var/www/togetherpay/storage
sudo chmod -R 775 /var/www/togetherpay/bootstrap/cache
```

**Run Migrations:**
```bash
php artisan migrate --force
```

**Cache Configuration:**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 3. Web Server Configuration

**Nginx Configuration:**
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name api.togetherpay.app;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name api.togetherpay.app;
    root /var/www/togetherpay/public;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/api.togetherpay.app/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.togetherpay.app/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security Headers
    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;

    # Logging
    access_log /var/log/nginx/togetherpay-access.log;
    error_log /var/log/nginx/togetherpay-error.log;

    # Index files
    index index.php;

    charset utf-8;

    # Main location block
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Health check endpoint
    location /up {
        access_log off;
        return 200 "OK\n";
        add_header Content-Type text/plain;
    }

    # PHP FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }

    # Client body size (for file uploads)
    client_max_body_size 20M;
}
```

Save to `/etc/nginx/sites-available/togetherpay`:
```bash
sudo ln -s /etc/nginx/sites-available/togetherpay /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 4. Queue Worker Configuration

**Supervisor Configuration:**

Create `/etc/supervisor/conf.d/togetherpay-worker.conf`:
```ini
[program:togetherpay-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/togetherpay/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopascompletely=true
stopwaitsecs=3600
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/togetherpay/storage/logs/worker.log
stopwaitsecs=3600
```

Start workers:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start togetherpay-worker:*
```

### 5. Scheduler Configuration

**Add to crontab:**
```bash
sudo crontab -e -u www-data
```

Add this line:
```cron
* * * * * cd /var/www/togetherpay && php artisan schedule:run >> /dev/null 2>&1
```

### 6. SSL Certificate (Let's Encrypt)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d api.togetherpay.app
```

## Database Setup

### PostgreSQL Configuration

**Create Database:**
```sql
CREATE DATABASE togetherpay_production;
CREATE USER togetherpay_user WITH ENCRYPTED PASSWORD 'your-secure-password';
GRANT ALL PRIVILEGES ON DATABASE togetherpay_production TO togetherpay_user;
\c togetherpay_production
GRANT ALL ON SCHEMA public TO togetherpay_user;
```

**Performance Tuning:**
```sql
-- Adjust based on your server specs
ALTER SYSTEM SET shared_buffers = '256MB';
ALTER SYSTEM SET effective_cache_size = '1GB';
ALTER SYSTEM SET maintenance_work_mem = '64MB';
ALTER SYSTEM SET checkpoint_completion_target = 0.9;
ALTER SYSTEM SET wal_buffers = '16MB';
ALTER SYSTEM SET default_statistics_target = 100;
ALTER SYSTEM SET random_page_cost = 1.1;
ALTER SYSTEM SET effective_io_concurrency = 200;

SELECT pg_reload_conf();
```

**Backup Strategy:**
```bash
# Daily automated backup script
sudo nano /usr/local/bin/backup-togetherpay-db.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/togetherpay"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

pg_dump -h your-postgres-host -U togetherpay_user togetherpay_production | \
  gzip > $BACKUP_DIR/togetherpay_$DATE.sql.gz

# Upload to S3
aws s3 cp $BACKUP_DIR/togetherpay_$DATE.sql.gz s3://togetherpay-backups/database/

# Keep only last 7 days locally
find $BACKUP_DIR -name "*.sql.gz" -mtime +7 -delete
```

Make executable and add to cron:
```bash
sudo chmod +x /usr/local/bin/backup-togetherpay-db.sh
sudo crontab -e
# Add: 0 2 * * * /usr/local/bin/backup-togetherpay-db.sh
```

## Redis Configuration

**Redis Sentinel for High Availability (Optional):**
```bash
# Install Redis Sentinel
sudo apt install redis-sentinel

# Configure sentinel
sudo nano /etc/redis/sentinel.conf
```

## Monitoring & Logging

### Application Monitoring

**Install Laravel Horizon (Queue Dashboard):**
```bash
php artisan horizon:install
php artisan horizon:publish
```

Configure authentication in `.env`:
```env
HORIZON_BASIC_AUTH=true
HORIZON_USERNAME=admin
HORIZON_PASSWORD=secure-password
```

Access at: `https://api.togetherpay.app/horizon`

### Error Tracking (Sentry)

**Install Sentry SDK:**
```bash
composer require sentry/sentry-laravel
php artisan vendor:publish --provider="Sentry\Laravel\ServiceProvider"
```

Configure in `.env`:
```env
SENTRY_LARAVEL_DSN=your-sentry-dsn
SENTRY_TRACES_SAMPLE_RATE=0.1
```

### Log Management

**Log Rotation:**
Create `/etc/logrotate.d/togetherpay`:
```
/var/www/togetherpay/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
    sharedscripts
}
```

## Security Checklist

- [ ] All environment variables configured with strong values
- [ ] APP_DEBUG=false in production
- [ ] APP_KEY generated and unique
- [ ] Database passwords are strong (20+ characters)
- [ ] Redis password configured
- [ ] File permissions set correctly (755 for directories, 644 for files)
- [ ] SSL certificate installed and auto-renewal configured
- [ ] Security headers configured in Nginx
- [ ] Rate limiting enabled
- [ ] CSRF protection enabled for web routes
- [ ] SQL injection prevention (using Eloquent ORM)
- [ ] XSS protection (using Blade templating)
- [ ] API authentication via Sanctum
- [ ] Sensitive data encrypted in database
- [ ] Error pages don't reveal sensitive information
- [ ] Logs don't contain sensitive data

## Performance Optimization

### OPcache Configuration

Edit `/etc/php/8.2/fpm/conf.d/10-opcache.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
opcache.enable_cli=1
```

Restart PHP-FPM:
```bash
sudo systemctl restart php8.2-fpm
```

### Database Query Optimization

```bash
# Enable query logging temporarily to identify slow queries
php artisan telescope:install  # Optional monitoring tool
```

### Redis Optimization

Configure Redis for production in `/etc/redis/redis.conf`:
```conf
maxmemory 256mb
maxmemory-policy allkeys-lru
save ""  # Disable RDB persistence if using AOF
appendonly yes
appendfilename "appendonly.aof"
```

## Scaling Considerations

### Horizontal Scaling

**Load Balancer Configuration:**
- Use AWS ALB or similar
- Enable sticky sessions for web routes
- Health check endpoint: `/up`

**Database Read Replicas:**
```env
DB_READ_HOST=your-postgres-read-replica.example.com
```

**Redis Cluster:**
- Configure Redis Cluster for high availability
- Use Redis Sentinel for automatic failover

### Vertical Scaling

**Server Resources:**
- Monitor CPU, memory, disk usage
- Scale up when consistently above 70% utilization

## Deployment Checklist

- [ ] Environment variables configured
- [ ] Dependencies installed
- [ ] Database migrations run
- [ ] Configuration cached
- [ ] File permissions set
- [ ] SSL certificate installed
- [ ] Queue workers running
- [ ] Scheduler configured
- [ ] Monitoring configured
- [ ] Backups configured
- [ ] Health checks passing
- [ ] API documentation deployed
- [ ] Mobile apps point to production API

## Zero-Downtime Deployment

**Using Laravel Envoy or Deployer:**

```bash
# Install Deployer
composer require --dev deployer/deployer

# Configure deployment
php artisan vendor:publish --tag=deployer-config
```

**Deployment Script:**
```php
// deploy.php
namespace Deployer;

require 'recipe/laravel.php';

set('application', 'togetherpay');
set('repository', 'git@github.com:Abdallah-Tah/togetherpay.git');
set('keep_releases', 5);

host('production')
    ->set('remote_user', 'www-data')
    ->set('deploy_path', '/var/www/togetherpay');

// Tasks
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'artisan:migrate',
    'artisan:config:cache',
    'artisan:route:cache',
    'artisan:view:cache',
    'deploy:publish',
]);

after('deploy:failed', 'deploy:unlock');
```

Deploy:
```bash
./vendor/bin/dep deploy production
```

## Rollback Procedure

**Quick Rollback:**
```bash
# Using Deployer
./vendor/bin/dep rollback production

# Manual rollback
cd /var/www/togetherpay
git reset --hard HEAD~1
composer install
php artisan migrate:rollback
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl reload php8.2-fpm
```

## Troubleshooting

### Application Not Loading
```bash
# Check Nginx logs
sudo tail -f /var/log/nginx/togetherpay-error.log

# Check PHP-FPM logs
sudo tail -f /var/log/php8.2-fpm.log

# Check Laravel logs
tail -f storage/logs/laravel.log
```

### Queue Workers Not Processing
```bash
# Check supervisor status
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart togetherpay-worker:*

# Check worker logs
tail -f storage/logs/worker.log
```

### Database Connection Issues
```bash
# Test connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check PostgreSQL status
sudo systemctl status postgresql
```

### Redis Connection Issues
```bash
# Test connection
redis-cli -h your-redis-host -a your-password ping

# Check Redis status
sudo systemctl status redis
```

## Support & Maintenance

### Regular Maintenance Tasks

**Daily:**
- Monitor error logs
- Check queue processing
- Verify backups completed

**Weekly:**
- Review performance metrics
- Check disk usage
- Update dependencies (security patches)

**Monthly:**
- Review security logs
- Update SSL certificates if needed
- Database performance tuning
- Review and optimize slow queries

### Emergency Contacts

- DevOps Team: devops@togetherpay.app
- Database Admin: dba@togetherpay.app
- Security Team: security@togetherpay.app

## Additional Resources

- [Laravel Deployment Documentation](https://laravel.com/docs/deployment)
- [PostgreSQL Production Checklist](https://postgresql.org)
- [Redis Best Practices](https://redis.io/docs/manual/admin/)
- [Nginx Security Guide](https://nginx.org/en/docs/http/ngx_http_ssl_module.html)
