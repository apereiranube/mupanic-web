# MU PANIC: recargas de WCoin C

## Decisiones confirmadas

- Moneda: WCoin C para la cuenta del juego, no créditos genéricos de WebEngine.
- Mobbex será la primera pasarela. Alta solicitada por Agustín; espera informada de 72 horas.
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
6. Webhook autenticado por mecanismo acordado con Mobbex; MercadoPago usa su
   firma oficial cuando se habilite. Verificar mediante GET del proveedor antes
   de registrar aprobación. Los eventos se guardan de forma durable y se
   procesan/reintentan sin depender de que el jugador mantenga abierta la web.
7. Conciliación programada de pagos pendientes y trabajos de entrega fallidos.
   No depender exclusivamente del webhook. Reembolsos/contracargos crean un caso
   de revisión; no descontar automáticamente monedas ya gastadas.
8. Historial real por sesión; administración con roles del CMS y CSRF para
   paquetes, disponibilidad, bonos y casos de revisión. Auditoría de cambios.
   Definir expiración de bonos y conservar el snapshot de compras anteriores.
9. Mobbex sandbox, aprobación, rechazo, retorno abandonado, webhook repetido,
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
