#!/bin/bash

# TogetherPay - Quick iPhone Installation Script
# This script will help you install the app on your iPhone

set -e

echo "📱 TogetherPay - iPhone Installation"
echo "===================================="
echo ""

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if we're in the mobile directory
if [ ! -f "pubspec.yaml" ]; then
    echo -e "${RED}❌ Error: Please run this script from the mobile directory${NC}"
    echo "cd /Users/amohamed/Herd/togetherpay/mobile"
    exit 1
fi

# Check Flutter installation
echo -e "${BLUE}Checking Flutter installation...${NC}"
if ! command -v flutter &> /dev/null; then
    echo -e "${RED}❌ Flutter is not installed${NC}"
    echo "Install from: https://docs.flutter.dev/get-started/install"
    exit 1
fi
echo -e "${GREEN}✅ Flutter is installed${NC}"
echo ""

# Check Xcode installation
echo -e "${BLUE}Checking Xcode installation...${NC}"
if ! command -v xcodebuild &> /dev/null; then
    echo -e "${RED}❌ Xcode is not installed${NC}"
    echo "Install from App Store: https://apps.apple.com/app/xcode/id497799835"
    exit 1
fi
echo -e "${GREEN}✅ Xcode is installed${NC}"
echo ""

# Check for connected iPhone
echo -e "${BLUE}Checking for connected iPhone...${NC}"
if ! flutter devices | grep -q "ios"; then
    echo -e "${YELLOW}⚠️  No iPhone detected${NC}"
    echo ""
    echo "Please:"
    echo "1. Connect your iPhone via USB cable"
    echo "2. Unlock your iPhone"
    echo "3. Trust this computer (tap 'Trust' on iPhone)"
    echo "4. Run this script again"
    exit 1
fi
echo -e "${GREEN}✅ iPhone detected${NC}"
flutter devices | grep "ios"
echo ""

# Ask for API URL
echo -e "${BLUE}API Configuration${NC}"
echo "Current ngrok URL: https://georgiann-semiacademic-never.ngrok-free.dev/api/v1"
echo ""
read -p "Use this URL? (Y/n): " use_default
API_BASE="https://georgiann-semiacademic-never.ngrok-free.dev/api/v1"

if [[ $use_default =~ ^[Nn]$ ]]; then
    read -p "Enter your API URL: " custom_url
    API_BASE=$custom_url
fi

echo ""
echo -e "${GREEN}Using API: $API_BASE${NC}"
echo ""

# Clean and get dependencies
echo -e "${BLUE}Cleaning and getting dependencies...${NC}"
flutter clean
flutter pub get
echo -e "${GREEN}✅ Dependencies ready${NC}"
echo ""

# Check iOS setup
echo -e "${BLUE}Checking iOS setup...${NC}"
cd ios
if [ ! -d "Pods" ]; then
    echo "Installing CocoaPods dependencies..."
    pod install
fi
cd ..
echo -e "${GREEN}✅ iOS setup complete${NC}"
echo ""

# Open Xcode for signing setup
echo -e "${YELLOW}⚠️  IMPORTANT: Configure Signing${NC}"
echo ""
echo "Xcode will open now. Please:"
echo "1. Select your iPhone from the device dropdown"
echo "2. Click 'Runner' in the left sidebar"
echo "3. Go to 'Signing & Capabilities' tab"
echo "4. Check 'Automatically manage signing'"
echo "5. Select your Team (Apple ID)"
echo "6. Change Bundle ID to: com.yourname.togetherpay"
echo ""
read -p "Press Enter to open Xcode..."

open ios/Runner.xcworkspace

echo ""
echo -e "${YELLOW}After configuring signing in Xcode:${NC}"
echo "1. Close Xcode (you can leave it open too)"
echo "2. Press Enter to continue building..."
read -p "Press Enter to continue..."

echo ""
echo -e "${BLUE}Building and installing on iPhone...${NC}"
echo "This may take 2-5 minutes on first build..."
echo ""

# Run on iPhone
flutter run --dart-define=API_BASE="$API_BASE" --dart-define=DEBUG=true

echo ""
echo -e "${GREEN}🎉 Installation complete!${NC}"
echo ""
echo -e "${YELLOW}If you see 'Untrusted Developer' error:${NC}"
echo "1. Go to Settings on iPhone"
echo "2. Tap General → VPN & Device Management"
echo "3. Tap your email/Apple ID"
echo "4. Tap 'Trust'"
echo "5. Open the app again"
echo ""
echo -e "${GREEN}Happy testing! 🚀${NC}"
