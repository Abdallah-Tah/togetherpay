#!/bin/bash

# Test registration API with various curl options

echo "Test 1: Basic curl with -d"
curl -s -X POST https://www.togetherpay.app/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"name\":\"Test1\",\"email\":\"test1_$(date +%s)@example.com\",\"password\":\"Pass123!\",\"password_confirmation\":\"Pass123!\"}"
echo -e "\n\n"

echo "Test 2: curl with --data-binary"
curl -s -X POST https://www.togetherpay.app/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  --data-binary "{\"name\":\"Test2\",\"email\":\"test2_$(date +%s)@example.com\",\"password\":\"Pass123!\",\"password_confirmation\":\"Pass123!\"}"
echo -e "\n\n"

echo "Test 3: curl with heredoc"
curl -s -X POST https://www.togetherpay.app/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d @- << 'EOF'
{
  "name": "Test3",
  "email": "test3@example.com",
  "password": "Pass123!",
  "password_confirmation": "Pass123!"
}
EOF
echo -e "\n\n"

echo "Test 4: HTTP/1.1 explicit"
curl -s --http1.1 -X POST https://www.togetherpay.app/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"name\":\"Test4\",\"email\":\"test4_$(date +%s)@example.com\",\"password\":\"Pass123!\",\"password_confirmation\":\"Pass123!\"}"
echo -e "\n"
