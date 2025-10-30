#!/bin/bash

# Quick run script for TogetherPay mobile app with ngrok API

echo "🚀 Running TogetherPay with ngrok API..."
echo ""

API_URL="https://georgiann-semiacademic-never.ngrok-free.dev/api/v1"

echo "📍 API Base URL: $API_URL"
echo ""

# Check if Flutter is installed
if ! command -v flutter &> /dev/null; then
    echo "❌ Flutter is not installed or not in PATH"
    exit 1
fi

echo "✅ Flutter found: $(flutter --version | head -n 1)"
echo ""

# Ask user what to do
echo "What would you like to do?"
echo ""
echo "1) Run on simulator/emulator (default)"
echo "2) Run on connected device"
echo "3) List available devices"
echo "4) Test API with curl"
echo "5) Clean build and run"
echo ""
read -p "Enter choice [1-5]: " choice

case $choice in
    1|"")
        echo ""
        echo "🏃 Running on default device..."
        flutter run --dart-define=API_BASE=$API_URL
        ;;
    2)
        echo ""
        flutter devices
        echo ""
        read -p "Enter device ID: " device_id
        echo ""
        echo "🏃 Running on device: $device_id"
        flutter run -d "$device_id" --dart-define=API_BASE=$API_URL
        ;;
    3)
        echo ""
        echo "📱 Available devices:"
        flutter devices
        ;;
    4)
        echo ""
        echo "🧪 Testing API endpoints..."
        echo ""

        echo "Testing login endpoint..."
        curl -s -X POST "$API_URL/auth/login" \
          -H "Content-Type: application/json" \
          -H "Accept: application/json" \
          -H "ngrok-skip-browser-warning: true" \
          -d '{"email":"test@example.com","password":"password"}' \
          -w "\nHTTP Status: %{http_code}\n\n"

        echo ""
        echo "Testing payment request endpoint (should NOT require auth)..."
        curl -s "$API_URL/requests/by-code/PR_TEST" \
          -H "Accept: application/json" \
          -H "ngrok-skip-browser-warning: true" \
          -w "\nHTTP Status: %{http_code}\n\n"

        echo ""
        echo "If you see 401 on the payment request test, read LARAVEL_FIX_REQUIRED.md"
        ;;
    5)
        echo ""
        echo "🧹 Cleaning build..."
        flutter clean
        echo ""
        echo "📦 Getting dependencies..."
        flutter pub get
        echo ""
        echo "🏃 Running on default device..."
        flutter run --dart-define=API_BASE=$API_URL
        ;;
    *)
        echo "Invalid choice"
        exit 1
        ;;
esac

