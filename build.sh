#!/bin/bash

# 84EM Consent Banner Build Script
# Builds minified CSS and JS files with source maps and creates installable ZIP

set -e

echo "🔨 Building 84EM Consent Banner..."

# Configuration
PLUGIN_NAME="84em-consent"
BUILD_DIR="build"
VERSION=$(grep "Version:" 84em-consent.php | awk '{print $3}')

# Check if npm is installed
if ! command -v npm &> /dev/null; then
    echo "❌ npm is not installed. Please install Node.js and npm first."
    exit 1
fi

# Install dependencies if needed
if [ ! -d "node_modules" ]; then
    echo "📦 Installing dependencies..."
    npm install
fi

# Clean old build files
echo "🧹 Cleaning old build files..."
npm run clean
rm -rf "$BUILD_DIR"
rm -f "${PLUGIN_NAME}.zip"
rm -f "${PLUGIN_NAME}-*.zip"

# Build CSS
echo "🎨 Minifying CSS..."
npm run build:css

# Build JS
echo "📜 Minifying JavaScript..."
npm run build:js

# Check if files were created
if [ ! -f "assets/consent.min.css" ] || [ ! -f "assets/consent.min.js" ]; then
    echo "❌ Build failed - minified files not created"
    exit 1
fi

# Create build directory
echo "📁 Creating build directory..."
mkdir -p "$BUILD_DIR/$PLUGIN_NAME"

# Copy necessary files
echo "📋 Copying plugin files..."
cp -r assets "$BUILD_DIR/$PLUGIN_NAME/"
cp 84em-consent.php "$BUILD_DIR/$PLUGIN_NAME/"
cp README.md "$BUILD_DIR/$PLUGIN_NAME/"

# Remove source files from build (keep only minified)
rm -f "$BUILD_DIR/$PLUGIN_NAME/assets/consent.css"
rm -f "$BUILD_DIR/$PLUGIN_NAME/assets/consent.js"

# Create ZIP file
echo "📦 Creating ZIP archive..."
cd "$BUILD_DIR"
zip -r "../${PLUGIN_NAME}-${VERSION}.zip" "$PLUGIN_NAME" -q
cd ..

# Clean up build directory
rm -rf "$BUILD_DIR"

# Final output
echo ""
echo "✅ Build complete!"
echo ""
echo "📦 Plugin package created:"
echo "  - ${PLUGIN_NAME}-${VERSION}.zip"
echo ""
echo "File size:"
ls -lh "${PLUGIN_NAME}-${VERSION}.zip" | awk '{print "  - " $5}'
echo ""
echo "To install:"
echo "  1. Go to WordPress Admin → Plugins → Add New"
echo "  2. Click 'Upload Plugin'"
echo "  3. Choose ${PLUGIN_NAME}-${VERSION}.zip"
echo "  4. Click 'Install Now' and activate"