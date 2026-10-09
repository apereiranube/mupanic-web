# Vínculos de PANIC

Nueva sección del Atlas en `/info/#vinculos`. Incluye los ocho compañeros de
**El Pacto de los Ocho**, Grum, Vaelkar, Aurethia y las tres monturas exclusivas.
Las fichas tienen bonos, obtención, inventario y enlaces a sus evoluciones.

## Fuentes

- `MU_PANIC_El_Pacto_de_los_Ocho.pdf`: prólogo completo, historias y función de
  los ocho compañeros. El texto se conserva en `inc/atlas-vinculos.json`.
- `MU_PANIC_CONTINUIDAD_SHOP_APP(1).zip`, `PROMPT_PARA_EL_NUEVO_WORK.md` y
  `Catalogo.lua` versión 15: nombres, bonos y cantidades aprobadas.
- Parches de mascotas exclusivas, monturas, Grum y materiales; modelos de
  Pets 2, Fierce Lion e Ice Dragon: imágenes de los modelos reales. Elyndra
  usa el modelo actualizado `elfqueen.bmd`. Nerathys usa `flymount_pet6.bmd`
  (905095 bytes), correspondiente a `pet_mupanic_flymount_pet6` en el cliente,
  con las tres cabezas y alas completas. No usa la mascota Muun `hydra.bmd`.
- El manifiesto `tools/atlas-vinculos-art.json` registra modelos, imágenes del
  PDF, procesamiento y hashes de las 18 imágenes publicadas.

## Materiales y obtención

Fragmentos de Vínculo y Núcleos de Evolución tienen sus imágenes reales,
cantidades, Zen y enlaces a las reglas de drop del snapshot público vigente.
No se modifican tasas ni condiciones del servidor. Las reglas se presentan
como tasas configuradas, sin prometer una probabilidad final por muerte.

Talisman of Luck y Chaos Assembly tienen fichas sobre su función y límites.
Sus fichas usan reconstrucciones ilustradas de alta resolución (1254 px),
basadas en los íconos clásicos publicados por MU.lv. Conservan su forma y
colores reconocibles; son ilustraciones, no renders del cliente MU PANIC.
El manifiesto registra la referencia, el procesamiento y los hashes. Los
archivos versionados evitan reutilizar los íconos pixelados desde la caché.

La invocación cuesta 10 fragmentos y 20 millones de Zen, con 50% de éxito.
La segunda etapa cuesta 10 núcleos y 50 millones, con 60% de éxito; la final,
20 núcleos y 100 millones, con 30%. No se usan los costos de los parches
antiguos de prueba. El reparto 30/30/30/10 se aplica sólo al éxito.

Vaelkar, Aurethia y las monturas no se anuncian como drop ni resultado del
Custodio. La venta directa de la tienda figura como próximamente: el catálogo
visual no implica que el cobro o entrega estén habilitados. Los bonos de
monturas siguen sujetos a los ajustes de beta; no se promete acumulación.

## Navegación y despliegue

El buscador incluye filtros de mascotas, monturas y materiales. Los enlaces
`#vinculo-*` abren la sección y la historia correspondiente, incluidos los
accesos directos desde el catálogo de drops. Las imágenes se entregan junto
con la guía; `scripts/deploy-beta.sh` comprueba que estén presentes antes de
copiar. Este cambio no modifica SQL, cron, pagos ni el despliegue de main.

## Verificación

```bash
node tools/test_atlas_search.cjs
PHP_BIN=php NODE_PATH=/ruta/a/node_modules node tests/atlas-vinculos-browser.cjs
```

El test de navegador revisa las 18 entradas, imágenes, lore completo,
exclusión de drops para exclusivos, apertura de fichas, búsqueda, errores JS
y ausencia de desborde a 1920, 1440, 390 y 320 píxeles. También comprueba
que los modelos entren completos y las tarjetas cerradas compartan altura. `CHROMIUM_PATH` permite usar
un ejecutable de Chromium existente. `ATLAS_SCREENSHOT_DIR` guarda capturas.
Con `PANIC_ATLAS_RUNTIME_DIR` también comprueba los enlaces de las reglas de
materiales del snapshot publicado, sin conexiones SQL ni cambios de filas.
