#!/bin/bash

echo "=== TogetherPay Queue Worker Monitor ==="
echo "Date: $(date)"
echo ""

# Check if supervisor is running
echo "=== Supervisor Status ==="
sudo supervisorctl status togetherpay-worker:*
echo ""

# Check memory usage
echo "=== Memory Usage ==="
ps aux | grep "queue:work" | grep -v grep | awk '{print "PID: " $2 " | CPU: " $3"% | MEM: " $4"% | RSS: " $6/1024 " MB | CMD: " $11 " " $12 " " $13}'
echo ""

# Total memory usage
TOTAL_MEM=$(ps aux | grep "queue:work" | grep -v grep | awk '{sum+=$6} END {print sum/1024}')
echo "Total Memory Used: ${TOTAL_MEM} MB"
echo ""

# Queue statistics
echo "=== Queue Statistics ==="
cd /home/togetherpay/htdocs/www.togetherpay.app
php artisan queue:monitor redis:default,redis:high --max=100 2>/dev/null || echo "Queue monitoring command not available"
echo ""

# Failed jobs count
echo "=== Failed Jobs ==="
php artisan queue:failed 2>/dev/null || echo "No failed jobs"
echo ""

# Recent log entries
echo "=== Recent Worker Logs (Last 10 lines) ==="
tail -n 10 /home/togetherpay/htdocs/www.togetherpay.app/storage/logs/worker.log 2>/dev/null || echo "No logs available"
