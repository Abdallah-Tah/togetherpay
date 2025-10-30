#!/bin/bash

# TogetherPay API Test Script
# Tests all endpoints that the mobile app uses

echo "🧪 Testing TogetherPay API Endpoints"
echo "===================================="
echo ""

# Configuration
API_BASE="${1:-https://georgiann-semiacademic-never.ngrok-free.dev/api/v1}"
echo "📍 API Base URL: $API_BASE"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test counter
PASS=0
FAIL=0

# Test helper function
test_endpoint() {
    local method=$1
    local endpoint=$2
    local description=$3
    local data=$4
    local token=$5

    echo -n "Testing: $description... "

    local headers=""
    if [ ! -z "$token" ]; then
        headers="-H 'Authorization: Bearer $token'"
    fi

    local cmd="curl -s -w '\n%{http_code}' -X $method '$API_BASE$endpoint' -H 'Content-Type: application/json' -H 'Accept: application/json'"

    if [ ! -z "$data" ]; then
        cmd="$cmd -d '$data'"
    fi

    if [ ! -z "$token" ]; then
        cmd="$cmd -H 'Authorization: Bearer $token'"
    fi

    response=$(eval $cmd)
    status_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | sed '$d')

    if [ "$status_code" -ge 200 ] && [ "$status_code" -lt 300 ]; then
        echo -e "${GREEN}✓ PASS${NC} (HTTP $status_code)"
        ((PASS++))
    else
        echo -e "${RED}✗ FAIL${NC} (HTTP $status_code)"
        echo "   Response: $body"
        ((FAIL++))
    fi
}

echo "1️⃣  Public Endpoints (No Auth Required)"
echo "========================================="

# Test registration
test_endpoint "POST" "/auth/register" "User Registration" '{
  "name": "Test User",
  "email": "test'$(date +%s)'@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}'

echo ""

# Test login
echo "Testing login to get auth token..."
LOGIN_RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password"
  }')

AUTH_TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

if [ ! -z "$AUTH_TOKEN" ]; then
    echo -e "${GREEN}✓ Login successful${NC}"
    echo "   Token: ${AUTH_TOKEN:0:20}..."
    ((PASS++))
else
    echo -e "${YELLOW}⚠ Login failed (using test token)${NC}"
    AUTH_TOKEN="test_token_123"
    ((FAIL++))
fi

echo ""

# Test device registration
test_endpoint "POST" "/devices/register" "Device Registration" '{
  "fcm_token": "test_fcm_token_123",
  "platform": "ios"
}'

echo ""

# Test payment request by code (IMPORTANT - should work without auth)
test_endpoint "GET" "/requests/by-code/PR_TEST" "Payment Request by Code (Public)"

echo ""

# Test mark payment as paid
test_endpoint "POST" "/requests/by-code/PR_TEST/mark" "Mark Payment as Paid" '{
  "evidence_url": null,
  "evidence_type": null
}'

echo ""
echo "2️⃣  Protected Endpoints (Auth Required)"
echo "========================================="

# Test groups
test_endpoint "GET" "/groups" "Get Groups" "" "$AUTH_TOKEN"

echo ""

# Test payment requests
test_endpoint "GET" "/payment-requests" "Get Payment Requests" "" "$AUTH_TOKEN"

echo ""

# Test user profile
test_endpoint "GET" "/auth/me" "Get User Profile" "" "$AUTH_TOKEN"

echo ""
echo "📊 Test Summary"
echo "==============="
echo -e "Passed: ${GREEN}$PASS${NC}"
echo -e "Failed: ${RED}$FAIL${NC}"
echo ""

if [ $FAIL -eq 0 ]; then
    echo -e "${GREEN}🎉 All tests passed!${NC}"
    exit 0
else
    echo -e "${RED}❌ Some tests failed. Check your API configuration.${NC}"
    echo ""
    echo "Common issues:"
    echo "  • Make sure ngrok is running: ngrok http 8000 --host-header=rewrite"
    echo "  • Check Laravel CORS configuration"
    echo "  • Verify routes are in correct sections (public vs protected)"
    echo "  • Ensure PR code exists in database for testing"
    exit 1
fi

