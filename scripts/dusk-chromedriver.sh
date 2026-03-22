#!/usr/bin/env bash
# Download Chrome for Testing chromedriver matching the installed Google Chrome.
# Laravel Dusk's `php artisan dusk:chrome-driver --detect` uses the legacy
# Google Storage API (Chrome ≤114). Modern Chrome needs this script once per machine.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$ROOT/storage/dusk/chromedriver"
CHROME_BIN="${CHROME_BIN:-/Applications/Google Chrome.app/Contents/MacOS/Google Chrome}"

if [[ ! -x "$CHROME_BIN" ]]; then
  echo "Chrome not found at: $CHROME_BIN" >&2
  echo "Set CHROME_BIN to your chrome executable." >&2
  exit 1
fi

VER="$("$CHROME_BIN" --version 2>/dev/null | awk '{print $3}')"
if [[ -z "$VER" ]]; then
  echo "Could not read Chrome version." >&2
  exit 1
fi

ARCH="$(uname -m)"
case "$ARCH" in
  arm64) PLATFORM=mac-arm64 ;;
  x86_64) PLATFORM=mac-x64 ;;
  *)
    echo "Unsupported architecture: $ARCH" >&2
    exit 1
    ;;
esac

URL="https://storage.googleapis.com/chrome-for-testing-public/${VER}/${PLATFORM}/chromedriver-${PLATFORM}.zip"
TMP="$ROOT/storage/dusk/chromedriver.zip"

mkdir -p "$ROOT/storage/dusk"
echo "Downloading chromedriver $VER ($PLATFORM)..."
curl -fsSL "$URL" -o "$TMP"
rm -f "$OUT"
unzip -q -o "$TMP" -d "$ROOT/storage/dusk"
rm -f "$TMP"

BIN="$(find "$ROOT/storage/dusk" -maxdepth 3 -type f -name chromedriver 2>/dev/null | head -1)"
if [[ -z "$BIN" ]]; then
  echo "chromedriver binary not found after unzip." >&2
  exit 1
fi

mv "$BIN" "$OUT"
chmod +x "$OUT"
# remove empty dirs left by unzip
find "$ROOT/storage/dusk" -mindepth 1 -maxdepth 1 -type d -exec rm -rf {} + 2>/dev/null || true

echo "Installed: $OUT"
"$OUT" --version
