# Main independiente durante la campana

`tools/main/separar-main.php` se instala mediante el deploy de main en el directorio de auditoria protegido con Basic Auth. Solo admite HTTPS, el host de auditoria y la ruta exacta. GET muestra nombres de archivos; no lee credenciales ni consulta SQL. POST requiere sesion y CSRF.

Antes de aplicar, revisar las tareas de cPanel: ninguna debe depender de `/home/mupanic/public_html/api` o `/home/mupanic/public_html/includes`. Si existe esa dependencia, ajustar y verificar la tarea antes de marcar la casilla. Verificar tambien que integraciones externas utilicen los endpoints conservados; los APIs adicionales abortan la operacion.

La aplicacion archiva admincp, api, includes, modules, install, img, templates y los indices anteriores en `/home/mupanic/main-backups/landing-*`, fuera del acceso web. Conserva beta, auditoria, certificados, configuracion PHP y archivos privados de pagos/Atlas. Los recursos estaticos del template se copian a una carpeta limpia y las dependencias PHP del template quedan en `/home/mupanic/main-services` (0700). No hay includes de WebEngine ni conexion SQL en el nuevo index de main.

Mantiene exactamente las URLs de recharge-worker, recharge-hook, uala-pilot-worker, uala-pilot-hook, atlas-sync, atlas-events-sync y atlas-events. Sus archivos originales se ejecutan mediante wrappers; no se modifican firmas, tokens, ledger, configuracion de proveedores ni tareas Windows. El frontend de compras y cuentas se usa desde beta. No se redirigen solicitudes POST ni webhooks a otro dominio.

El allowlist de main permite el index, recursos estaticos, endpoints conservados y desafios ACME. Las rutas antiguas devuelven 404. Beta y auditoria reciben una regla que impide utilizarlas mediante aliases como `www.mupanic.com.ar/beta/`, conservando sus dominios y sus reglas anteriores de autenticacion.

El estado `/home/mupanic/main-landing-state.json` activa la rama de deploy exclusivo de landing; futuras actualizaciones de main no reinstalan WebEngine. Se conservan snapshots privados de actualizaciones posteriores. La herramienta ofrece restauracion de los archivos y reglas originales, sin restaurar SQL ni retroceder datos de pagos. Una vez restaurado, el deploy original vuelve a estar habilitado.

Verificacion local: `PHP_BIN=/ruta/php MAIN_TEMPLATE_SOURCE=/ruta/template python tests/main-isolation.py`. Comprueba la landing real y sus recursos, endpoints originales byte por byte, datos privados intactos, archivos de beta/auditoria intactos salvo reglas de alias, refresh sin reinstalar core, restauracion exacta y aborto ante API desconocida. No prueba Apache ni el hosting: despues de aplicar hay que comprobar HTTP 200 en landing, 404 en rutas retiradas/aliases, 401 en auditoria sin autenticar y el funcionamiento del worker/recargas. No ejecutar un cron manual ni simular POST de pago para comprobar rutas.

Antes del lanzamiento completo, preparar un deploy de WebEngine revisado con la configuracion de produccion. No copiar la configuracion de auditoria ni reemplazar credenciales o ledger con los de otra instalacion.
