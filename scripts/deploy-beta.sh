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

# Page artwork must be present before any deployment copy.
for asset in server-world server-progression server-atlas server-events server-portals; do
  if [ ! -s "$TEMPLATE_SOURCE/img/server/$asset.webp" ]; then
    echo "[MU PANIC] ERROR: Missing page artwork: $asset.webp" >&2
    exit 1
  fi
done

# System artwork is shipped as real WebP binaries; reject incomplete releases before copying.
SYSTEM_ART_FILES=(
  "chronicles-d6291c69cf6b.webp"
  "hero-path-5331edd1e310.webp"
  "daily-e36b93392874.webp"
  "fortune-a300dc40b62a.webp"
  "vault-47074095857a.webp"
  "vip-66179e5e17f8.webp"
  "nexus-4a6cbef57b72.webp"
  "hub-f11-03b5aefe024d.webp"
)
for asset in "${SYSTEM_ART_FILES[@]}"; do
  if [ ! -s "$TEMPLATE_SOURCE/img/server/systems/$asset" ]; then
    echo "[MU PANIC] ERROR: Missing system artwork: $asset" >&2
    exit 1
  fi
done

# The Atlas redesign ships real binary artwork with the overlay.
for atlas_art in atlas-chamber blood-castle devil-square pandora pvp-arena imperial-temple atlas-territories atlas-medusa; do
  if [ ! -s "$TEMPLATE_SOURCE/img/atlas/editorial/$atlas_art.webp" ]; then
    echo "[MU PANIC] ERROR: Missing Atlas artwork: $atlas_art" >&2
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

# Remove the replaced, unversioned artwork only after the new template/modules are copied.
# Content-versioned filenames prevent browsers and CDNs from serving the previous low-res art.
rm -f "$TEMPLATE_DEST/img/server/systems/nexus-5696e2bab745.webp"
for legacy in chronicles hero-path daily fortune vault vip nexus; do
  rm -f "$TEMPLATE_DEST/img/server/systems/$legacy.webp"
done

echo "[MU PANIC] Beta overlay deployed successfully."
