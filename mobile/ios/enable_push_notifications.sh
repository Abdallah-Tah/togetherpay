#!/bin/bash

# Enable Push Notifications capability for iOS
echo "🔧 Enabling Push Notifications capability..."

# Open Xcode workspace
open Runner.xcworkspace

echo ""
echo "✅ Xcode workspace opened!"
echo ""
echo "📋 Next steps in Xcode:"
echo "   1. Click 'Runner' (blue icon) in left sidebar"
echo "   2. Select 'Runner' target"
echo "   3. Go to 'Signing & Capabilities' tab"
echo "   4. Click '+ Capability'"
echo "   5. Add 'Push Notifications'"
echo ""
echo "⚠️  Make sure you see:"
echo "   - ✅ Push Notifications capability"
echo "   - ✅ Background Modes with 'Remote notifications' checked"
echo ""
