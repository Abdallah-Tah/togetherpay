#!/bin/bash

# Quick launcher script for screenshot capture
# This script helps you build, install, and launch the app for screenshots

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${BLUE}╔════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║  TogetherPay Screenshot Device Launcher        ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════╝${NC}"
echo ""

echo -e "${CYAN}Select device to launch:${NC}"
echo ""
echo "iOS Devices (2024+):"
echo "  1. iPhone 16 Pro Max  (6.9\" - 1320x2868) [REQUIRED]"
echo "  2. iPhone 16 Plus     (6.7\" - 1290x2796) [REQUIRED]"
echo ""
echo "Android Devices:"
echo "  3. Pixel 9 Pro        (1080x1920)        [REQUIRED]"
echo ""
echo "  0. Exit"
echo ""

read -p "Enter choice (0-3): " choice

# Function to build and launch iOS
launch_ios() {
    local device_name=$1
    local size=$2

    echo ""
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${GREEN}  Building and launching $device_name${NC}"
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""

    # Boot simulator first
    echo -e "${YELLOW}→ Booting $device_name simulator...${NC}"
    DEVICE_ID=$(xcrun simctl list devices | grep "$device_name" | grep -o '[A-Z0-9-]\{36\}' | head -1)

    if [ -z "$DEVICE_ID" ]; then
        echo -e "${YELLOW}⚠️  $device_name not found. Available devices:${NC}"
        xcrun simctl list devices | grep "iPhone"
        exit 1
    fi

    xcrun simctl boot "$DEVICE_ID" 2>/dev/null || echo "  (Simulator already running)"
    open -a Simulator
    sleep 3

    # Launch app (flutter run handles building and installing automatically)
    echo ""
    echo -e "${YELLOW}→ Building and launching TogetherPay on $device_name...${NC}"
    echo -e "${YELLOW}  (This may take 1-2 minutes on first run)${NC}"
    flutter run -d "$DEVICE_ID" --device-timeout=30 &
    FLUTTER_PID=$!

    sleep 5

    show_screenshot_instructions "$device_name" "$size" "iOS"

    # Wait for user to finish
    echo ""
    read -p "Press ENTER when you've captured all 6 screenshots..."

    # Kill flutter process
    kill $FLUTTER_PID 2>/dev/null || true

    echo ""
    echo -e "${GREEN}✓ Done with $device_name${NC}"
}

# Function to build and launch Android
launch_android() {
    echo ""
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${GREEN}  Building and launching Pixel 9 Pro${NC}"
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""

    # Check if emulator is available
    echo -e "${YELLOW}→ Checking for Pixel 9 Pro emulator...${NC}"
    EMULATOR_NAME=$(flutter emulators | grep -i "pixel.*9.*pro" | head -1 | awk '{print $1}' || echo "Pixel_9_Pro")

    if [ -z "$EMULATOR_NAME" ]; then
        echo -e "${YELLOW}⚠️  Pixel 9 Pro emulator not found. Available emulators:${NC}"
        flutter emulators
        echo ""
        echo -e "${YELLOW}Please create a Pixel 9 Pro emulator first or update the script.${NC}"
        exit 1
    fi

    # Launch emulator
    echo -e "${YELLOW}→ Starting $EMULATOR_NAME emulator...${NC}"
    echo -e "${YELLOW}  (This may take 30-60 seconds to boot)${NC}"
    flutter emulators --launch "$EMULATOR_NAME" > /dev/null 2>&1 &

    # Wait for device to be ready
    echo -e "${YELLOW}→ Waiting for emulator to boot...${NC}"
    timeout=60
    elapsed=0
    while [ $elapsed -lt $timeout ]; do
        if adb devices | grep -q "emulator.*device$"; then
            echo -e "${GREEN}  ✓ Emulator ready!${NC}"
            sleep 3  # Extra wait for full boot
            break
        fi
        sleep 2
        elapsed=$((elapsed + 2))
        echo -n "."
    done
    echo ""

    if [ $elapsed -ge $timeout ]; then
        echo -e "${YELLOW}⚠️  Emulator boot timeout. Continuing anyway...${NC}"
    fi

    # Get actual device ID
    DEVICE_ID=$(flutter devices | grep "android" | head -1 | awk '{print $1}' | sed 's/[•()]//g')

    if [ -z "$DEVICE_ID" ]; then
        echo -e "${YELLOW}⚠️  No Android device found. Available devices:${NC}"
        flutter devices
        exit 1
    fi

    # Launch app (flutter run handles building and installing automatically)
    echo ""
    echo -e "${YELLOW}→ Building and launching TogetherPay on emulator...${NC}"
    echo -e "${YELLOW}  Device ID: $DEVICE_ID${NC}"
    echo -e "${YELLOW}  (This may take 1-2 minutes on first run)${NC}"
    flutter run -d "$DEVICE_ID" &
    FLUTTER_PID=$!

    sleep 8

    show_screenshot_instructions "Pixel 9 Pro" "1080x1920" "Android"

    # Wait for user to finish
    echo ""
    read -p "Press ENTER when you've captured all 6 screenshots..."

    # Kill flutter process
    kill $FLUTTER_PID 2>/dev/null || true

    echo ""
    echo -e "${GREEN}✓ Done with Pixel 9 Pro${NC}"
}

# Function to show screenshot instructions
show_screenshot_instructions() {
    local device=$1
    local size=$2
    local platform=$3

    echo ""
    echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  📸 SCREENSHOT CAPTURE INSTRUCTIONS${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
    echo ""
    echo -e "${CYAN}Device:${NC} $device ($size)"
    echo ""

    if [ "$platform" = "iOS" ]; then
        echo -e "${YELLOW}How to capture:${NC} Press ${GREEN}⌘ + S${NC} (Command + S)"
        echo -e "${YELLOW}Save location:${NC} Screenshots will be saved to your Desktop"
        echo -e "${YELLOW}Move them to:${NC} screenshots/ios/${size}/"
    else
        echo -e "${YELLOW}How to capture:${NC} Click the ${GREEN}camera icon 📷${NC} in emulator toolbar"
        echo -e "${YELLOW}Or press:${NC} ${GREEN}⌘ + S${NC}"
        echo -e "${YELLOW}Save location:${NC} Screenshots will be saved to your Desktop"
        echo -e "${YELLOW}Move them to:${NC} screenshots/android/phone/"
    fi

    echo ""
    echo -e "${CYAN}Required 6 screens (in order):${NC}"
    echo ""
    echo "  1. ${GREEN}Onboarding/Welcome${NC} - First screen when app opens (logged out)"
    echo "  2. ${GREEN}Dashboard${NC} - Home screen with 3-4 groups visible"
    echo "  3. ${GREEN}Group Details${NC} - Inside a group showing members list"
    echo "  4. ${GREEN}Add Member${NC} - ⚠️  MUST show Venmo/Cash App options!"
    echo "  5. ${GREEN}Payment Requests${NC} - List of payment requests"
    echo "  6. ${GREEN}Request Detail${NC} - ⚠️  MUST show payment buttons!"
    echo ""
    echo -e "${YELLOW}💡 Tips:${NC}"
    echo "  • Make sure you have demo data (groups, members, requests)"
    echo "  • Use realistic group names (Family Phone Plan, Netflix, etc.)"
    echo "  • Mix of payment statuses (Paid ✓, Pending ⏱)"
    echo "  • Capture each screen clearly with all UI visible"
    echo ""
}

# Execute based on choice
case $choice in
    1)
        launch_ios "iPhone 16 Pro Max" "6.9-inch"
        ;;
    2)
        launch_ios "iPhone 16 Plus" "6.7-inch"
        ;;
    3)
        launch_android
        ;;
    0)
        echo "Exiting..."
        exit 0
        ;;
    *)
        echo "Invalid choice"
        exit 1
        ;;
esac

echo ""
echo -e "${GREEN}═══════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  Screenshot capture complete! 🎉${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════${NC}"
echo ""
echo "Screenshots saved to Desktop"
echo "Move them to the appropriate folder:"
echo "  • iOS: screenshots/ios/[size]/"
echo "  • Android: screenshots/android/phone/"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "  1. Review screenshots for quality"
echo "  2. Rename files: 01-onboarding.png, 02-dashboard.png, etc."
echo "  3. Repeat for other required devices"
echo "  4. Upload to App Store Connect and Play Console"
echo ""
