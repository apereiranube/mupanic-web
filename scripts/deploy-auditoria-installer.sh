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
# La auditoria cerrada permite solo sus herramientas protegidas por Basic Auth.
php -r '
$path = $argv[1];
$old = "RewriteRule ^(?!(?:index|preparar-auditoria)\\.php$).*\\.php(?:/.*)?$ - [F,L,NC]";
$new = "RewriteRule ^(?!(?:index|preparar-auditoria|separar-main)\\.php$).*\\.php(?:/.*)?$ - [F,L,NC]";
$before = file_get_contents($path);
if ($before === false) { fwrite(STDERR, "No se pudo leer la proteccion de auditoria.\n"); exit(1); }
$after = str_replace($old, $new, $before);
if ($after !== $before) {
    $temporary = tempnam(dirname($path), ".audit-rules-");
    if ($temporary === false) exit(1);
    if (file_put_contents($temporary, $after) !== strlen($after) ||
        !chmod($temporary, fileperms($path) & 0777) || !rename($temporary, $path)) {
        @unlink($temporary); exit(1);
    }
}
' "$DEST/.htaccess"
echo '[Auditoria] Instalador preparado. La proteccion del directorio se conserva.'
