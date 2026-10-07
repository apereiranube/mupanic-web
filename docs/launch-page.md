# Campaña de lanzamiento

Beta conserva el sitio completo. La campaña se revisa en `https://beta.mupanic.com.ar/?preview=launch`.

La portada es una secuencia de escenas completas: batalla de lanzamiento, economía de Zen, Hub F11, selector de eventos y comunidad. Reemplaza la estructura anterior de encabezado lateral y tarjetas. Los únicos enlaces externos son a Discord; no tiene accesos al Atlas ni al registro.

## Fecha de prueba

`inc/launch-config.php` contiene `launchAt = 2026-10-31T20:00:00-03:00` y su etiqueta visible. Es la fecha de prueba solicitada por el usuario (31 de octubre de 2026, 20:00 de Argentina), no una fecha de lanzamiento definitiva. Sustituir ambos valores antes de publicar la campaña en producción. El contador se detiene en cero; nunca anuncia automáticamente que el servidor está abierto.

## Datos y rutas

Los rates Free se obtienen de `panicAtlasBalance()`, la misma fuente sincronizada del Atlas. No se promete latencia sin mediciones. Survivor y arenas por clase mantienen fecha a anunciar, independiente del contador de lanzamiento.

`productionMode = coming-soon` activa la campaña solamente en el inicio de producción cuando la versión aprobada se promueve a main. Las rutas interiores siguen funcionando; la portada no representa un bloqueo de registro ni de acceso a esas rutas. El enrutamiento usa la URL configurada de WebEngine, no el encabezado Host. Para restaurar el inicio completo cambiar `productionMode` a `website`, revisar en beta y promover a main.

## Arte y movimiento

Arte original de batalla en WebP, con composiciones horizontal y vertical independientes; las rutas nuevas evitan la caché anterior. Prompts y archivos finales: `tools/launch-art.json` (herramienta integrada `image_gen.imagegen`). Ambos archivos se distribuyen dentro de `overlay/templates/mupanic/img/launch/`.

Canvas de brasas, rayos laterales e impactos; entrada del título, iluminación, franja animada de rates, desplazamiento suave de fondos y botón de pausa. Resolución canvas limitada a DPR 1.5, densidad menor en mobile y límite de 30 FPS. Los efectos se detienen al ocultar la pestaña o salir del hero, y respetan movimiento reducido. No hay sonido automático, scroll forzado ni pantalla de acceso obligatoria. El contenido y todas las descripciones de eventos están disponibles sin JavaScript.

El selector de eventos incluye tabs con navegación por flechas, Home y End, foco visible y paneles relacionados. El contador evita anuncios por lector de pantalla cada segundo.

## Revisión y despliegue

`php tests/launch-gate.php` comprueba el modo y las rutas. `tests/launch-browser.cjs` comprueba 1920, 1366, 1024, 768, 390 y 320 px, assets visibles, tabs, teclado, pausa, movimiento reducido, cuenta regresiva, ausencia de links internos y funcionamiento sin JavaScript. Admite `CHROMIUM_EXECUTABLE` para un navegador ya instalado y `LAUNCH_SCREENSHOTS` para capturas temporales.

Deploy de beta: Update from Remote → Deploy HEAD Commit → Ctrl+F5. Revisar la vista previa. Main recibe la portada solamente después de la aprobación. El deploy copia el arte nuevo automáticamente.
