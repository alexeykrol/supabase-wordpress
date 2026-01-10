#!/bin/bash

# ═══════════════════════════════════════════════════════════════
# Telemetry Analyzer Script (v0.10.2)
# ═══════════════════════════════════════════════════════════════
#
# Purpose: Analyze auth_telemetry data from Supabase using Claude API
# Schedule: Run every 3 hours via cron
# Output: Markdown reports saved to wp-content/telemetry-reports/
#
# Dependencies:
#   - curl (for Supabase + Claude API)
#   - jq (for JSON parsing)
#
# Usage:
#   bash telemetry-analyzer.sh
#
# ═══════════════════════════════════════════════════════════════

set -euo pipefail

# ═══════════════════════════════════════════════════════════════
# Configuration
# ═══════════════════════════════════════════════════════════════

# Script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Load .env file if exists
if [[ -f "${SCRIPT_DIR}/telemetry-analyzer.env" ]]; then
  set -a
  source "${SCRIPT_DIR}/telemetry-analyzer.env"
  set +a
fi

# Supabase credentials (from .env or environment)
SUPABASE_URL="${SUPABASE_URL:-}"
SUPABASE_ANON_KEY="${SUPABASE_ANON_KEY:-}"

# Claude API credentials
CLAUDE_API_KEY="${CLAUDE_API_KEY:-}"
CLAUDE_API_URL="https://api.anthropic.com/v1/messages"
CLAUDE_MODEL="claude-3-5-sonnet-20241022"

# Report directory (relative to script location)
REPORT_DIR="${REPORT_DIR:-${SCRIPT_DIR}/telemetry-reports}"

# Time window for analysis (hours)
ANALYSIS_WINDOW_HOURS="${ANALYSIS_WINDOW_HOURS:-3}"

# ═══════════════════════════════════════════════════════════════
# Functions
# ═══════════════════════════════════════════════════════════════

log() {
  echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

error() {
  echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: $1" >&2
  exit 1
}

check_dependencies() {
  if ! command -v curl &> /dev/null; then
    error "curl is not installed"
  fi

  if ! command -v jq &> /dev/null; then
    error "jq is not installed (required for JSON parsing)"
  fi
}

check_credentials() {
  if [[ -z "$SUPABASE_URL" ]]; then
    error "SUPABASE_URL not set (export it or add to .env)"
  fi

  if [[ -z "$SUPABASE_ANON_KEY" ]]; then
    error "SUPABASE_ANON_KEY not set (export it or add to .env)"
  fi

  if [[ -z "$CLAUDE_API_KEY" ]]; then
    error "CLAUDE_API_KEY not set (get from https://console.anthropic.com/)"
  fi
}

fetch_telemetry_data() {
  log "Fetching telemetry data from Supabase..."

  # Calculate timestamp for time window
  local cutoff_time=$(date -u -d "$ANALYSIS_WINDOW_HOURS hours ago" '+%Y-%m-%dT%H:%M:%S' 2>/dev/null || date -u -v-${ANALYSIS_WINDOW_HOURS}H '+%Y-%m-%dT%H:%M:%S')

  # Fetch data from Supabase
  local response=$(curl -s -X GET \
    "${SUPABASE_URL}/rest/v1/auth_telemetry?created_at=gte.${cutoff_time}Z&order=created_at.desc" \
    -H "apikey: ${SUPABASE_ANON_KEY}" \
    -H "Authorization: Bearer ${SUPABASE_ANON_KEY}" \
    -H "Content-Type: application/json")

  # Check if response is valid JSON
  if ! echo "$response" | jq empty 2>/dev/null; then
    error "Invalid JSON response from Supabase"
  fi

  echo "$response"
}

analyze_with_claude() {
  local telemetry_data="$1"
  local event_count=$(echo "$telemetry_data" | jq '. | length')

  log "Analyzing $event_count events with Claude API..."

  # Build prompt for Claude
  local prompt="You are an expert in authentication systems and data analysis. Analyze this telemetry data from a WordPress + Supabase authentication system.

**Context:**
- MagicLink emails sent via Amazon SES
- OAuth providers: Google, Facebook
- Current bounce rate: 0.28%
- Reported failure rate: ~13%

**Telemetry Data (last ${ANALYSIS_WINDOW_HOURS} hours):**
\`\`\`json
${telemetry_data}
\`\`\`

**Analysis Tasks:**

1. **Calculate Statistics:**
   - Total events
   - Event breakdown by type
   - Success rate (auth_success / total auth requests)
   - Failure rate (auth_failure / total auth requests)
   - Click-through rate (magic_link_clicked / magic_link_requested)

2. **Identify Issues:**
   - Top error codes and their meanings
   - Patterns in failures (time-based, device-based, etc.)
   - Missing events in flow (e.g., magic_link_requested but no click)

3. **Root Cause Analysis:**
   - Why are users failing authentication?
   - Are bounce rate (0.28%) and failure rate (13%) related?
   - Device switching issues (if any)

4. **Recommendations:**
   - Immediate fixes needed
   - UX improvements
   - Monitoring alerts to setup

**Output Format:**
Provide a concise markdown report with these sections:
- 📊 Statistics Summary
- 🔴 Critical Issues
- 🟡 Warnings
- ✅ Working Well
- 💡 Recommendations

Keep it actionable and specific."

  # Escape JSON for API call
  local escaped_prompt=$(echo "$prompt" | jq -Rs .)

  # Call Claude API
  local api_response=$(curl -s -X POST "${CLAUDE_API_URL}" \
    -H "x-api-key: ${CLAUDE_API_KEY}" \
    -H "anthropic-version: 2023-06-01" \
    -H "content-type: application/json" \
    -d "{
      \"model\": \"${CLAUDE_MODEL}\",
      \"max_tokens\": 4096,
      \"messages\": [
        {
          \"role\": \"user\",
          \"content\": ${escaped_prompt}
        }
      ]
    }")

  # Check if response is valid
  if ! echo "$api_response" | jq empty 2>/dev/null; then
    error "Invalid JSON response from Claude API"
  fi

  # Extract analysis from response
  local analysis=$(echo "$api_response" | jq -r '.content[0].text // empty')

  if [[ -z "$analysis" ]]; then
    # Check for API error
    local error_message=$(echo "$api_response" | jq -r '.error.message // "Unknown error"')
    error "Claude API error: $error_message"
  fi

  echo "$analysis"
}

save_report() {
  local analysis="$1"
  local event_count="$2"

  # Create report directory if not exists
  mkdir -p "$REPORT_DIR"

  # Generate report filename
  local timestamp=$(date '+%Y-%m-%d_%H-%M-%S')
  local report_file="${REPORT_DIR}/telemetry-report-${timestamp}.md"

  # Build report
  cat > "$report_file" <<EOF
# Telemetry Analysis Report

**Generated:** $(date '+%Y-%m-%d %H:%M:%S')
**Analysis Window:** Last ${ANALYSIS_WINDOW_HOURS} hours
**Events Analyzed:** ${event_count}

---

${analysis}

---

**Report ID:** ${timestamp}
**Analyzer Version:** v0.10.2
EOF

  log "Report saved: $report_file"
  echo "$report_file"
}

generate_status_summary() {
  local report_file="$1"

  # Determine status based on report content
  local status="🟢"  # Green by default

  if grep -qi "critical" "$report_file" || grep -qi "🔴" "$report_file"; then
    status="🔴"  # Red - critical issues
  elif grep -qi "warning" "$report_file" || grep -qi "🟡" "$report_file"; then
    status="🟡"  # Yellow - warnings
  fi

  echo "$status"
}

# ═══════════════════════════════════════════════════════════════
# Main Execution
# ═══════════════════════════════════════════════════════════════

main() {
  log "Starting telemetry analysis..."

  # Pre-flight checks
  check_dependencies
  check_credentials

  # Fetch telemetry data
  telemetry_data=$(fetch_telemetry_data)
  event_count=$(echo "$telemetry_data" | jq '. | length')

  if [[ "$event_count" -eq 0 ]]; then
    log "No telemetry events in last ${ANALYSIS_WINDOW_HOURS} hours - skipping analysis"
    exit 0
  fi

  # Analyze with Claude
  analysis=$(analyze_with_claude "$telemetry_data")

  # Save report
  report_file=$(save_report "$analysis" "$event_count")

  # Generate status summary
  status=$(generate_status_summary "$report_file")

  log "Analysis complete! Status: $status"
  log "Report: $report_file"
}

# Run main function
main
