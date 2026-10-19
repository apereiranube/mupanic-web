#!/bin/bash
# Scoped, reversible installation. No SQL, credentials, or payment operations.
set -euo pipefail
umask 077
ACTION="${1:-Estado}"
ROOT_INPUT="${2:?Indicar la raiz instalada del CMS}"
PACKAGE_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PAYLOAD="$PACKAGE_ROOT/overlay"
MANIFEST="$PACKAGE_ROOT/scripts/economy-files.txt"
HASHES="$PACKAGE_ROOT/scripts/economy-sha256.txt"
fail(){ echo "[Economia] ERROR: $*" >&2; exit 1; }
[ ! -L "$ROOT_INPUT" ] || fail 'La raiz no puede ser un enlace simbolico.'
ROOT="$(cd "$ROOT_INPUT" && pwd -P)"
[ -f "$ROOT/index.php" ] && [ -d "$ROOT/templates/mupanic" ] || fail 'No se encontro el CMS con template mupanic.'
valid_relative(){ [[ "$1" == templates/mupanic/* ]] && [[ "$1" != *'..'* ]] && [[ "$1" != *$'\t'* ]] && [[ "$1" != *$'\n'* ]]; }
check_path(){
    local rel="$1" part current="$ROOT"
    valid_relative "$rel" || fail 'Ruta fuera del alcance autorizado.'
    IFS='/' read -r -a parts <<< "$rel"
    for part in "${parts[@]}"; do current="$current/$part"; [ ! -L "$current" ] || fail "Enlace simbolico: $rel"; done
    [ ! -d "$ROOT/$rel" ] || fail "El archivo destino es un directorio: $rel"
}
hash_file(){ sha256sum "$1" | cut -d ' ' -f 1; }
validate_package(){
    [ -f "$MANIFEST" ] && [ -f "$HASHES" ] || fail 'Paquete incompleto.'
    (cd "$PAYLOAD" && sha256sum --status -c "$HASHES") || fail 'El contenido del paquete no coincide con su manifiesto.'
    while IFS= read -r rel; do check_path "$rel"; [ -f "$PAYLOAD/$rel" ] && [ ! -L "$PAYLOAD/$rel" ] || fail "Falta archivo: $rel"; done < "$MANIFEST"
}
prepare(){
    validate_package
    local changed=0 rel expected actual backup_parent backup existed before
    while IFS= read -r rel; do expected="$(hash_file "$PAYLOAD/$rel")"; actual='-'; [ ! -f "$ROOT/$rel" ] || actual="$(hash_file "$ROOT/$rel")"; [ "$actual" = "$expected" ] || changed=$((changed+1)); done < "$MANIFEST"
    if [ "$changed" = 0 ]; then echo 'SIN_CAMBIOS'; return; fi
    backup_parent="${MUPANIC_ECO_BACKUP_ROOT:-/home/mupanic/.mupanic-economy-backups}"
    [ ! -L "$backup_parent" ] || fail 'El directorio de respaldos no puede ser un enlace.'
    mkdir -p "$backup_parent"; backup_parent="$(cd "$backup_parent" && pwd -P)"
    case "$backup_parent/" in "$ROOT/"*) fail 'El respaldo debe quedar fuera del sitio publico.';; esac
    # The cPanel document roots share this ancestor; never keep backups in public_html.
    case "$backup_parent/" in */public_html/*) fail 'El respaldo debe quedar fuera de public_html.';; esac
    backup="$(mktemp -d "$backup_parent/economy-$(date -u +%Y%m%dT%H%M%SZ)-XXXXXXXX")"; chmod 700 "$backup"
    printf '%s\n' "$ROOT" > "$backup/cms-root.txt"
    : > "$backup/files.tsv"
    while IFS= read -r rel; do
        expected="$(hash_file "$PAYLOAD/$rel")"; before='-'; existed=0
        if [ -f "$ROOT/$rel" ]; then before="$(hash_file "$ROOT/$rel")"; existed=1; fi
        [ "$before" != "$expected" ] || continue
        if [ "$existed" = 1 ]; then mkdir -p "$backup/files/$(dirname "$rel")"; cp -p "$ROOT/$rel" "$backup/files/$rel"; fi
        printf '%s\t%s\t%s\t%s\n' "$rel" "$existed" "$before" "$expected" >> "$backup/files.tsv"
    done < "$MANIFEST"
    echo "$backup"
}
verify_backup(){
    local backup="$1"
    [ "$backup" != 'SIN_CAMBIOS' ] || return 0
    [ -d "$backup" ] && [ ! -L "$backup" ] && [ -f "$backup/cms-root.txt" ] && [ -f "$backup/files.tsv" ] || fail 'Respaldo invalido.'
    [ "$(cat "$backup/cms-root.txt")" = "$ROOT" ] || fail 'El respaldo pertenece a otro CMS.'
}
case "$ACTION" in
Estado)
    validate_package
    while IFS= read -r rel; do if [ ! -f "$ROOT/$rel" ]; then echo "NUEVO $rel"; elif [ "$(hash_file "$ROOT/$rel")" = "$(hash_file "$PAYLOAD/$rel")" ]; then echo "OK $rel"; else echo "DIFERENTE $rel"; fi; done < "$MANIFEST"
    ;;
Preparar) prepare ;;
Aplicar)
    backup="$(prepare)"
    if [ "$backup" = 'SIN_CAMBIOS' ]; then echo '[Economia] Ya esta instalado; sin cambios.'; exit 0; fi
    echo "[Economia] Respaldo: $backup"
    while IFS=$'\t' read -r rel existed before expected; do check_path "$rel"; mkdir -p "$ROOT/$(dirname "$rel")"; cp "$PAYLOAD/$rel" "$ROOT/$rel"; chmod 644 "$ROOT/$rel"; done < "$backup/files.tsv"
    /bin/bash "$0" Finalizar "$ROOT" "$backup"
    ;;
Finalizar)
    backup="${3:?Indicar el respaldo}"
    [ "$backup" != 'SIN_CAMBIOS' ] || exit 0
    verify_backup "$backup"
    while IFS=$'\t' read -r rel existed before expected; do check_path "$rel"; [ -f "$ROOT/$rel" ] && [ "$(hash_file "$ROOT/$rel")" = "$expected" ] || fail "No coincide el archivo instalado: $rel"; done < "$backup/files.tsv"
    date -u +%FT%TZ > "$backup/aplicado.txt"; echo "[Economia] Instalacion verificada. Respaldo: $backup"
    ;;
Restaurar)
    backup="${3:?Indicar la ruta exacta del respaldo}"
    verify_backup "$backup"
    [ "$backup" != 'SIN_CAMBIOS' ] || fail 'No hay respaldo para restaurar.'
    # Validate every file before restoring any; do not overwrite later edits.
    while IFS=$'\t' read -r rel existed before expected; do
        check_path "$rel"; actual='-'; [ ! -f "$ROOT/$rel" ] || actual="$(hash_file "$ROOT/$rel")"
        [ "$actual" = "$expected" ] || [ "$actual" = "$before" ] || fail "Cambio posterior; no se sobrescribe: $rel"
        if [ "$existed" = 1 ]; then [ -f "$backup/files/$rel" ] && [ "$(hash_file "$backup/files/$rel")" = "$before" ] || fail "Respaldo corrupto: $rel"; fi
    done < "$backup/files.tsv"
    while IFS=$'\t' read -r rel existed before expected; do if [ "$existed" = 1 ]; then mkdir -p "$ROOT/$(dirname "$rel")"; cp -p "$backup/files/$rel" "$ROOT/$rel"; else rm -f "$ROOT/$rel"; fi; done < "$backup/files.tsv"
    date -u +%FT%TZ > "$backup/restaurado.txt"; echo '[Economia] Restauracion completa.'
    ;;
*) fail 'Acciones: Estado / Aplicar / Restaurar.';;
esac
