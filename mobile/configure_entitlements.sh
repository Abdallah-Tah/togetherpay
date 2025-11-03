#!/bin/bash
# Configure iOS entitlements in Xcode project

set -e

echo "Configuring iOS entitlements..."

PROJECT_FILE="ios/Runner.xcodeproj/project.pbxproj"

# Check if entitlements files exist
if [ ! -f "ios/Runner/Runner.entitlements" ]; then
    echo "Error: ios/Runner/Runner.entitlements not found"
    exit 1
fi

if [ ! -f "ios/Runner/Runner-Release.entitlements" ]; then
    echo "Error: ios/Runner/Runner-Release.entitlements not found"
    exit 1
fi

# Check if CODE_SIGN_ENTITLEMENTS is already configured
if grep -q "CODE_SIGN_ENTITLEMENTS" "$PROJECT_FILE"; then
    echo "✓ Entitlements already configured in Xcode project"
else
    echo "⚠ Entitlements not yet configured in Xcode project"
    echo ""
    echo "Manual steps required:"
    echo "1. Open: open ios/Runner.xcworkspace"
    echo "2. Select 'Runner' project in navigator"
    echo "3. Select 'Runner' target"
    echo "4. Go to 'Build Settings' tab"
    echo "5. Search for 'Code Signing Entitlements'"
    echo "6. For Debug configuration, set: Runner/Runner.entitlements"
    echo "7. For Release configuration, set: Runner/Runner-Release.entitlements"
    echo ""
    echo "Or add manually to project.pbxproj:"
    echo "  CODE_SIGN_ENTITLEMENTS = Runner/Runner.entitlements; (Debug)"
    echo "  CODE_SIGN_ENTITLEMENTS = Runner/Runner-Release.entitlements; (Release)"
fi

echo ""
echo "✓ Entitlements files exist:"
echo "  - ios/Runner/Runner.entitlements (development)"
echo "  - ios/Runner/Runner-Release.entitlements (production)"
