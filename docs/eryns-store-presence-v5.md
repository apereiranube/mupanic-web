# Tienda ERYNS · Presencia y misterio · V5

El hero vende presencia: **Que te vean llegar. Que recuerden tu nombre.** Una línea: **Elegí la presencia que querés llevar a PANIC.** CTA: **Conseguí tus Eryns**.

La vitrina muestra Theryon en Lorencia (**Que te vean llegar.**), Nerathys en las ruinas heladas de Devias (**Tres cabezas. Una presencia.**) y Vaeraxes en una fortaleza volcánica (**El cielo lleva tu nombre.**). Escenas semiveladas, candado dorado, rareza visual azul/violeta/oro y barrido de luz. Estado: **Se revela en la apertura**. La cuarta tarjeta permanece completamente oculta, con un signo de pregunta luminoso. Los colores son lenguaje visual; no definen rarezas ni estadísticas del juego.

El **Salón privado** va entre bestias y Eryns. Titular: **Tu nombre. Otra presencia.** Personaje conceptual de MU, aura dorada y nombre de la cuenta con insignia VIP en HTML. Comparación Normal/VIP, tres espacios de beneficios y tres tarjetas de duración con la del medio destacada. Beneficios, precios y días: **[COMPLETAR]**, sin ofertas nuevas. El estado de cuenta se lee de SQL sin escritura: normal → **Activá tu VIP**; activo → días restantes y **Extender VIP**. Si la consulta falla, **Conocé el VIP**, sin asumir que es normal. Los botones muestran detalles pendientes: no ejecutan compras de planes no definidos.

En los packs se conservan cantidades, bonus, precios e identificadores. El destacado ocupa el centro y recibe mayor espacio, luz y partículas. Se conserva el catálogo real. Bolsa → cofre → cofre legendario. Bóveda: **La bóveda de [usuario]**, con cofre y saldo real. El botón de pago mantiene Ualá y la barra fija móvil. Combinaciones, FAQ, historial y términos quedan al final, colapsados. Los inputs externos conservan su asociación al formulario de compra mediante el atributo `form`.

Movimiento: idle por transform, tilt/parallax solo con puntero fino, destello al seleccionar, contador animado, partículas CSS y foil. Las animaciones fuera de pantalla se pausan con IntersectionObserver. `prefers-reduced-motion` apaga todas las animaciones y los movimientos del puntero. No hay videos, nuevas fuentes ni librerías. Las escenas son WebP de 810×1080, cargadas de forma diferida.

## Arte y prompts

Las cuatro escenas se generaron con la herramienta integrada image_gen. Las tres monturas usan los retratos existentes como referencia de identidad. Los SVG mantienen el sistema vectorial de la web y no requieren generación raster.

| Archivo | Uso |
| --- | --- |
| `theryon-scene-v5.webp` | Lorencia, cristal azul |
| `nerathys-scene-v5.webp` | Devias, cristal violeta |
| `vaeraxes-scene-v5.webp` | Fortaleza volcánica, fuego |
| `vip-champion-scene-v5.webp` | Salón privado, personaje con aura dorada |
| `vip-insignia-v5.svg` | Nombre de cuenta, insignia VIP |
| `vip-benefit-crown-v5.svg` | Espacio de beneficio 1 |
| `vip-benefit-shield-v5.svg` | Espacio de beneficio 2 |
| `vip-benefit-star-v5.svg` | Espacio de beneficio 3 |

Todos están en `overlay/templates/mupanic/img/recharge/`.

### theryon

```text
Use case: identity-preserve. Asset type: portrait cinematic mount scene for MU PANIC MMO store. Preserve the reference Theryon exactly: white lion, icy eyes, sapphire blue and silver spiked armor, four legs, white mane. Put this same beast standing majestically in a medieval Lorencia town square, ancient castle gate, blue magical crystals, nighttime, pale blue rim light and warm distant torchlight. Entire beast visible with breathing room, dominates central frame, realistic integrated ground shadows, no rider. Tall 3:4 composition, AAA fantasy game cinematic art, rich contrast, midnight navy palette, blue crystal aura, full scene no flat background. No letters, UI, text, numbers, badges or frame. Reference is identity and armor reference, retain recognizable design.
```

### nerathys

```text
Use case: identity-preserve. Asset type: portrait cinematic mount scene for MU PANIC MMO store. Preserve Nerathys reference: THREE-headed winged hydra with gold armored scales, violet glowing wing membranes, gold horns and armor, exactly three heads. Pose this same creature in the snowy ruins of Devias with towering frost crystals and violet magical fog. Wings fully visible, creature occupies center, full creature with breathing room. Nighttime moonlit ruins with violet and blue rim light, gold metal highlights. No rider. Tall 3:4 composition, AAA fantasy game cinematic illustration, integrated landscape, no flat background. No text, letters, numbers, UI, badges or frame. Preserve recognizable creature anatomy and armor.
```

### vaeraxes

```text
Use case: identity-preserve. Asset type: portrait cinematic mount scene for MU PANIC MMO store. Preserve reference Vaeraxes exactly: ONE red dragon with crimson scales, silver ornate spiked armor, orange glowing eyes, red wings. This same beast crouches in a volcanic MU fantasy fortress surrounded by embers, fractured obsidian and flame fissures, ancient stone arches. Entire beast and wings visible with breathing room, dominates center, no rider. Dark cinematic night, fire backlight, red rim light and gold sparks. Tall 3:4 composition, AAA fantasy game promotional illustration with rich detailed environment, no flat background. No text, letters, numbers, UI, badges or frame. Retain recognizable armor and anatomy.
```

### vip-champion

```text
Use case: ads-marketing. Asset type: portrait cinematic VIP champion scene for MU PANIC MU Online Season 6 store. A powerful Dark Knight fantasy game character in elaborate black and gold plate armor, spiked shoulder guards and a dramatic cloak, standing in an exclusive ancient throne hall of black stone and gold metal. A luminous ethereal gold crown floats above his helmet, gold aura and subtle sparks, elegant luxury rather than yellow everywhere. Heroic full body centered, sword pointing down, dignified still pose. Tall 3:4 composition. Deep black midnight navy palette, brilliant controlled gold edge lighting, realistic AAA fantasy MMORPG cinematic illustration. Leave dark quiet space above the crown for account name and VIP badge to be rendered in real HTML. No text, letters, numbers, logos, UI, visible statistics or frame.
```

### Insignia VIP — prompt alternativo raster

```text
Use case: logo-brand. Asset type: transparent VIP badge for a MU PANIC fantasy MMORPG store. A compact symmetrical five-point crown above a black enamel plaque with the exact letters "VIP". Brilliant but controlled gold foil, crisp metallic bevels, deep black recesses, premium medieval style, front view, clearly readable at 64 pixels. Entire badge visible. True transparent background. No additional words, numbers, glow rectangle or environment. Match the black-and-gold VIP champion scene.
```

### Íconos de beneficios — un prompt por símbolo

```text
Use case: stylized-concept. Asset type: transparent premium UI benefit icon for MU PANIC VIP. A single symmetrical medieval CROWN, metallic gold rim, black enamel interior, frontal view, simple strong silhouette, controlled gold edge light. Entire icon visible, readable at 48 pixels. True transparent background. No letters, labels, numbers, UI frame or environment. Match the crown/shield/star vector icon family. This is a decorative slot: do not depict or imply an undefined gameplay benefit.
```

Para el segundo y tercer ícono usá el mismo prompt reemplazando **CROWN** por **SHIELD** y **FIVE-POINT STAR**, respectivamente. Los beneficios asociados siguen como `[COMPLETAR]`.

## Integración

HTML/PHP: `inc/recharge.php`, `inc/recharge-vip-showcase.php`; lectura del estado: `inc/recharge-vip-status.php`; CSS: `css/recharge.css`; JS: `js/recharge.js`. El despliegue de beta verifica la presencia de los ocho assets. No se modifican la landing de main, los planes del juego, el worker, SQL ni la configuración de pagos.
