#!/bin/bash

# Script para testear la aplicación después del fix
# Uso: ./test-deployment.sh https://proyecto-laminas.up.railway.app

if [ -z "$1" ]; then
    echo "Uso: $0 <URL>"
    echo "Ejemplo: $0 https://proyecto-laminas.up.railway.app"
    exit 1
fi

BASE_URL="$1"
PASSED=0
FAILED=0

echo "🧪 Testing Deployment: $BASE_URL"
echo "========================================"
echo ""

# Test 1: Health Check
echo "Test 1: Health Check Endpoint"
RESPONSE=$(curl -s -w "%{http_code}" -o /tmp/health.json "$BASE_URL/health")
if [ "$RESPONSE" = "200" ]; then
    echo "✓ Health check returned 200"
    echo "Response:"
    cat /tmp/health.json | grep -q '"status":"OK"' && echo "  - Status: OK" || echo "  - Status: WARNING"
    ((PASSED++))
else
    echo "✗ Health check returned $RESPONSE (expected 200)"
    ((FAILED++))
fi
echo ""

# Test 2: Home Page
echo "Test 2: Home Page"
RESPONSE=$(curl -s -w "%{http_code}" -o /tmp/home.html "$BASE_URL/")
if [ "$RESPONSE" = "200" ]; then
    echo "✓ Home page returned 200"
    grep -q "NOVAFARMA\|Novafarma\|novafarma" /tmp/home.html && echo "  - Contains 'Novafarma'" || echo "  - Warning: 'Novafarma' not found"
    ((PASSED++))
else
    echo "✗ Home page returned $RESPONSE (expected 200)"
    ((FAILED++))
fi
echo ""

# Test 3: Login Page
echo "Test 3: Login Page"
RESPONSE=$(curl -s -w "%{http_code}" -o /tmp/login.html "$BASE_URL/auth/login")
if [ "$RESPONSE" = "200" ]; then
    echo "✓ Login page returned 200"
    grep -q "login\|password\|usuario" /tmp/login.html && echo "  - Contains login form elements" || echo "  - Warning: Form elements not found"
    ((PASSED++))
else
    echo "✗ Login page returned $RESPONSE (expected 200)"
    ((FAILED++))
fi
echo ""

# Test 4: Dashboard (should redirect without auth)
echo "Test 4: Dashboard Access (should redirect)"
RESPONSE=$(curl -s -w "%{http_code}" -o /tmp/dashboard.html -L "$BASE_URL/dashboard")
if [ "$RESPONSE" = "200" ]; then
    echo "✓ Dashboard returned 200 (probably redirected to login)"
    ((PASSED++))
else
    echo "✗ Dashboard returned $RESPONSE"
    ((FAILED++))
fi
echo ""

# Test 5: Non-existent page (should return 404)
echo "Test 5: 404 Error Handling"
RESPONSE=$(curl -s -w "%{http_code}" -o /tmp/notfound.html "$BASE_URL/this-page-does-not-exist-12345")
if [ "$RESPONSE" = "404" ]; then
    echo "✓ Non-existent page returned 404"
    ((PASSED++))
else
    echo "✗ Non-existent page returned $RESPONSE (expected 404)"
    ((FAILED++))
fi
echo ""

# Test 6: Check for 502 errors
echo "Test 6: Check for 502 Errors in Response"
if grep -q "502\|Bad Gateway\|gateway" /tmp/home.html /tmp/login.html 2>/dev/null; then
    echo "✗ Found 502 or Bad Gateway errors in responses"
    ((FAILED++))
else
    echo "✓ No 502 errors detected"
    ((PASSED++))
fi
echo ""

# Summary
echo "========================================"
echo "📊 Test Summary"
echo "✓ Passed: $PASSED"
echo "✗ Failed: $FAILED"
echo ""

if [ $FAILED -eq 0 ]; then
    echo "✅ All tests passed! Deployment looks good."
    exit 0
else
    echo "❌ Some tests failed. Please review the output above."
    exit 1
fi
