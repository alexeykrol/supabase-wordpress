#!/bin/bash

# ═══════════════════════════════════════════════════════════════
# AI-Assisted Test Report Generator
# ═══════════════════════════════════════════════════════════════
# Generates JSON report for Claude to analyze
# Usage: bash tests/ai-assisted/generate-report.sh
# Output: tests/reports/YYYY-MM-DD-HH-MM.json
# ═══════════════════════════════════════════════════════════════

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
REPORTS_DIR="$PROJECT_ROOT/tests/reports"

# Create reports directory
mkdir -p "$REPORTS_DIR"

# Generate timestamp
TIMESTAMP=$(date +%Y-%m-%d-%H-%M)
REPORT_FILE="$REPORTS_DIR/$TIMESTAMP.json"

echo "🤖 Generating AI-assisted test report..."
echo ""

# ═══ COLLECT DATA ═══

# Git info
GIT_BRANCH=$(git branch --show-current 2>/dev/null || echo "unknown")
GIT_COMMIT=$(git rev-parse --short HEAD 2>/dev/null || echo "unknown")
GIT_STATUS=$(git status --porcelain 2>/dev/null | wc -l | tr -d ' ')

# File stats
PLUGIN_LINES=$(wc -l < "$PROJECT_ROOT/supabase-bridge.php" | tr -d ' ')
PLUGIN_SIZE=$(ls -lh "$PROJECT_ROOT/supabase-bridge.php" | awk '{print $5}')

# Function count
FUNCTION_COUNT=$(grep -c "^function " "$PROJECT_ROOT/supabase-bridge.php" || echo "0")

# Critical functions check
CRITICAL_FUNCTIONS=(
    "sb_handle_callback"
    "sb_get_thankyou_url_for_registration"
    "sb_validate_email"
    "sb_validate_url_path"
    "sb_validate_uuid"
    "sb_log_registration_to_supabase"
)

FUNCTIONS_STATUS=()
for func in "${CRITICAL_FUNCTIONS[@]}"; do
    if grep -q "function $func" "$PROJECT_ROOT/supabase-bridge.php"; then
        FUNCTIONS_STATUS+=("{\"name\": \"$func\", \"exists\": true}")
    else
        FUNCTIONS_STATUS+=("{\"name\": \"$func\", \"exists\": false}")
    fi
done

# PHP syntax check
PHP_SYNTAX="valid"
if ! php -l "$PROJECT_ROOT/supabase-bridge.php" > /dev/null 2>&1; then
    PHP_SYNTAX="invalid"
fi

# Dependencies check
COMPOSER_INSTALLED="false"
if [ -f "$PROJECT_ROOT/vendor/autoload.php" ]; then
    COMPOSER_INSTALLED="true"
fi

JWT_LIBRARY="false"
if [ -d "$PROJECT_ROOT/vendor/firebase/php-jwt" ]; then
    JWT_LIBRARY="true"
fi

# Test infrastructure
PHPUNIT_CONFIG="false"
if [ -f "$PROJECT_ROOT/phpunit.xml" ]; then
    PHPUNIT_CONFIG="true"
fi

UNIT_TESTS=$(find "$PROJECT_ROOT/tests/unit" -name "*Test.php" 2>/dev/null | wc -l | tr -d ' ')
SMOKE_TESTS=$(find "$PROJECT_ROOT/tests/smoke" -name "*.sh" 2>/dev/null | wc -l | tr -d ' ')

# ═══ GENERATE JSON REPORT ═══

cat > "$REPORT_FILE" <<EOF
{
  "generated_at": "$(date -Iseconds)",
  "report_type": "test_infrastructure",
  "version": "1.0",
  "project": {
    "name": "Supabase Bridge",
    "root": "$PROJECT_ROOT"
  },
  "git": {
    "branch": "$GIT_BRANCH",
    "commit": "$GIT_COMMIT",
    "uncommitted_changes": $GIT_STATUS
  },
  "plugin": {
    "file": "supabase-bridge.php",
    "lines": $PLUGIN_LINES,
    "size": "$PLUGIN_SIZE",
    "function_count": $FUNCTION_COUNT,
    "php_syntax": "$PHP_SYNTAX"
  },
  "critical_functions": [
    $(IFS=,; echo "${FUNCTIONS_STATUS[*]}")
  ],
  "dependencies": {
    "composer_installed": $COMPOSER_INSTALLED,
    "jwt_library": $JWT_LIBRARY
  },
  "tests": {
    "phpunit_config": $PHPUNIT_CONFIG,
    "unit_tests": $UNIT_TESTS,
    "smoke_tests": $SMOKE_TESTS
  },
  "health_check": {
    "timestamp": "$(date -Iseconds)",
    "status": "pending"
  },
  "recommendations": []
}
EOF

echo "✅ Report generated: $REPORT_FILE"
echo ""
echo "════════════════════════════════════════════════════════"
echo "📊 Report Summary"
echo "════════════════════════════════════════════════════════"
echo "Git: $GIT_BRANCH @ $GIT_COMMIT"
echo "Plugin: $PLUGIN_LINES lines, $FUNCTION_COUNT functions"
echo "Tests: $UNIT_TESTS unit, $SMOKE_TESTS smoke"
echo ""
echo "════════════════════════════════════════════════════════"
echo "🤖 Next Steps"
echo "════════════════════════════════════════════════════════"
echo ""
echo "1. Share report with Claude:"
echo "   \"Claude, analyze tests/reports/$TIMESTAMP.json\""
echo ""
echo "2. Claude will:"
echo "   - Check for missing tests"
echo "   - Detect patterns in failures"
echo "   - Suggest improvements"
echo "   - Compare with previous reports"
echo ""
echo "════════════════════════════════════════════════════════"
echo ""

# Run smoke test and append results
if [ -f "$PROJECT_ROOT/tests/smoke/health-check.sh" ]; then
    echo "🧪 Running smoke test..."
    if bash "$PROJECT_ROOT/tests/smoke/health-check.sh" > /tmp/smoke-test-output.txt 2>&1; then
        SMOKE_STATUS="pass"
    else
        SMOKE_STATUS="fail"
    fi

    # Update report with smoke test results
    cat "$REPORT_FILE" | jq --arg status "$SMOKE_STATUS" '.health_check.status = $status' > "$REPORT_FILE.tmp"
    mv "$REPORT_FILE.tmp" "$REPORT_FILE"

    echo "✅ Smoke test: $SMOKE_STATUS"
fi

echo ""
echo "Report ready for AI analysis: $REPORT_FILE"
