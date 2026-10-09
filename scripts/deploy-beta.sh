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

# The Eryns storefront and its original artwork must arrive together.
if [ ! -s "$TEMPLATE_SOURCE/img/recharge/eryns-legends-v3.webp" ]; then
  echo "[MU PANIC] ERROR: Missing Eryns storefront artwork." >&2
  exit 1
fi

# The Vínculos guide and its illustrated bestiary must arrive together.
for asset in eryns-pack-pouch.webp eryns-pack-chest.webp eryns-pack-legendary.webp theryon-scene-v5.webp nerathys-scene-v5.webp vaeraxes-scene-v5.webp vip-champion-scene-v5.webp vip-insignia-v5.svg vip-benefit-crown-v5.svg vip-benefit-shield-v5.svg vip-benefit-star-v5.svg; do
  test -s "$TEMPLATE_SOURCE/img/recharge/$asset"
done
test -s "$TEMPLATE_SOURCE/inc/recharge-storefront.json"
test -s "$TEMPLATE_SOURCE/inc/recharge-vip-status.php"
test -s "$TEMPLATE_SOURCE/inc/recharge-vip-showcase.php"
test -s "$TEMPLATE_SOURCE/inc/recharge-admin-storefront.php"
test -s "$TEMPLATE_SOURCE/js/recharge-admin.js"

for file in atlas-vinculos.php atlas-vinculos.json; do
  test -s "$TEMPLATE_SOURCE/inc/$file"
done
for portrait in aelira-cinematic sylthara-cinematic elyndra-cinematic aurik-cinematic aurion-cinematic vaeryn-cinematic vaerath-cinematic vaerion-cinematic grum-cinematic vaelkar-cinematic aurethia-cinematic theryon-cinematic nerathys-cinematic vaeraxes-cinematic sanctum-cinematic ritual-cinematic fragmentos nucleos luck-hd assembly-hd; do
  if [ ! -s "$TEMPLATE_SOURCE/img/atlas/vinculos/$portrait.webp" ]; then
    echo "[MU PANIC] ERROR: Missing Vínculos portrait: $portrait" >&2
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

# Every territory card ships its own content-versioned WebP.
TERRITORY_ART_FILES=(
  "lorencia-a0c7cfc8d4f1.webp"
  "dungeon-a4ae5d86647a.webp"
  "devias-7bb43c3f8fd1.webp"
  "noria-cda7ef08ff22.webp"
  "lost-tower-a17ab00289fb.webp"
  "stadium-2a3dd9b01bdd.webp"
  "atlans-b70c14f4f5b2.webp"
  "tarkan-ea9b287ce5f4.webp"
  "icarus-7c88f7429743.webp"
  "castle-siege-b96e1c2a4f6f.webp"
  "land-trials-ce95d57b3ca9.webp"
  "aida-460a107b4d30.webp"
  "crywolf-b07007514659.webp"
  "kanturu-one-4e57dfdf4277.webp"
  "kanturu-two-b153450cca63.webp"
  "kanturu-three-93b2fd9c911e.webp"
  "silent-02f6e83d349a.webp"
  "barracks-eb855c5573d5.webp"
  "refuge-f88a3edb257d.webp"
  "elbeland-412b5ae6d76c.webp"
  "swamp-f4ef9d7e0139.webp"
  "lacleon-fa9be9d4f42f.webp"
  "raklion-two-cdb28c27c8b8.webp"
  "santa-town-e2f475f2ef05.webp"
  "vulcanus-9c56bc9896a7.webp"
  "duel-arena-33f9e40fce7f.webp"
  "loren-market-c973033b848d.webp"
  "karutan-one-d966f9ca7313.webp"
  "karutan-two-7ba3a11e9003.webp"
  "arkania-0f0c599a0e1c.webp"
  "lorencia-768-486e181876d1.webp"
  "dungeon-768-1ab8624ddcae.webp"
  "devias-768-9fe4bdc1759a.webp"
  "noria-768-db546482c17f.webp"
  "lost-tower-768-a5f4035be505.webp"
  "stadium-768-25cd13b13989.webp"
  "atlans-768-940c531ce130.webp"
  "tarkan-768-bd7032836fef.webp"
  "icarus-768-209e2c1128d3.webp"
  "castle-siege-768-8e2b9831da3f.webp"
  "land-trials-768-d20a8cbf24f0.webp"
  "aida-768-bfe4043fd7bd.webp"
  "crywolf-768-3e7a1e403f10.webp"
  "kanturu-one-768-f5424ca77a38.webp"
  "kanturu-two-768-5db1ff6d12bd.webp"
  "kanturu-three-768-5e586727b13a.webp"
  "silent-768-9e44b61840d4.webp"
  "barracks-768-82285da5aced.webp"
  "refuge-768-acfbac6551e0.webp"
  "elbeland-768-5e03a6261d76.webp"
  "swamp-768-ce844938a67f.webp"
  "lacleon-768-b05328fd0167.webp"
  "raklion-two-768-788784c4f16d.webp"
  "santa-town-768-70d595c15b5e.webp"
  "vulcanus-768-1b5bcce25e6c.webp"
  "duel-arena-768-f8c604a6ec8b.webp"
  "loren-market-768-4092d35e381b.webp"
  "karutan-one-768-3de90af28728.webp"
  "karutan-two-768-07832c012abf.webp"
  "arkania-768-4cbc53f1dc9e.webp"
)
for asset in "${TERRITORY_ART_FILES[@]}"; do
  if [ ! -s "$TEMPLATE_SOURCE/img/atlas/territories/$asset" ]; then
    echo "[MU PANIC] ERROR: Missing territory artwork: $asset" >&2
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


# Remove the six inventory icons replaced by the new Atlas jewel artwork.
for asset in 6159-0-cd8d6cb459d6.webp 7181-0-29ae502f9fef.webp 7182-0-55910930295d.webp 7184-0-685e3ef7832d.webp 7190-0-1c1bb600aa8c.webp 7209-0-ad95e9dee78b.webp; do
  rm -f "$TEMPLATE_DEST/img/atlas/drops/$asset"
done

echo "[MU PANIC] Beta overlay deployed successfully."
