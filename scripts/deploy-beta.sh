#!/bin/bash
set -euo pipefail

SOURCE_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DEPLOY_ROOT="/home/mupanic/public_html/beta"
CONFIG_FILE="$DEPLOY_ROOT/includes/config/webengine.json"
BACKUP_FILE="/home/mupanic/tmp/mupanic-webengine.beta.lastgood.json"

echo "[MU PANIC] Deploying beta overlay..."

if [ ! -f "$DEPLOY_ROOT/index.php" ]; then
  echo "[MU PANIC] ERROR: WebEngine core not found in beta." >&2
  exit 1
fi

if [ ! -s "$CONFIG_FILE" ]; then
  echo "[MU PANIC] ERROR: WebEngine config is missing or empty. Deploy aborted." >&2
  exit 1
fi

mkdir -p "$(dirname "$BACKUP_FILE")"
cp "$CONFIG_FILE" "$BACKUP_FILE"

# Only project-owned overlay files are copied. WebEngine core and live
# configuration stay on the server.
cp -a "$SOURCE_ROOT/overlay/." "$DEPLOY_ROOT/"

# webengine.json is intentionally server-owned and must survive every deploy.
cp "$BACKUP_FILE" "$CONFIG_FILE"

if [ ! -s "$CONFIG_FILE" ]; then
  echo "[MU PANIC] ERROR: Config verification failed." >&2
  exit 1
fi

echo "[MU PANIC] Beta overlay deployed successfully."
