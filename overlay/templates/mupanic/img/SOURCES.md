# Arte V5

## Character avatars restored for the account panel

`character-avatars/*.jpg`: original 100 × 100 class portraits distributed with WebEngine CMS, copied without alteration from `templates/default/img/character-avatars/` at upstream revision `5cf16f1284abb970e29bde2a937dbec412d1bc94`. These illustrate the class, not the player's equipped character. MU Online artwork remains the property of its respective owners.

- `threshold.webp`: arte de Dark Knight / MU Online, disponible en https://wallpaper.dog/large/20380063.jpg (página: https://wallpaper.dog/mu-online-wallpapers). El arte original contiene el emblema de Global MU Online. Se conserva; el encuadre y las capas CSS priorizan el personaje.
- `conquest.webp`: `templates/default/img/background.jpg` de WebEngine en la revisión fijada `5cf16f1284abb970e29bde2a937dbec412d1bc94`. Personaje de MU, no captura del servidor.
- `devias.webp`: arte conceptual original generado para este rediseño con image_gen integrado. Prompt: escena panorámica de una entrada de castillo gótico helado evocadora de Devias, puerta y escalinata a la derecha, ruinas y montañas a la izquierda, niebla azul y antorchas cobre; sin personajes, texto, interfaz ni logos. Ilustración de ambientación, no representación exacta del mapa.

Los WebP se codificaron para entrega web. MU Online y sus marcas pertenecen a sus respectivos titulares.

- `origin.webp`: arte conceptual original generado con image_gen integrado. Prompt: plaza medieval evocadora de Lorencia al atardecer azul, fuente, casas de herrero, senderos de piedra húmeda, vegetación y puerta monumental, luz cobre; sin personajes, logos ni interfaz. No es una captura de gameplay.

## V6 character layer — `knight-v6.webp`

Original concept illustration generated with the built-in image generation tool, using `threshold.webp` as a visual reference for the classic MU Dark Knight silhouette. Native result: 1024 × 1536 RGBA; encoded as WebP quality 94 with alpha preserved, without enlargement. Not a gameplay screenshot or an official new game render.

Prompt direction: premium detailed modern cinematic MU Dark Knight; red hair, dark silver spiked armor, gold filigree, flowing cape, blue runic sword, copper and blue rim lighting; transparent character layer; no text/logo/watermark. The model returned 1024 × 1536 despite the requested larger frame. Site rendering separates the existing Devias environment, character, sword aura, fog, lighting and embers to avoid scaling a low-resolution full scene.

## V6.1 Conquista — `conquest-v61.webp`

Original built-in generated concept scene, 1983 × 793, WebP quality 93. `knight-v6.webp` was a material/lighting reference only. Different character: a Dark Lord with closed crown helmet, royal scepter, burgundy cloak and blackened silver/copper armor at a ruined fortress. Prompt: realistic mature cinematic game rendering, detailed engraved weathered metal, copper and steel rim lighting, character on the right and open darker fortress on the left; no red-haired knight, blue sword, cartoon style, text, logos or watermark. Replaces the previous Conquista artwork; the hero character is not reused.
