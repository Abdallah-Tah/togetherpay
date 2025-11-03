#!/bin/bash
# Build Android release (App Bundle) for Play Store submission

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== TogetherPay Android Release Build ===${NC}\n"

# Check if API_BASE is set
if [ -z "$API_BASE" ]; then
    echo -e "${YELLOW}Warning: API_BASE not set. Using default.${NC}"
    echo -e "${YELLOW}Set with: export API_BASE='https://api.togetherpay.app/api/v1'${NC}\n"
    API_BASE="https://api.togetherpay.app/api/v1"
fi

echo -e "API Base URL: ${GREEN}$API_BASE${NC}\n"

# Check if key.properties exists
if [ ! -f "android/key.properties" ]; then
    echo -e "${RED}✗ Error: android/key.properties not found!${NC}"
    echo -e "${YELLOW}Create it from the example:${NC}"
    echo -e "  cp android/key.properties.example android/key.properties"
    echo -e "  # Then edit with your keystore details"
    echo -e "\n${YELLOW}Or generate a new keystore:${NC}"
    echo -e "  keytool -genkey -v -keystore android/app/upload-keystore.jks -keyalg RSA -keysize 2048 -validity 10000 -alias upload"
    exit 1
fi

# Clean previous builds
echo -e "${YELLOW}Cleaning previous builds...${NC}"
flutter clean

# Clean Android-specific builds
cd android
./gradlew clean
cd ..

# Get Flutter dependencies
echo -e "${YELLOW}Getting Flutter dependencies...${NC}"
flutter pub get

# Build Android App Bundle
echo -e "\n${YELLOW}Building Android App Bundle (AAB)...${NC}"
flutter build appbundle --release --dart-define=API_BASE="$API_BASE"

if [ $? -eq 0 ]; then
    echo -e "\n${GREEN}✓ Android build completed successfully!${NC}"
    echo -e "\n${GREEN}Output file:${NC}"
    echo -e "  ${YELLOW}build/app/outputs/bundle/release/app-release.aab${NC}"
    echo -e "\n${GREEN}File size:${NC}"
    ls -lh build/app/outputs/bundle/release/app-release.aab | awk '{print "  " $5}'
    echo -e "\n${GREEN}Next steps:${NC}"
    echo -e "1. Go to Google Play Console: ${YELLOW}https://play.google.com/console${NC}"
    echo -e "2. Select your app → Release → Production"
    echo -e "3. Create new release → Upload the AAB file"
    echo -e "4. Add release notes and submit for review"
    
    # Verify signing
    echo -e "\n${YELLOW}Verifying signing...${NC}"
    jarsigner -verify -verbose -certs build/app/outputs/bundle/release/app-release.aab | grep "Signed by" && echo -e "${GREEN}✓ AAB is properly signed${NC}" || echo -e "${RED}✗ Warning: Signing verification failed${NC}"
else
    echo -e "\n${RED}✗ Android build failed!${NC}"
    exit 1
fi
