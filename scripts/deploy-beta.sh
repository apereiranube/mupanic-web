#!/bin/bash
set -euo pipefail

SOURCE_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DEPLOY_ROOT="/home/mupanic/public_html/beta"

TEMPLATE_SOURCE="$SOURCE_ROOT/overlay/templates/mupanic"
TEMPLATE_DEST="$DEPLOY_ROOT/templates/mupanic"

MODULE_SOURCE="$SOURCE_ROOT/overlay/modules"
MODULE_DEST="$DEPLOY_ROOT/modules"
DEPLOY_MODULES=("tos.php" "privacy.php" "refunds.php" "information.php")

echo "[MU PANIC] Deploying beta overlay..."

if [ ! -f "$DEPLOY_ROOT/index.php" ]; then
  echo "[MU PANIC] ERROR: WebEngine core not found in beta." >&2
  exit 1
fi

if [ ! -f "$TEMPLATE_SOURCE/index.php" ] || [ ! -f "$TEMPLATE_SOURCE/css/style.css" ] || [ ! -f "$TEMPLATE_SOURCE/js/main.js" ]; then
  echo "[MU PANIC] ERROR: Incomplete template overlay." >&2
  exit 1
fi

for module in "${DEPLOY_MODULES[@]}"; do
  if [ ! -f "$MODULE_SOURCE/$module" ]; then
    echo "[MU PANIC] ERROR: Missing overlay module: $module" >&2
    exit 1
  fi
done

# Never read, rewrite or copy live config or credentials.
if [ -L "$DEPLOY_ROOT/templates" ] || [ -L "$TEMPLATE_DEST" ] || [ -L "$DEPLOY_ROOT/modules" ]; then
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

mkdir -p "$TEMPLATE_DEST" "$MODULE_DEST"

# Keep the approved hero artwork that is managed directly on the server.
# This prevents Git deploys from restoring the older cropped source image.
HERO_DEST="$TEMPLATE_DEST/img/knight-v6.webp"
HERO_KEEP="/home/mupanic/.mupanic-beta-knight-v6.keep"
HERO_PRESENT=0
if [ -f "$HERO_DEST" ]; then
  cp -a "$HERO_DEST" "$HERO_KEEP"
  HERO_PRESENT=1
fi

cp -a "$TEMPLATE_SOURCE/." "$TEMPLATE_DEST/"

if [ "$HERO_PRESENT" -eq 1 ] && [ -f "$HERO_KEEP" ]; then
  cp -a "$HERO_KEEP" "$HERO_DEST"
  rm -f "$HERO_KEEP"
fi

for module in "${DEPLOY_MODULES[@]}"; do
  cp -a "$MODULE_SOURCE/$module" "$MODULE_DEST/$module"
done

# Decode visual assets committed as base64 text so cPanel/Git can carry binary artwork.
SYSTEM_ART_SOURCE="$SOURCE_ROOT/overlay/assets/system-art"
SYSTEM_ART_DEST="$TEMPLATE_DEST/img/server/systems"
SYSTEM_ART_FILES=("chronicles" "hero-path" "daily" "fortune" "vault" "vip" "nexus")
mkdir -p "$SYSTEM_ART_DEST"

for asset in "${SYSTEM_ART_FILES[@]}"; do
  src="$SYSTEM_ART_SOURCE/$asset.webp.b64"
  dst="$SYSTEM_ART_DEST/$asset.webp"
  if [ ! -f "$src" ]; then
    echo "[MU PANIC] ERROR: Missing system artwork source: $src" >&2
    exit 1
  fi
  /usr/bin/base64 -d "$src" > "$dst"
done

echo "[MU PANIC] Beta overlay deployed successfully."
