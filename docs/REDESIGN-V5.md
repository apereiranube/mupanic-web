# MU PANIC V5 — El umbral

## Auditoría de la beta (30 septiembre 2026)

- La V4 dividía el hero en copy y un panel de estado. El background original contiene mucho negro y su encuadre dentro de ese panel ocultaba al personaje.
- EXP, drop, season y conectados se repetían en el hero, Server DNA y bloque de estado.
- El cache de resets no daba filas al ranking de portada; se mostraba una promesa de actualización sin evidencia.
- El módulo home de WebEngine insertaba debajo de Novedades un segundo login, rankings y eventos. Su banner apuntaba a un asset inexistente del template.
- La identidad decía Season 6 mientras el título/config editorial heredada decía Season 20.
- `/information/` redirigía a la home. La ruta nativa del CMS es `/info/`.
- `/downloads/` respondía, pero mostraba categorías sin enlaces al cliente.
- El deploy copiaba todo overlay y reescribía webengine.json después de respaldarlo.

## Dirección e implementación

Identidad editorial de videojuego: monograma angular propio, título monumental, fondos de arte MU completos, cobre/verde petróleo, cambios de luz y temperatura. Sin tarjetas de métricas, cuadrículas de funcionalidades ni rankings de relleno.

1. **El umbral:** Dark Knight real de MU, marca grande y dos acciones.
2. **Dossier:** única fuente visible de EXP, master EXP, drop y conectados en home. Los valores provienen de CMS/cache; conectados dice «último registro», nunca afirma salud del servidor por su cuenta. Season 6 / Louis UP43 corresponde a la identidad confirmada del proyecto.
3. **El viaje:** tres capítulos sobre fondo sticky. Scroll nativo con crossfade de escenas y progreso de capítulo. No se intercepta la rueda ni se bloquea la navegación.
4. **La esencia:** editorial clara sobre Zen, etapas y party, alternando superficie clara para evitar una página uniforme demasiado oscura.
5. **Preparación:** cuenta, descarga y guía con accesos directos.

La guía se renderiza en `/info/` desde el template. No modifica módulos de WebEngine ni inventa niveles, ingredientes o tasas de éxito. Los módulos de cuenta y rankings se conservan. Descargas usa el módulo real y presenta una explicación si no se publicaron enlaces.

Los assets se entregan localmente como WebP (aproximadamente 1,2 MiB en total). Las escenas de pueblo y hielo son arte conceptual de ambientación, no capturas de gameplay. Se precarga únicamente el arte de entrada.

Animación: brasas CSS, respiración de luz, parallax vertical pequeño con requestAnimationFrame, revelado de la introducción y crossfade de escenas. Respeta prefers-reduced-motion; sin JS, todo el contenido y los enlaces siguen disponibles.

## Despliegue

`.cpanel.yml` ejecuta `scripts/deploy-beta.sh`. El script copia **únicamente** `overlay/templates/mupanic/` hacia `/home/mupanic/public_html/beta/templates/mupanic/` y rechaza symlinks. No lee, copia, respalda ni reescribe `includes/config/webengine.json`. Tampoco despliega timezone ni el núcleo de WebEngine.

cPanel → Git Version Control → MU PANIC Web → Pull or Deploy → Update from Remote → Deploy HEAD Commit. Aplicar únicamente al checkout de la rama beta. La web principal permanece fuera del destino.

No se puede publicar un cliente inexistente desde un cambio visual: falta cargar su enlace por el procedimiento administrativo existente.

Las tipografías Barlow Condensed y Manrope se alojan localmente en WOFF2, con sus licencias OFL. El diseño no depende de Google Fonts durante la visita.

## Verificación

- Lint PHP del template, funciones y guía; sintaxis JS y Bash; git diff --check.
- Render PHP con doble de WebEngine: home invitado, sesión activa, cache ausente, guía y contenedores de módulos. La home no carga el módulo legacy ni el ranking de resets.
- Navegador de prueba: 1440, 1024, 768, 390 y 320 px sin overflow horizontal; los tres capítulos activan el arte correspondiente; menú móvil e Ingresar accesibles; Escape cierra; guía y anchors correctos; estados con/sin cache y sesión; prefers-reduced-motion; contenido sin JS; sin errores JS.
- Revisión de screenshots: corregidos encuadre del rostro, amarillo excesivo, carga de fuentes y contraste de la marca en páginas internas. Escena de origen reemplazada por arte conceptual propio de mayor detalle.
- Deploy en destino temporal: config centinela conserva bytes, inode y mtime; timezone no copiado; symlink de destino rechazado.
- Estos controles prueban el overlay en un render de prueba, no autentican ni alteran cuentas reales. La aplicación a beta requiere el despliegue del checkout cPanel; no se dispone de una sesión cPanel en este entorno.
