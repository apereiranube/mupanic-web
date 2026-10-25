# Inicio: revisión de composición V6

Solo Inicio. No modifica El servidor ni fuentes del endpoint público.

## Cambios

- Retira separadores SVG dorados y el panel biselado de estadísticas.
- Estadísticas abiertas sobre terreno oscuro, con jugadores online destacados y tasas legibles.
- Cinco uniones ambientales diferentes: atmósfera azul/roja hacia Fractura, raíces y vegetación hacia compañeros, follaje hacia Hub, arcos y faroles hacia mercado, luz ámbar y tela hacia juegos.
- Las piezas transparentes se solapan con los paisajes; los bordes superior/inferior se difuminan y las escenas ocupan el ancho disponible.
- Furia conserva su interfaz animada real; explicación más compacta, sin numeración gigante.
- Las ocho pestañas del Hub comparten una misma celda de grilla: la altura queda determinada por el contenido más alto, no por la pestaña elegida. Las ocultas no reciben foco.
- Se conserva el árbol Master real, sin añadirle un marco o sombra rectangular.

## Arte

Generado con imagegen integrado. Piezas nuevas `img/home/join-{rocks,mist,forest,canopy,market,arcade}-v6.webp`, alfa original preservado. Origen, medidas, peso y SHA en `tools/home-connected-art.json`. Briefs en `tools/home-world-prompts.json`.

## Video de combate: pendiente, no simulado

No hay un clip nuevo de BK contra Rage en esta revisión. La herramienta integrada genera imágenes; se encontró Runway para imagen a video y se sugirió su conexión. Hasta disponer del clip, la portada conserva la ilustración y el movimiento de nubes existente. No se presenta como una pelea animada.

Brief de video: usar `hero-connected-v3.webp` como primer fotograma. Preservar exactamente identidad y equipo: Rage Fighter blanco/plata Phoenix Soul, puños verdes; Blade Knight carmesí, negro y dorado con espada roja. Plano general fijo, misma composición y espacio izquierdo para texto. 6–8 segundos: RF adelanta torso y golpea; BK bloquea con espada, retrocede ligeramente y devuelve un golpe; impacto visible sincronizado, capa y nubes con movimiento natural, polvo levantado del suelo. Anatomía y armaduras consistentes, sin morphing, sin nuevos personajes, sin cámara que recorte a los combatientes. Movimiento que vuelva al encuadre inicial para loop. Sin letras, música ni interfaz. Mantener paisaje y suelo compatibles con la unión inferior. Exportar MP4 H.264 y WebM, sin audio. Revisar el clip antes de integrarlo; un zoom/parallax de la imagen no satisface el pedido.

## Revisión

Preparar capturas reales de navegador en escritorio y móvil antes de mover beta. No confundir el commit de la rama de trabajo con despliegue de cPanel.
