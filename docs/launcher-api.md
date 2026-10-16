# API pública del launcher

`GET /api/launcher.php` devuelve únicamente `online` (entero), `servidor`
(`online`, `offline`, `mantenimiento`) y `eventos` (`nombre`, `proximo`).
Cabeceras: `Content-Type: application/json; charset=utf-8` y
`Cache-Control: max-age=30`. No requiere sesión ni parámetros.

## Fuentes y significado

- Conectados: `LoadCacheData('server_info.cache')[1][0]`, campo 3 separado por
  `|`, exactamente como `templates/mupanic/index.php`. Es el último registro
  del cron de WebEngine, no un recuento SQL nuevo por visita.
- Estado: `system_active=false` de WebEngine significa mantenimiento. En modo
  activo, una consulta fija `SELECT 1` por la conexión `Me_MuOnline` comprueba
  disponibilidad SQL. Si falla o no se puede leer el contador: offline y cero.
  **No prueba que GameServer acepte conexiones**: la web no tiene esa fuente.
- Agenda: `panicAtlasEvents()` prioriza el snapshot sincronizado privado y usa
  `inc/public-events.json` como respaldo, igual que el Atlas. Solo eventos
  habilitados y programados; arenas manuales excluidas. Se respetan todos los
  campos del calendario y comodines, zona Argentina y horizonte de 370 días
  del Atlas. Una próxima fecha por evento, hasta diez, ordenadas. Nombres
  traducidos al español únicamente para el launcher.

El estado (incluidos fallos) se guarda 45 segundos en un archivo privado del
sistema temporal, separado por raíz WebEngine, con bloqueo para evitar consultas
concurrentes. Si no se puede mantener el caché devuelve offline sin consultar
SQL. Las fechas se calculan en cada petición para no devolver inicios vencidos.
No modifica tablas, configuración ni archivos del juego.

## Despliegue

La rama beta instala en `/home/mupanic/public_html/beta/api/launcher.php`.
El script de producción queda preparado para `/home/mupanic/public_html/api/launcher.php`
cuando este cambio se integre a la rama de producción.

En cPanel: **Update from Remote → Deploy HEAD Commit** sobre la rama beta.
El deploy conserva la configuración privada y añade una excepción exacta para
`api/launcher.php` antes de las reglas Rewrite de `.htaccess`, conservando el
resto del archivo. No abre otros endpoints. Si hay protección adicional en
cPanel, WAF, autenticación HTTP o `auto_prepend_file`, hay que permitir la misma
ruta allí; esa configuración no está versionada y no se puede verificar desde
este checkout.

Verificar públicamente después de desplegar:

```sh
curl -i https://beta.mupanic.com.ar/api/launcher.php
```

No se considera publicada ni verificada una URL que todavía responda 404,
redirección o HTML. Una salida local con fuentes simuladas no es una respuesta
real del hosting.

Pruebas: `php tests/launcher.php`; sintaxis de PHP y scripts shell; prueba HTTP
con fixture del formato de caché CMS, driver simulado y SQL caído.
