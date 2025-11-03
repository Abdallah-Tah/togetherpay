#!/bin/bash
# Verify production configuration setup for TogetherPay mobile

# Note: Don't use 'set -e' because check functions return non-zero on warnings

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== TogetherPay Mobile - Production Configuration Verification ===${NC}\n"

# Track overall status
ERRORS=0
WARNINGS=0

# Function to check file exists
check_file() {
    local file=$1
    local description=$2
    local required=$3
    
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓${NC} $description: ${GREEN}$file${NC}"
        return 0
    else
        if [ "$required" = "true" ]; then
            echo -e "${RED}✗${NC} $description: ${RED}$file (MISSING - REQUIRED)${NC}"
            ERRORS=$((ERRORS + 1))
            return 1
        else
            echo -e "${YELLOW}⚠${NC} $description: ${YELLOW}$file (missing - optional)${NC}"
            WARNINGS=$((WARNINGS + 1))
            return 1
        fi
    fi
}

# Function to check directory exists
check_dir() {
    local dir=$1
    local description=$2
    
    if [ -d "$dir" ]; then
        echo -e "${GREEN}✓${NC} $description: ${GREEN}$dir${NC}"
        return 0
    else
        echo -e "${RED}✗${NC} $description: ${RED}$dir (MISSING)${NC}"
        ERRORS=$((ERRORS + 1))
        return 1
    fi
}

echo -e "${BLUE}## iOS Configuration${NC}"
check_file "ios/Runner/Info.plist" "Info.plist" "true"
check_file "ios/Runner/GoogleService-Info.plist" "Firebase config (iOS)" "true"
check_file "ios/Runner/Runner.entitlements" "Development entitlements" "true"
check_file "ios/Runner/Runner-Release.entitlements" "Production entitlements" "true"
check_dir "ios/Runner.xcworkspace" "Xcode workspace" "true"

# Check Info.plist for required keys
if [ -f "ios/Runner/Info.plist" ]; then
    if grep -q "LSApplicationQueriesSchemes" "ios/Runner/Info.plist"; then
        echo -e "${GREEN}✓${NC} Info.plist contains LSApplicationQueriesSchemes"
    else
        echo -e "${RED}✗${NC} Info.plist missing LSApplicationQueriesSchemes"
        ERRORS=$((ERRORS + 1))
    fi
    
    if grep -q "venmo" "ios/Runner/Info.plist"; then
        echo -e "${GREEN}✓${NC} Info.plist includes 'venmo' scheme"
    else
        echo -e "${RED}✗${NC} Info.plist missing 'venmo' scheme"
        ERRORS=$((ERRORS + 1))
    fi
    
    if grep -q "UIBackgroundModes" "ios/Runner/Info.plist"; then
        echo -e "${GREEN}✓${NC} Info.plist contains UIBackgroundModes"
    else
        echo -e "${YELLOW}⚠${NC} Info.plist missing UIBackgroundModes (may affect push notifications)"
        WARNINGS=$((WARNINGS + 1))
    fi
fi

echo ""
echo -e "${BLUE}## Android Configuration${NC}"
check_file "android/app/google-services.json" "Firebase config (Android)" "true"
check_file "android/app/build.gradle.kts" "App build.gradle.kts" "true"
check_file "android/build.gradle.kts" "Project build.gradle.kts" "true"
check_file "android/app/src/main/AndroidManifest.xml" "AndroidManifest.xml" "true"
check_file "android/app/proguard-rules.pro" "ProGuard rules" "true"
check_file "android/key.properties.example" "Keystore config example" "true"
check_file "android/key.properties" "Keystore config (ACTUAL)" "false"

if [ ! -f "android/key.properties" ]; then
    echo -e "  ${YELLOW}→ Create from example: cp android/key.properties.example android/key.properties${NC}"
fi

# Check for keystore file
if [ -f "android/key.properties" ]; then
    KEYSTORE_FILE=$(grep "storeFile=" android/key.properties | cut -d'=' -f2)
    if [ -f "android/app/$KEYSTORE_FILE" ] || [ -f "$KEYSTORE_FILE" ]; then
        echo -e "${GREEN}✓${NC} Keystore file found"
    else
        echo -e "${YELLOW}⚠${NC} Keystore file not found: $KEYSTORE_FILE"
        echo -e "  ${YELLOW}→ Generate with: keytool -genkey -v -keystore android/app/upload-keystore.jks -keyalg RSA -keysize 2048 -validity 10000 -alias upload${NC}"
        WARNINGS=$((WARNINGS + 1))
    fi
fi

# Check build.gradle.kts for signing config
if [ -f "android/app/build.gradle.kts" ]; then
    if grep -q "signingConfigs" "android/app/build.gradle.kts"; then
        echo -e "${GREEN}✓${NC} Signing configuration present in build.gradle.kts"
    else
        echo -e "${RED}✗${NC} Signing configuration missing in build.gradle.kts"
        ERRORS=$((ERRORS + 1))
    fi
fi

# Check AndroidManifest.xml
if [ -f "android/app/src/main/AndroidManifest.xml" ]; then
    if grep -q "android.permission.INTERNET" "android/app/src/main/AndroidManifest.xml"; then
        echo -e "${GREEN}✓${NC} INTERNET permission in AndroidManifest.xml"
    else
        echo -e "${RED}✗${NC} INTERNET permission missing"
        ERRORS=$((ERRORS + 1))
    fi
    
    if grep -q "android.permission.POST_NOTIFICATIONS" "android/app/src/main/AndroidManifest.xml"; then
        echo -e "${GREEN}✓${NC} POST_NOTIFICATIONS permission in AndroidManifest.xml"
    else
        echo -e "${YELLOW}⚠${NC} POST_NOTIFICATIONS permission missing (required for Android 13+)"
        WARNINGS=$((WARNINGS + 1))
    fi
fi

echo ""
echo -e "${BLUE}## Flutter Dependencies${NC}"
check_file "pubspec.yaml" "pubspec.yaml" "true"
check_file "pubspec.lock" "pubspec.lock" "true"

if [ -f "pubspec.yaml" ]; then
    echo -e "${GREEN}✓${NC} Key dependencies check:"
    
    for dep in "firebase_core" "firebase_messaging" "url_launcher" "dio" "flutter_secure_storage"; do
        if grep -q "$dep:" "pubspec.yaml"; then
            echo -e "  ${GREEN}✓${NC} $dep"
        else
            echo -e "  ${RED}✗${NC} $dep (MISSING)"
            ERRORS=$((ERRORS + 1))
        fi
    done
fi

echo ""
echo -e "${BLUE}## Documentation${NC}"
check_file "README.md" "Main README" "true"
check_file "PRODUCTION_SETUP.md" "Production setup guide" "true"
check_file "BUILD_DEPLOY.md" "Build & deploy guide" "true"
check_file "TESTING_CHECKLIST.md" "Testing checklist" "true"

echo ""
echo -e "${BLUE}## Build Scripts${NC}"
check_file "build_ios_release.sh" "iOS build script" "true"
check_file "build_android_release.sh" "Android build script" "true"
check_file "configure_entitlements.sh" "Entitlements config script" "false"

# Check if scripts are executable
if [ -f "build_ios_release.sh" ]; then
    if [ -x "build_ios_release.sh" ]; then
        echo -e "${GREEN}✓${NC} build_ios_release.sh is executable"
    else
        echo -e "${YELLOW}⚠${NC} build_ios_release.sh not executable (run: chmod +x build_ios_release.sh)"
        WARNINGS=$((WARNINGS + 1))
    fi
fi

if [ -f "build_android_release.sh" ]; then
    if [ -x "build_android_release.sh" ]; then
        echo -e "${GREEN}✓${NC} build_android_release.sh is executable"
    else
        echo -e "${YELLOW}⚠${NC} build_android_release.sh not executable (run: chmod +x build_android_release.sh)"
        WARNINGS=$((WARNINGS + 1))
    fi
fi

echo ""
echo -e "${BLUE}## Source Code Structure${NC}"
check_dir "lib" "lib directory" "true"
check_file "lib/main.dart" "Main app file" "true"
check_file "lib/env.dart" "Environment config" "false"
check_dir "lib/screens" "Screens directory" "false"
check_dir "lib/services" "Services directory" "false"
check_dir "lib/widgets" "Widgets directory" "false"

echo ""
echo -e "${BLUE}## Git Configuration${NC}"
check_file ".gitignore" ".gitignore" "true"

if [ -f ".gitignore" ]; then
    if grep -q "key.properties" ".gitignore" || grep -q "key.properties" "android/.gitignore"; then
        echo -e "${GREEN}✓${NC} key.properties in .gitignore"
    else
        echo -e "${RED}✗${NC} key.properties NOT in .gitignore (SECURITY RISK!)"
        ERRORS=$((ERRORS + 1))
    fi
    
    if grep -q "*.keystore\|*.jks" ".gitignore" || grep -q "*.keystore\|*.jks" "android/.gitignore"; then
        echo -e "${GREEN}✓${NC} Keystore files in .gitignore"
    else
        echo -e "${RED}✗${NC} Keystore files NOT in .gitignore (SECURITY RISK!)"
        ERRORS=$((ERRORS + 1))
    fi
    
    if grep -q "GoogleService-Info.plist" ".gitignore" || grep -q "GoogleService-Info.plist" "ios/.gitignore"; then
        echo -e "${YELLOW}⚠${NC} GoogleService-Info.plist in .gitignore (may be intentional)"
        # Not counting as warning since it's in the repo
    fi
fi

echo ""
echo -e "${BLUE}## Summary${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}✓ All checks passed! Configuration looks good.${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Review PRODUCTION_SETUP.md for external setup (App Store Connect, Play Console)"
    echo "2. Generate Android keystore if not done: android/key.properties"
    echo "3. Configure entitlements in Xcode: ./configure_entitlements.sh"
    echo "4. Test on physical devices using TESTING_CHECKLIST.md"
    echo "5. Build for production:"
    echo "   - iOS: ./build_ios_release.sh"
    echo "   - Android: ./build_android_release.sh"
elif [ $ERRORS -eq 0 ]; then
    echo -e "${YELLOW}⚠ Configuration complete with $WARNINGS warning(s).${NC}"
    echo ""
    echo "Review warnings above and address as needed."
    echo "See PRODUCTION_SETUP.md for detailed instructions."
else
    echo -e "${RED}✗ Configuration incomplete: $ERRORS error(s), $WARNINGS warning(s).${NC}"
    echo ""
    echo "Fix errors above before proceeding with production build."
    echo "See PRODUCTION_SETUP.md for detailed instructions."
    exit 1
fi

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
