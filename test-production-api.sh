#!/bin/bash

# Production API Testing Script
# Tests critical endpoints before mobile app testing

API_BASE="https://www.togetherpay.app/api/v1"
BOLD='\033[1m'
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BOLD}TogetherPay Production API Test${NC}"
echo "Testing: $API_BASE"
echo "================================================"
echo ""

# Test counter
PASSED=0
FAILED=0

# Function to test endpoint
test_endpoint() {
    local method=$1
    local endpoint=$2
    local description=$3
    local expected_status=$4
    local data=$5

    echo -e "${BOLD}Testing:${NC} $description"
    echo -e "  ${YELLOW}→${NC} $method $endpoint"

    if [ -n "$data" ]; then
        response=$(curl -s -w "\n%{http_code}" -X "$method" \
            -H "Content-Type: application/json" \
            -H "Accept: application/json" \
            -d "$data" \
            "$API_BASE$endpoint")
    else
        response=$(curl -s -w "\n%{http_code}" -X "$method" \
            -H "Accept: application/json" \
            "$API_BASE$endpoint")
    fi

    status=$(echo "$response" | tail -n1)
    body=$(echo "$response" | head -n-1)

    if [ "$status" = "$expected_status" ]; then
        echo -e "  ${GREEN}✓${NC} Status: $status (Expected: $expected_status)"
        ((PASSED++))
    else
        echo -e "  ${RED}✗${NC} Status: $status (Expected: $expected_status)"
        echo -e "  ${RED}Response:${NC} $body"
        ((FAILED++))
    fi
    echo ""
}

# Test 1: Health check (at root /api, not /api/v1)
echo -e "${BOLD}Testing:${NC} Health Check"
echo -e "  ${YELLOW}→${NC} GET https://www.togetherpay.app/api/health"
health_response=$(curl -s -w "\n%{http_code}" -H "Accept: application/json" "https://www.togetherpay.app/api/health")
health_status=$(echo "$health_response" | tail -n1)
if [ "$health_status" = "200" ]; then
    echo -e "  ${GREEN}✓${NC} Status: $health_status"
    ((PASSED++))
else
    echo -e "  ${RED}✗${NC} Status: $health_status (Expected: 200)"
    ((FAILED++))
fi
echo ""

# Test 2: CORS headers
echo -e "${BOLD}Testing:${NC} CORS Headers"
echo -e "  ${YELLOW}→${NC} OPTIONS request"
cors_response=$(curl -s -I -X OPTIONS \
    -H "Origin: http://localhost:3000" \
    -H "Access-Control-Request-Method: POST" \
    "$API_BASE/auth/login")

if echo "$cors_response" | grep -q "Access-Control-Allow-Origin"; then
    echo -e "  ${GREEN}✓${NC} CORS headers present"
    ((PASSED++))
else
    echo -e "  ${RED}✗${NC} CORS headers missing"
    ((FAILED++))
fi
echo ""

# Test 3: Registration endpoint (should fail with validation)
test_endpoint "POST" "/auth/register" "Registration Endpoint (Validation)" "422" \
    '{"name":"","email":"invalid","password":"123"}'

# Test 4: Login endpoint (should fail with invalid credentials)
test_endpoint "POST" "/auth/login" "Login Endpoint (Invalid Credentials)" "401" \
    '{"email":"test@example.com","password":"wrongpassword"}'

# Test 5: Unauthorized access to protected route
test_endpoint "GET" "/auth/me" "Protected Route (No Token)" "401"

# Test 6: Groups endpoint (should require auth)
test_endpoint "GET" "/groups" "Groups List (No Token)" "401"

# Test 7: SSL/HTTPS check
echo -e "${BOLD}Testing:${NC} SSL Certificate"
echo -e "  ${YELLOW}→${NC} HTTPS connection"
ssl_check=$(curl -s -o /dev/null -w "%{http_code}" "https://www.togetherpay.app")
if [ "$ssl_check" = "200" ] || [ "$ssl_check" = "302" ]; then
    echo -e "  ${GREEN}✓${NC} SSL certificate valid"
    ((PASSED++))
else
    echo -e "  ${RED}✗${NC} SSL certificate issue"
    ((FAILED++))
fi
echo ""

# Test 8: API response time
echo -e "${BOLD}Testing:${NC} API Response Time"
echo -e "  ${YELLOW}→${NC} GET /health"
response_time=$(curl -s -w "%{time_total}" -o /dev/null "$API_BASE/health")
response_ms=$(echo "$response_time * 1000" | bc)
if (( $(echo "$response_time < 2" | bc -l) )); then
    echo -e "  ${GREEN}✓${NC} Response time: ${response_ms}ms (< 2000ms)"
    ((PASSED++))
else
    echo -e "  ${YELLOW}⚠${NC} Response time: ${response_ms}ms (> 2000ms)"
    ((FAILED++))
fi
echo ""

# Test 9: API versioning
echo -e "${BOLD}Testing:${NC} API Versioning"
echo -e "  ${YELLOW}→${NC} GET /v1 prefix"
version_check=$(curl -s -w "%{http_code}" -o /dev/null "$API_BASE/health")
if [ "$version_check" = "200" ]; then
    echo -e "  ${GREEN}✓${NC} API v1 accessible"
    ((PASSED++))
else
    echo -e "  ${RED}✗${NC} API v1 not accessible"
    ((FAILED++))
fi
echo ""

# Summary
echo "================================================"
echo -e "${BOLD}Test Summary${NC}"
echo "================================================"
echo -e "${GREEN}Passed:${NC} $PASSED"
echo -e "${RED}Failed:${NC} $FAILED"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}${BOLD}✓ All tests passed!${NC}"
    echo -e "${GREEN}Your API is ready for mobile app testing.${NC}"
    exit 0
else
    echo -e "${RED}${BOLD}✗ Some tests failed.${NC}"
    echo -e "${RED}Please fix the issues before proceeding with mobile testing.${NC}"
    exit 1
fi
