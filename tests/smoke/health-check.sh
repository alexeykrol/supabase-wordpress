#!/bin/bash

# ═══════════════════════════════════════════════════════════════
# Supabase Bridge - Smoke Test (Health Check)
# ═══════════════════════════════════════════════════════════════
# Quick validation that basic functionality is working
# Run before/after deployment
# Expected runtime: 5-10 seconds
# ═══════════════════════════════════════════════════════════════

set -e  # Exit on first error

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Counters
PASSED=0
FAILED=0
WARNINGS=0

echo ""
echo "════════════════════════════════════════════════════════"
echo "🧪 Supabase Bridge - Health Check"
echo "════════════════════════════════════════════════════════"
echo ""

# ═══ TEST 1: Plugin File Exists ═══
echo -n "📄 Plugin file exists... "
if [ -f "$PROJECT_ROOT/supabase-bridge.php" ]; then
    echo -e "${GREEN}✓ PASS${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗ FAIL${NC}"
    echo "   Error: supabase-bridge.php not found"
    ((FAILED++))
fi

# ═══ TEST 2: HTML Files Exist ═══
echo -n "📄 Auth form HTML exists... "
if [ -f "$PROJECT_ROOT/auth-form.html" ]; then
    echo -e "${GREEN}✓ PASS${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗ FAIL${NC}"
    ((FAILED++))
fi

echo -n "📄 Callback handler HTML exists... "
if [ -f "$PROJECT_ROOT/test-no-elem-2-wordpress-paste.html" ]; then
    echo -e "${GREEN}✓ PASS${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗ FAIL${NC}"
    ((FAILED++))
fi

# ═══ TEST 3: Dependencies Installed ═══
echo -n "📦 Composer dependencies installed... "
if [ -d "$PROJECT_ROOT/vendor" ] && [ -f "$PROJECT_ROOT/vendor/autoload.php" ]; then
    echo -e "${GREEN}✓ PASS${NC}"
    ((PASSED++))
else
    echo -e "${YELLOW}⚠ WARNING${NC}"
    echo "   Run: composer install"
    ((WARNINGS++))
fi

# ═══ TEST 4: JWT Library Present ═══
echo -n "📚 JWT library present... "
if [ -d "$PROJECT_ROOT/vendor/firebase/php-jwt" ]; then
    echo -e "${GREEN}✓ PASS${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗ FAIL${NC}"
    echo "   Error: firebase/php-jwt not found"
    ((FAILED++))
fi

# ═══ TEST 5: PHP Syntax Check ═══
echo -n "🔍 PHP syntax check... "
if php -l "$PROJECT_ROOT/supabase-bridge.php" > /dev/null 2>&1; then
    echo -e "${GREEN}✓ PASS${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗ FAIL${NC}"
    echo "   Error: PHP syntax errors in supabase-bridge.php"
    ((FAILED++))
fi

# ═══ TEST 6: Critical Functions Exist ═══
echo -n "🔧 Critical functions defined... "
FUNCTIONS=(
    "sb_handle_callback"
    "sb_get_thankyou_url_for_registration"
    "sb_validate_email"
    "sb_validate_url_path"
)

MISSING_FUNCTIONS=()
for func in "${FUNCTIONS[@]}"; do
    if ! grep -q "function $func" "$PROJECT_ROOT/supabase-bridge.php"; then
        MISSING_FUNCTIONS+=("$func")
    fi
done

if [ ${#MISSING_FUNCTIONS[@]} -eq 0 ]; then
    echo -e "${GREEN}✓ PASS${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗ FAIL${NC}"
    echo "   Missing functions: ${MISSING_FUNCTIONS[*]}"
    ((FAILED++))
fi

# ═══ TEST 7: Shortcodes Registered ═══
echo -n "📌 Shortcodes registered... "
SHORTCODES=(
    "supabase_auth_form"
    "supabase_auth_callback"
)

MISSING_SHORTCODES=()
for shortcode in "${SHORTCODES[@]}"; do
    if ! grep -q "add_shortcode.*'$shortcode'" "$PROJECT_ROOT/supabase-bridge.php"; then
        MISSING_SHORTCODES+=("$shortcode")
    fi
done

if [ ${#MISSING_SHORTCODES[@]} -eq 0 ]; then
    echo -e "${GREEN}✓ PASS${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗ FAIL${NC}"
    echo "   Missing shortcodes: ${MISSING_SHORTCODES[*]}"
    ((FAILED++))
fi

# ═══ TEST 8: Test Infrastructure ═══
echo -n "🧪 Test infrastructure present... "
if [ -f "$PROJECT_ROOT/phpunit.xml" ] && [ -d "$PROJECT_ROOT/tests/unit" ]; then
    echo -e "${GREEN}✓ PASS${NC}"
    ((PASSED++))
else
    echo -e "${YELLOW}⚠ WARNING${NC}"
    echo "   Missing test files"
    ((WARNINGS++))
fi

# ═══ TEST 9: Documentation Present ═══
echo -n "📖 Documentation present... "
if [ -f "$PROJECT_ROOT/README.md" ]; then
    echo -e "${GREEN}✓ PASS${NC}"
    ((PASSED++))
else
    echo -e "${YELLOW}⚠ WARNING${NC}"
    ((WARNINGS++))
fi

# ═══ SUMMARY ═══
echo ""
echo "════════════════════════════════════════════════════════"
echo "📊 Summary"
echo "════════════════════════════════════════════════════════"
echo -e "${GREEN}✓ Passed:   $PASSED${NC}"
echo -e "${RED}✗ Failed:   $FAILED${NC}"
echo -e "${YELLOW}⚠ Warnings: $WARNINGS${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✅ All critical checks passed!${NC}"
    echo ""
    exit 0
else
    echo -e "${RED}❌ $FAILED test(s) failed!${NC}"
    echo ""
    exit 1
fi
