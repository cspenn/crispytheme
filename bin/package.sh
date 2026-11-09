#!/usr/bin/env bash
# Build the release zip of CrispyTheme from the real theme folders.
#
# Usage:
#   bin/package.sh [--out DIR] [--version X.Y.Z] [--slug NAME] [--prebuilt DIR] [--plan] [--list]
#
#   --out DIR        Folder for the zip and the file list. Default: temp/release in the repo (ignored by git).
#   --version X.Y.Z  Version in the file name. Default: the Version line of style.css.
#   --slug NAME      Folder name inside the zip. Default: crispytheme-v2 (the live theme folder).
#   --prebuilt DIR   Copy DIR as build/ and skip npm. Default: build the assets with npm in the staging copy.
#   --plan           Print what the zip holds and what it leaves out, then stop. Builds nothing.
#   --list           Print the whole file list at the end (the default prints a summary by folder).
#
# The tool never changes the working tree. It copies the files into a staging folder under the output
# folder, runs `composer install --no-dev` and `npm run build` only inside the staging copy, and removes
# the staging folder when it finishes. The GitHub release workflow calls this same script.
# It never uploads anything. It writes no file outside the output folder.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# Folders and files the theme needs at run time. functions.php loads inc/ and vendor/autoload.php.
# PatternManager reads block-patterns/. Setup reads languages/ when it exists. Assets reads assets/ and build/.
INCLUDE_DIRS=(assets block-patterns inc parts src templates)
OPTIONAL_DIRS=(languages)
INCLUDE_FILES=(functions.php index.php style.css theme.json LICENSE README.md)
# Never in the zip: tests, notes, inputs, outputs, tool state, and development configuration.
EXCLUDE_NAMES=(tests input output graphify-out docs data node_modules .git .github archived logs references temp
  bin includes .claude .serena .opencode .phpcs.xml .deptrac.cache AGENTS.md CLAUDE.md composer.json composer.lock
  package.json package-lock.json webpack.config.js phpstan.neon rector.php deptrac.yaml patchwork.json opencode.json
  .gitignore '*.zip')
# Inside assets/ the JavaScript sources stay out, because build/ holds their built output.
ASSET_EXCLUDES=('assets/js/src' '*.map' '.DS_Store')
# Files that must exist in the zip, or the build fails.
REQUIRED_PATHS=(functions.php index.php style.css theme.json inc/template-tags.php vendor/autoload.php
  block-patterns/booking-page.php templates/page-public-speaking.html src/Booking/BookingPage.php
  src/Booking/BookingFacts.php assets/css/booking-page.css assets/js/booking-page.js
  assets/images/booking/manifest.json build/dark-mode-toggle.js)

OUT="$ROOT/temp/release"
VERSION=""
SLUG="crispytheme-v2"
PREBUILT=""
PLAN=0
LIST=0

die() { echo "package.sh: $*" >&2; exit 1; }

while [ "$#" -gt 0 ]; do
  case "$1" in
    --out) OUT="${2:?--out needs a folder}"; shift 2 ;;
    --version) VERSION="${2:?--version needs a value}"; shift 2 ;;
    --slug) SLUG="${2:?--slug needs a name}"; shift 2 ;;
    --prebuilt) PREBUILT="${2:?--prebuilt needs a folder}"; shift 2 ;;
    --plan) PLAN=1; shift ;;
    --list) LIST=1; shift ;;
    -h|--help) sed -n '2,17p' "$0"; exit 0 ;;
    *) die "unknown option: $1" ;;
  esac
done

if [ "$PLAN" -eq 1 ]; then
  echo "INCLUDE_DIRS: ${INCLUDE_DIRS[*]}"
  echo "OPTIONAL_DIRS: ${OPTIONAL_DIRS[*]}"
  echo "INCLUDE_FILES: ${INCLUDE_FILES[*]}"
  echo "GENERATED: vendor build"
  echo "EXCLUDE_NAMES: ${EXCLUDE_NAMES[*]}"
  echo "ASSET_EXCLUDES: ${ASSET_EXCLUDES[*]}"
  echo "REQUIRED_PATHS: ${REQUIRED_PATHS[*]}"
  exit 0
fi

for tool in rsync zip composer; do command -v "$tool" >/dev/null || die "$tool is not installed"; done
[ -f "$ROOT/functions.php" ] || die "functions.php not found in $ROOT"
[ -f "$ROOT/composer.json" ] || die "composer.json not found in $ROOT"

if [ -z "$VERSION" ]; then
  VERSION="$(sed -n 's/^Version:[[:space:]]*//p' "$ROOT/style.css" | head -1 | tr -d '[:space:]')"
  [ -n "$VERSION" ] || die "no Version line in style.css"
fi

mkdir -p "$OUT"
OUT="$(cd "$OUT" && pwd)"
case "$OUT" in
  /tmp|/tmp/*|/private/tmp|/private/tmp/*|/var/tmp|/var/tmp/*) die "the output folder must not be under /tmp" ;;
esac
STAGE="$(mktemp -d "$OUT/.stage.XXXXXX")"
trap 'rm -rf "$STAGE"' EXIT
DEST="$STAGE/$SLUG"
mkdir -p "$DEST"

# 1. Copy the real folders and files.
RSYNC_EXCLUDES=()
for pattern in "${ASSET_EXCLUDES[@]}"; do RSYNC_EXCLUDES+=(--exclude="$pattern"); done
for dir in "${INCLUDE_DIRS[@]}"; do
  [ -d "$ROOT/$dir" ] || die "required folder missing: $dir"
  rsync -a "${RSYNC_EXCLUDES[@]}" "$ROOT/$dir" "$DEST/"
done
for dir in "${OPTIONAL_DIRS[@]}"; do
  if [ -d "$ROOT/$dir" ]; then rsync -a "${RSYNC_EXCLUDES[@]}" "$ROOT/$dir" "$DEST/"; fi
done
for file in "${INCLUDE_FILES[@]}"; do
  [ -f "$ROOT/$file" ] || die "required file missing: $file"
  cp "$ROOT/$file" "$DEST/"
done

# 2. Production dependencies, in the staging copy only.
cp "$ROOT/composer.json" "$DEST/"
if [ -f "$ROOT/composer.lock" ]; then cp "$ROOT/composer.lock" "$DEST/"; fi
(cd "$DEST" && composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction --no-progress --no-scripts) \
  || die "composer install failed"
rm -f "$DEST/composer.json" "$DEST/composer.lock"
# Remove what a runtime copy of vendor/ does not need.
find "$DEST/vendor" -type d \( -name .git -o -name .github -o -name tests -o -name Tests -o -name test -o -name docs -o -name doc -o -name bin \) -prune -exec rm -rf {} + 2>/dev/null || true
find "$DEST/vendor" -type f \( -name '*.md' -o -name 'phpunit.xml*' -o -name 'phpstan.neon*' -o -name '.gitignore' \) -delete 2>/dev/null || true

# 3. Built assets.
if [ -n "$PREBUILT" ]; then
  [ -f "$PREBUILT/dark-mode-toggle.js" ] || die "--prebuilt folder holds no dark-mode-toggle.js: $PREBUILT"
  mkdir -p "$DEST/build" && rsync -a --exclude='*.map' "$PREBUILT/" "$DEST/build/"
else
  command -v npm >/dev/null || die "npm is not installed (or pass --prebuilt DIR)"
  BUILD_DIR="$STAGE/assets-build"
  mkdir -p "$BUILD_DIR/assets/js"
  cp "$ROOT/package.json" "$ROOT/webpack.config.js" "$BUILD_DIR/"
  if [ -f "$ROOT/package-lock.json" ]; then cp "$ROOT/package-lock.json" "$BUILD_DIR/"; fi
  rsync -a "$ROOT/assets/js/src" "$BUILD_DIR/assets/js/"
  (
    cd "$BUILD_DIR"
    if [ -f package-lock.json ]; then npm ci --no-audit --no-fund; else npm install --no-audit --no-fund; fi
    npm run build
  ) || die "asset build failed"
  [ -f "$BUILD_DIR/build/dark-mode-toggle.js" ] || die "the build did not write build/dark-mode-toggle.js"
  mkdir -p "$DEST/build" && rsync -a --exclude='*.map' "$BUILD_DIR/build/" "$DEST/build/"
  rm -rf "$BUILD_DIR"
fi

# 4. Check, then zip.
missing=0
for path in "${REQUIRED_PATHS[@]}"; do
  if [ ! -e "$DEST/$path" ]; then echo "package.sh: missing in the zip: $path" >&2; missing=1; fi
done
[ "$missing" -eq 0 ] || die "required files are missing"
for name in "${EXCLUDE_NAMES[@]}"; do
  case "$name" in
    '*'*) continue ;;
  esac
  [ ! -e "$DEST/$name" ] || die "excluded item found in the zip: $name"
done
find "$DEST" -name .DS_Store -delete

ZIP="$OUT/${SLUG}-${VERSION}.zip"
FILES="$OUT/${SLUG}-${VERSION}.files.txt"
rm -f "$ZIP"
(cd "$STAGE" && zip -rqX "$ZIP" "$SLUG")
unzip -Z1 "$ZIP" | sort > "$FILES"

echo "zip:   $ZIP"
echo "files: $FILES ($(wc -l < "$FILES" | tr -d ' ') entries)"
echo "size:  $(du -h "$ZIP" | cut -f1)"
echo "top level:"
awk -F/ 'NF>1 && $2 != "" {print $2}' "$FILES" | sort | uniq -c | sort -rn | awk '{printf "  %7d  %s\n", $1, $2}'
if [ "$LIST" -eq 1 ]; then cat "$FILES"; fi
