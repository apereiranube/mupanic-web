# Atlas PANIC — beta

`/info/` es ahora un archivo de consulta interactivo con identidad oscura y cobre, búsqueda global, navegación por capítulos, 21 mapas, 133 zonas de spawn agrupadas y 72 reglas activas de drop de monstruos. Conserva los enlaces existentes `#primeros-pasos`, `#progresion` y `#sistemas`.

## Fuente y actualización

El administrador confirmó que el ZIP de balance corresponde al servidor en ejecución y que los valores del panel web no estaban configurados. La información pública se exportó selectivamente; el ZIP y las configuraciones originales NO forman parte del repositorio.

Recrear el snapshot público:

```sh
python tools/build_public_balance.py /ruta/local/al/ZIP
```

La herramienta lee exclusivamente Common (cuatro claves públicas por cuenta), Monster, MonsterSetBase de los mapas seleccionados, Move, Gate, ItemDrop, ExperienceTable y ResetTable. Produce `overlay/templates/mupanic/inc/public-balance.json`. No extrae el ZIP ni copia configuraciones completas. Revisar el resultado y la fecha de revisión antes de publicar. La fecha visible de esta edición es 01/10/2026.

La home utiliza el mismo snapshot para EXP Free (15x), Master (1000x) y drop general (50%). El online sigue leyendo la caché real del CMS. VIP: EXP 18x; Master 1000x; drop general 60/70/75%.

## Interpretación y límites

- `ExperienceTable`: factores 100/90/75/60% según resets 0–4/5–9/10–14/15–19; EXP Free base resultante 15/13,5/11,25/9x antes de party y otros bonos.
- `ItemDrop`: tasas `x/1000000`, convertidas a porcentaje. Solo reglas con alguna tasa positiva. El drop general no se presenta como probabilidad de una joya ni se multiplica sin evidencia sobre el flujo del emulador.
- El filtro por mapa cruza el mapa de la regla, nivel del monstruo y clase específica cuando exista con la población configurada. Es compatibilidad de configuración, no medición de drops por muerte ni ocupación en vivo de spots.
- Los 21 mapas seleccionados tienen factor de EXP y drop de mapa 100 y tipo de drop `*` en MapManager del ZIP revisado.
- Spots: se agrupan las líneas de la sección 1 con el mismo rectángulo de spawn de hasta 16 unidades por eje. Las poblaciones de transición amplias y las apariciones individuales permanecen en el listado de monstruos, sin convertirse en falsos spots. Cantidades son las configuradas; coordenadas son el centro de la zona.
- Los planos son diagramas de coordenadas; no muestran terrenos, colisiones, rutas ni posiciones en vivo.
- Los niveles de Move son requisitos configurados del menú; clase, gates y accesos pueden imponer otras condiciones. No se venden como niveles recomendados de combate.
- ResetTable: nivel 400; Zen 5/15/30/50 millones y puntos de tramo 200/250/300/350. Command: límite 20, nivel de regreso 1, reset de puntos, conserva quests y skills. Los puntos del tramo no equivalen por sí solos al total final de estadísticas.
- Requisitos de ítems de reset/Master Reset y aritmética final de puntos requieren contrastar el comportamiento UP43 en juego antes de publicarlos como promesa. `CommandResetCheckItem=0` significa revisión de equipo, no prueba de que ResetTableItemRequirement esté desactivado. No se descartan esos requisitos basándose en esa bandera.
- No se publican cantidades de Zen por monstruo, tasas fijas de alas, horarios de eventos ni recompensas por la mera existencia de archivos. Master Reset se mantiene como consulta de requisitos en juego hasta validar su comportamiento.

Semántica contrastada con la [documentación de Louis Emulator](https://www.jogandomu.com.br/louisup42/), secciones ExperienceTable, Common, Command e ItemDrop. La guía técnica es UP42; la configuración del servidor es UP43, por eso los casos ambiguos se mantienen explícitos y no se infieren automáticamente.

## Interacción

- Búsqueda global de capítulos, mapas/monstruos y objetos, con resultados enlazados.
- Filtros de objeto, mapa y cuenta; tasa específica de la cuenta seleccionada.
- «Qué puedo farmear acá» enlaza el atlas con los drops compatibles.
- Puntos del plano enlazados a las filas del spot; enlaces directos a mapas, spots y objetos.
- Mapas guardados en localStorage del navegador; no requiere cuenta ni escribe datos del servidor.
- Sin JavaScript siguen disponibles todos los artículos, tablas y detalles nativos de mapas.

## Validación

PHP lint y JS syntax check. Navegador real: 1440/1024/768/390/320 px, sin desbordamiento horizontal; filtros de mapas y objetos; Icarus/Feather frente a Condor; Moonstone Free 0,5% frente a VIP 0,1%; resultados vacíos; enlaces profundos; guardados persistentes; tasas de home independientes del CMS; lectura sin JS; ausencia de errores de ejecución. Regresión de capítulos, navegación, online sin caché, sesión iniciada, movimiento reducido y hero estático.

## Deploy

Únicamente overlay mediante el flujo beta existente. No cambiar `webengine.json`, configuración del servidor ni secretos. Subir la rama no equivale a desplegar: cPanel → Update from Remote → Deploy HEAD Commit.
