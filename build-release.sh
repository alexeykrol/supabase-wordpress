#!/bin/bash

# Build script for Supabase Bridge WordPress Plugin
# Creates a production-ready ZIP file for WordPress installation

set -e

VERSION="0.8.1"
PLUGIN_NAME="supabase-bridge"
BUILD_DIR="build"
RELEASE_NAME="${PLUGIN_NAME}-v${VERSION}"

echo "🚀 Building ${RELEASE_NAME}..."

# Clean previous builds
rm -rf ${BUILD_DIR}
mkdir -p ${BUILD_DIR}/${PLUGIN_NAME}

echo "📦 Copying plugin files..."

# Copy main plugin files
cp supabase-bridge.php ${BUILD_DIR}/${PLUGIN_NAME}/
cp auth-form.html ${BUILD_DIR}/${PLUGIN_NAME}/
cp LICENSE ${BUILD_DIR}/${PLUGIN_NAME}/
cp README.md ${BUILD_DIR}/${PLUGIN_NAME}/

# Copy SQL files
cp supabase-tables.sql ${BUILD_DIR}/${PLUGIN_NAME}/
cp SECURITY_RLS_POLICIES_FINAL.sql ${BUILD_DIR}/${PLUGIN_NAME}/

# Copy documentation (production guides only)
cp QUICK_SETUP_CHECKLIST.md ${BUILD_DIR}/${PLUGIN_NAME}/
cp PRODUCTION_SETUP.md ${BUILD_DIR}/${PLUGIN_NAME}/
cp SECURITY_ROLLBACK_SUMMARY.md ${BUILD_DIR}/${PLUGIN_NAME}/

# Copy vendor directory (already has production dependencies)
echo "📚 Copying PHP dependencies (vendor/)..."
cp -r vendor ${BUILD_DIR}/${PLUGIN_NAME}/

echo "🗑️  Removing macOS junk files..."
find ${BUILD_DIR}/${PLUGIN_NAME} -name ".DS_Store" -delete

echo "📝 Creating ZIP archive..."
cd ${BUILD_DIR}
zip -r ../${RELEASE_NAME}.zip ${PLUGIN_NAME} -q
cd ..

echo "🧹 Cleaning up..."
rm -rf ${BUILD_DIR}

echo "✅ Build complete!"
echo "📦 Release file: ${RELEASE_NAME}.zip"
echo "📊 Size: $(du -h ${RELEASE_NAME}.zip | cut -f1)"
echo ""
echo "🚀 Ready for WordPress installation!"
echo "   Upload via: WordPress Admin → Plugins → Add New → Upload Plugin"
