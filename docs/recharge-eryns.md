# Panel de recarga de Eryns

El nombre público de la moneda es **Eryns** en `/usercp/recharge/`, el menú
de cuenta, el VIP, la administración, el historial y las nuevas descripciones
de compra enviadas a Ualá. Los nombres históricos de paquetes se adaptan
al mostrarlos, sin reescribir compras guardadas.

El panel usa arte original de una tesorería de Eryns, una portada oscura,
saldo y cuenta de destino visibles, paquetes ilustrados, cantidades editables,
resumen de compra, seguimiento de estados y guía de entrega. En celular,
una barra muestra el total y lleva al resumen; no inicia ni duplica pagos.
Los estilos están limitados a `.recharge-shop--eryns` y no cambian el editor
de administración ni otros módulos de cuenta.

## Compatibilidad del cobro

- Se mantienen las credenciales privadas, entornos habilitados y condiciones
  que permiten comprar; el rediseño no abre una tienda cerrada.
- Se conservan cantidades, límites, precios en centavos, bonos y validaciones.
- Los identificadores `wcoin-*`, el campo SQL `WCoinC`, la clave interna
  `wallet: WCoin C`, el ledger y el worker del VPS siguen iguales.
- Los formularios mantienen CSRF, nonce, acciones y ruta. Ualá sigue generando
  el enlace y verificando el pago; la entrega se realiza por el worker existente.
- El estado pendiente de desconexión continúa visible: para recibir la
  acreditación, la cuenta debe salir del juego.
- No se modifican el cliente del juego, SQL ni las órdenes históricas.

## Arte y despliegue

`img/recharge/eryns-treasury-v1.webp` se generó con la herramienta integrada
image_gen. Es arte conceptual de moneda del juego, sin logotipos de pagos.
El prompt completo, dimensiones y hash están en
`tools/recharge-eryns-art.json`. El despliegue de beta verifica el archivo
antes de copiar el overlay. Main conserva su landing.

## Validación

Los tests PHP de dominio, Ualá, pedidos, configuración, wallet y piloto usan
transportes simulados y almacenamiento de prueba. No generan cobros reales.
El test de navegador revisa el panel a 1440, 1024, 768, 390 y 320 píxeles:
totales mixtos, límites, controles táctiles, estados, recursos y desborde.
Incluye tienda cerrada, enlace preparado, historial vacío y contrato del POST.
El navegador de administración verifica edición, VIP y desborde a 1440,
1024 y 390 píxeles.

## Presentación comercial

La portada conduce a paquetes mediante una llamada visible. Una vitrina de
vínculos, monturas y exclusivos enlaza sus capítulos del Atlas. Presenta
objetivos del juego; precios y disponibilidad se consultan en la tienda del
cliente. No anuncia disponibilidad ni resultados garantizados de mezclas.

Las tarjetas de compra usan fondos claros, cantidades y precios grandes,
bonos reales cuando están activos y un botón para agregar una unidad. El
selector de cantidades sigue disponible. El destacado respeta la selección
administrativa; si no hay ninguno, resalta editorialmente el paquete central
sin afirmar popularidad ni ahorro. No se crean promociones ni cambian precios.
Una nota explica que las recargas aportan al mantenimiento del servidor.
