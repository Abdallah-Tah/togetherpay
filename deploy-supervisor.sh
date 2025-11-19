#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== TogetherPay Supervisor Deployment ===${NC}"
echo ""

# Check if running as root for supervisor commands
if [ "$EUID" -ne 0 ]; then
    echo -e "${YELLOW}Note: Supervisor commands require sudo. You may be prompted for password.${NC}"
    echo ""
fi

# Step 1: Copy configuration files
echo -e "${GREEN}[1/5] Copying configuration files...${NC}"
sudo cp /home/togetherpay/htdocs/www.togetherpay.app/supervisor-togetherpay-worker.conf /etc/supervisor/conf.d/togetherpay-worker.conf
sudo chmod 644 /etc/supervisor/conf.d/togetherpay-worker.conf

if [ -f "/home/togetherpay/htdocs/www.togetherpay.app/supervisor-togetherpay-scheduler.conf" ]; then
    sudo cp /home/togetherpay/htdocs/www.togetherpay.app/supervisor-togetherpay-scheduler.conf /etc/supervisor/conf.d/togetherpay-scheduler.conf
    sudo chmod 644 /etc/supervisor/conf.d/togetherpay-scheduler.conf
fi

echo -e "${GREEN}✓ Configuration files copied${NC}"
echo ""

# Step 2: Reload supervisor
echo -e "${GREEN}[2/5] Reloading Supervisor configuration...${NC}"
sudo supervisorctl reread
sudo supervisorctl update
echo -e "${GREEN}✓ Supervisor configuration reloaded${NC}"
echo ""

# Step 3: Start/Restart workers
echo -e "${GREEN}[3/5] Starting queue workers...${NC}"
sudo supervisorctl restart togetherpay-worker:* 2>/dev/null || sudo supervisorctl start togetherpay-worker:*
echo -e "${GREEN}✓ Queue workers started${NC}"
echo ""

# Step 4: Check status
echo -e "${GREEN}[4/5] Verifying worker status...${NC}"
sleep 2
STATUS=$(sudo supervisorctl status togetherpay-worker:*)
echo "$STATUS"

if echo "$STATUS" | grep -q "RUNNING"; then
    echo -e "${GREEN}✓ Workers are running successfully${NC}"
else
    echo -e "${RED}⚠ Warning: Some workers may not be running properly${NC}"
    echo -e "${YELLOW}Check logs: sudo tail -f /var/log/supervisor/supervisord.log${NC}"
fi
echo ""

# Step 5: Display monitoring info
echo -e "${GREEN}[5/5] Setup complete!${NC}"
echo ""
echo -e "${YELLOW}Useful Commands:${NC}"
echo "  Monitor workers:     ./monitor-queue.sh"
echo "  Check status:        sudo supervisorctl status togetherpay-worker:*"
echo "  View logs:           sudo supervisorctl tail -f togetherpay-worker:togetherpay-worker_00 stdout"
echo "  Restart workers:     sudo supervisorctl restart togetherpay-worker:*"
echo ""
echo -e "${GREEN}Deployment successful!${NC}"
