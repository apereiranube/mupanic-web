#!/bin/bash
set -euo pipefail

SOURCE_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DEPLOY_ROOT="/home/mupanic/public_html/beta"
WORK_ROOT="/home/mupanic/tmp/mupanic-webengine-build"
UPSTREAM_REPO="https://github.com/lautaroangelico/WebEngine.git"
UPSTREAM_COMMIT="5cf16f1284abb970e29bde2a937dbec412d1bc94"

CONFIG_REL="includes/config/webengine.json"
PRODUCTION_ROOT="/home/mupanic/public_html"

echo "[MU PANIC] Building beta from WebEngine 1.2.7..."

rm -rf "$WORK_ROOT"
mkdir -p "$WORK_ROOT"

# Preserve the installed WebEngine configuration before copying a clean upstream
# tree. If beta was already overwritten with WebEngine's empty config, seed it
# from the working production installation instead. Secrets never enter Git.
CONFIG_BACKUP="$WORK_ROOT/webengine.json.local"
if [ -s "$DEPLOY_ROOT/$CONFIG_REL" ]; then
  cp "$DEPLOY_ROOT/$CONFIG_REL" "$CONFIG_BACKUP"
  echo "[MU PANIC] Preserved beta WebEngine configuration."
elif [ -s "$PRODUCTION_ROOT/$CONFIG_REL" ]; then
  cp "$PRODUCTION_ROOT/$CONFIG_REL" "$CONFIG_BACKUP"
  echo "[MU PANIC] Beta config missing/empty; using production config as seed."
else
  echo "[MU PANIC] ERROR: No installed WebEngine configuration was found." >&2
  exit 1
fi

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
rm -f \
  "$DEPLOY_ROOT/index.html" \
  "$DEPLOY_ROOT/check.php" \
  "$DEPLOY_ROOT/db-port.php" \
  "$DEPLOY_ROOT/db-test.php"

# Copy code over the current beta installation.
cp -a "$WORK_ROOT/site/." "$DEPLOY_ROOT/"

# Restore the local installation config that upstream ships empty.
mkdir -p "$DEPLOY_ROOT/$(dirname "$CONFIG_REL")"
cp "$CONFIG_BACKUP" "$DEPLOY_ROOT/$CONFIG_REL"

# Beta must use the new MU PANIC presentation while keeping WebEngine as backend.
php -r '
$path = $argv[1];
$config = json_decode(file_get_contents($path), true);
if (!is_array($config)) {
    fwrite(STDERR, "[MU PANIC] ERROR: Invalid WebEngine JSON config.\n");
    exit(1);
}
$config["website_template"] = "mupanic";
$json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if ($json === false || file_put_contents($path, $json . PHP_EOL) === false) {
    fwrite(STDERR, "[MU PANIC] ERROR: Could not update beta template setting.\n");
    exit(1);
}
' "$DEPLOY_ROOT/$CONFIG_REL"

rm -rf "$WORK_ROOT"

echo "[MU PANIC] Beta deployed to $DEPLOY_ROOT"
