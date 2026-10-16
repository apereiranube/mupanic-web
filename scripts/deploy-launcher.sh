#!/bin/bash
# Called after the template is installed; preserve existing domain protection.
set -euo pipefail
ROOT="$1"
HTACCESS="$ROOT/.htaccess"
if [ -L "$HTACCESS" ]; then
  echo '[MU PANIC] ERROR: .htaccess must not be a symlink.' >&2
  exit 1
fi
if ! /bin/grep -q '^# BEGIN MU PANIC launcher$' "$HTACCESS" 2>/dev/null; then
  TEMP_FILE="$(mktemp "$ROOT/.launcher-htaccess.XXXXXX")"
  trap 'rm -f "$TEMP_FILE"' EXIT
  {
    echo '# BEGIN MU PANIC launcher'
    echo '<IfModule mod_rewrite.c>'
    echo 'RewriteEngine On'
    echo 'RewriteRule ^api/launcher\.php$ - [END]'
    echo '</IfModule>'
    echo '# END MU PANIC launcher'
    if [ -f "$HTACCESS" ]; then cat "$HTACCESS"; fi
  } > "$TEMP_FILE"
  chmod 644 "$TEMP_FILE"
  mv "$TEMP_FILE" "$HTACCESS"
fi
