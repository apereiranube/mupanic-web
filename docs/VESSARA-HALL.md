# Vessara: implementación del render aprobado

El mercado utiliza arte nuevo basado en el render aprobado del 21 de octubre de 2026. La composición conserva el ambiente continuo negro, oro antiguo y telas borgoña, Vessara a la derecha, título a la izquierda, franja de actividad y vitrinas ornamentales. El encabezado global no cambia.

Los destacados permanecen en su carrusel independiente. Las publicaciones comunes conservan filtros, ocho objetos por página, opciones y tooltips reales. Una publicación común queda centrada; sin publicaciones se muestra el estado vacío y se conservan los destacados. No se crean publicaciones ficticias ni botones de compra: el intercambio se completa en el juego.

## Arte nuevo y prompts

Generado con la herramienta integrada de imágenes, utilizando el render aprobado como referencia visual para las tres primeras piezas. Los PNG de origen se convirtieron a WebP; el ícono transparente se redujo a 256 px para su uso a 55–74 px. No se usan textos ni objetos falsos pintados sobre las imágenes.

Directorio: `overlay/templates/mupanic/img/market/`.

- `vessara-bazaar-hero-v2.webp` (1942 × 809): escena panorámica nueva. Prompt: mercado medieval íntimo, velas cálidas, telas borgoña, filigrana antigua; Vessara adulta en vestido negro a la derecha sellando un contrato, cabeza completa con margen, centro e izquierda oscuros para HTML legible; sin interfaz, texto, tarjetas ni logos.
- `vessara-bazaar-world-v2.webp` (1086 × 1448): continuidad del ambiente. Prompt: bazar medieval oscuro con puestos y velas a los extremos, paños borgoña, arquitectura antigua y centro atmosférico; sin pilares monumentales, personajes, objetos de venta ni interfaz.
- `vessara-bazaar-card-v2.webp` (1070 × 1470): vitrina vacía. Prompt: marco delgado de oro antiguo con esquinas grabadas, puesto oscuro con pedestal circular bajo en el área superior y cuero negro en el tercio inferior reservado para título, vendedor y precio HTML; sin ítems, letras ni botones.
- `vessara-zen-coins-v2.webp` (256 × 256, alfa): ícono Zen. Prompt: pila de monedas antiguas doradas con sello solar, tres alturas y moneda apoyada en primer plano, iluminación cálida y detalle realista legible a 64 px, aislada con transparencia auténtica; sin letras, números ni fondo.
- `vessara-bronze-frame.svg`: marco escalable para actividad y estadísticas; conserva esquinas decorativas sin estirar una ilustración completa sobre cada bloque.

La imagen del hero mantiene su proporción completa en escritorio, sin máscara lateral que corte a Vessara ni movimiento del arte. En celular se recorta el ambiente con la cara conservada y se coloca el texto sobre la parte inferior sombreada. El fondo se dibuja una sola vez en una capa fija sin animaciones ni repetición. Los sprites pequeños conservan el límite de ampliación; las joyas utilizan las imágenes existentes del Atlas.

## Validación

`tests/market-hall-browser.cjs`: 2560, 1920, 1440, 390 y 320 px; ocho objetos con imágenes locales, destacado separado, una publicación, estado vacío, proporciones del hero, fondo continuo, marcos y ausencia de desbordamiento. Con `MARKET_SCREENSHOT_DIR` guarda capturas reales del navegador; los nombres, precios y métricas son datos de prueba, nunca se despliegan en producción. Incluye vistas completas de escritorio para comparar la composición con el render.

También se verifican interacciones del mercado, filtros y actualizaciones de datos, carrusel, modal móvil y suspensión de efectos fuera de pantalla mediante las pruebas existentes.
