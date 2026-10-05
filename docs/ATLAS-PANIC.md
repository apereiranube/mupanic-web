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

## Ampliación 7.1 — Taller y equipo

La revisión agrega 14 recetas: arma Chaos, alas 1/2/3, capas de segunda generación, Pluma de Cóndor, tres etapas de Fenrir, fruta, Dark Horse, Dark Spirit, Seed y Seed Sphere. Cada receta incluye ingredientes, cantidades, condiciones de nivel/opción, NPC, pasos y resultado. Los checklists se guardan en el navegador. Los ingredientes enlazan al drop real disponible o a su receta previa.

La calculadora +0 a +15 cruza categoría, cuenta, nivel inicial/final y Luck. Exporta las tasas de Chaos por las cinco categorías (normal/Excellent/Ancient/Socket/alas), Soul y los bonos Luck. Para las mezclas configuradas con -1 se muestra **Variable**, sin inventar un porcentaje exacto de alas ni aplicar automáticamente un tope de otra versión.

El resultado de una cadena es el producto de probabilidades por paso. Materiales = suma de los ingredientes de una cadena sin fallos; no se presenta como costo promedio, cantidad garantizada de intentos ni predicción del resultado del jugador. No incluye talismanes, impuestos ni otras ayudas. El porcentaje final se verifica en la máquina.

Se agregan guías de niveles del equipo, Life, Luck, Skill, Excellent, Ancient, Socket, Harmony y equipo 380. MapManager aporta las tasas configuradas de Excellent/Ancient y la bandera Socket, con el alcance de elegibilidad explicado.

### Fuentes de crafting

- Configuración activa aportada por el administrador: GameServerInfo Common y ChaosMix; MapManager; CustomWingMix sin recetas adicionales. Se exportan exclusivamente claves públicas permitidas.
- La [guía oficial de upgrades de Webzen](https://muonline.webzen.com/es/gameinfo/guide/detail/32) explica los ingredientes de +10 a +15. Sus probabilidades genéricas no se usan como rates de PANIC.
- [Código publicado por LouisEmulator](https://github.com/LouisEmulator/Main5.2/blob/acfdd2bf8bd8f8ca6d0c15e1a3e71a70b89924c5/Source%20MuServer%20Update%2015/GameServer/GameServer/ChaosBox.cpp): base de ingredientes y pasos de crafting de esta familia de emulador; esta fuente es Update 15, no el binario UP43 en ejecución. Los costos fijos se marcan como referencia antes de impuestos y la tasa final se contrasta en juego. No se traslada la guía moderna de alas Season 18, que cambió sus ingredientes.
- El código `JewelOfHarmonyOption.cpp` de ese mismo repositorio distingue la refinación por la piedra utilizada: Lower usa SmeltStoneSuccessRate1 y Higher (14,44) usa SmeltStoneSuccessRate2. La documentación antigua etiqueta esas claves de forma ambigua como normal/Excellent; la guía usa la interpretación del código.

### Mapas reales: pendiente de arte del cliente

El ZIP contiene configuración y población, no las imágenes de minimapa del cliente. No se sustituyeron con arte generado ni mapas de otro servidor. `tools/collect_atlas_client.ps1` reúne exclusivamente imágenes identificables de mapas y Mix/Item.bmd de Local, conserva rutas relativas y crea un ZIP en el Escritorio. Nunca copia EXE, MainInfo, seriales ni configuración. No se ejecutó en Windows en este entorno; debe indicarse `-ClientRoot` si el cliente está en otra carpeta. El arte requiere revisión y calibración de coordenadas antes de superponer spots.

### Validación 7.1

PHP lint y JS syntax; navegador real en 1440/1024/768/390/320: taller sin desbordamiento, selección/filtros, vacío de búsqueda, checklist persistente, ingredientes encadenados a recetas y drops, búsquedas globales de recetas, capítulos de equipo, todas las recetas sin JS. Calculadora: normal +9→+10=20%; Excellent=30%; Excellent con Luck=50%; Excellent +9→+12 con Luck=12,5%, con 6 Bless/6 Soul/3 Chaos; rango inválido; Soul +6→+9=100%. Regresión del Atlas 7.0 aprobada. Deploy continúa limitado al overlay.
