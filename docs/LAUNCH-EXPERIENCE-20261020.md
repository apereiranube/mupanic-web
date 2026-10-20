# Inicio y El servidor — propuesta de lanzamiento

## Resultado

El inicio presenta el combate RF contra BK, datos actuales, Nexo/Caos, las tres líneas de mascotas con Grum, el Hub real, próximos eventos y Vessara. Los ocho juegos aparecen después como actividad adicional. La página El servidor explica progresión, Fractura, compañeros, cliente, mercado, eventos, juegos y cuenta.

Arte nuevo: combate RF/BK, Aldren/Varkor/Señor de la Fractura y Elyndra/Aurion/Vaerion/Grum. Los dos últimos usan referencias del proyecto; la portada conserva el RF que el dueño eligió. Las ilustraciones promocionales no se presentan como capturas. El Hub y la sala de juegos sí son capturas reales enviadas el 20/10/2026. Se muestran mediante recorte CSS del archivo completo, con enlace para ampliarlo. No se usa la portada rechazada de personajes antiguos ni las mascotas duplicadas. El manifiesto `tools/home-art-20261020.json` registra archivos, dimensiones y hashes.

## Fuentes y comportamiento

- Conectados y estado: endpoint público existente `/api/launcher.php`. La cifra inicial proviene del cache habitual del CMS. Hasta verificar el endpoint, el estado dice «Sin verificar».
- Rates: `panicAtlasBalance()`, cuenta Free. Se conserva la aclaración de rates base y drop general.
- Agenda: proyección existente del launcher/Atlas. Tres próximas aperturas, zona Argentina; no se inventan horarios para eventos manuales.
- Mascotas: nombres, linajes y bonos de `atlas-vinculos.json`.
- Juegos: nombres de la captura actual, no los nombres internos o antiguos.

El refresco es cada 60 segundos, sólo con la pestaña visible. Tiene timeout, validación de datos y un aviso explícito si falla. Nunca transforma un fallo de red en un cero inventado. Los nombres se insertan como texto, no HTML. No se agrega SQL ni se cambia el endpoint existente.

Documento con scroll nativo, escenas abiertas, sin tarjetas cuadradas, sin parallax ni efectos del cursor en estas páginas. Arte WebP con variantes móviles, carga diferida salvo portada y preload según ancho. Los estilos se limitan a Inicio y El servidor. Menú móvil, navegación por anclas, details nativos y respeto por movimiento reducido. Las otras rutas conservan sus estilos.

## Verificación

- Sintaxis PHP, JavaScript y shell: correcta.
- `tests/experience-render.py`: template completo como invitado, usuario conectado y cache ausente; recursos, enlaces, IDs, jerarquía, cuatro compañeros, ocho juegos y hashes: PASS.
- `tests/launcher.php`: PASS.
- `tests/market-render.php`: PASS.
- `tests/fractura-totals.php` (vistas vigentes): PASS.
- `git diff --check`: PASS.
- `tests/fractura-data.php` es una prueba antigua ya desalineada con la vista V6: falla esperando la tabla y cache V1. No se cambió este archivo ni el módulo Fractura; la prueba vigente de totales pasa.

El test de navegador de sistemas se actualizó para ambas páginas y ocho anchos, interacción del menú, details, imágenes, fallos del endpoint y nombres tratados como texto. No se pudo ejecutar navegador en este entorno; queda pendiente esa comprobación visual antes de considerar validado el diseño en pantalla. El workflow existente ejecutará esa prueba cuando los cambios lleguen a beta.

## Estado

Cambios preparados en rama local `feature/launch-home-20261020`, basada en beta. No se publicaron en el hosting. El dueño autorizó expresamente la subida al repositorio el 20/10/2026. La revisión se entrega en una rama separada contra beta. La vista previa HTML es autocontenida y no consulta jugadores en vivo; sus enlaces Inicio/El servidor permiten recorrer el diseño.
