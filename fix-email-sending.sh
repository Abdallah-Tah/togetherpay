#!/bin/bash

# Fix Email Sending Issue
# Removes Eloquent model serialization issues in queued emails

set -e

cd /home/togetherpay/htdocs/www.togetherpay.app || exit 1

echo "🔧 Fixing email sending issue..."
echo ""

echo "📝 The problem:"
echo "- GroupInvitationMail was storing the full \$invitation Eloquent model"
echo "- When queued, Laravel serializes the mailable"
echo "- Serialized Eloquent models with relationships can cause auth context issues"
echo "- Solution: Extract only the data we need, don't store the full model"
echo ""

echo "📝 Pulling latest changes from Git..."
git pull origin main
echo "✅ Code updated"
echo ""

echo "📝 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
echo "✅ Caches cleared"
echo ""

echo "📝 Restarting queue workers..."
php artisan queue:restart
echo "✅ Queue workers restarted"
echo ""

echo "✅ Email sending issue fixed!"
echo ""
echo "📱 Next step: Test by sending a group invitation from the mobile app"
echo ""
