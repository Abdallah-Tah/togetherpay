#!/bin/bash

# TogetherPay Screenshot Capture Script
# Captures all required screenshots for App Store and Play Store submission

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
SCREENSHOTS_DIR="$PROJECT_DIR/screenshots"

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  TogetherPay Screenshot Capture Tool${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════${NC}\n"

# Create screenshots directory structure
mkdir -p "$SCREENSHOTS_DIR"/{ios,android}/{6.7-inch,6.5-inch,5.5-inch,phone}

# iOS Simulators needed
IOS_SIMULATORS=(
    "iPhone 15 Pro Max:6.7-inch:1290x2796"
    "iPhone 14 Plus:6.5-inch:1242x2688"
    "iPhone 8 Plus:5.5-inch:1242x2208"
)

# Android Emulator
ANDROID_EMULATOR="Pixel_9_Pro"

echo -e "${YELLOW}📱 Required Screens to Capture:${NC}"
echo "1. Onboarding/Welcome screen"
echo "2. Dashboard with groups"
echo "3. Group details with members"
echo "4. Add member screen (showing Venmo/Cash App)"
echo "5. Payment requests list"
echo "6. Payment request detail"
echo ""

# Function to capture iOS screenshots
capture_ios_screenshots() {
    local simulator_name=$1
    local size=$2
    local resolution=$3

    echo -e "\n${GREEN}━━━ Capturing iOS Screenshots: $simulator_name ($size) ━━━${NC}"

    # Boot simulator
    echo "Starting $simulator_name..."
    xcrun simctl boot "$simulator_name" 2>/dev/null || echo "Simulator already running"
    sleep 3

    # Launch app
    echo "Launching app on $simulator_name..."
    flutter run -d "$(xcrun simctl list devices | grep "$simulator_name" | grep -o '[A-Z0-9-]\{36\}' | head -1)" &
    FLUTTER_PID=$!

    echo ""
    echo -e "${YELLOW}═══════════════════════════════════════════════════${NC}"
    echo -e "${YELLOW}  MANUAL SCREENSHOT INSTRUCTIONS${NC}"
    echo -e "${YELLOW}═══════════════════════════════════════════════════${NC}"
    echo ""
    echo "The app is now running on $simulator_name"
    echo ""
    echo "Please navigate through the app and capture screenshots using:"
    echo "  ⌘ + S (or File > New Screen Shot)"
    echo ""
    echo "Required screens:"
    echo "  1. Onboarding/Welcome screen"
    echo "  2. Dashboard with groups"
    echo "  3. Group details with members"
    echo "  4. Add member screen"
    echo "  5. Payment requests list"
    echo "  6. Payment request detail"
    echo ""
    echo "Screenshots will be saved to your Desktop"
    echo "Move them to: $SCREENSHOTS_DIR/ios/$size/"
    echo ""
    echo "Press ENTER when you've captured all 6 screenshots..."
    read

    # Kill flutter process
    kill $FLUTTER_PID 2>/dev/null || true

    # Shutdown simulator
    xcrun simctl shutdown "$simulator_name" 2>/dev/null || true

    echo -e "${GREEN}✓ Completed $simulator_name${NC}"
}

# Function to capture Android screenshots
capture_android_screenshots() {
    echo -e "\n${GREEN}━━━ Capturing Android Screenshots ━━━${NC}"

    # Launch emulator
    echo "Starting $ANDROID_EMULATOR emulator..."
    flutter emulators --launch $ANDROID_EMULATOR &
    sleep 10

    # Launch app
    echo "Launching app on $ANDROID_EMULATOR..."
    flutter run -d $ANDROID_EMULATOR &
    FLUTTER_PID=$!

    echo ""
    echo -e "${YELLOW}═══════════════════════════════════════════════════${NC}"
    echo -e "${YELLOW}  MANUAL SCREENSHOT INSTRUCTIONS - ANDROID${NC}"
    echo -e "${YELLOW}═══════════════════════════════════════════════════${NC}"
    echo ""
    echo "The app is now running on $ANDROID_EMULATOR"
    echo ""
    echo "Please navigate through the app and capture screenshots using:"
    echo "  Camera button in emulator toolbar (or ⌘ + S)"
    echo ""
    echo "Required screens (same 6 as iOS):"
    echo "  1. Onboarding/Welcome"
    echo "  2. Dashboard with groups"
    echo "  3. Group details"
    echo "  4. Add member"
    echo "  5. Payment requests list"
    echo "  6. Payment request detail"
    echo ""
    echo "Move screenshots to: $SCREENSHOTS_DIR/android/phone/"
    echo ""
    echo "Press ENTER when you've captured all 6 screenshots..."
    read

    # Kill flutter process
    kill $FLUTTER_PID 2>/dev/null || true

    echo -e "${GREEN}✓ Completed Android screenshots${NC}"
}

# Main menu
echo -e "${BLUE}Select platform to capture:${NC}"
echo "1. iOS only"
echo "2. Android only"
echo "3. Both platforms"
echo ""
read -p "Enter choice (1-3): " choice

case $choice in
    1)
        for simulator_config in "${IOS_SIMULATORS[@]}"; do
            IFS=':' read -r name size resolution <<< "$simulator_config"
            capture_ios_screenshots "$name" "$size" "$resolution"
        done
        ;;
    2)
        capture_android_screenshots
        ;;
    3)
        for simulator_config in "${IOS_SIMULATORS[@]}"; do
            IFS=':' read -r name size resolution <<< "$simulator_config"
            capture_ios_screenshots "$name" "$size" "$resolution"
        done
        capture_android_screenshots
        ;;
    *)
        echo "Invalid choice"
        exit 1
        ;;
esac

echo ""
echo -e "${GREEN}═══════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  Screenshot Capture Complete!${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════${NC}"
echo ""
echo "Screenshots saved to: $SCREENSHOTS_DIR"
echo ""
echo "Next steps:"
echo "1. Review all screenshots for quality"
echo "2. Rename files appropriately (1.png, 2.png, etc.)"
echo "3. Upload to App Store Connect and Play Console"
echo ""
