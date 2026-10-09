# Tienda de Eryns

La recarga tiene un diseño independiente del panel de cuenta: escena original
con Theryon, Aurion y Vaerion, selección compacta de importes y un único resumen
de compra. La navegación lateral y el encabezado genérico del panel se ocultan
solo mientras se muestra `.eryns-store`; «Mi cuenta» sigue disponible arriba.

## Compra

Los cinco paquetes del catálogo se presentan como un selector de una sola
recarga. Cada selección sustituye cantidades anteriores. Para combinar o repetir
paquetes se abre «Combiná paquetes», con los mismos campos `quantity[wcoin-*]`.
Se conserva una selección enviada; al abrir por primera vez se propone una
unidad del destacado administrativo, o del paquete central si no hay destacado.
No se crea una orden hasta que el jugador envía el formulario. Importes y bonos
siempre proceden del catálogo vigente, y el servidor los vuelve a validar.

La exhibición de criaturas usa pestañas y ventanas nativas dentro de la tienda.
No tiene enlaces al Atlas ni navegación externa de productos. Las fichas leen
nombre, efectos y obtención del mismo JSON del Atlas. Se muestran disponibilidad
y probabilidades sin prometer acceso inmediato a productos aún no habilitados.
Las pestañas permiten teclado; las ventanas cierran con Escape y devuelven foco.

## Compatibilidad

Ualá, credenciales, límites, precios, worker, SQL `WCoinC`, metadata del wallet,
CSRF, nonce, ledger, propiedad de órdenes, historial y seguimiento siguen iguales.
El nombre público es Eryns. La selección no crea pagos; el botón prepara el
checkout existente. Con un pago aprobado hay que desconectarse del juego para
la entrega. El rediseño no habilita una tienda cerrada ni cambia main.

## Arte y pruebas

El arte original integrado está en `img/recharge/eryns-legends-v3.webp`.
El prompt completo y las referencias de diseño están documentados en
`tools/recharge-eryns-v3-art.json`. Se creó con image_gen integrado.
El despliegue verifica su presencia antes de copiar.

Las pruebas de navegador cubren cinco anchos de 320 a 1440 píxeles, selector,
combinaciones, límites, importes, ventanas y retorno a la compra sin salir,
seguimiento de pago, tienda cerrada, checkout preparado y contrato del POST.
Las pruebas de dominio y órdenes usan pagos simulados: sin cobros reales.
