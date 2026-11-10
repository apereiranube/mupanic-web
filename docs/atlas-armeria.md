# Atlas · Armería (sets y armas por clase)

Sección `#armeria` del Atlas (menú "Aprender a jugar" → "Sets y armas por clase"). Enlaces directos por clase: `#armeria-dk`, `#armeria-dw`, `#armeria-elfa`, `#armeria-mg`, `#armeria-dl`, `#armeria-su`, `#armeria-rf`.

- `inc/atlas-armeria.php` (marcado), `css/atlas-armeria.css` (todo bajo `#armeria`, clases `arm-`; usa `!important` en fondos y bordes porque atlas-v2.css los resetea), `js/atlas-armeria.js`.
- Datos: `inc/atlas-armeria.json` = sets (Item.txt secciones 7–11, sin Lucky), armas (secciones 0–5, por familia de cada clase) y Ancient (SetItemType.txt + SetItemOption.txt). Se generan con los scripts de la sesión del 10/11/2026 a partir de los archivos del VPS.
- Imágenes: `img/atlas/armeria/<sección>-<índice>.webp`, de bless.gs/images/items (y mureforge.com/item-images para las que faltan). Assets del juego © Webzen, sin modificar.
- "Dónde se consigue": se arma en el navegador con los datos en vivo del Atlas (`#wiki-data` + `PanicAtlasSearch.build` de atlas-search.js): mapas y monstruos compatibles de cada drop directo, jefes y cajas/eventos. Si el Atlas actualiza los drops, esto se actualiza solo.
- Sets de nivel 380: Kundun (Dragon Knight, Venom Mist, Sylphid Ray, Volcano, Sunlight, Storm Blitz, Phoenix Soul) y Selupan (Hades, Titan, Brave, Seraphim, Divine, Phantom, Destroy, Royal, Queen).
- Los botones usan `data-on` en vez de `aria-pressed` porque atlas-v2.css pinta en lima todo `[aria-pressed=true]`.
