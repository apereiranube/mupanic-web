#!/bin/bash
set -euo pipefail

SOURCE_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DEPLOY_ROOT="/home/mupanic/public_html/beta"
WORK_ROOT="/home/mupanic/tmp/mupanic-webengine-build"
UPSTREAM_REPO="https://github.com/lautaroangelico/WebEngine.git"
UPSTREAM_COMMIT="5cf16f1284abb970e29bde2a937dbec412d1bc94"

echo "[MU PANIC] Building beta from WebEngine 1.2.7..."

rm -rf "$WORK_ROOT"
mkdir -p "$WORK_ROOT"

git clone --quiet "$UPSTREAM_REPO" "$WORK_ROOT/site"
cd "$WORK_ROOT/site"
git checkout --quiet "$UPSTREAM_COMMIT"

rm -rf .git .github

if [ -d "$SOURCE_ROOT/overlay" ]; then
  cp -a "$SOURCE_ROOT/overlay/." "$WORK_ROOT/site/"
fi

cp "$SOURCE_ROOT/UPSTREAM.md" "$WORK_ROOT/site/MU_PANIC_UPSTREAM.md"

mkdir -p "$DEPLOY_ROOT"

# Remove only temporary test files from initial hosting checks.
rm -f   "$DEPLOY_ROOT/index.html"   "$DEPLOY_ROOT/check.php"   "$DEPLOY_ROOT/db-port.php"   "$DEPLOY_ROOT/db-test.php"

# Copy code over the current beta installation.
# Files created by WebEngine at runtime (including local config/secrets)
# are intentionally not deleted by this deployment.
cp -a "$WORK_ROOT/site/." "$DEPLOY_ROOT/"

rm -rf "$WORK_ROOT"

echo "[MU PANIC] Beta deployed to $DEPLOY_ROOT"
