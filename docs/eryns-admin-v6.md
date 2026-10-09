# Administrador de tienda y VIP único · V6

El panel mantiene su ruta `usercp/shopadmin/`, acceso exclusivo a `panic`, CSRF y registro de recargas. Se organiza en packs, monturas, VIP único, compras y entrega. La estética es oscura, con precios y vistas previas legibles.

## Packs

Eryns base determina el precio ARS; Eryns de regalo se suma cuando el bono está vigente y el worker es compatible. La vista previa muestra monedas recibidas, importe y regalo. No se cambian valores existentes ni compras ya creadas.

## Monturas manuales

Theryon, Nerathys y Vaeraxes tienen precio normal, precio VIP y habilitación del objetivo de recarga. Copiar el precio exacto de X permite respetar su redondeo sin agregar sincronización. La web usa el precio VIP solo cuando confirma que la membresía está activa. Sin precio VIP confirmado, no habilita ese objetivo para una cuenta VIP. Las recargas genéricas siguen disponibles.

Guardar estos valores no modifica el juego. El botón prepara Eryns suficientes con los paquetes vigentes; el usuario sigue comprando el objeto dentro de X. No se entregan objetos ni se reserva su precio. Revisar ambos precios web al cambiarlos en el juego.

## Un solo VIP

La presentación usa una membresía, un precio y una duración. El salón ya no muestra tres tarjetas de duración ni una comparación que sugiera planes diferentes. Beneficios reales publicados aparecen como tarjetas; cada detalle se despliega.

El descuento confirmado se muestra como **10% en X**. EXP y drop son borradores ocultos, sin porcentajes ni tasas inventadas. Hay seis espacios editables de título, detalle y publicación. Precio y duración vacíos se muestran como `[COMPLETAR]`.

Con precio configurado, cuenta normal: **Activá tu VIP**; VIP activo: días restantes y **Extender VIP**. Ambos botones preparan una recarga suficiente según los packs y bonus vigentes y llevan al resumen **Pagar con Ualá**. No envían el pago automáticamente. Sin precio configurado: **Conocé el VIP**, con información desplegable. La activación y renovación siguen dentro del juego. No se crean niveles ni suscripciones con cobro automático.

## Almacenamiento y límites

Monturas y presentación VIP se guardan en `recharge-storefront.json` dentro del directorio privado existente de pagos. El catálogo de packs continúa separado. Guardado atómico, bloqueo y revisión evitan sobrescribir cambios de otra ventana. Los despliegues no reemplazan esta configuración privada. Los datos se validan en servidor y se escapan al mostrarlos.

No hay endpoints nuevos, conexiones al VPS, credenciales nuevas ni escrituras SQL. El precio y cantidad del pago siguen calculándose desde el catálogo de recargas en servidor; los objetivos visuales no pueden determinar importes de Ualá.

## Validación

Pruebas de administración en escritorio y celular; guardado POST y rechazo de CSRF incorrecto; acceso restringido, concurrencia y valores inválidos; precios manuales normal/VIP reflejados en el objetivo; checkout y bonos existentes; membresía única, estado activo/desconocido y movimiento reducido. Todas las pruebas usan archivos temporales y pagos simulados.
