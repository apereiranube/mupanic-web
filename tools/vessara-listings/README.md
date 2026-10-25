# MU PANIC — Mercado de Vessara: vidriera de publicaciones (beta)

## Estado del trabajo — diagnóstico VPS 14/10/2026

Los metadatos y procedimientos SQL reales están confirmados en `MuOnline43`. El diagnóstico V2 muestra **47 filas acumuladas** en `PanicMarketPublishedProbe`, **10 puestos** en `PanicMarketPromotion` y **1 puesto de prueba** en `PanicMarketSpotlightTrial`. Las 47 filas no equivalen a ofertas activas.

La web `/market/` mantiene su historial de ventas confirmadas desde `dbo.PanicMarketPublicSales` y añade la vidriera de publicaciones mediante una vista separada: **`dbo.PanicMarketPublicListings`**. El SQL está en `tools/vessara-listings/VISTA_PUBLICACIONES.sql`.

## Contrato público

Campos exactos: `ListingID`, `Seller`, `Item`, `ItemIndex`, `Level`, `Currency`, `Price`, `ExpiresAt`, `FeaturedUntil`.

La vista cruza `PanicMarketPublishedProbe` con `PanicMarketPersistentProbe`, usa `PanicMarketBrowseCatalog` para el nombre del ítem y filtra estrictamente `State='STORED'`, `Expires>SYSDATETIME()`, `Currency IN (0,1)`, precio válido y ausencia de cualquier compra iniciada en `PanicMarketBuyProbe`. No incluye objetos de la moneda vieja (MarketCoin), pendientes, cancelados, entregados ni vencidos.

El campo `FeaturedUntil` sale únicamente de `PanicMarketPromotion` cuando el puesto coincide con el dueño real y sigue vigente. **No** incluye `PanicMarketSpotlightTrial`: ese objeto es una prueba restringida, no un destaque público pago.

El detalle de inventarios, códigos binarios, seriales, balances y cuentas no se expone. La conversión del objeto se hace dentro de SQL, usando las mismas fórmulas de índice y nivel del procedimiento existente.

## Instalación y despliegue

El instalador de VPS se entrega como `MU_PANIC_VESSARA_WEB_V1.zip`, con `INSTALAR_VPS.ps1`, `VISTA.sql`, `REVERTIR_VPS.ps1` y `LEEME.txt`. **La creación de la vista en el VPS debe confirmarse con el usuario antes de dar por conectada la web.**

**Salidas, informes y backups del VPS siempre en `%USERPROFILE%\\Downloads`**, nunca en Escritorio.

Secuencia: instalar solo la vista SQL en el VPS Windows (sin reiniciar GS) → verificar conteos reales → desplegar rama `beta` desde cPanel → probar `https://mupanic.com.ar/market/`. No modificar `main` ni Atlas como parte de la vidriera.

Mientras no exista la vista, el endpoint de publicaciones devuelve 503, y el histórico de ventas de `PanicMarketPublicSales` sigue operando. No mostrar objetos ficticios ni snapshots de ofertas obsoletas. Los íconos de categoría actuales no son renders exactos del cliente; las imágenes individuales y opciones detalladas requieren una siguiente integración visual.

Reglas del juego que no se cambian: publicar/cancelar gratis, 5% de comisión normal, 8% total en venta destacada, hasta 24 horas de promoción, compraventa exclusivamente dentro de MU PANIC.
