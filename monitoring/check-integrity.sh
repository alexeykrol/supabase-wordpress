#!/bin/bash

# ═══════════════════════════════════════════════════════════════
# Data Integrity Monitoring Script
# Checks registration tracking, memberships, and enrollments
# ═══════════════════════════════════════════════════════════════

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default period: 1 hour
PERIOD="${1:-1 hour}"

echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}         DATA INTEGRITY CHECK${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "Period: ${YELLOW}last $PERIOD${NC}"
echo -e "Time:   $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

# ═══════════════════════════════════════════════════════════════
# Step 1: Load Supabase credentials from .env
# ═══════════════════════════════════════════════════════════════

echo -e "${BLUE}[1/5] Loading Supabase credentials...${NC}"

# Get script directory
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

# Load from .env file
if [ -f "$SCRIPT_DIR/.env" ]; then
  source "$SCRIPT_DIR/.env"
else
  echo -e "${RED}❌ ERROR: monitoring/.env file not found${NC}"
  echo -e "${YELLOW}Please create it from .env.example:${NC}"
  echo -e "  cp monitoring/.env.example monitoring/.env"
  echo -e "  # Then edit monitoring/.env with your Supabase credentials"
  exit 1
fi

# Validate credentials loaded
if [ -z "$SUPABASE_URL" ] || [ -z "$SUPABASE_ANON_KEY" ]; then
  echo -e "${RED}❌ ERROR: Supabase credentials not configured in .env${NC}"
  echo -e "${YELLOW}Please edit monitoring/.env and add:${NC}"
  echo -e "  SUPABASE_URL=https://yourproject.supabase.co"
  echo -e "  SUPABASE_ANON_KEY=your-anon-key-here"
  exit 1
fi

# Use SUPABASE_ANON_KEY for SUPABASE_KEY
SUPABASE_KEY="$SUPABASE_ANON_KEY"

echo -e "${GREEN}✓ Credentials loaded from .env${NC}"
echo ""

# ═══════════════════════════════════════════════════════════════
# Step 2: Query Supabase Auth users (new registrations)
# ═══════════════════════════════════════════════════════════════

echo -e "${BLUE}[2/5] Querying Supabase Auth...${NC}"

# Convert period to PostgreSQL interval format
PG_INTERVAL=$(echo "$PERIOD" | sed 's/ hour/ hours/' | sed 's/ minute/ minutes/')

AUTH_USERS=$(curl -s "${SUPABASE_URL}/rest/v1/rpc/count_auth_users" \
  -H "apikey: ${SUPABASE_KEY}" \
  -H "Authorization: Bearer ${SUPABASE_KEY}" \
  -H "Content-Type: application/json" \
  -d "{\"period_interval\":\"${PG_INTERVAL}\"}" 2>/dev/null || echo "0")

# If RPC doesn't exist, use direct query
if [ "$AUTH_USERS" = "0" ] || [ -z "$AUTH_USERS" ]; then
  AUTH_USERS=$(curl -s "${SUPABASE_URL}/rest/v1/users?select=id&created_at=gte.$(date -u -v-1H '+%Y-%m-%dT%H:%M:%S' 2>/dev/null || date -u -d '1 hour ago' '+%Y-%m-%dT%H:%M:%S')Z" \
    -H "apikey: ${SUPABASE_KEY}" \
    -H "Authorization: Bearer ${SUPABASE_KEY}" 2>/dev/null | grep -o '"id"' | wc -l | tr -d ' ')
fi

echo -e "  New auth users: ${YELLOW}${AUTH_USERS}${NC}"
echo ""

# ═══════════════════════════════════════════════════════════════
# Step 3: Query wp_user_registrations (tracking)
# ═══════════════════════════════════════════════════════════════

echo -e "${BLUE}[3/5] Querying registration tracking...${NC}"

# Total registrations tracked
WP_REGISTRATIONS=$(curl -s "${SUPABASE_URL}/rest/v1/wp_user_registrations?select=id&registered_at=gte.$(date -u -v-1H '+%Y-%m-%dT%H:%M:%S' 2>/dev/null || date -u -d '1 hour ago' '+%Y-%m-%dT%H:%M:%S')Z" \
  -H "apikey: ${SUPABASE_KEY}" \
  -H "Authorization: Bearer ${SUPABASE_KEY}" 2>/dev/null | grep -o '"id"' | wc -l | tr -d ' ')

# Registrations with pair_id (from landing pages)
WITH_PAIR_ID=$(curl -s "${SUPABASE_URL}/rest/v1/wp_user_registrations?select=id&registered_at=gte.$(date -u -v-1H '+%Y-%m-%dT%H:%M:%S' 2>/dev/null || date -u -d '1 hour ago' '+%Y-%m-%dT%H:%M:%S')Z&pair_id=not.is.null" \
  -H "apikey: ${SUPABASE_KEY}" \
  -H "Authorization: Bearer ${SUPABASE_KEY}" 2>/dev/null | grep -o '"id"' | wc -l | tr -d ' ')

# Registrations without pair_id
WITHOUT_PAIR_ID=$((WP_REGISTRATIONS - WITH_PAIR_ID))

echo -e "  Tracked registrations: ${YELLOW}${WP_REGISTRATIONS}${NC}"
echo -e "  With landing (pair_id): ${YELLOW}${WITH_PAIR_ID}${NC}"
echo -e "  Without landing: ${YELLOW}${WITHOUT_PAIR_ID}${NC}"
echo ""

# ═══════════════════════════════════════════════════════════════
# Step 4: Query WordPress data (memberships, enrollments)
# ═══════════════════════════════════════════════════════════════

echo -e "${BLUE}[4/5] Querying WordPress data...${NC}"

# Note: This requires database access or WordPress API
# For now, we'll skip this part and focus on Supabase data
# TODO: Add membership and enrollment checks

echo -e "  ${YELLOW}⚠ Membership/Enrollment checks not implemented yet${NC}"
echo ""

# ═══════════════════════════════════════════════════════════════
# Step 5: Analysis and Alerts
# ═══════════════════════════════════════════════════════════════

echo -e "${BLUE}[5/5] Analysis...${NC}"
echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}                    RESULTS${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo ""

ERRORS=0

# Check 1: Registration tracking
LOST_TRACKING=$((AUTH_USERS - WP_REGISTRATIONS))
echo -e "1️⃣  Registration Tracking:"
echo -e "   Supabase Auth:     ${YELLOW}${AUTH_USERS}${NC}"
echo -e "   WP Tracking:       ${YELLOW}${WP_REGISTRATIONS}${NC}"
if [ $LOST_TRACKING -eq 0 ]; then
  echo -e "   Status:            ${GREEN}✓ OK (no losses)${NC}"
else
  echo -e "   Status:            ${RED}❌ LOST: ${LOST_TRACKING} registrations${NC}"
  ERRORS=$((ERRORS + 1))
fi
echo ""

# Check 2: Landing page tracking
EXPECTED_WITH_LANDING=$WP_REGISTRATIONS
echo -e "2️⃣  Landing Page Tracking:"
echo -e "   Expected:          ${YELLOW}${EXPECTED_WITH_LANDING}${NC}"
echo -e "   With pair_id:      ${YELLOW}${WITH_PAIR_ID}${NC}"
echo -e "   Without pair_id:   ${YELLOW}${WITHOUT_PAIR_ID}${NC}"
if [ $WITHOUT_PAIR_ID -eq 0 ]; then
  echo -e "   Status:            ${GREEN}✓ OK (all tracked)${NC}"
else
  PERCENT=$((WITHOUT_PAIR_ID * 100 / EXPECTED_WITH_LANDING))
  echo -e "   Status:            ${RED}❌ LOST: ${WITHOUT_PAIR_ID} (${PERCENT}%)${NC}"
  ERRORS=$((ERRORS + 1))
fi
echo ""

# Check 3: Memberships (placeholder)
echo -e "3️⃣  Membership Assignments:"
echo -e "   ${YELLOW}⚠ Not implemented yet${NC}"
echo ""

# Check 4: Enrollments (placeholder)
echo -e "4️⃣  Course Enrollments:"
echo -e "   ${YELLOW}⚠ Not implemented yet${NC}"
echo ""

# ═══════════════════════════════════════════════════════════════
# Final Verdict
# ═══════════════════════════════════════════════════════════════

echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
if [ $ERRORS -eq 0 ]; then
  echo -e "${GREEN}✅ ALL CHECKS PASSED - No data integrity issues detected${NC}"
  echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
  exit 0
else
  echo -e "${RED}❌ ALERT: ${ERRORS} data integrity issue(s) detected!${NC}"
  echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
  exit 1
fi
