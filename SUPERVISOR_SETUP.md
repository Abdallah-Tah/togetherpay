# Supervisor Queue Worker Setup - Optimized for Performance & Memory

This configuration provides optimal performance while preventing memory leaks and RAM drainage.

## Memory Optimization Features

✅ **256MB memory limit** per worker process
✅ **Auto-restart after 1000 jobs** (prevents memory buildup)
✅ **Auto-restart after 30 minutes** (clears accumulated memory)
✅ **Single worker process** (scales based on load)
✅ **Queue prioritization** (high priority jobs first)
✅ **Graceful shutdown** (completes current job before restart)

## Installation

### 1. Install Supervisor

```bash
sudo apt-get update
sudo apt-get install supervisor -y
sudo systemctl enable supervisor
sudo systemctl start supervisor
```

### 2. Deploy Configuration Files

```bash
# Copy queue worker config
sudo cp /home/togetherpay/htdocs/www.togetherpay.app/supervisor-togetherpay-worker.conf /etc/supervisor/conf.d/togetherpay-worker.conf

# Copy scheduler config (optional, alternative to cron)
sudo cp /home/togetherpay/htdocs/www.togetherpay.app/supervisor-togetherpay-scheduler.conf /etc/supervisor/conf.d/togetherpay-scheduler.conf

# Set correct permissions
sudo chmod 644 /etc/supervisor/conf.d/togetherpay-*.conf
```

### 3. Start Services

```bash
# Reload supervisor configuration
sudo supervisorctl reread
sudo supervisorctl update

# Start queue worker
sudo supervisorctl start togetherpay-worker:*

# Start scheduler (if using supervisor instead of cron)
sudo supervisorctl start togetherpay-scheduler

# Check status
sudo supervisorctl status
```

## Management Commands

### Daily Operations

```bash
# Check worker status
sudo supervisorctl status togetherpay-worker:*

# View real-time logs
sudo supervisorctl tail -f togetherpay-worker:togetherpay-worker_00 stdout

# Monitor memory usage
./monitor-queue.sh

# Restart workers (after deployment)
sudo supervisorctl restart togetherpay-worker:*
```

### Troubleshooting

```bash
# Stop workers
sudo supervisorctl stop togetherpay-worker:*

# Check for errors in supervisor logs
sudo tail -f /var/log/supervisor/supervisord.log

# Check Laravel worker logs
tail -f storage/logs/worker.log

# Check failed jobs
php artisan queue:failed

# Retry all failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

## Scaling Guidelines

### Low Traffic (Current Setup)
- **Workers:** 1 process
- **Memory:** ~256MB
- **Best for:** Up to 1000 jobs/hour

### Medium Traffic
Edit `numprocs=2` in config file:
```bash
sudo nano /etc/supervisor/conf.d/togetherpay-worker.conf
# Change: numprocs=2
sudo supervisorctl reread && sudo supervisorctl update
```
- **Workers:** 2 processes
- **Memory:** ~512MB
- **Best for:** 1000-5000 jobs/hour

### High Traffic
Edit `numprocs=4` in config file:
```bash
sudo nano /etc/supervisor/conf.d/togetherpay-worker.conf
# Change: numprocs=4
sudo supervisorctl reread && sudo supervisorctl update
```
- **Workers:** 4 processes
- **Memory:** ~1GB
- **Best for:** 5000+ jobs/hour

## Memory Monitoring

### Set up automated monitoring (cron)

```bash
# Add to crontab
crontab -e

# Add this line to check memory every 30 minutes
*/30 * * * * /home/togetherpay/htdocs/www.togetherpay.app/monitor-queue.sh >> /home/togetherpay/htdocs/www.togetherpay.app/storage/logs/queue-monitor.log 2>&1
```

### Manual memory check

```bash
# Run monitoring script
./monitor-queue.sh

# Or check directly
ps aux | grep "queue:work" | grep -v grep
```

## Performance Tuning Parameters

### Current Optimized Settings

| Parameter | Value | Purpose |
|-----------|-------|---------|
| `memory_limit` | 256M | Prevents single process from using excessive RAM |
| `max-jobs` | 1000 | Restart after processing 1000 jobs (clears memory) |
| `max-time` | 1800 | Restart after 30 minutes (prevents memory leaks) |
| `timeout` | 60 | Kill jobs that run longer than 60 seconds |
| `sleep` | 3 | Wait 3 seconds between checking for jobs (reduces CPU) |
| `tries` | 3 | Retry failed jobs up to 3 times |
| `numprocs` | 1 | Start with single worker (scale as needed) |

### Adjust for Your Needs

**If jobs are timing out:**
```bash
# Increase timeout to 120 seconds
--timeout=120
```

**If you have memory to spare:**
```bash
# Increase memory limit
php -d memory_limit=512M
```

**If processing is slow:**
```bash
# Reduce sleep time for faster polling
--sleep=1

# Or add more workers
numprocs=2
```

**If experiencing memory issues:**
```bash
# Restart more frequently
--max-jobs=500
--max-time=900  # 15 minutes
```

## Deployment Workflow

After every code deployment:

```bash
# 1. Pull latest code
cd /home/togetherpay/htdocs/www.togetherpay.app
git pull

# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Run migrations
php artisan migrate --force

# 4. Clear and rebuild cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. IMPORTANT: Restart queue workers
sudo supervisorctl restart togetherpay-worker:*

# 6. Verify workers restarted
sudo supervisorctl status togetherpay-worker:*
```

## Alternative: Cron-based Scheduler (Recommended)

Instead of using the supervisor scheduler, you can use traditional cron:

```bash
# Edit crontab for the togetherpay user
crontab -e

# Add this single line:
* * * * * cd /home/togetherpay/htdocs/www.togetherpay.app && php artisan schedule:run >> /dev/null 2>&1
```

If using cron, don't install the scheduler supervisor config.

## Health Check Endpoint

Consider adding a health check to monitor queue workers:

```php
// routes/web.php or api.php
Route::get('/health/queue', function () {
    $queueSize = \Illuminate\Support\Facades\Redis::llen('queues:default');
    $failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();

    return response()->json([
        'status' => 'healthy',
        'queue_size' => $queueSize,
        'failed_jobs' => $failedJobs,
        'timestamp' => now(),
    ]);
});
```

## Logs Location

- **Worker output:** `/home/togetherpay/htdocs/www.togetherpay.app/storage/logs/worker.log`
- **Scheduler output:** `/home/togetherpay/htdocs/www.togetherpay.app/storage/logs/scheduler.log`
- **Supervisor logs:** `/var/log/supervisor/supervisord.log`
- **Laravel logs:** `/home/togetherpay/htdocs/www.togetherpay.app/storage/logs/laravel.log`

## Security Notes

- Worker runs as `togetherpay` user (not root)
- Memory limits prevent resource exhaustion
- Auto-restart prevents zombie processes
- Logs are rotated to prevent disk space issues

## Support

For issues:
1. Check logs first: `./monitor-queue.sh`
2. Verify supervisor status: `sudo supervisorctl status`
3. Check failed jobs: `php artisan queue:failed`
4. Review Laravel logs: `tail -f storage/logs/laravel.log`
