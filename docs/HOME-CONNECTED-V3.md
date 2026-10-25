# Inicio — composición continua

Inicio de beta de MU PANIC, versión `connected-v4`, 20 de octubre de 2026. El recorrido es RF contra BK → datos reales → Furia → Fractura → cuatro compañeros → F11 y Hub → Vessara → eventos → minijuegos. `information.php`, la página de El servidor, Fractura, Mercado y Atlas se conservan. El dominio principal queda fuera del alcance.

El combate usa RF Phoenix Soul y BK moderno. El Señor de la Fractura aparece solo con Aldren/Varkor. Las mascotas son Elyndra, Aurion, Vaerion y Grum, una vez cada una. Esta primera mitad conserva la composición aprobada por Agustín.

## Segunda mitad

La tecla F11 se conserva junto a una presentación amplia del Hub. Sus vistas de Inicio y Juegos se prepararon con imagegen desde las capturas actuales suministradas por Agustín, retirando el entorno externo del juego. Las pestañas web explican Inicio, Personaje, La Fractura, Eventos, Mercado, Juegos y Ayuda. Solo Inicio y Juegos disponen de vistas reales: no se inventan pantallas de los demás apartados. La galería no ejecuta acciones del cliente ni apuestas. Todas sus descripciones quedan accesibles sin JavaScript; con JavaScript funciona como pestañas con flechas, Home/End y foco de teclado.

Vessara ocupa una escena nueva de comercio de equipo en Loren Market, siguiendo la identidad de Vessara ya aprobada. Los eventos reúnen el Castillo Sangriento del Atlas y las tres próximas entradas reales, con cuenta regresiva de la primera. La sala de juegos muestra tragamonedas, cartas y ruleta, seguida de ocho pestañas explicativas de los juegos de la captura actual. Los juegos se abren dentro del cliente, F11 → Juegos. No se muestra la anterior ilustración de dragón volando ni la presentación de tres joyas en el mercado.

Las composiciones conservan proporciones completas con `object-fit:contain`. En celular, Vessara y la sala de juegos usan nuevas composiciones verticales completas y la copia va debajo del arte. Las máscaras de niebla conectan los ambientes sin encerrar las secciones en tarjetas. El Hub y la tecla comparten un escenario abierto; mercado, eventos y juegos tienen escenas amplias.

## Movimiento

Efectos por capas: energía e impacto sobre el combate, energía de bandos en Fractura, luz y brasas de los faroles del mercado y castillo, y rueda de ruleta independiente girando sobre la mesa. No son videos nuevos de personajes peleando. No se deforman ni se desplazan las ilustraciones completas. Un solo bucle de canvas a un máximo de 25fps atiende las escenas visibles; se detiene al ocultar la pestaña y con movimiento reducido. La ruleta también se pausa fuera de pantalla. Se retira el control visible de pausa por instrucción del usuario; la preferencia de movimiento del sistema sigue respetándose.

Furia conserva el marco, emblema, ojos, energía, números y MASACRE originales de `MU_PANIC_FURIA_ELECTRICA.zip` y `MU_PANIC_FURIA_COMBO_EFECTOS.zip`, convertidos a WebP. Sus videos reutilizan los loops originales de `MU_PANIC_FURIA_LATIDOS_Y_DESTELLO.zip`, a 30fps sin audio, cargados solo al entrar en pantalla. Se pausan fuera de ella y con movimiento reducido. El pulso usa VP9 con transparencia; si falta soporte queda la calavera estática. La combinación x20/MASACRE es una demostración, no telemetría del jugador. La ilustración de Furia no repite al BK ni inventa otra clase.

## Datos y despliegue

Rates desde `panicAtlasBalance()`, conectados de la misma caché que el resto de la web, estado y eventos de `/api/launcher.php`. Refresco cada 60 segundos con la pestaña visible; eventos iniciales renderizados por PHP desde la proyección existente. No se abren consultas SQL nuevas ni se inventan números u horarios. Se elimina la fecha de última consulta. Los horarios de eventos siguen expresados en Argentina.

`inc/home-world.php` contiene la segunda mitad; `home-launch.php`, `home-launch.css` y `home-launch.js` solo afectan Inicio. `index.php` no se modificó en esta revisión y su firma sigue coincidiendo con `scripts/economy-sha256.txt`. El despliegue comprueba todos los recursos antes de copiar. `tools/home-connected-art.json` registra procedencia, dimensiones, tamaños y SHA256; `tools/home-world-prompts.json` conserva el brief de las nuevas artes.

Validación: `python3 tests/home-render.py`, `php tests/launcher.php`, sintaxis PHP/JS/bash y manifiesto de Economía. `node tests/home-browser.cjs` se ejecuta en GitHub Actions y verifica el template real a 1920, 1440, 1100, 1024, 768, 760, 390 y 320 px: recursos, proporciones, overflow, menú, pestañas, movimiento reducido, datos y renderizado seguro de la API. Las capturas usan datos aislados, sin SQL. El despliegue completo también se prueba dos veces en una carpeta temporal para validar copia e idempotencia.

Publicación: rama `beta` → cPanel Git Version Control → Update from Remote → Deploy HEAD Commit. Publicar en GitHub no confirma que cPanel haya aplicado el cambio.
