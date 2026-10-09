# Tienda de Eryns

El diseño y el copy completo están en `docs/eryns-store-wireframe.md`.
El catálogo visual sigue este orden: hero, vitrina de tres monturas, packs
ilustrados, resumen oscuro, confianza y soporte desplegable.
Los prompts originales están en `docs/eryns-store-images-prompts.md` y
`tools/recharge-pack-art.json`.

## Integración

El HTML se renderiza en `inc/recharge.php` con la sesión y los formularios
existentes. CSS y JavaScript permanecen en `css/recharge.css` y
`js/recharge.js`. No requiere una librería frontend ni un checkout nuevo.

Paquetes, precios y bonos se leen de las ofertas activas de la tienda. La
etiqueta «Mejor valor» exige una ventaja estricta en Eryns recibidos por peso;
no se inventan datos de popularidad ni promociones. La selección inicial
resuelve también el GET donde el controlador entrega todas las cantidades
como cero. No crea una orden antes del envío del formulario.

La vitrina usa `inc/recharge-storefront.json` y lee arte e información del JSON
del Atlas. Se confirmó dejar Theryon, Nerathys y Vaeraxes como próximamente,
con `price_coins:null` y `available:false`. Si más adelante se publica un precio
confirmado y se habilita el producto, «Recargar para este» elige un pack que
alcanza su costo. La vitrina no compra ni entrega el objeto: vende Eryns.

El resumen separa base, regalo, total de Eryns y ARS. La barra fija de celular
envía el mismo formulario con las mismas protecciones. «Mis compras» se abre
sin salir de la ruta, incluso si el documento tiene un `<base href>`.

Se mantienen Ualá, credenciales, límites, CSRF, nonce, pertenencia de órdenes,
SQL `WCoinC`, metadata del wallet, ledger, worker, historial y acreditación.
La tienda cerrada sigue cerrada. La landing de main no cambia.

## Validación

Pruebas con pagos simulados: importes, bonos, combinaciones, formulario, cinco
anchos de 320 a 1440, preselección con cantidades cero, pack 20.000 + 2.000,
selección por objetivo, ventanas de criaturas, historial y botón móvil.
Las pruebas no generan cobros reales ni escrituras en el juego.
