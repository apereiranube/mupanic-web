# MU PANIC: recargas de WCoin C

## Decisiones confirmadas

- Moneda: WCoin C para la cuenta del juego, no créditos genéricos de WebEngine.
- Ualá Bis será la pasarela principal, decisión de Agustín del 1/10/2026. Credenciales disponibles según lo informado; conector v2 preparado, cobros todavía no habilitados. Documentación oficial: https://developers.ualabis.com.ar/.
- Mobbex queda como alternativa. Alta solicitada por Agustín; espera informada de 72 horas.
- MercadoPago queda opcional y desactivado. Nunca mostrarlo como disponible sin activación expresa.
- Precios, cantidades y bonos todavía no definidos. Catálogo vacío: no inventar ofertas ni saldos.
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
GET autorizado. Amount es centavos enteros, no pesos decimales. Solo APPROVED
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
siguen pendientes. Los paquetes/precios todavía no fueron definidos.

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
