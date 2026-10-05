#!/bin/bash
set -euo pipefail

SOURCE_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DEPLOY_ROOT="/home/mupanic/public_html/beta"
TEMPLATE_SOURCE="$SOURCE_ROOT/overlay/templates/mupanic"
TEMPLATE_DEST="$DEPLOY_ROOT/templates/mupanic"

echo "[MU PANIC] Deploying beta template overlay..."
if [ ! -f "$DEPLOY_ROOT/index.php" ]; then
  echo "[MU PANIC] ERROR: WebEngine core not found in beta." >&2
  exit 1
fi
if [ ! -f "$TEMPLATE_SOURCE/index.php" ] || [ ! -f "$TEMPLATE_SOURCE/css/style.css" ] || [ ! -f "$TEMPLATE_SOURCE/js/main.js" ]; then
  echo "[MU PANIC] ERROR: Incomplete template overlay." >&2
  exit 1
fi
# A strict template-only destination: never read, rewrite or copy live config.
if [ -L "$DEPLOY_ROOT/templates" ] || [ -L "$TEMPLATE_DEST" ]; then
  echo "[MU PANIC] ERROR: Template destination must not be a symlink." >&2
  exit 1
fi
if find "$TEMPLATE_SOURCE" -type l -print -quit | /bin/grep -q .; then
  echo "[MU PANIC] ERROR: Symlinks are not allowed in the overlay." >&2
  exit 1
fi
# Reject destination symlinks so recursive copy cannot follow one into config.
if [ -d "$TEMPLATE_DEST" ] && find "$TEMPLATE_DEST" -type l -print -quit | /bin/grep -q .; then
  echo "[MU PANIC] ERROR: Symlinks are not allowed in the template destination." >&2
  exit 1
fi
mkdir -p "$TEMPLATE_DEST"
cp -a "$TEMPLATE_SOURCE/." "$TEMPLATE_DEST/"
echo "[MU PANIC] Beta template overlay deployed successfully."
