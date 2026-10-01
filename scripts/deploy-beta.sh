#!/bin/bash
set -euo pipefail

SOURCE_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DEPLOY_ROOT="/home/mupanic/public_html/beta"

echo "[MU PANIC] Applying beta overlay..."

mkdir -p "$DEPLOY_ROOT"
cp -a "$SOURCE_ROOT/overlay/." "$DEPLOY_ROOT/"

echo "[MU PANIC] Beta overlay applied."
