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
cp "$SOURCE_ROOT/tools/main/promover-beta.php" "$DEST/promover-beta.php"
chmod 600 "$DEST/promover-beta.php"
# La auditoria cerrada permite solo sus herramientas protegidas por Basic Auth.
HT="$DEST/.htaccess"
NEW_RULE='RewriteRule ^(?!(?:index|preparar-auditoria|separar-main|promover-beta)\.php$).*\.php(?:/.*)?$ - [F,L,NC]'
OLD_RULE_1='RewriteRule ^(?!(?:index|preparar-auditoria)\.php$).*\.php(?:/.*)?$ - [F,L,NC]'
OLD_RULE_2='RewriteRule ^(?!(?:index|preparar-auditoria|separar-main)\.php$).*\.php(?:/.*)?$ - [F,L,NC]'
# Sin "php" de linea de comandos: en el hosting nuevo ese comando es el PHP CGI de la web.
if grep -qxF -e "$OLD_RULE_1" -e "$OLD_RULE_2" "$HT"; then
  TMP_RULES="$(mktemp "$DEST/.audit-rules-XXXXXX")"
  awk -v n="$NEW_RULE" -v o1="$OLD_RULE_1" -v o2="$OLD_RULE_2" '{ if ($0 == o1 || $0 == o2) print n; else print }' "$HT" > "$TMP_RULES"
  chmod --reference="$HT" "$TMP_RULES"
  mv -f "$TMP_RULES" "$HT"
fi
echo '[Auditoria] Instalador preparado. La proteccion del directorio se conserva.'
