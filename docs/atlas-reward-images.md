# Imágenes de las recompensas

Las 171 fichas del snapshot tienen imagen: 34 retratos de criaturas y 137 asociaciones explícitas en `atlas-assets.json.rewards`, indexadas por ID de lista. Un código de ítem puede tener varias apariencias según su variante: las cajas de Kundun, corazones, chocolate y candy se asocian por lista, no solamente por código de ítem.

Se agregaron 39 WebP binarios locales. `tools/atlas-reward-image-sources.json` conserva URL, hash, dimensiones y procesamiento de cada fuente. Son iconos originales del juego, no objetos inventados con IA. Se conserva la resolución del origen sin ampliación: el marco común centra los iconos pequeños a tamaño natural y limita los grandes. No hay hotlinks, base64 ni dependencias de las webs de origen durante la navegación.

Eventos y sistemas reutilizan el arte conceptual de MU PANIC según su familia. Sus niveles comparten ilustración: Blood Castle, Devil Square, Illusion Temple, Imperial Guardian, Double Goer, Castle Siege, Crywolf, Pandora y sistemas de progreso. El contenido desplegado aclara que el arte es ilustrativo; no se presenta como captura del cliente. Chaos Card Mix no tiene una relación confirmada entre sus cinco listas y las variantes de tarjeta en el snapshot, por lo que ilustra la mezcla y no asigna tarjetas arbitrarias.

Un ID nuevo aún sin asociación usa una ilustración neutra de recompensas, evitando imágenes rotas durante actualizaciones del snapshot. El test de navegador exige una imagen cargada en cada una de las 171 fichas actuales, sin clic ni recarga, en cinco tamaños de pantalla. El deploy copia los binarios desde el overlay junto con su manifiesto; no requiere copiar archivos manualmente. Las listas, condiciones, nombres y cantidades exportadas no se modifican.
