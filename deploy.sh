#!/usr/bin/env bash
# Post-upload steps after deploying (e.g. zip + FTP extract to project root).
# Usage: from project root, run: bash deploy.sh   or: ./deploy.sh

set -e

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT"

echo "==> php artisan migrate --force"
php artisan migrate --force

echo "==> php artisan optimize:clear"
php artisan optimize:clear

echo "==> php artisan storage:link"
php artisan storage:link

echo "==> php artisan config:cache (with ASSET_VERSION for static cache busting)"
if GIT_SHA="$(git rev-parse --short HEAD 2>/dev/null)" && [[ -n "$GIT_SHA" ]]; then
  export ASSET_VERSION="$GIT_SHA"
  echo "    ASSET_VERSION from git: $ASSET_VERSION"
else
  export ASSET_VERSION="$(date -u +%Y%m%d%H%M%S)"
  echo "    No .git here (typical after FTP upload); ASSET_VERSION=timestamp: $ASSET_VERSION"
fi
php artisan config:cache

echo "==> Deploy finished OK (ASSET_VERSION is baked into config cache)."
