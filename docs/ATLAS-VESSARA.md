# Atlas: guía técnica del Mercado de Vessara (beta)

## Alcance

Entrada Atlas en /info/#vessara para explicar cómo opera el mercado **dentro del juego**, sin duplicar los contadores, rankings y registros en vivo que ya funcionan en /market/.

Se crea el capítulo de forma aislada en inc/atlas-vessara.php y su hoja de estilos css/atlas-vessara.css (sólo se sirve para page=info). Se agrega un acceso en la navegación lateral y una tarjeta editorial en la portada del Atlas. No toca la lógica del mercado, SQL, GameServer ni al Pacto de los Ocho, que ya figura en Vínculos de PANIC.

## Reglas publicadas

- NPC: Vessara, Loren Market (114,143).
- Consulta: Hub F11 > Mercado de Vessara.
- Publicar, comprar, cancelar y cobrar: NPC dentro del juego.
- Monedas: Zen y Eryns, una moneda por publicación.
- Tributo: 5% de cada venta completada, descontado al vendedor y redondeado hacia abajo. Publicar/cancelar es gratis.
- Límites simultáneos compartidos entre ambas monedas: Free 3, VIP MU PANIC 10.
- Vigencia: 7 días por publicación.
- Una publicación no cuenta como una venta. Una venta se registra después de confirmarse compra y entrega.
- Las operaciones comprobadas conservan estado a través de reconexión; no se promete disponibilidad sin fallos.
- No se divulgan rutas internas de consulta ni comandos técnicos.

La introducción sobre La Fractura y el Sello es la historia breve que ya aparece en la sección pública del mercado; no conecta artificialmente a Vessara con el Pacto de los Ocho.

## Verificación y despliegue

Ruta directa /info/#vessara. Comprobar estado inicial del capítulo (atlas-boot), navegación lateral, buscador global que indexa el título, vista sin JavaScript, FAQ nativo, imagen de Vessara y lectura móvil. La guía no consulta endpoints dinámicos.

El commit se envía a la rama beta. Publicación en beta.mupanic.com.ar sólo después del despliegue existente en cPanel: Update from Remote y Deploy HEAD Commit. No modificar webengine.json ni producción (main).
