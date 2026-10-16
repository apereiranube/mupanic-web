# Atlas: spots, élites y obtención de equipo — 16/10/2026

Actualiza el Atlas existente en beta, conservando su diseño, sus mapas y su navegación.

- 208 spots: los 149 anteriores y los 59 de Super Spots V2. Se identifican los 29 super spots, los 24 tipos de élites y sus 41 apariciones simultáneas. Se conservan Vulcanus y Arkania.
- 400 reglas positivas de ItemDrop: condiciones, tasas nominales y nombres reales de Item.txt. Las entradas desactivadas no se publican.
- Las selecciones `ItemIndex=-1` enlazan sus listas de equipo/joyería y sus rutas reales. Son objetos al suelo, no cajas que el jugador deba conseguir. La tasa pertenece a la selección del grupo; no se atribuye a cada pieza.
- Las 428 armas y piezas de armadura auditadas tienen una fuente en las listas publicadas, entre mobs y bosses. Lucky permanece fuera de la nueva distribución.
- Se incorporan las cuatro selecciones de joyería, Large Complex Potion, Harmony, Guardian y materiales de Vínculos. Los élites usan el retrato del modelo base y muestran su vida y respawn reales.
- Las listas sin una fuente identificada no se ofrecen como obtención. Se cruza población, invasiones/eventos habilitados, ItemDrop y cajas que efectivamente aparecen en esas recompensas. Se retiran las referencias al intercambio Lucky Coin y a las cajas selladas retiradas. Tener un archivo EventItemBag no prueba que su contenido se pueda obtener.
- Las listas de drops de cada mob se incorporan al documento al abrirlas, conservando el HTML completo sin JavaScript y aplicando la cuenta Free/VIP al abrir. Esto reduce el trabajo de navegación con la población ampliada.

Los índices de reglas se mantienen dentro de cada snapshot y los enlaces se generan desde ese mismo snapshot. El buscador permite llegar de una pieza a su selección de drop, mapa, mob y spot compatible.

## Fuentes y límites

`tools/atlas-hunting-source-audit.json` registra las lecturas recibidas y los hashes de las tablas públicas reconstruidas. La última lectura de población/drop es `SUPER_SPOTS_LECTURA_V1_20261016_171732`; sobre ella se aplican exactamente las adiciones del V2 que el administrador instaló y cuyo recorrido confirmó. Las tablas de joyería proceden del paquete V1 aplicado y de sus referencias presentes en la lectura posterior.

La actualización de respaldo sustituye solamente `maps`, `drops` y `eventBags`. Conserva las fuentes de experiencia, cuentas, resets y crafting. La metadata `hunting` registra su fecha y hash propios; no presenta la fecha/hash históricos del balance completo como una lectura nueva de toda la economía. Los traslados usan Move.txt de la lectura VPS de Ascensión, junto con Gate.txt de la lectura posterior de super spots.

El respaldo de eventos usa las tablas Event/EventItemBag leídas, los switches de la auditoría de equipo, el Custom.dat de la lectura PKV28 y las claves numéricas posteriores de JOYERIA_ACTUAL_V1. No inventa horarios. El lector de listas ahora encuentra también archivos en subcarpetas Monster/Special. La futura sincronización firmada tiene prioridad sobre los respaldos cuando es más reciente.

**Hallazgo en V2:** las reglas rotuladas Gemstone con código 7199 corresponden a **Jewel of Guardian (14,31)**. Gemstone es 7209 (14,41); Harmony es 7210 (14,42). El Atlas publica los objetos reales del archivo. Esta entrega no cambia los drops del servidor para corregir ese error del paquete V2.

Las coordenadas son configuradas, no posiciones en vivo. Los élites no cuentan como el mob normal en quests nativas que exigen su clase original. Las tasas son nominales por regla, no una probabilidad final por muerte ni por pieza dentro de una selección.

## Publicación

1. Desplegar la rama beta desde cPanel: Update from Remote → Deploy HEAD Commit.
2. SOLO VPS: ejecutar `tools/update_atlas_sync.ps1 -Equipo VPS -Revision <SHA del commit>`. Descarga y valida los seis módulos Python del exportador, conserva token, wrapper, destinos y frecuencia de la tarea; genera respaldo y restaurador en Descargas. Fuerza una publicación firmada del balance. Si el wrapper ya tiene sincronización de eventos, también actualiza ese catálogo en su destino existente.
3. La sincronización lee configuración; no requiere detener GameServer, no modifica archivos del juego, SQL ni main.premium.

Restaurar la web mediante Git/cPanel. El restaurador del exportador conserva la última copia publicada y rechaza cambios posteriores en sus módulos. No restaura configuración de juego porque no la modifica.

## Validación

Pruebas Python de parsing, cobertura de equipo, fuentes retiradas, tasas nominales, metadata y recepción HMAC. Pruebas JS de rutas de objetos y mapas. PHP lint y render del Atlas completo. Navegación Chromium de catálogo de drops a 1920/1366/1024/390/320 px, premios a 1920/1024/390/320 px, y élites/rutas nuevas a 1366/390 px. El exportador completo también se ejecutó contra tablas reales de caza y fixtures temporales de progresión: se comprobó que su hash coincide con el sincronizador, sin publicar la economía de prueba. También se verifica la compatibilidad del endpoint del launcher con el catálogo de eventos actualizado.

El script PowerShell y su restaurador se validan sintácticamente; no se ejecutaron en Windows desde este entorno. El despliegue cPanel y la ejecución real del sincronizador requieren completarse en los equipos correspondientes.
