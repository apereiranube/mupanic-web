# Campaña de lanzamiento

Beta conserva el sitio completo. La campaña se revisa en `https://beta.mupanic.com.ar/?preview=launch`.

La portada es una secuencia de escenas completas: batalla de lanzamiento, manifiesto No Pay to Win, economía de Zen y joyas, cliente personalizado y Hub F11, selector de eventos y comunidad. Cuatro separadores con luz conectan los cambios de escena. El Hub usa una vista completa contenida, sin recorte ni ampliación como fondo; sus funciones se explican junto a la imagen. El bloque del cliente incorpora una captura de ingreso mejorada desde una imagen proporcionada por el usuario y una segunda vista independiente del Hub, seleccionables con tabs Ingreso / Hub F11. Los tabs de eventos, del cliente y el acceso superior a Discord comparten el acabado angular del CTA principal. Reemplaza la estructura anterior de encabezado lateral y tarjetas. Los únicos enlaces externos son a Discord; no tiene accesos al Atlas ni al registro.

## Apertura sin fecha anunciada

`inc/launch-config.php` contiene `launchAt = null` y `launchDateLabel = null`, por pedido del usuario. La campaña muestra PRÓXIMAMENTE con iluminación y una línea de energía animada, sin fecha ni cuenta regresiva. La fecha se anunciará en Discord. El día 31 se retiró de la portada; no es una fecha aprobada.

El contador sigue disponible para cuando se confirme la apertura: definir `launchAt` con zona horaria explícita y su etiqueta visible. Se detiene en cero; nunca anuncia automáticamente que el servidor está abierto. La fecha antigua sólo existe en la fixture de pruebas como caso artificial del contador opcional.

## Datos y rutas

Los rates Free se obtienen de `panicAtlasBalance()`, la misma fuente sincronizada del Atlas. No se promete latencia sin mediciones. Survivor y arenas por clase mantienen fecha a anunciar, independiente del contador de lanzamiento.

`productionMode = coming-soon` activa la campaña solamente en el inicio de producción cuando la versión aprobada se promueve a main. Las rutas interiores siguen funcionando; la portada no representa un bloqueo de registro ni de acceso a esas rutas. El enrutamiento usa la URL configurada de WebEngine, no el encabezado Host. Para restaurar el inicio completo cambiar `productionMode` a `website`, revisar en beta y promover a main.

## Arte y movimiento

Arte original de batalla en WebP, con composiciones horizontal y vertical independientes; las rutas nuevas evitan la caché anterior. Prompts y archivos finales: `tools/launch-art.json` (herramienta integrada `image_gen.imagegen`). Ambos archivos se distribuyen dentro de `overlay/templates/mupanic/img/launch/`.

Canvas de brasas, rayos laterales e impactos; entrada del título, iluminación, franja animada de rates, desplazamiento suave de fondos y botón de pausa. Resolución canvas limitada a DPR 1.5, densidad menor en mobile y límite de 30 FPS. Los efectos se detienen al ocultar la pestaña o salir del hero, y respetan movimiento reducido. No hay sonido automático, scroll forzado ni pantalla de acceso obligatoria. El contenido y todas las descripciones de eventos están disponibles sin JavaScript.

El selector de eventos incluye tabs con navegación por flechas, Home y End, foco visible y paneles relacionados. El contador evita anuncios por lector de pantalla cada segundo.

## Revisión y despliegue

`php tests/launch-gate.php` comprueba el modo y las rutas. `tests/launch-browser.cjs` comprueba 1920, 1366, 1024, 768, 390 y 320 px, assets visibles, tabs, teclado, pausa, movimiento reducido, Próximamente sin fecha, contador opcional, selector Ingreso / Hub F11, mensajes de campaña, proporciones completas del Hub, separadores, botones angulares, ausencia de links internos y funcionamiento sin JavaScript. Admite `CHROMIUM_EXECUTABLE` para un navegador ya instalado y `LAUNCH_SCREENSHOTS` para capturas temporales.

Deploy de beta: Update from Remote → Deploy HEAD Commit → Ctrl+F5. Revisar la vista previa. Main recibe la portada solamente después de la aprobación. El deploy copia el arte nuevo automáticamente.

### Final boss scene (v7)
Medusa closes the event showcase before the Discord invitation. Original cinematic artwork generated from scratch shows the boss attacking a knight, elf and wizard party. Green/violet overlays and particles echo the campaign palette. Mobile gives the heading its own space above the battle. No boss rewards or schedule are announced. PRÓXIMAMENTE now has animated gold lettering, a traveling highlight, glow and sparks; all new motion respects pause and reduced-motion preferences.

V8: Medusa has an independent canvas animation, with a hand aura, expanding energy rings, rising green/violet particles and lightning attacks. It renders only while the battle is visible and respects the shared pause control and reduced motion. Mobile buttons hide decorative arrows.

V9: removes the synthetic boss rings and lightning. Animated lighting masks isolate the green/violet energy already painted in the artwork and make those pixels glow in place. PANIC title has a full glyph line box to prevent clipping the C.
