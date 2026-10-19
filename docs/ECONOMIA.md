# Panel de Economía — MU PANIC

Panel completo de seis pestañas dentro de la administración existente de tienda:

`/usercp/shopadmin/?eco=1`

Se abre desde el enlace **Economía →** de `/usercp/shopadmin/`. Reutiliza el acceso exclusivo de `panic`: sesión válida y cuenta presente en `config('admins', true)`. No modifica `/admincp/`, sus menús ni sus configuraciones. Los PHP auxiliares no admiten acceso directo.

## Instalación habitual por Git (beta)

1. Actualizar la rama **beta** en el checkout habitual de cPanel.
2. Ejecutar **Deploy HEAD Commit** como de costumbre.
3. Entrar con `panic` a `https://beta.mupanic.com.ar/usercp/shopadmin/` y abrir **Economía →**.

El script existente `scripts/deploy-beta.sh` crea primero un respaldo de cada archivo nuevo o distinto de esta entrega y luego ejecuta su copia habitual del overlay. El registro muestra `Economy backup: ...` y la verificación posterior. Si ya coinciden todos los archivos, indica `SIN_CAMBIOS` y no genera respaldos repetidos.

Los respaldos viven en `/home/mupanic/.mupanic-economy-backups/`, fuera de `public_html`, con carpeta privada por instalación. El respaldo comprende los archivos enumerados en `scripts/economy-files.txt`, no toda la web. Se conserva la configuración y el comportamiento del resto del deploy existente. La modificación del deploy se limita a beta; esta entrega no ejecuta ningún despliegue de producción.

## ZIP alternativo con PowerShell

No hace falta instalar además el ZIP si ya se hizo el deploy por Git. El ZIP contiene solo los archivos del panel, dos integraciones del template, manifiestos, instalador e instrucciones. No contiene credenciales, SQL de escritura ni archivos de configuración del CMS.

Verificar el SHA256 publicado junto al ZIP antes de descomprimir. El comando único suministrado con la entrega verifica el hash, descomprime en una carpeta nueva y llama a Aplicar. Requiere OpenSSH del hosting para operar desde Windows contra cPanel; el VPS del juego no es el destino de instalación.

Desde el paquete descomprimido:

```powershell
# Reemplazar usuario@host por el acceso SSH real del hosting. No es un usuario SQL.
.\scripts\INSTALLAR_ECONOMIA.ps1 -Accion Estado -CmsRoot '/home/mupanic/public_html/beta' -SshTarget 'usuario@host'
.\scripts\INSTALLAR_ECONOMIA.ps1 -Accion Aplicar -CmsRoot '/home/mupanic/public_html/beta' -SshTarget 'usuario@host'
.\scripts\INSTALLAR_ECONOMIA.ps1 -Accion Restaurar -CmsRoot '/home/mupanic/public_html/beta' -SshTarget 'usuario@host' -BackupPath '/home/mupanic/.mupanic-economy-backups/RESPALDO-EXACTO'
```

Para puerto o clave propios usar la configuración OpenSSH local. No se solicitan ni almacenan contraseñas del CMS o de SQL Server. Si se ejecuta PowerShell en Linux con acceso local al CMS, se puede omitir `-SshTarget`.

El instalador valida los hashes de todos los archivos antes de escribir. Guarda los originales e identifica cuáles no existían. Restaurar repone los originales y elimina solo los archivos creados por esa instalación. Ante cambios posteriores ajenos o un respaldo corrupto, aborta antes de restaurar nada. No restaura automáticamente «el más reciente»: se debe indicar la carpeta exacta.

Desde una terminal del hosting, después de actualizar el repositorio:

```bash
bash scripts/economy-install.sh Estado /home/mupanic/public_html/beta
bash scripts/economy-install.sh Restaurar /home/mupanic/public_html/beta /home/mupanic/.mupanic-economy-backups/RESPALDO-EXACTO
```

Si se restaura pero se vuelve a desplegar el mismo Git, el panel se instalará otra vez. El respaldo económico no representa un rollback del resto de cambios que pueda contener un deploy general.

## Pestañas

- **Resumen:** pesos, emisión por recargas, retiros de juegos, tienda, VIP, comisiones, circulación comercial actual y variación desde la foto anterior al rango. Barras de flujos y línea del último saldo de cada período.
- **Juegos:** rendimiento, devolución, participación en apuestas, neto por juego, ranking de ganadores/perdedores y últimas 200 líneas al seleccionar un juego. Las líneas pueden representar apuesta y premio por separado, identificadas por Tx.
- **Mercado Vessara:** tarjetas y gráficos separados de Zen/Eryns; rankings y últimas 500 ventas de la moneda seleccionada. Solo la comisión representa retiro de circulación.
- **Tienda y VIP:** compras, compradores únicos de tienda, gasto, productos extraídos de Detalle, VIP sin cobro y membresías vigentes.
- **Recargas:** dinero real en ARS, emisión, cantidad, cuentas únicas y ticket; proveedores y últimas 1.000 recargas. La búsqueda por cuenta exacta se ejecuta en SQL sobre todo el rango antes de limitar filas. Las tarjetas siempre son del rango completo.
- **Control:** cuadre, alertas, saldos, concentración de cuentas reales, top 20, excluidas y estado de carga. Las diferencias mayores al 1% del saldo final se resaltan; los NULL se conservan como «—».

Todas las tablas de detalle tienen búsqueda, orden y paginación local con DataTables. Los límites de filas se indican en pantalla para no confundirlos con el historial completo. Los rangos admiten hasta diez años.

## Reglas de datos

Se usa `Connection::Database('MuOnline')`. Solo se emiten SELECT sobre las catorce vistas `dbo.vMUPanicEco*` de la especificación; nunca se consultan las tablas económicas directamente. Los valores del navegador se parametrizan o se validan mediante listas cerradas. No se conceden permisos.

El filtro de pruebas está apagado por defecto. Funciona en las vistas con `Prueba`. SaldoActual/SaldoDia exponen campos comerciales y de prueba separados: la circulación se etiqueta siempre como comercial, aunque se incluyan movimientos de prueba. CuadreDia y Estado no se pueden segmentar por Prueba y se identifican como globales. No se reconstruye una contabilidad comercial inexistente en esas vistas. Borrar cuentas en el lanzamiento no elimina necesariamente el historial; se puede consultar desde la fecha de apertura.

Las fechas de SQL ya están en hora Argentina y no se convierten. Hoy, rangos y vigencia VIP se calculan con reloj argentino. El límite Hasta es inclusivo para el usuario y exclusivo en SQL para abarcar todas las horas. Las semanas combinan `DATEPART(iso_week)` con su año ISO, también en el cambio de año. Se usan expresiones compatibles con SQL Server 2008 / nivel 100.

Los distintos diarios de jugadores o compradores no se suman como si fueran cuentas únicas del rango. Para recargas y tienda se cuenta la cuenta distinta desde el detalle. Los saldos no se suman entre días: se toma la última foto de cada período. No se inventa una foto inicial si falta.

Los cambios de tarjetas comparan contra el período anterior de igual duración. Si el denominador es cero o falta, se muestra «sin base comparable». La circulación actual se compara contra la última foto anterior al comienzo del rango y lo indica explícitamente. Datos actuales y cuadre global se diferencian del rango filtrado.

Los errores de consulta se muestran como errores; no se convierten en cero. Un estado de carga sin datos, con más de 15 minutos de demora, o distinto de OK produce el aviso rojo de carga atrasada.

Chart.js 4.4.8 y DataTables 2.2.2 se distribuyen localmente con sus licencias. Al cambiar del admincp original a shopadmin, DataTables dejó de estar cargado allí; por eso esta entrega incluye la copia local. Se reutiliza el jQuery que ya carga el template. El panel permite consultar los valores del gráfico como tabla incluso si un gráfico no puede dibujarse. La ruta de administración lleva `private, no-store` y no se indexa.

## Archivos existentes modificados

- `overlay/templates/mupanic/inc/recharge-admin.php`: enlace y entrada de Economía antes de ejecutar servicios o formularios de pagos.
- `overlay/templates/mupanic/index.php`: cabeceras privadas/no-cache para shopadmin.
- `scripts/deploy-beta.sh`: respaldo/verificación del conjunto económico en el deploy habitual.

El manifiesto enumera los archivos nuevos de `inc/admin-economy/`, estilos, JavaScript y dependencias. No cambia ningún archivo del núcleo WebEngine. Los scripts e instrucciones no se copian dentro del sitio público.

## Verificación realizada y límites

- Sintaxis PHP de los archivos agregados/modificados y sintaxis JavaScript/Bash/PowerShell.
- Pruebas de filtros, fechas inválidas, rangos, parámetros, vistas autorizadas, ausencia de funciones posteriores a SQL 2008 y manejo de errores.
- Análisis de 159 consultas generadas como T-SQL en seis pestañas y tres agrupaciones.
- Pruebas de navegador a 1440 y 390 px con datos sintéticos: seis pestañas, gráficos, DataTables, ausencia de desborde y fechas personalizadas.
- Vacíos, errores, carga atrasada, texto malicioso, acceso de invitados/cuentas comunes y rechazo de POST.
- Regresión del panel original: formularios, VIP, guardado y CSRF a 1440/1024/390 px.
- Aplicar/Estado/Restaurar, hashes, idempotencia, rechazo de enlaces y conflictos en carpetas aisladas. PowerShell probado localmente y con transporte SSH simulado.

No hubo conexión a MuOnline43 ni al hosting. El primer deploy permite verificar los permisos y resultados contra las vistas reales. El análisis T-SQL no sustituye la ejecución en SQL Server. No se envió ningún correo ni se modificó ninguna compra.

Pruebas reproducibles:

```bash
php tests/economy.php
python3 tests/economy-install.py
# Requiere Playwright, Chromium y una copia de jQuery 2.2.4 para el fixture aislado:
PHP_BIN=php CHROME_BIN=/ruta/chrome ECO_JQUERY_FIXTURE=/ruta/jquery.js node tests/economy-browser.cjs
```
