# Inicio connected-v5

Revisión de Inicio sobre beta `f8e79135a4d9b2fa2e9d1cef77e82da30c0332be`. Solo cambia la presentación del Inicio. No modifica El servidor, el índice compartido del template, la API, SQL ni datos del juego.

La animación de chispas del hero se retira. Una capa de nubes realistas con transparencia se mueve sobre el cielo, fuera de los personajes, y se detiene fuera de pantalla o con movimiento reducido. Esto aporta atmósfera; los personajes mantienen la pose del arte aprobado. Una secuencia de pelea con movimiento real de personajes sigue necesitando un clip de video. No se presenta este efecto como una pelea animada terminada.

La barra de servidor aumenta su jerarquía: marco con extremos biselados, título, valores más grandes y pulso del indicador Online. El estado depende del mismo endpoint real. No se inventan jugadores ni actividad. Furia conserva sus videos reales y estrena una secuencia tipográfica Cargala / Desatala / No pares, con tecla Shift y los efectos explicados. Se reducen las máscaras negras profundas y se agregan uniones doradas con gema entre escenas; bandos y mascotas dejan de solaparse.

El Hub tiene un entorno arquitectónico nuevo que continúa el bosque de los compañeros. Sus ocho pestañas reemplazan el panel completo: Inicio, Personaje, Fractura, Eventos, Mercado, Tienda, Juegos y Ayuda. Inicio y Juegos muestran sus dos vistas conocidas. El resto son presentaciones web explicativas, sin fabricar capturas del cliente ni datos de una cuenta. Mercado explica comprar/publicar/cobrar; Tienda presenta Eryns y su acceso; Ayuda enlaza al Atlas. La agenda real, con cuenta regresiva del primer evento, se integra al panel Eventos y desaparece la escena independiente de gran tamaño.

La mejora del cliente tiene una presentación propia con el árbol Master real, recuperado de la captura `image(20261020-020400).png`. Se conserva la captura completa convertida a WebP; el visor CSS muestra la ventana completa, quitando del encuadre el juego que la rodea, sin regenerar sus iconos o textos. Helper e interfaces se explican con texto legible. Para mostrar capturas específicas de otras pantallas del Hub hacen falta esas capturas; no se las sustituye por imágenes inventadas que se anuncien como reales.

Vessara usa texto más grande, claro y concreto: buscar equipo, publicar objetos y cobrar en Zen o Eryns, con entrada desde F11 o Loren Market. La ruleta superpuesta y su giro se eliminan. La rueda está ahora pintada en perspectiva dentro de las composiciones de la sala, tanto horizontal como vertical. No se simula un giro real a partir de una capa plana. Se conservan los ocho minijuegos y se amplían sus descripciones.

Los recursos nuevos tienen nombres v5 para evitar caché antigua. `tools/home-connected-art.json` conserva sus fuentes, dimensiones, tamaños y hashes. Los prompts se describen en `tools/home-world-prompts.json`. El despliegue exige todos los recursos antes de copiar. La firma de Economía sigue coincidiendo porque `index.php` no cambia.

Validación: render con sesión/visitante/caché ausente, recursos y firmas; API existente; sintaxis PHP/JS/bash; navegador en ocho tamaños, pestañas completas, agenda segura ante datos externos, movimiento reducido y capturas para revisión visual. Las pruebas de tamaño no sustituyen la evaluación estética del usuario.
