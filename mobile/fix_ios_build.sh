#!/bin/bash

echo "🔧 Fixing iOS Build Issues..."
echo "=============================="
echo ""

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Step 1: Clean Flutter
echo -e "${BLUE}Step 1: Cleaning Flutter build...${NC}"
flutter clean
echo -e "${GREEN}✅ Flutter cleaned${NC}"
echo ""

# Step 2: Clean iOS build
echo -e "${BLUE}Step 2: Cleaning iOS build folders...${NC}"
cd ios
rm -rf build/
rm -rf Pods/
rm -rf .symlinks/
rm -rf Flutter/Flutter.framework
rm -rf Flutter/Flutter.podspec
rm -rf Podfile.lock
echo -e "${GREEN}✅ iOS build folders cleaned${NC}"
echo ""

# Step 3: Update CocoaPods
echo -e "${BLUE}Step 3: Updating CocoaPods...${NC}"
pod repo update
echo -e "${GREEN}✅ CocoaPods updated${NC}"
echo ""

# Step 4: Get Flutter dependencies
echo -e "${BLUE}Step 4: Getting Flutter dependencies...${NC}"
cd ..
flutter pub get
echo -e "${GREEN}✅ Flutter dependencies installed${NC}"
echo ""

# Step 5: Install Pods
echo -e "${BLUE}Step 5: Installing iOS Pods...${NC}"
cd ios
pod install --repo-update
cd ..
echo -e "${GREEN}✅ Pods installed${NC}"
echo ""

# Step 6: Clean Xcode derived data
echo -e "${BLUE}Step 6: Cleaning Xcode derived data...${NC}"
rm -rf ~/Library/Developer/Xcode/DerivedData/*
echo -e "${GREEN}✅ Xcode derived data cleaned${NC}"
echo ""

echo -e "${GREEN}🎉 Cleanup complete!${NC}"
echo ""
echo -e "${YELLOW}Now try running:${NC}"
echo "flutter run -d 00008140-0002208C1E11801C --dart-define=API_BASE=https://georgiann-semiacademic-never.ngrok-free.dev/api/v1"
echo ""
