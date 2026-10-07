# Portada de lanzamiento

Beta conserva el sitio completo. La portada se revisa en `https://beta.mupanic.com.ar/?preview=launch`.

`inc/launch-config.php` mantiene `productionMode = coming-soon`: al promover la versión aprobada a main, solamente el inicio de `mupanic.com.ar` y `www.mupanic.com.ar` muestra la portada. Las rutas interiores y el sincronizador del Atlas siguen funcionando. No usa el encabezado HTTP Host para decidir: usa la URL configurada por WebEngine.

Los rates Free provienen de `panicAtlasBalance()`, la misma fuente sincronizada del Atlas. No se publica un contador de apertura ni una promesa de latencia sin mediciones. Las arenas y Survivor tienen fecha a anunciar.

Los efectos son CSS (zoom suave, iluminación y 12 brasas). Se pausan con el botón, con la preferencia de movimiento reducido y cuando la pestaña queda en segundo plano. El contenido y enlaces funcionan sin JavaScript.

Deploy beta: Update from Remote → Deploy HEAD Commit → Ctrl+F5. Revisar la URL de preview. Después de aprobar, promover a main y desplegar con el mismo flujo. No requiere copiar imágenes ni configurar cPanel manualmente.

Apertura: cambiar `productionMode` a `website` en beta, revisar y promover a main. El inicio original vuelve a mostrarse; la vista previa permanece disponible.
