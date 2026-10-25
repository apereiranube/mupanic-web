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

## Revisión V14.1 — 12/10/2026

Guía reorganizada con una sección de los diez vendedores móviles y publicaciones destacadas; tabla de condiciones por objeto; explicación de lotes exactos de cinco joyas sueltas compatibles, precio total, un cupo por lote y cinco espacios libres para entrega. Cancelar devuelve el lote completo. Incluye las cinco mascotas custom verificadas, monturas excluidas, equipos Harmony permitidos y sockets/joyas empaquetadas bloqueados. La base V14.1 fue confirmada en juego por el administrador. Se conserva lore, monedas, tributo, vigencia y límites. La guía no consulta actividad en vivo.

## Cuadrilla Rota y destacados

Incorpora el lore original aportado por el administrador: diez cazadores traicionan a Vessara y sirven como mercaderes esqueléticos hasta saldar su deuda. Diez reseñas individuales y una ilustración de lore (no captura de skins), sombreros/dagas y oficios. Comisión: 5% normal, 3% adicional por venta destacada, 8% total, solo al vender; sin adelanto. Destacado hasta 24 horas. Ejemplo: 1000 Eryns, comisión destacada 80, neto 920.
