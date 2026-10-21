# Vessara: salón del mercado

El diseño integra el banner existente con un ambiente oscuro de piedra, cobre y velas. Las métricas siguen usando datos reales y se presentan en una franja compacta. La navegación general no cambia.

Los destacados permanecen en su carrusel propio. Las publicaciones comunes conservan su consulta, filtros, ocho objetos por página y exclusión de destacados. La grilla utiliza cuatro vitrinas por fila en escritorio, tres en pantalla intermedia y una en celulares angostos. Si no hay publicaciones comunes, aparece una explicación sin mostrar objetos ficticios; los destacados permanecen disponibles.

Los tooltips y las opciones reales de los objetos se mantienen. El arte de fondo de cada vitrina no contiene ítems; las imágenes de objetos se superponen usando las fuentes verificadas existentes. No se agregan botones de compra web, ya que el intercambio se completa dentro del juego.

## Recursos nuevos

- `overlay/templates/mupanic/img/market/vessara-hall-v1.webp`: fondo del salón; 1086 × 1448, WebP. Generado con la herramienta integrada de imágenes, a partir de una descripción de una sala medieval oscura con pilares y velas a los lados, centro vacío y oscuro, piedra gastada, bronce, paños rojos y desvanecimiento negro en los extremos; sin texto, personas ni objetos en venta.
- `overlay/templates/mupanic/img/market/vessara-showcase-v1.webp`: fondo vacío de vitrina; WebP de hasta 900 × 600. Generado con la herramienta integrada de imágenes, a partir de una descripción de un pedestal rectangular bajo de piedra negra, luz cenital dorada tenue, puesto medieval desenfocado en los bordes y centro oscuro vacío; sin texto, marcos ni ítems.

La composición completa tiene un ancho máximo de 1440 px. El banner conserva la ilustración completa, con texto colocado explícitamente a la izquierda; la imagen no se recorta para llenar otro formato. El fondo del salón no se repite y se desvanece hacia el negro. Las publicaciones no llevan un panel exterior que genere un gran rectángulo vacío. Una única publicación queda centrada. Los sprites pequeños se limitan a una ampliación de hasta dos veces su tamaño original. Los fondos son estáticos, sin `background-attachment: fixed` ni filtros de pantalla completa. El control de efectos y las pausas de animaciones decorativas fuera de pantalla siguen funcionando.

## Validación

`tests/market-hall-browser.cjs` comprueba las vitrinas con ocho publicaciones y el caso de destacados sin publicaciones comunes, separación de secciones, imágenes locales y ausencia de desbordamiento en 2048, 1440, 390 y 320 px, incluido el caso de una sola publicación. Comprueba también el banner sin recorte, alineación del texto, fondo sin repetición y límite de ancho de la composición. Las pruebas existentes cubren filtros, paginación, tooltips, joyas, carrusel y navegación móvil.
