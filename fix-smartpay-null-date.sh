#!/bin/bash

# Fix SmartPay null due_at error
# Adds null check for due_at field

set -e

cd /home/togetherpay/htdocs/www.togetherpay.app || exit 1

echo "🔧 Fixing SmartPay null due_at error..."
echo ""

echo "📝 The problem:"
echo "- Payment requests can have null due_at field"
echo "- SmartPay page tried to call ->format() on null"
echo "- Solution: Add @if check before displaying due date"
echo ""

echo "📝 Pulling latest changes from Git..."
git pull origin main
echo "✅ Code updated"
echo ""

echo "📝 Clearing view cache..."
php artisan view:clear
echo "✅ View cache cleared"
echo ""

echo "✅ SmartPay fix deployed!"
echo ""
echo "📱 The payment page should now work properly"
echo ""
