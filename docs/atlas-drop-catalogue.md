# Catálogo de drops

El catálogo reemplaza la tabla de ItemDrop con 30 fichas derivadas de sus 82 reglas actuales. Agrupa los materiales numerados por familia y conserva los niveles de cada regla; Feather/Crest y Horse/Spirit mantienen identidades separadas. Los porcentajes siguen asociados a su índice original, con selector Free/VIP. El snapshot del servidor y sus receptores no se modifican.

`atlas-drop-model.php` prepara grupos; `atlas-drops.php` presenta el catálogo y los detalles; `atlas-drops.js` controla navegación y filtros. Los enlaces `#drop-N` permanecen válidos para buscadores y recetas. Los nuevos enlaces `#drop-item-clave` abren una familia. Sin JavaScript los detalles y las reglas siguen presentes.

El cruce de mapa, nivel y monstruo usa `PanicAtlasSearch.compatible`, incluyendo el modo de drop del mapa. Cada regla muestra los mapas con población fija compatible; se elige uno y se presentan hasta seis mobs por página, con enlaces al mob y a spots que cumplen esa misma regla. Las variantes de niveles de un mismo ID se evalúan en el mapa real, evitando usar el último nivel encontrado en otro mapa. Las tasas permanecen independientes.

Los 30 iconos son recursos reales del juego guardados en WebP sin pérdida. `atlas-drop-items.json` relaciona la ficha con su imagen; `tools/atlas-drop-image-sources.json` registra origen, hash y procesamiento. Las familias numeradas usan el icono base del material. Las imágenes se muestran sin superar sus dimensiones naturales. El deploy habitual copia estos binarios con el template.

Validación: integridad de las reglas y variantes; cobertura de imágenes; selección de las 30 fichas; filtros de categoría/mapa/mob; nivel de material; enlaces históricos y hacia mobs; paginación; cambio Free/VIP con un dato de prueba distinto; teclado; cinco tamaños de pantalla; imágenes decodificadas y ausencia de desbordes horizontales.
