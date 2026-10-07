# Portada de lanzamiento

Beta conserva el sitio completo. La portada se revisa en `https://beta.mupanic.com.ar/?preview=launch`.

`inc/launch-config.php` mantiene `productionMode = coming-soon`: al promover la versión aprobada a main, solamente el inicio de `mupanic.com.ar` y `www.mupanic.com.ar` muestra la portada. Las rutas interiores y el sincronizador del Atlas siguen funcionando. No usa el encabezado HTTP Host para decidir: usa la URL configurada por WebEngine.

Los rates Free provienen de `panicAtlasBalance()`, la misma fuente sincronizada del Atlas. El contador usa `launchAt` con zona horaria explícita. Fecha de prueba autorizada: 31 de octubre de 2026 a las 20:00 de Argentina (`2026-10-31T20:00:00-03:00`); debe reemplazarse por la fecha definitiva antes de publicar la campaña. No se publica una promesa de latencia sin mediciones. Las arenas y Survivor tienen fecha a anunciar.

La composición de campaña destaca el combate, Hub F11, economía de Zen y eventos. Los efectos combinan CSS (entrada del título, iluminación y una grieta diagonal) y canvas (brasas, corrientes diagonales de energía y partículas que reaccionan al puntero). El canvas limita la densidad y resolución en móvil, trabaja a 30 FPS y deja de dibujar fuera del hero. Se pausan con el botón, con la preferencia de movimiento reducido y cuando la pestaña queda en segundo plano. El contenido funciona sin JavaScript, con los efectos estáticos. La portada sólo enlaza a sus propias secciones y a Discord; no incluye accesos al Atlas, información, registro ni páginas internas.

Deploy beta: Update from Remote → Deploy HEAD Commit → Ctrl+F5. Revisar la URL de preview. Después de aprobar, promover a main y desplegar con el mismo flujo. No requiere copiar imágenes ni configurar cPanel manualmente.

Apertura: cambiar `productionMode` a `website` en beta, revisar y promover a main. El inicio original vuelve a mostrarse; la vista previa permanece disponible.
