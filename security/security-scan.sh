#!/bin/bash

# Security Scan Script
# Finds exposed credentials in dialog files and source code

set -e  # Exit on error

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"

REPORT_FILE="security/reports/security-scan-$(date +%Y-%m-%d-%H-%M-%S).txt"
TEMP_FILE="/tmp/security-scan-$$.txt"

echo "════════════════════════════════════════════════════════"
echo "🔒 SECURITY SCAN"
echo "════════════════════════════════════════════════════════"
echo "Project: Supabase Bridge"
echo "Started: $(date)"
echo "════════════════════════════════════════════════════════"
echo ""

# Initialize counters
CRITICAL_COUNT=0
HIGH_COUNT=0
MEDIUM_COUNT=0

# Create reports directory
mkdir -p security/reports

# Function to report finding
report_finding() {
  local severity=$1
  local category=$2
  local file=$3
  local line=$4
  local content=$5

  case $severity in
    CRITICAL) ((CRITICAL_COUNT++)) ;;
    HIGH) ((HIGH_COUNT++)) ;;
    MEDIUM) ((MEDIUM_COUNT++)) ;;
  esac

  echo "[$severity] $category" >> "$TEMP_FILE"
  echo "  File: $file:$line" >> "$TEMP_FILE"
  echo "  Content: ${content:0:80}..." >> "$TEMP_FILE"
  echo "" >> "$TEMP_FILE"
}

# Check if dialog/ is gitignored
SKIP_DIALOG_SCAN=false
if [ -f ".gitignore" ] && grep -q "^dialog/" ".gitignore" 2>/dev/null; then
  SKIP_DIALOG_SCAN=true
  echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
  echo "ℹ️  STEP 1: Skipping dialog/ scan (gitignored)"
  echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
  echo ""
  echo "✅ dialog/ folder is in .gitignore - excluded from GitHub"
  echo "✅ Local dialog files are safe (not pushed to repository)"
  echo ""
fi

if [ "$SKIP_DIALOG_SCAN" = false ]; then
  echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
  echo "📋 STEP 1: Scanning dialog/ for SSH credentials"
  echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
  echo ""

# 1. SSH Private Keys
echo "🔍 Checking for SSH private keys..."
if grep -rn "PRIVATE KEY\|BEGIN.*KEY.*PRIVATE\|BEGIN OPENSSH\|BEGIN RSA\|BEGIN DSA\|BEGIN EC" dialog/ --include="*.md" | grep -v "\[REDACTED\]" > /tmp/ssh-private.txt 2>/dev/null; then
  while IFS=: read -r file line content; do
    report_finding "CRITICAL" "SSH Private Key" "$file" "$line" "$content"
  done < /tmp/ssh-private.txt
  echo "  🚨 CRITICAL: Found $(wc -l < /tmp/ssh-private.txt) SSH private key occurrences"
else
  echo "  ✅ No SSH private keys found"
fi
echo ""

# 2. SSH Public Keys
echo "🔍 Checking for SSH public keys..."
if grep -rn "ssh-ed25519\|ssh-rsa\|ssh-dss\|ecdsa-sha2" dialog/ --include="*.md" | grep -v "\[REDACTED\]" > /tmp/ssh-public.txt 2>/dev/null; then
  while IFS=: read -r file line content; do
    report_finding "HIGH" "SSH Public Key" "$file" "$line" "$content"
  done < /tmp/ssh-public.txt
  echo "  ⚠️  HIGH: Found $(wc -l < /tmp/ssh-public.txt) SSH public key occurrences"
else
  echo "  ✅ No SSH public keys found"
fi
echo ""

# 3. SSH Configuration
echo "🔍 Checking for SSH configuration..."
if grep -rn "HostName\|IdentityFile\|Port [0-9]\{4,5\}\|ProxyCommand" dialog/ --include="*.md" > /tmp/ssh-config.txt 2>/dev/null; then
  while IFS=: read -r file line content; do
    report_finding "HIGH" "SSH Configuration" "$file" "$line" "$content"
  done < /tmp/ssh-config.txt
  echo "  ⚠️  HIGH: Found $(wc -l < /tmp/ssh-config.txt) SSH config occurrences"
else
  echo "  ✅ No SSH configuration found"
fi
echo ""

# 4. IP Addresses
echo "🔍 Checking for IP addresses..."
if grep -rn "\b[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}\b" dialog/ --include="*.md" | grep -v "192.168\|127.0.0.1\|0.0.0.0" > /tmp/ip-addresses.txt 2>/dev/null; then
  while IFS=: read -r file line content; do
    report_finding "MEDIUM" "Public IP Address" "$file" "$line" "$content"
  done < /tmp/ip-addresses.txt
  echo "  📋 MEDIUM: Found $(wc -l < /tmp/ip-addresses.txt) public IP addresses"
else
  echo "  ✅ No public IP addresses found"
fi
echo ""

# 5. Passwords and Tokens
echo "🔍 Checking for passwords and tokens..."
if grep -rin "password.*:\|pass.*=\|pwd.*=\|token.*:\|bearer.*:\|authorization.*:" dialog/ --include="*.md" | grep -v "\[REDACTED\]" > /tmp/passwords.txt 2>/dev/null; then
  while IFS=: read -r file line content; do
    report_finding "CRITICAL" "Password/Token" "$file" "$line" "$content"
  done < /tmp/passwords.txt
  echo "  🚨 CRITICAL: Found $(wc -l < /tmp/passwords.txt) password/token occurrences"
else
  echo "  ✅ No passwords or tokens found"
fi
echo ""

# 6. Database Credentials
echo "🔍 Checking for database credentials..."
if grep -rin "DB_PASSWORD\|DATABASE_URL\|mysql://\|postgresql://\|mongodb://" dialog/ --include="*.md" > /tmp/db-creds.txt 2>/dev/null; then
  while IFS=: read -r file line content; do
    report_finding "CRITICAL" "Database Credentials" "$file" "$line" "$content"
  done < /tmp/db-creds.txt
  echo "  🚨 CRITICAL: Found $(wc -l < /tmp/db-creds.txt) database credential occurrences"
else
  echo "  ✅ No database credentials found"
fi
echo ""

fi  # End of SKIP_DIALOG_SCAN check

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📋 STEP 2: Scanning source code for hardcoded secrets"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# 7. Hardcoded secrets in PHP/JS/TS
echo "🔍 Checking source code for hardcoded secrets..."
if grep -rn "password.*=.*['\"].\{8,\}['\"\;]\|api.*key.*=.*['\"].\{20,\}['\"\;]\|secret.*=.*['\"].\{8,\}['\"\;]" . --include="*.php" --include="*.js" --include="*.ts" --exclude-dir=node_modules --exclude-dir=vendor --exclude-dir=dialog > /tmp/hardcoded.txt 2>/dev/null; then
  while IFS=: read -r file line content; do
    report_finding "HIGH" "Hardcoded Secret" "$file" "$line" "$content"
  done < /tmp/hardcoded.txt
  echo "  ⚠️  HIGH: Found $(wc -l < /tmp/hardcoded.txt) hardcoded secrets"
else
  echo "  ✅ No hardcoded secrets found"
fi
echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📊 SUMMARY"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Critical Issues: $CRITICAL_COUNT"
echo "High Severity:   $HIGH_COUNT"
echo "Medium Severity: $MEDIUM_COUNT"
echo "Total Issues:    $((CRITICAL_COUNT + HIGH_COUNT + MEDIUM_COUNT))"
echo ""

# Write full report
cat > "$REPORT_FILE" <<EOF
════════════════════════════════════════════════════════
🔒 SECURITY SCAN REPORT
════════════════════════════════════════════════════════
Project: Supabase Bridge
Date: $(date)
════════════════════════════════════════════════════════

SUMMARY
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Critical Issues: $CRITICAL_COUNT
High Severity:   $HIGH_COUNT
Medium Severity: $MEDIUM_COUNT
Total Issues:    $((CRITICAL_COUNT + HIGH_COUNT + MEDIUM_COUNT))

FINDINGS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

EOF

if [ -f "$TEMP_FILE" ]; then
  cat "$TEMP_FILE" >> "$REPORT_FILE"
else
  echo "✅ No security issues found!" >> "$REPORT_FILE"
fi

echo "Report saved: $REPORT_FILE"
echo ""

# Cleanup
rm -f /tmp/ssh-*.txt /tmp/ip-addresses.txt /tmp/passwords.txt /tmp/db-creds.txt /tmp/hardcoded.txt "$TEMP_FILE"

# Exit with error if critical or high issues found
if [ $CRITICAL_COUNT -gt 0 ] || [ $HIGH_COUNT -gt 0 ]; then
  echo "🚨 SECURITY SCAN FAILED"
  echo ""
  exit 1
else
  echo "✅ SECURITY SCAN PASSED"
  echo ""
  exit 0
fi
