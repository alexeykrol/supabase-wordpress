#!/bin/bash

# ═══════════════════════════════════════════════════════════════
# RUN ALL TESTS - Unified Test Runner
# ═══════════════════════════════════════════════════════════════
# Usage: bash tests/run-all.sh
# Or: npm run test:all
# Or: Just type "/test" in Claude
# ═══════════════════════════════════════════════════════════════

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
REPORTS_DIR="$PROJECT_ROOT/tests/reports"

# Create reports directory
mkdir -p "$REPORTS_DIR"

# Generate timestamp
TIMESTAMP=$(date +%Y-%m-%d-%H-%M-%S)
REPORT_FILE="$REPORTS_DIR/test-run-$TIMESTAMP.txt"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Start report
{
    echo "════════════════════════════════════════════════════════"
    echo "🧪 TEST RUN REPORT"
    echo "════════════════════════════════════════════════════════"
    echo "Project: Supabase Bridge"
    echo "Started: $(date)"
    echo "════════════════════════════════════════════════════════"
    echo ""
} | tee "$REPORT_FILE"

# ═══ STEP 1: SMOKE TESTS ═══
{
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "📋 STEP 1: SMOKE TESTS (Health Check)"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
} | tee -a "$REPORT_FILE"

if bash "$PROJECT_ROOT/tests/smoke/health-check.sh" >> "$REPORT_FILE" 2>&1; then
    echo -e "${GREEN}✅ SMOKE TESTS: PASSED${NC}" | tee -a "$REPORT_FILE"
    SMOKE_STATUS="PASS"
else
    echo -e "${RED}❌ SMOKE TESTS: FAILED${NC}" | tee -a "$REPORT_FILE"
    SMOKE_STATUS="FAIL"
fi

echo "" | tee -a "$REPORT_FILE"

# ═══ STEP 2: UNIT TESTS ═══
{
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "📋 STEP 2: UNIT TESTS (PHPUnit)"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
} | tee -a "$REPORT_FILE"

# Check if PHPUnit is installed
if [ -f "$PROJECT_ROOT/vendor/bin/phpunit" ]; then
    if "$PROJECT_ROOT/vendor/bin/phpunit" --no-coverage >> "$REPORT_FILE" 2>&1; then
        echo -e "${GREEN}✅ UNIT TESTS: PASSED${NC}" | tee -a "$REPORT_FILE"
        UNIT_STATUS="PASS"
    else
        echo -e "${RED}❌ UNIT TESTS: FAILED${NC}" | tee -a "$REPORT_FILE"
        UNIT_STATUS="FAIL"
    fi
else
    echo -e "${YELLOW}⚠️  UNIT TESTS: SKIPPED (PHPUnit not installed)${NC}" | tee -a "$REPORT_FILE"
    echo "   Run: composer install" | tee -a "$REPORT_FILE"
    UNIT_STATUS="SKIP"
fi

echo "" | tee -a "$REPORT_FILE"

# ═══ STEP 3: GENERATE AI REPORT ═══
{
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "📋 STEP 3: AI ANALYSIS REPORT"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
} | tee -a "$REPORT_FILE"

if [ -f "$PROJECT_ROOT/tests/ai-assisted/generate-report.sh" ]; then
    bash "$PROJECT_ROOT/tests/ai-assisted/generate-report.sh" >> "$REPORT_FILE" 2>&1
    echo -e "${GREEN}✅ AI REPORT: GENERATED${NC}" | tee -a "$REPORT_FILE"
else
    echo -e "${YELLOW}⚠️  AI REPORT: SKIPPED${NC}" | tee -a "$REPORT_FILE"
fi

echo "" | tee -a "$REPORT_FILE"

# ═══ STEP 4: SECURITY SCAN ═══
{
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "📋 STEP 4: SECURITY SCAN"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
} | tee -a "$REPORT_FILE"

if bash "$PROJECT_ROOT/tests/security-scan.sh" >> "$REPORT_FILE" 2>&1; then
    echo -e "${GREEN}✅ SECURITY SCAN: PASSED${NC}" | tee -a "$REPORT_FILE"
    SECURITY_STATUS="PASS"
else
    echo -e "${RED}🚨 SECURITY SCAN: FAILED (credentials found)${NC}" | tee -a "$REPORT_FILE"
    SECURITY_STATUS="FAIL"
fi

echo "" | tee -a "$REPORT_FILE"

# ═══ FINAL SUMMARY ═══
{
    echo "════════════════════════════════════════════════════════"
    echo "📊 FINAL SUMMARY"
    echo "════════════════════════════════════════════════════════"
    echo ""
    echo "Smoke Tests:     $SMOKE_STATUS"
    echo "Unit Tests:      $UNIT_STATUS"
    echo "Security Scan:   $SECURITY_STATUS"
    echo ""
    echo "Report saved: $REPORT_FILE"
    echo "Finished: $(date)"
    echo "════════════════════════════════════════════════════════"
} | tee -a "$REPORT_FILE"

# Determine exit code
if [ "$SMOKE_STATUS" = "FAIL" ] || [ "$UNIT_STATUS" = "FAIL" ] || [ "$SECURITY_STATUS" = "FAIL" ]; then
    echo ""
    echo -e "${RED}❌ TESTS FAILED - See report above${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Review detailed report: cat $REPORT_FILE"

    if [ "$SECURITY_STATUS" = "FAIL" ]; then
        echo "2. 🚨 CRITICAL: Security scan found exposed credentials in dialog/"
        echo "   Review: tests/reports/security-scan-*.txt"
        echo "   Action: Remove sensitive data from dialog files before committing"
    fi

    echo ""
    exit 1
else
    echo ""
    echo -e "${GREEN}✅ ALL TESTS PASSED${NC}"
    echo ""
    exit 0
fi
