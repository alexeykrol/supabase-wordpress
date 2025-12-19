#!/bin/bash

# Clean sensitive data from dialog markdown files
# Removes SSH keys, passwords, tokens, IP addresses

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
DIALOG_DIR="$PROJECT_ROOT/dialog"

echo "════════════════════════════════════════════════════════"
echo "🧹 CLEANING DIALOG FILES"
echo "════════════════════════════════════════════════════════"
echo ""

if [ ! -d "$DIALOG_DIR" ]; then
  echo "❌ Dialog directory not found: $DIALOG_DIR"
  exit 1
fi

CLEANED_COUNT=0
TOTAL_FILES=0

# Process all .md files in dialog/
for file in "$DIALOG_DIR"/*.md; do
  if [ ! -f "$file" ]; then
    continue
  fi

  TOTAL_FILES=$((TOTAL_FILES + 1))
  BACKUP="${file}.backup"

  echo "Processing: $(basename "$file")"

  # Create backup
  cp "$file" "$BACKUP"

  # 1. Remove SSH private key blocks (entire block from BEGIN to END)
  perl -i -p0e 's/-----BEGIN [A-Z ]*PRIVATE KEY-----.*?-----END [A-Z ]*PRIVATE KEY-----/[REDACTED]/gs' "$file"

  # 1b. Clean up old cleanup markers
  perl -i -pe 's/\[SSH PRIVATE KEY REMOVED\]/[REDACTED]/g' "$file"

  # 2. Remove SSH public keys (replace keys, keep context)
  perl -i -pe 's/ssh-ed25519 [A-Za-z0-9+\/=]+/ssh-ed25519 [REDACTED]/g' "$file"
  perl -i -pe 's/ssh-rsa [A-Za-z0-9+\/=]+/ssh-rsa [REDACTED]/g' "$file"
  perl -i -pe 's/ssh-dss [A-Za-z0-9+\/=]+/ssh-dss [REDACTED]/g' "$file"
  perl -i -pe 's/ecdsa-sha2-[^ ]+ [A-Za-z0-9+\/=]+/ecdsa-sha2-nistp256 [REDACTED]/g' "$file"

  # 3. Remove lines with HostName, IdentityFile, Port (SSH config)
  perl -i -ne 'print unless /HostName\s+[0-9\.]+|IdentityFile.*\.ssh|Port\s+[0-9]{4,5}/' "$file"

  # 4. Remove IP addresses (except localhost/private)
  perl -i -pe 's/\b(?!127\.0\.0\.1|192\.168\.|10\.|172\.)\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/XXX.XXX.XXX.XXX/g' "$file"

  # 5. Remove JWT tokens and API keys
  perl -i -pe 's/eyJ[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+/[REDACTED-JWT]/g' "$file"

  # 6. Remove Supabase URLs and keys
  perl -i -pe 's/https:\/\/[a-z0-9]+\.supabase\.co/https:\/\/[PROJECT].supabase.co/g' "$file"
  perl -i -pe 's/SUPABASE_ANON_KEY["\s:=]+[A-Za-z0-9._-]+/SUPABASE_ANON_KEY=[REDACTED]/g' "$file"

  # 6. Remove database connection strings
  sed -i '' '/mysql:\/\//d' "$file"
  sed -i '' '/postgresql:\/\//d' "$file"
  sed -i '' '/mongodb:\/\//d' "$file"

  # Check if file changed
  if ! diff -q "$file" "$BACKUP" > /dev/null 2>&1; then
    CLEANED_COUNT=$((CLEANED_COUNT + 1))
    echo "  ✅ Cleaned"
    rm "$BACKUP"
  else
    echo "  ⏭  No changes needed"
    mv "$BACKUP" "$file"  # Restore from backup
  fi

  echo ""
done

echo "════════════════════════════════════════════════════════"
echo "📊 SUMMARY"
echo "════════════════════════════════════════════════════════"
echo ""
echo "Total files processed: $TOTAL_FILES"
echo "Files cleaned:         $CLEANED_COUNT"
echo "Files unchanged:       $((TOTAL_FILES - CLEANED_COUNT))"
echo ""
echo "✅ Dialog files cleaned!"
echo ""
echo "Next steps:"
echo "1. Run security scan:  bash tests/security-scan.sh"
echo "2. Review cleaned files manually"
echo "3. Commit cleaned files"
echo "4. Clean git history with BFG"
echo ""
