#!/bin/bash
set -euo pipefail

SOURCE_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DEPLOY_ROOT="/home/mupanic/public_html"

TEMPLATE_SOURCE="$SOURCE_ROOT/overlay/templates/mupanic"
TEMPLATE_DEST="$DEPLOY_ROOT/templates/mupanic"

TOS_SOURCE="$SOURCE_ROOT/overlay/modules/tos.php"
TOS_DEST="$DEPLOY_ROOT/modules/tos.php"

echo "[MU PANIC] Deploying production overlay..."

if [ ! -f "$DEPLOY_ROOT/index.php" ]; then
  echo "[MU PANIC] ERROR: WebEngine core not found in production root." >&2
  exit 1
fi

if [ ! -f "$TEMPLATE_SOURCE/index.php" ] || [ ! -f "$TEMPLATE_SOURCE/css/style.css" ] || [ ! -f "$TEMPLATE_SOURCE/js/main.js" ]; then
  echo "[MU PANIC] ERROR: Incomplete template overlay." >&2
  exit 1
fi

if [ ! -f "$TOS_SOURCE" ]; then
  echo "[MU PANIC] ERROR: Terms module not found in overlay." >&2
  exit 1
fi

# Production deploy intentionally excludes live config, SQL credentials,
# runtime data and the /beta environment.
if [ -L "$DEPLOY_ROOT/templates" ] || [ -L "$TEMPLATE_DEST" ] || [ -L "$DEPLOY_ROOT/modules" ] || [ -L "$TOS_DEST" ]; then
  echo "[MU PANIC] ERROR: Deployment destinations must not be symlinks." >&2
  exit 1
fi

if find "$TEMPLATE_SOURCE" -type l -print -quit | /bin/grep -q .; then
  echo "[MU PANIC] ERROR: Symlinks are not allowed in the template overlay." >&2
  exit 1
fi

if [ -d "$TEMPLATE_DEST" ] && find "$TEMPLATE_DEST" -type l -print -quit | /bin/grep -q .; then
  echo "[MU PANIC] ERROR: Symlinks are not allowed in the template destination." >&2
  exit 1
fi

mkdir -p "$TEMPLATE_DEST" "$DEPLOY_ROOT/modules"
cp -a "$TEMPLATE_SOURCE/." "$TEMPLATE_DEST/"
cp -a "$TOS_SOURCE" "$TOS_DEST"

echo "[MU PANIC] Production overlay deployed successfully."
