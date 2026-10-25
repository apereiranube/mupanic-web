# Pasar la web de beta a mupanic.com.ar

Herramienta: `tools/main/promover-beta.php`, instalada por `scripts/deploy-auditoria-installer.sh`
en la auditoría protegida (`https://auditoria.mupanic.com.ar/promover-beta.php`, Basic Auth + CSRF).

## Qué hace
- Vista previa sin cambios: lista lo que copia de beta y lo que archiva de main.
- Aplicar: copia la instalación completa de `public_html/beta` (WebEngine + config + plantilla)
  a `public_html`, archiva la landing (`index.php`, `templates`, lo que choque, `main-services`,
  `main-landing-state.json`) en `/home/mupanic/main-backups/promover-*`, arma el `.htaccess` de main
  con las reglas de beta (sin la guarda de alias, conservando el handler PHP de cPanel) y
  agrega a beta una redirección 301 para GET/HEAD hacia `https://mupanic.com.ar/`.
  `/templates/mupanic/api/` y `/api/` siguen respondiendo en beta (bot del banco, workers del VPS).
- Restaurar: vuelve byte a byte a la landing.
- No toca SQL, `/home/mupanic/payments-private`, `atlas-runtime` ni tareas del VPS.

## Orden
1. cPanel → rama `beta` → Update from Remote → Deploy HEAD Commit (lleva `productionMode = website`).
2. cPanel → rama `main` → Update from Remote → Deploy HEAD Commit. Mientras no haya promoción,
   `deploy-prod.sh` no modifica `public_html`; solo instala la herramienta en la auditoría.
3. Abrir `promover-beta.php`, revisar la vista previa y aplicar (sin recargas en curso).
4. Comprobar: inicio, crear cuenta, ingresar, tienda/recargas, admin, Atlas, Mercado.
5. Después, cambiar a `mupanic.com.ar` las direcciones externas: bot del banco de Discord,
   `recharge_worker.ps1`, sync del Atlas, `endpoint.txt` de referidos, piloto Ualá,
   novedades del launcher. Recién entonces beta puede quedar solo como redirección.

Desde la promoción, los deploys de `main` copian la plantilla en `public_html` (igual que beta).

Prueba local: `python tests/promover-beta.py`.
