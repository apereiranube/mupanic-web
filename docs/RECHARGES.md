# MU PANIC: recargas de WCoin C

## Decisiones confirmadas

- Moneda: WCoin C para la cuenta del juego, no créditos genéricos de WebEngine.
- Ualá Bis será la pasarela principal, decisión de Agustín del 1/10/2026. Credenciales disponibles según lo informado; conector v2 preparado, cobros todavía no habilitados. Documentación oficial: https://developers.ualabis.com.ar/.
- Mobbex queda como alternativa. Alta solicitada por Agustín; espera informada de 72 horas.
- MercadoPago queda opcional y desactivado. Nunca mostrarlo como disponible sin activación expresa.
- Equivalencia confirmada el 1/10/2026: $1 ARS = 1 WCoin C (100 centavos por moneda), sin bonos por ahora. Paquetes aprobados: $1.000, $3.000, $5.000, $10.000 y $20.000 ARS, entregando respectivamente 1.000, 3.000, 5.000, 10.000 y 20.000 WCoin C. Visibles como disponibles próximamente; compras deshabilitadas. La equivalencia se guarda como enteros en `exchange_rate`; el futuro checkout debe aplicarla al crear los paquetes y preservar el importe y las monedas en cada orden.
- Autenticación Ualá Bis de prueba confirmada por captura del administrador el 1/10/2026. No se creó ningún cobro ni se acreditaron monedas con esa comprobación.
- Primera etapa: vender monedas; los productos se compran en el Cash Shop del cliente.

## Primera entrega (preparación, no sistema habilitado para cobrar)

`usercp/recharge/` es una página privada integrada al panel existente, con arte MU,
paleta cobre, cuenta de destino, paquetes, explicación y sección Mis compras.
Actualmente muestra preparación y explica los estados; NO muestra un historial ficticio.
No tiene formularios de compra, llamadas API, saldo simulado ni endpoint de pago.
El catálogo público `inc/recharge-config.php` permite preparar paquetes con
`id`, `title`, `price_cents`, `coins`, `bonus`. Aunque se carguen, siguen sin poder comprarse.
No agregar secretos a ese archivo ni al repositorio. No se reemplaza la donación
legacy ni se habilitan sus métodos mediante este cambio.

`inc/recharge-domain.php` implementa importes en centavos, validación de paquetes,
identificadores aleatorios y órdenes que guardan el precio/cantidad originales.
Valida proveedor, operación, referencia, comercio receptor, moneda, importe y
ambiente. Un pago de prueba nunca genera una entrega real. Devoluciones, pagos
parciales, estados desconocidos y discrepancias requieren revisión.
Pago aprobado y entrega acreditada son estados diferentes.

`inc/recharge-providers.php` contiene adaptadores PHP para crear checkout y
consultar pagos por API de Mobbex y MercadoPago, con transporte HTTPS y pruebas
inyectables. El reconciliador consulta al proveedor: jamás toma como prueba de
pago una URL de regreso ni el contenido de un webhook. No están conectados a
endpoints públicos ni a una base de órdenes en esta entrega.

La respuesta autenticada de Mobbex debe incluir la identidad del comercio;
el adaptador falla si no está presente en `data.transaction.entity.uid`.
La documentación pública de consulta tiene ejemplos parciales e inconsistentes
(encabezado GET operations y ejemplo antiguo POST transactions/status).
Hay que validar el contrato real con Mobbex y ejemplos de prueba antes de activar.
El estado Mobbex 200 es paga; autorizada, retenida o aceptada sin pago no habilitan
entrega. Otros estados positivos posteriores se dejan en revisión hasta homologar.

## Siguiente entrega: persistencia y administración

Auditoría recibida: `MU_PANIC_WALLET_AUDIT_20261001_163845.json`, solo metadatos,
base MuOnline43, 1/10/2026 19:38:45 UTC. Se confirmó:

| Uso | Tabla / columna | Tipo y clave |
| --- | --- | --- |
| WCoin C | dbo.CashShopData.WCoinC | int NOT NULL |
| Cuenta del saldo | dbo.CashShopData.AccountID | varchar(10), PK única PK_TempCashShop |
| Otras monedas, no tocar | WCoinP / GoblinPoint | int NOT NULL |
| Estado de conexión | dbo.MEMB_STAT.ConnectStat | tinyint nullable; no asumir NULL = desconectado |
| Cuenta de conexión | dbo.MEMB_STAT.memb___id | varchar(10), PK única |

El segundo informe, `MU_PANIC_WALLET_AUDIT_20261001_164526.json`, incluye
`dbo.WZ_SetCoin`: suma @Value1 a WCoinC, @Value2 a WCoinP y @Value3 a
GoblinPoint para AccountID=@Account. @Name se declara pero no se usa.
No crea filas faltantes, no comprueba que se haya actualizado una cuenta y
no registra una entrega única. No contiene una notificación al GameServer.
Activa XACT_ABORT al entrar y lo desactiva al salir: cualquier wrapper debe
reestablecerlo y manejar transacción/rollback explícitamente.
Esto verifica el SQL, no la caché ni el refresco del cliente.

### Prueba autorizada: pruebacoin

Agustín eligió `pruebacoin` para la prueba. `tools/test_coin_delivery.ps1`
consulta únicamente esa cuenta y la base MuOnline43. Por defecto solo lee.
Con `-AddOneCoin` suma exactamente una WCoin C usando el procedimiento auditado:
requiere fila de saldo existente y ConnectStat=1, toma locks, comprueba el
incremento y que WCoinP/GoblinPoint no cambien, y hace rollback ante error SQL.
No crea cuentas/filas ni modifica procedimientos ni procesos del servidor.

Antes de sumar, reserva un archivo local con CreateNew y lo fuerza a disco,
en `C:\MuServer43\PaymentsPrivate\pruebacoin-one-coin-test.json`. El archivo
bloquea una segunda ejecución de la suma en ese VPS/ruta. Si hay timeout o corte
queda reservado: no borrar ni repetir hasta revisar el resultado. Esto es una
protección de la prueba, NO el ledger durable de recargas de producción.

Procedimiento de observación: entrar con pruebacoin, abrir Cash Shop y anotar
WCoin C; ejecutar una vez con -AddOneCoin; cerrar/reabrir la tienda; salir por
completo de la cuenta y volver a entrar; ejecutar sin -AddOneCoin para consultar
SQL después de salir. No gastar, comprar ni obtener recompensas durante la
prueba. Comparar SQL antes/después y saldo visible en el cliente. No prometer
actualización instantánea hasta completar esta prueba.

Prueba completada por Agustín el 1/10/2026: Cash Shop mostró 1 WCoin C,
consulta posterior con ConnectStat=0 mostró WCoinC=1 y el usuario confirmó
que siguió mostrando 1 al volver a entrar. Evidencia de persistencia para esa
cuenta/prueba; no demuestra concurrencia, entrega masiva ni ausencia de toda
posible carrera con el GameServer. No repetir ni eliminar el marcador de prueba.

### Conector Ualá Bis v2 y configuración privada

`inc/recharge-uala.php` genera tokens con username/client_id/client_secret_id,
usa los hosts oficiales separados para test/production y consulta órdenes por
GET autorizado. El dominio guarda centavos enteros; el sandbox recibe y devuelve pesos, normalizados al leer. Las unidades de producción siguen pendientes de verificar. Solo APPROVED
puede habilitar una entrega; PROCESSED (y PROCCESED en ejemplos oficiales) sigue
pendiente. Estados desconocidos y devoluciones requieren revisión.
El GET omite comercio/moneda/ambiente: se derivan del scope autenticado del
client_id y del host de Argentina, no de un webhook. Antes de producción hay
que probar acceso a órdenes ajenas (debe denegarse), referencias/importe y
guardar UUID de checkout en el intento antes de aceptar aprobación.

`examples/uala-settings.example.json` contiene solo placeholders. Copiarlo
como `/home/mupanic/payments-private/settings.json`, fuera de public_html,
permisos 0600, directorio 0700. Nunca subir valores reales al repositorio/chat.
Usar environment test con credenciales de prueba; production solo si las claves
recibidas corresponden a producción. sales_enabled debe quedar false: el loader
rechaza true en esta etapa. El archivo aún no habilita checkout en la página.
El adaptador conserva el token solo en memoria y no devuelve su valor al cliente.

Después de desplegar, cPanel Terminal puede ejecutar:
`php /home/mupanic/public_html/beta/templates/mupanic/bin/check-uala.php`
Este comando verifica únicamente autenticación y devuelve estado/ambiente,
nunca tokens ni cuerpos API. Se niega a ejecutarse por HTTP. No crea órdenes,
cobra dinero ni acredita WCoin. Falta verificarlo con las credenciales reales.

Pruebas locales en `tests/recharge-uala.php` con credenciales ficticias y sin
red: autenticación, reutilización de token, centavos, ambiente, estados, límites
y rechazo de placeholders. Ledger, webhook, historial real y panel de paquetes
siguen pendientes. Los cinco paquetes y la equivalencia fueron definidos; todavía no se habilitaron compras.

`inc/recharge-wallet.php` consulta WCoinC de la cuenta de sesión con SQL
parametrizado y base física fija MuOnline43. El saldo es una lectura al cargar;
no prueba que el cliente muestre lo mismo en ese instante. Cuenta sin fila,
error de conexión o valor inválido se muestran como No disponible, nunca 0.
Un 0 válido se muestra como tal. No cambia saldos ni ejecuta WZ_SetCoin.


1. Obtener esquema con `tools/audit_wallet.ps1` (solo lectura, MuOnline43).
   Verificar tabla y columna reales de WCoin C, clave única de cuenta, tipo,
   límites y procedimientos del Cash Shop. No usar la vieja base MuOnline.
2. Comprobar en una cuenta de prueba el comportamiento conectado/desconectado:
   caché de saldo del GameServer, refresco y si sobrescribe cambios al salir.
   La auditoría de esquema por sí sola NO demuestra cómo funciona esa caché.
3. Implementar ledger persistente de órdenes/pagos y outbox de entregas.
   El contrato `PanicRechargeLedger` exige transacción, locks, revalidación y
   unicidad global `(provider,payment_id)`; no incluye una implementación real aún.
4. Implementar un mecanismo de entrega que el servidor admita. Registro de
   entrega y modificación de saldo deben compartir transacción, o usar un
   receptor idempotente en el VPS con recibo durable. No incrementar por HTTP
   y marcar luego en otra base: un corte duplicaría monedas.
5. Formulario con cuenta tomada de sesión, CSRF, límites por usuario y creación
   idempotente. Primero persistir orden/attempt; después solicitar checkout.
   Un timeout del proveedor necesita conciliación, no una nueva orden ciega.
6. Integrar primero Ualá Bis usando su documentación oficial v2 y validar sus
   notificaciones mediante consulta autenticada de la orden. Mobbex queda para
   una conexión posterior; MercadoPago usa su
   firma oficial cuando se habilite. Verificar mediante GET del proveedor antes
   de registrar aprobación. Los eventos se guardan de forma durable y se
   procesan/reintentan sin depender de que el jugador mantenga abierta la web.
7. Conciliación programada de pagos pendientes y trabajos de entrega fallidos.
   No depender exclusivamente del webhook. Reembolsos/contracargos crean un caso
   de revisión; no descontar automáticamente monedas ya gastadas.
8. Historial real por sesión; administración con roles del CMS y CSRF para
   paquetes, disponibilidad, bonos y casos de revisión. Auditoría de cambios.
   Definir expiración de bonos y conservar el snapshot de compras anteriores.
9. Ualá Bis en entorno de prueba, aprobación, rechazo, retorno abandonado, webhook repetido,
   eventos fuera de orden, importe alterado, proveedor caído y corte durante
   entrega. Luego una compra real pequeña y activación explícita.

## Alcance del futuro panel

El primer panel administra recargas: pesos, WCoin, bonos, orden y visibilidad.
No modifica productos del cliente. Administrar el Cash Shop del juego es otra
integración: ya se usó un editor que genera archivos Server y Client. Hay que
validar ambos formatos, respaldos y distribución del cliente antes de publicar.
Una tienda de ítems en la web necesita además entrega de objetos, inventario,
opciones legales de cada ítem y control de duplicados; no forma parte de recargas.

## Credenciales y publicación

Guardar claves en un archivo privado fuera de `public_html`, con permisos
restringidos, o variables de entorno del hosting. Separar sandbox/producción.
Nunca pedir que se peguen tokens en el chat. No usar el token del Atlas.
El despliegue sigue siendo solo template: no instala tablas, tareas ni cambia
configuración del CMS. Desplegar esta entrega no habilita cobros.

## Fuentes oficiales consultadas el 1/10/2026

- https://mobbex.dev/checkout
- https://mobbex.dev/consulta-de-operaciones-y-childs
- https://mobbex.dev/webhooks
- https://mobbex.dev/codigos-de-estado
- https://www.mercadopago.com.ar/developers/es/docs/checkout-pro-preferences/payment-notifications

## Validación local

Pruebas del dominio y adaptadores en `tests/recharges.php`, sin credenciales ni
operaciones reales. Casos de importes, snapshots, ambiente, recepción correcta,
suplantación, devolución, respuestas incompletas y pagos repetidos.
Las pruebas de replay usan un ledger de memoria: NO prueban concurrencia SQL,
persistencia tras reinicio, acreditación real ni homologación con las pasarelas.

La página se renderizó localmente en Chromium a 1440 y 390 px: sin desborde
horizontal, preguntas desplegables funcionando y sin controles para pagar.
También se verificó acceso bloqueado para invitados y regresión de las 12
vistas existentes de cuenta, incluidos los datos originales de sus formularios.
El script de auditoría está revisado como solo lectura; falta ejecutarlo en
Windows/SQL Server. No se probó contra el VPS desde este entorno.
# Browser authentication check without cPanel Terminal

`usercp/recharge/` displays a test authentication button only for logged-in accounts present in the existing WebEngine `admins` configuration. No administrator account is hardcoded or granted new permissions. POST requires a session CSRF token and allows one attempt per minute per session; GET never calls Ualá. It reads the existing private settings file, requires `environment: test` and disabled sales, and calls authentication only. Credentials, tokens and provider exception messages are never rendered. Deployment still requires cPanel Update from Remote and Deploy HEAD Commit. `tests/recharge-check.php` covers guest/player denial, CSRF, throttling, safe errors and admin rendering; gateway behavior remains covered by mocked adapter tests.

## Compra piloto con pago simulado y monedas reales (autorizada 1/10/2026)

Agustín autorizó probar el circuito con monedas reales antes del lanzamiento.
Esta excepción es **una sola compra Ualá de test de $1.000 ARS, por 1.000 WCoin C,
exclusivamente para `pruebacoin`**. No habilita ventas, otros paquetes ni otras
cuentas. El reconciliador general sigue rechazando entregas reales de sandbox.

La creación está reservada a administradores WebEngine autenticados, mediante
POST con CSRF. `recharge-pilot.php` guarda una reserva antes de llamar a Ualá;
un error ambiguo conserva esa reserva y no crea otro checkout al reintentar.
Estado privado `payments-private/uala-pilot.json`, archivo 600 y lock estable:
flock más reemplazo atómico de JSON. No borrar ni restaurar una versión vieja.
El enlace de checkout debe ser HTTPS en un dominio Ualá permitido; el importe,
UUID y referencia se verifican. `uala-pilot-hook.php` usa una capacidad aleatoria
por orden, ignora el estado del POST y consulta el UUID almacenado con el token
Ualá. El worker vuelve a consultar Ualá antes de ofrecer el trabajo. Estado
APPROVED y coincidencias de importe, referencia, entorno y merchant son
obligatorios. PROCESSED queda pendiente; contradicciones requieren revisión.
La prueba vence a los siete días si no fue entregada.

El puente `uala-pilot-worker.php` exige HMAC-SHA256 de timestamp y body, ventana
de cinco minutos, TLS y límite de tamaño. Respuestas firmadas incluyen un nonce
único por petición para impedir aceptar una respuesta de otra consulta. El
secreto independiente `sandbox-worker-token` es generado en el VPS, se copia
privadamente a cPanel y no se muestra en el panel ni se guarda en GitHub. No
reutiliza el token Atlas, claves Ualá ni contraseñas SQL.

`tools/test_uala_pilot.ps1` conecta localmente a **MuOnline43**. `-Install` crea
únicamente la tabla `dbo.MUPanicUalaSandboxPilot` y un token de prueba; no suma
monedas. Sin opciones consulta el saldo. `-Run` consulta la orden y entrega
solo si pruebacoin está **desconectada** (ConnectStat=0, NULL no se acepta), para
evitar que una sesión con saldo en memoria sobrescriba la prueba. `-Run
-WatchSeconds 900` observa hasta 15 minutos; es un proceso temporal, no una
tarea permanente. Salir y reejecutar es seguro porque el registro vive en SQL.

SQL toma un applock exclusivo y usa una transacción para `WZ_SetCoin` + registro
de entrega. Una fila singleton, claves únicas de orden/pago y cantidades fijas
impiden una segunda acreditación aun con dos workers o tras perder la respuesta
HTTP. Se comprueba el delta de WCoin C y que WCoin P/Goblin Points no cambien;
fallos revierten la transacción. La respuesta web se reconoce solo tras COMMIT.
Si ese reconocimiento falla, la siguiente ejecución envía el recibo SQL sin
sumar monedas. No se inserta una billetera faltante.

`-Revert` retira exactamente 1.000 WCoin C una vez, exige cuenta offline y saldo
suficiente, y marca el registro como revertido en la misma transacción. Conserva
la moneda de la prueba anterior y otros cambios de saldo; no restaura un saldo
antiguo ni elimina el historial. También reconoce la reversión en la web. No
borrar la tabla ni sus registros para "reiniciar" la prueba. Antes de producción
deshabilitar este piloto retirando `sandbox-worker-token` del hosting y no
ejecutar más el worker. Se conservan los comprobantes privados y SQL.

Validación local: pruebas de aprobación auténtica con API simulada, importes y
entornos incorrectos, CSRF/admin, replay, excepción ambigua, dos procesos PHP
creando simultáneamente un solo checkout, persistencia y recibos de reversión.
No se ejecutó PowerShell/SQL Server contra el VPS ni se realizó un pago Ualá
desde este entorno. La prueba completa queda pendiente de despliegue, instalación,
pago con la tarjeta de test y comprobación del saldo en el cliente. Este piloto
no reemplaza el ledger general, panel de paquetes o procesamiento de producción.

### Diagnóstico de creación

El administrador informó que la instalación SQL/token terminó sin cambios de
saldo; luego la UI mostró un error genérico al crear la prueba. El panel ahora
recarga la reserva incluso si la creación falla, conserva códigos de error
seguros (HTTP numérico, transporte o etapas privadas) y nunca imprime respuestas
ni excepciones del proveedor. El retorno/webhook usa la URL HTTPS fija de beta,
sin depender del protocolo que WebEngine detecta detrás del proxy. El payload
se valida antes de autenticar o enviar la creación. `Revisar reserva sin crear
otro cobro` consulta páginas de 20 órdenes de test y compara la referencia
local; si encuentra una sola coincidencia al completar las páginas, obtiene su UUID con GET autenticado y
vuelve a validar el importe y referencia antes de permitir entrega. Sin match,
con más páginas pendientes, o con varios matches, no se elimina la reserva ni
se crea otra orden. La causa del error observado todavía no está confirmada;
requiere el código de diagnóstico del hosting y/o la inspección de la reserva.

El diagnóstico `RECOVERY_MORE_PAGES` informado por el administrador confirma que
la búsqueda quedó incompleta. Ahora cada consulta avanza una página mediante
`last_search_key`; cursor, coincidencias y contador persisten en el estado
privado, sin exponer datos de otras órdenes. No ofrece entrega hasta terminar
la búsqueda con una sola coincidencia. Detecta cursores repetidos y detiene a
250 páginas; formatos inesperados no eliminan la reserva ni generan un pago.
Las pruebas verifican avance entre solicitudes, escape del cursor, coincidencia
en una página parcial y duplicados en páginas distintas. No cambia el worker
PowerShell ni requiere reinstalar SQL/token.

### Flujo de prueba v2 y conservación del diagnóstico

La búsqueda real recuperó una orden pendiente, pero sin URL de checkout. Una
reposición explícita produjo `CHECKOUT_LINK_UNAVAILABLE`: identidad, referencia
e importe eran válidos, pero el enlace faltaba o no pasaba la validación. No se
conservó ese valor en las versiones anteriores; no se puede deducir su causa
ni recuperar el enlace inventando una URL a partir del UUID.

El flujo v2 guarda **antes de validar** una selección limitada de la respuesta
en `uala-pilot.json` privado: UUID, importe, referencia y checkout link hasta
4096 caracteres. Omite clientes, tarjetas, tokens y el cuerpo completo. La UI
solo muestra motivo, dominio y protocolo del enlace, nunca rutas/query privados.
El GET autenticado sigue siendo obligatorio para habilitar la entrega.

Una reserva pendiente de una versión anterior puede reemplazarse explícitamente
una vez con el flujo v2. Antes se vuelve a consultar el proveedor: una aprobación,
rechazo, revisión o enlace recuperado bloquea esa reposición. Se conserva el
intento retirado, cambia la referencia/callback y se reserva el nuevo antes del
POST. Repetir el botón o perder la respuesta no genera otro POST. Un intento
retirado puede seguir existiendo en el sandbox de Ualá; esta web deja de entregar
sus monedas. **La tabla SQL singleton no se borra ni permite dos entregas.**

El panel presenta una acción por estado, un contador visible de espera y bloqueo
de doble envío. El worker devuelve errores seguros firmados y no emite un job
con aprobación cacheada si la consulta fresca falla. PowerShell imprime el código
HTTP o de proveedor y reintenta solo la consulta hasta tres fallas consecutivas;
no reintenta a ciegas SQL ni modifica certificados, tokens o reloj.

Actualizar el script descargándolo nuevamente, sin `-Install`, sin cambiar el
token ni recrear tablas. Desplegar el overlay desde cPanel y luego usar
`Preparar nueva prueba`. Abrir el checkout solamente con tarjeta de sandbox.
Si vuelve a fallar el enlace, desplegar no basta: revisar el diagnóstico conservado
antes de cualquier nueva reposición. No hay garantía de cero fallas externas.

Validación v2: mocks de enlace rechazado preservado, redacción de ruta/query,
omisión de campos sensibles, reinicio de reserva legacy, doble clic, timeout y
bloqueo de aprobación cacheada ante HTTP 503. PHP y layout de la cuenta se
verificaron localmente. El script PowerShell se revisó, pero no se ejecutó aquí
contra Windows/SQL Server. Acreditación real de 1000 WCoin y reversión aún pendientes.

El diagnóstico real del flujo v2 confirmó `LINK_NOT_ALLOWED` con HTTPS y el host
`stage-uala-arg-bis-link-de-pago-web.vercel.app`. Ese es el enlace devuelto al crear
la orden de sandbox; el GET posterior no incluye el enlace. Se agrega ese nombre
**exacto y solo en test**, sin habilitar otros tenants, subdominios ni HTTP.
Una consulta canónica que confirme la misma orden, referencia e importe recupera
el enlace privado ya guardado y cambia el diagnóstico a `CHECKOUT_LINK_RECOVERED`.
No se requiere una cuarta compra ni otro reinicio; el pago continúa pendiente.


### Corrección de importe de sandbox (flujo v3)

El 1/10/2026 el checkout real de sandbox mostró $100.000 al enviar `100000`,
cuando la prueba debía costar $1.000. La documentación v2 tiene una tabla de
centavos y ejemplos decimales contradictorios. Para sandbox enviamos ahora
`1000.00` pesos y normalizamos el importe de POST/GET a 100000 centavos en el
dominio. No se cambió el comportamiento de producción, que sigue sin habilitar
ventas y requiere verificar sus unidades antes de abrir cobros.

El panel oculta enlaces e instrucciones de entrega de pilotos anteriores a v3.
El botón «Preparar prueba de $1.000» consulta el proveedor y solo retira el
intento anterior si confirma exactamente el importe equivocado de $100.000,
la misma identidad y referencia, el merchant configurado y estado sandbox
pendiente o aprobado. El intento queda en revisión e historial privado. Una
reserva v3 previa al POST impide repetir la corrección incluso ante timeout.
El worker no despacha pilotos anteriores a v3 y mantiene la acreditación única
SQL. No elimina ni reembolsa órdenes en Ualá; la operación anterior fue simulada.
La nueva prueba debe mostrar $1.000 antes de completar la tarjeta de sandbox.
