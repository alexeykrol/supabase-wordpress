#!/bin/bash

# Automatic credential cleanup for dialog exports
# Runs before dialog export to remove sensitive data

echo "🔒 Cleaning credentials from dialog files..."

# Load credentials from .production-credentials (if exists)
if [ -f ".production-credentials" ]; then
  source .production-credentials
fi

DIALOG_DIR="dialog"
CLEANED=0

for file in "$DIALOG_DIR"/*.md; do
  if [ -f "$file" ]; then
    # Clean SSH credentials (using variables or generic patterns)
    sed -i '' 's/[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}/[REDACTED_IP]/g' "$file"
    sed -i '' 's/u[0-9]\{9\}/[REDACTED_USER]/g' "$file"
    sed -i '' 's/\(Port\|port\|PORT\)[: ]*[0-9]\{4,5\}/\1 [REDACTED_PORT]/g' "$file"
    sed -i '' 's/claude_prod[_a-z]*/[REDACTED_KEY]/g' "$file"
    sed -i '' 's/alexeykrol_prod/[REDACTED_KEY]/g' "$file"

    # Clean environment variables
    sed -i '' 's/SSH_HOST=.*/SSH_HOST=[REDACTED]/g' "$file"
    sed -i '' 's/SSH_USER=.*/SSH_USER=[REDACTED]/g' "$file"
    sed -i '' 's/SSH_PORT=.*/SSH_PORT=[REDACTED]/g' "$file"

    # Clean paths with user IDs
    sed -i '' 's|/home/u[0-9]*/|/home/[REDACTED]/|g' "$file"

    # Clean actual SSH keys (if leaked)
    sed -i '' 's/ssh-ed25519 AAAA[A-Za-z0-9+/=]*/[REDACTED_SSH_KEY]/g' "$file"
    sed -i '' 's/ssh-rsa AAAA[A-Za-z0-9+/=]*/[REDACTED_SSH_KEY]/g' "$file"

    ((CLEANED++))
  fi
done

echo "✅ Cleaned $CLEANED dialog files"
