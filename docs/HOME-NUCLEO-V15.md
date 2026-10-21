# Inicio — Núcleo V15

Se implementa el concepto Núcleo elegido: cuatro emblemas con bases de piedra y un artefacto central, sobre terreno volcánico continuo. La pintura y los datos se mantienen separados. Todos los valores siguen saliendo de las fuentes existentes; no hay números ni textos pintados sobre las imágenes.

El contador dispone de una apertura interior con anchura del 46% del artefacto. El tamaño del texto se ajusta según la longitud del número formateado, incluidos separadores de miles. Se aplica tanto en PHP para la primera vista como al refrescar por la API. Los casos 1, 100, 999, 1.000 y 100.000 se comprueban con respuestas reales del fixture del navegador a 1440, 390 y 320 px; se compara la tinta del texto con la apertura. 100 conserva una fuente de al menos 40 px en los teléfonos comprobados.

La energía del núcleo gira y late; el escudo, la estrella y la gema reciben luz; bruma y brasas se desplazan por el terreno. Las animaciones se pausan fuera de pantalla y se detienen con movimiento reducido. El contador cambia solo al recibir un dato nuevo. Si el servidor está sin verificar, offline o en mantenimiento, el escudo pierde su resplandor verde.

En escritorio se mantiene la composición del render. En celular el núcleo encabeza la sección y los cuatro datos se reordenan en dos filas, con sus textos nativos. El terreno prolonga el suelo de la pelea y baja hacia las grietas de Furia; las capas se solapan con máscaras, sin marco exterior.

## Arte

Creado con la herramienta integrada de imágenes. Archivos en `overlay/templates/mupanic/img/home/`:

- `stats-core-v15.webp`: 1254×1254 RGBA; centro y contorno transparentes.
- `stats-emblems-v15.webp`: atlas 2×2 de 1254×1254 RGBA, cuatro cuadrantes de 627×627.
- `stats-world-v15.webp`: 2172×724 RGB.

Prompts finales:

Núcleo: “Extract and repaint ONLY the approved central front-facing antique gold/dark bronze circular artifact. Transparent outside silhouette AND empty transparent inner aperture. Remove all numbers, labels, letters and rune-like lettering. Preserve carved circular frame, broken orbital bands, warm amber rim and small irregular feet. Square production asset, crisp fine metal detail; no landscape or other stats.” Referencia: render Núcleo aprobado.

Emblemas: “Precise square 2×2 transparent sprite atlas: top left emerald gold shield, top right crossed MU swords with burgundy cloth, bottom left mastery sun/star, bottom right pointed amethyst crystal. Each independent object stands on its own irregular volcanic plinth with naturally dark clear stone space in the lower part for HTML. Same camera and visual weight; each stays inside its quadrant. No text, letters, numerals, runes, shared floor or background.” Referencia: render Núcleo aprobado.

Terreno: “Correct the environment layer into a downward continuation below the approved hero floor and above Furia fog. Remove sky, horizons, castles and four rectangular pedestal stumps. Replace with a close downward view of wet fractured volcanic floor, low irregular black rocks and ash-gray mist. Preserve the broken foreground ledge and fine amber fissures flowing toward muted crimson fractures below. Quiet dark center for separate stat artifacts. Full bleed 3:1, restrained saturation; no UI, artifacts, words, numbers, plinths, black border or vignette.” Referencias: hero-world-v10, furia-world-v3 y primera prueba de terreno.

La primera prueba del terreno se descartó porque repetía un horizonte y conservaba bases rectangulares. La instalación verifica los tres medios nuevos antes de publicar las plantillas.
