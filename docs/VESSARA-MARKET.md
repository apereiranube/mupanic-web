# Mercado de Vessara — live trading floor

Dedicated public page at `market/` in the beta template; Mercado follows El servidor in shared navigation. Deploy through cPanel Update from Remote → Deploy HEAD Commit. This revision needs no VPS/game/client installation; the previously installed public SQL view remains the data source.

## Public data and correctness

Three SELECT queries read `[MuOnline43].[dbo].[PanicMarketPublicSales]` through the existing WebEngine MuOnline connection. The view exposes nine public fields, and includes only delivered purchases in DELIVERY_SAVED, CREDIT_ATTEMPTED or CLOSED. Cancellation, incomplete delivery and legacy MarketCoin do not count as sales. Seller collection does not add another sale. The public projection excludes account identifiers, serials, item bytes, inventories, balances and connection errors.

Metrics show today's delivered trades, lifetime gross Zen/Eryns trading volumes and today's volumes. Currency volumes are separate decimal strings using SQL bigint SUM and JS BigInt. They measure turnover, not existing currency supply. No synthetic prices, players, random counters, fake trends or invented demand appear in production. Mini sparklines and the main seven-day chart use actual daily data, including zero days. Yesterday's comparison is a literal completed-day trade count, not a growth percentage comparing partial periods.

Last 40 purchases show actual item name/level, character names, price, currency and Argentina-local delivery date. Search and currency filters apply to these rows, while summary metrics remain market-wide. SQL dates are VPS-local Argentina; GETDATE defines the current day and displayed timestamps carry -03:00.

## Live behavior

Both shared cache TTL and browser polling are 15 seconds. The browser stops polling while hidden, keeps search/currency selections and aborts stuck requests after ten seconds. The visible countdown is to the next read, not an item expiry or promotional deadline. The clock displays Argentina time.

The moving ticker repeats labelled historical transactions with date/time. Hover or focus pauses it; the pause button is persistent and keyboard accessible. Reduced-motion disables motion. Initial historic sales never produce a new-sale notification. After a fresh update, newly seen delivery IDs at or after the previous latest delivery trigger a notification and highlight new feed/table rows for six seconds. Unchanged responses do not restart the ticker or animate counters. Older imported records and stale snapshots do not announce new live sales.

Numeric updates interpolate in BigInt over 800ms, then land on the exact total. Initial values render directly. Font fitting waits for fonts and responds to resize without splitting digits. Figures remain dashes if unavailable. Failed queries retain up to 24 hours of cached data labelled stale and mark the connection waiting; old/missing data never masquerades as zero/current activity.

## Visual direction

An illustrated Vessara hero opens onto a layered fantasy trading floor: spinning seals, gold coin and violet crystal currency cores, sculpted translucent panels, high-contrast luminous prices, floating category emblems on treasure pedestals, an amber scrolling transaction tape, actual daily sparklines, a chronological purchase feed and a daily activity chart. Creating an account and downloading the client link to existing routes. The NPC illustration and seal follow the established game/world identity.

## Verification

PHP checks cover projection, precision, error/empty states and cache behavior. Playwright checks five viewport sizes, navigation/title layout, overflow, exact counter fitting, filters/search, reduced motion, ticker pause, historical silence and new-sale notifications. UI fixtures are invented test data and never used in production. Live SQL itself can be checked by comparing the endpoint to:

```sql
SELECT Currency,COUNT_BIG(*) AS Tratos,SUM(CONVERT(bigint,Price)) AS Volumen
FROM dbo.PanicMarketPublicSales GROUP BY Currency;
```

## Living exchange scenery

The layered hero, seal rings, orbiting currency emblems, currency-core panels and floating treasure pedestals form the presentation scene. Canvas particles are decorative lights, never players, trades or market metrics. Drawing is capped at roughly 30fps with 52 desktop / 22 mobile particles and DPR 1.5; frames stop in a hidden tab or with effects paused. CSS effects also pause in hidden tabs. Reduced-motion disables all atmosphere and sale-impact effects. The prominent effects button lets visitors pause decorative motion while continuing to receive market data.

Treasure pedestals show the latest sale for up to four distinct itemIndex/level combinations. Category emblems are explicitly symbolic and identified as staff/armor/etc; they do not claim to be exact in-game model photographs. Titles, prices, character names and timestamps are the real public records. A newly confirmed delivery fires one restrained light ripple; initial history, stale/unchanged responses and older imported rows never fire it. Pointer tilt/parallax is decorative and disabled for touch/reduced-motion.

New-sale receipts display the real latest newly delivered item, price, buyer and seller, with a connecting light arrow. If several purchases arrive in a poll, the receipt identifies the count and displays the newest one.

## V2 — Imágenes reales, opciones y destacados (14/10/2026)

La sección /market/ conserva el registro de ventas de PanicMarketPublicSales y consulta
por separado las publicaciones activas desde PanicMarketPublicListings.
El catálogo mantiene búsqueda, filtros Zen/Eryns, paginación de 24 ítems y actualización cada 30 segundos.

### Imágenes verificadas del Atlas

inc/market-item-images.json reúne 38 referencias locales unívocas de los manifiestos reales
del Atlas: íconos originales y renders 2D de modelos BMD y texturas del cliente.
Excluye las ilustraciones generadas con IA y las variantes ambiguas.
Para índices sin recurso local, JS intenta rutas de imágenes 2D originales documentadas
en los manifiestos de fuentes (mureforge.com y bless.gs). Si fallan, muestra
Imagen no disponible: no reemplaza la foto por un símbolo que aparente ser el objeto.

### Opciones del objeto

La ampliación SQL V2 solo devuelve valores públicos normalizados desde ItemCode S6:
Skill, Luck, nivel, durabilidad, opción adicional, máscara Excellent, Ancient, Harmony,
indicador 380, sockets y cantidad por lote. El navegador nunca recibe ItemCode crudo,
seriales, AccountID ni datos de inventario. Las máscaras Excellent de armas y
defensa usan los nombres de opciones estándar; otras familias se identifican
sin inventar bonificaciones custom que no consten en la configuración del servidor.

El tooltip aparece con hover de mouse, foco de teclado y clic. Escape lo cierra.
En móvil funciona como panel inferior con cierre y fondo oscurecido.

### Destacados

El bloque premium da protagonismo a los diez puestos de PanicMarketPromotion con
imágenes grandes, insignia dorada, temporizador y opciones accesibles.
No toca asignaciones, precios, comisiones ni transacciones del juego.

### Despliegue

La vista SQL V1 fue confirmada por el VPS el 14/10/2026 a las 19:45:
13 publicaciones activas y 2 destacadas vigentes en ese momento.
El paso siguiente requiere instalar el paquete MU_PANIC_VESSARA_VISUAL_V2.zip
en el VPS, que guarda copias de seguridad y el TXT en Descargas y permite revertir
la vista original. No reiniciar GameServer. Después desplegar GitHub beta desde cPanel.
La vista V1 sigue permitiendo listar objetos sin opciones mientras no se instale V2.
Confirmar opciones e imágenes contra una publicación real en el cliente antes de
afirmar que todos los tipos personalizados reproducen exactamente el tooltip nativo.

## V3 — Carrusel de promociones y rendimiento (14/10/2026)

### Catálogo habitual
- Exactamente 8 publicaciones por página desde SQL; siguen funcionando filtros por moneda, búsqueda, orden y destacados.
- El cálculo de páginas y el OFFSET/FETCH ocurren en el servidor. El catálogo sigue publicando el total real, no solo la página actual.

### Destacados
- Los 10 puestos reales de promoción permanecen disponibles, pero se muestran en UNA SOLA franja horizontal compacta.
- Diseño en rojo oscuro/carmesí, con bordes y placa distintiva que conserva la estética de MU PANIC y el color de moneda.
- Se ven hasta 3 tarjetas a la vez en escritorio, 2 en tablets y 1 con avance lateral visible en celulares.
- Autoavance hacia la izquierda cada 6,5 segundos si la sección está en pantalla y hay desbordamiento. En móvil también permite deslizar con el dedo.
- El carrusel se detiene con hover, foco, tooltip abierto, pestaña oculta y la preferencia de movimiento reducido.
- Cuenta con flechas, posición y un botón de pausa/reanudación. La navegación manual sigue disponible con movimiento reducido.
- La comisión del juego (5% base, 8% venta destacada) y la expiración real no cambian.

### Optimización
- El catálogo NO reconstruye tarjetas ni imágenes cuando los datos de un sondeo son idénticos (excepto updated). Esto evita cerrar tooltips sin necesidad.
- El tablero histórico de ventas tampoco redibuja métricas, sparklines ni las 40 transacciones si no cambian los datos.
- El canvas atmosférico pasa aproximadamente de 30 a 15 FPS, de 52 a 22 partículas en escritorio y 22 a 9 en móvil, con DPR máximo 1,1.
- La carga habitual se reduce de 24 a 8 imágenes por página; las tarjetas del carrusel siguen usando lazy loading.
- El popup MU existente se conserva: ninguna alteración a las opciones ni a la base de datos ni a los procesos del juego.

### Pruebas y despliegue
- Tests PHP para límite SQL de 8 ítems; test de navegador con diez destacados, avance, pausa, reducción de movimiento, dos páginas y sondeos idénticos.
- Cambios exclusivamente en rama beta. Desplegar por cPanel Update from Remote y Deploy HEAD Commit.
- No requiere PowerShell, cambios de SQL, reiniciar GameServer ni modificar cliente.

## V4 — Vidriera simplificada y cinta continua de destacados

- Los destacados ahora se desplazan **continuamente hacia la izquierda**, sin pausas entre ítems ni saltos de página. Dos grupos equivalentes y una sola transformación CSS permiten un bucle uniforme, incluso con 1 o 2 promociones activas; con diez, el bloque conserva una única fila.
- Se pausa al pasar el mouse sobre el carrusel, enfocar sus elementos, abrir detalles, cambiar de pestaña o quedar fuera de la pantalla. El botón permite pausar/reanudar manualmente. Se respeta `prefers-reduced-motion`: sin movimiento automático y con desplazamiento táctil/manual.
- Las repeticiones internas de la cinta son solo una técnica de animación. Las copias tienen `aria-hidden` y no entran en el recorrido de teclado. El listado general **excluye** todas las ofertas que todavía tengan una promoción válida; tampoco las cuenta en sus ocho ítems por página, búsquedas ni totales ordinarios.
- Se eliminan del encabezado de cada tarjeta los textos de expiración, contador de promoción y “Ver opciones del objeto”. El tooltip también omite el ID interno del objeto y su vencimiento; mantiene opciones, vendedor, imagen y precio. El filtrado SQL continúa excluyendo ventas, cancelaciones y fechas vencidas.
- Colores según atributos públicos reales: Excellent verde, Socket violeta, Ancient azul, Harmony dorado, normal neutro. Nivel +15 rojo luminoso, +13/+14 ámbar dorado, +10–12 cobre, +7–9 sobrio y +1–6 discreto.
- Sin cambios en `PanicMarketPublicListings`, comisiones, motor de compraventa o scripts del cliente. Se utiliza únicamente la rama beta de GitHub. No se requiere PowerShell ni reiniciar GameServer.
- Tests de navegador en 1366px y 390px, diez promociones, dos promociones, pausa/reanudación, movimiento reducido, color por opción/nivel, no duplicación en catálogo y ocho objetos por página.
