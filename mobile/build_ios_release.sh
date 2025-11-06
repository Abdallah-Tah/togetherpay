#!/bin/bash
# Build iOS release for App Store submission

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== TogetherPay iOS Release Build ===${NC}\n"

# Enforce production defines (override if explicitly provided)
API_BASE=${API_BASE:-"https://api.togetherpay.app/api/v1"}
DEBUG=${DEBUG:-"false"}

echo -e "API Base URL: ${GREEN}$API_BASE${NC}"
echo -e "Debug Mode: ${GREEN}$DEBUG${NC}\n"

# Clean previous builds
echo -e "${YELLOW}Cleaning previous builds...${NC}"
flutter clean

# Clean iOS-specific files
cd ios
rm -rf Pods Podfile.lock
echo -e "${YELLOW}Reinstalling CocoaPods...${NC}"
pod install --repo-update
cd ..

# Get Flutter dependencies
echo -e "${YELLOW}Getting Flutter dependencies...${NC}"
flutter pub get

# Build iOS release
echo -e "\n${YELLOW}Building iOS release...${NC}"
flutter build ios --release \
    --dart-define=API_BASE="$API_BASE" \
    --dart-define=DEBUG="$DEBUG"

if [ $? -eq 0 ]; then
    echo -e "\n${GREEN}✓ iOS build completed successfully!${NC}"
    echo -e "\n${GREEN}Next steps:${NC}"
    echo -e "1. Open Xcode: ${YELLOW}open ios/Runner.xcworkspace${NC}"
    echo -e "2. Select 'Any iOS Device (arm64)' as target"
    echo -e "3. Product → Archive"
    echo -e "4. Distribute → App Store Connect"
    echo -e "\n${GREEN}Or run: ${YELLOW}open ios/Runner.xcworkspace${NC}"
else
    echo -e "\n${RED}✗ iOS build failed!${NC}"
    exit 1
fi
