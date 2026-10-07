#!/bin/bash
set -euo pipefail
SOURCE_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DEST=/home/mupanic/public_html/auditoria-web
for path in /home/mupanic /home/mupanic/public_html "$DEST" "$DEST/.htaccess" "$DEST/preparar-auditoria.php"; do
  if [ -L "$path" ]; then
    echo '[Auditoria] Destino con enlace simbolico: instalacion cancelada.' >&2
    exit 1
  fi
done
test -d "$DEST"
test "$(realpath "$DEST")" = "$DEST"
test -f "$DEST/.htaccess"
grep -Eiq '^[[:space:]]*AuthType[[:space:]]+Basic' "$DEST/.htaccess"
grep -Eiq '^[[:space:]]*Require[[:space:]]+(valid-user|user[[:space:]])' "$DEST/.htaccess"
cp "$SOURCE_ROOT/tools/auditoria/preparar-auditoria.php" "$DEST/preparar-auditoria.php"
chmod 600 "$DEST/preparar-auditoria.php"
cp "$SOURCE_ROOT/tools/main/separar-main.php" "$DEST/separar-main.php"
chmod 600 "$DEST/separar-main.php"
echo '[Auditoria] Instalador preparado. La proteccion del directorio se conserva.'
