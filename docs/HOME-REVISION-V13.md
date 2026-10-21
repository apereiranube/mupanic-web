# Inicio V13 — detalle del paisaje y entrada desde mascotas

Se mantiene la composición aprobada del Hub, mercado y minijuegos y la pintura vertical continua que une sus bordes. Tres placas nuevas aportan detalle nativo de 1774×887 en las zonas centrales; sus máscaras regresan a la pintura original en los empalmes. No se presenta como 4K ni se aplica un filtro de enfoque a una imagen ampliada. En pantallas mayores que 1774 px sigue existiendo ampliación.

La entrada desde mascotas se superpone al bosque, difumina el borde superior del Hub y utiliza una ilustración con transparencia real de vegetación, musgo y bruma. Se conservan la interfaz interactiva, sus dimensiones y las demás secciones.

## Archivos y generación

Herramienta integrada de imágenes. Nuevos archivos en `overlay/templates/mupanic/img/home/`:

- `journey-forest-v13.webp`, 1774×887.
- `journey-market-v13.webp`, 1774×887.
- `journey-games-v13.webp`, 1774×887.
- `join-canopy-v13.webp`, 2172×724 RGBA.

Prompts finales (referencia: journey-world-v12 para los tres fondos; companions-connected-v3 y el bosque nuevo para la unión):

Bosque: “Repaint ONLY the upper third as a full bleed wide 2:1 landscape. Preserve positions of moonlit castle left, lanternlit vine-covered arch right, mossy terrace, burgundy cloth and wet cobblestones. Reconstruct crisp native fine detail. Match boundary features to the reference. No text, UI, borders or new subjects.”

Mercado: “Repaint ONLY the middle third as a full bleed wide 2:1 landscape. Preserve Vessara on the right behind MU equipment, armored customer with burgundy cape, awnings, gothic arches and street on left. Restore fine native detail in faces, armor, stone and cloth. Match the top forest-to-market and bottom ledge-to-hall boundaries. No text, UI or borders.”

Juegos: “Repaint ONLY the lower third as a full bleed wide 2:1 landscape. Preserve candlelit gothic hall on left, ornate roulette foreground right, three red dragon reels behind, cards and jewels. Restore fine detail on metal, velvet, roulette segments and chandeliers. Match the market ledge above. No added dragons, text, UI or borders.”

Unión: “Wide transparent foreground joining the two forest scenes: irregular ivy, mossy stone fragments at sides, fine cool mist. Keep middle mostly transparent. Asymmetric natural silhouette with gaps, no horizontal bar, frame, characters, sky, text or logos. True alpha.”

La primera prueba de rehacer todo el panorama devolvió 1024×1536; se descartó por no resolver la resolución. Las dimensiones publicadas arriba son las reales de los archivos usados.

La instalación verifica los cuatro nuevos medios antes de cambiar las plantillas. Las comprobaciones de navegador exigen placas con al menos 1700 px de ancho real y una máscara activa en el comienzo del Hub.
