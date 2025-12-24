#!/bin/bash
# Download IBM Plex fonts in woff2 format
# Run this script from the fonts directory

set -e

FONT_DIR="$(dirname "$0")/ibm-plex"
mkdir -p "$FONT_DIR"

echo "Downloading IBM Plex fonts..."

# IBM Plex Sans (body text)
# Regular weights: 300, 400, 500, 600, 700
PLEX_SANS_BASE="https://cdn.jsdelivr.net/npm/@fontsource/ibm-plex-sans@5.0.8/files"
PLEX_SANS_WEIGHTS="300 400 500 600 700"

for weight in $PLEX_SANS_WEIGHTS; do
    echo "  Downloading IBM Plex Sans $weight..."
    curl -sL "${PLEX_SANS_BASE}/ibm-plex-sans-latin-${weight}-normal.woff2" -o "${FONT_DIR}/ibm-plex-sans-${weight}.woff2"
    curl -sL "${PLEX_SANS_BASE}/ibm-plex-sans-latin-${weight}-italic.woff2" -o "${FONT_DIR}/ibm-plex-sans-${weight}-italic.woff2" 2>/dev/null || true
done

# IBM Plex Serif (headings)
PLEX_SERIF_BASE="https://cdn.jsdelivr.net/npm/@fontsource/ibm-plex-serif@5.0.8/files"
PLEX_SERIF_WEIGHTS="300 400 500 600 700"

for weight in $PLEX_SERIF_WEIGHTS; do
    echo "  Downloading IBM Plex Serif $weight..."
    curl -sL "${PLEX_SERIF_BASE}/ibm-plex-serif-latin-${weight}-normal.woff2" -o "${FONT_DIR}/ibm-plex-serif-${weight}.woff2"
    curl -sL "${PLEX_SERIF_BASE}/ibm-plex-serif-latin-${weight}-italic.woff2" -o "${FONT_DIR}/ibm-plex-serif-${weight}-italic.woff2" 2>/dev/null || true
done

# IBM Plex Mono (code)
PLEX_MONO_BASE="https://cdn.jsdelivr.net/npm/@fontsource/ibm-plex-mono@5.0.8/files"
PLEX_MONO_WEIGHTS="400 500 600 700"

for weight in $PLEX_MONO_WEIGHTS; do
    echo "  Downloading IBM Plex Mono $weight..."
    curl -sL "${PLEX_MONO_BASE}/ibm-plex-mono-latin-${weight}-normal.woff2" -o "${FONT_DIR}/ibm-plex-mono-${weight}.woff2"
done

echo ""
echo "Font download complete!"
echo "Files saved to: $FONT_DIR"
ls -la "$FONT_DIR"
