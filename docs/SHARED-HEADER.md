# Cabecera común de MU PANIC

`style.css` contiene el único conjunto de reglas de `.site-header` y sus elementos. Se retiran las variantes de Inicio, El servidor, Atlas, Rankings y Perfil, junto con los ajustes inline específicos del template y los ajustes anteriores acumulados en `style.css`. El fallback sin JavaScript se comparte en `index.php`. Su hash se actualiza en `scripts/economy-sha256.txt`.

Se conserva la escala del Inicio: 86 px de altura en escritorio, 76 px hasta 1100 px y 70 px hasta 760 px. El fondo es opaco para que las ilustraciones distintas de cada página no alteren su color. Todas las páginas comparten fondo oscuro, borde de bronce, logo, tipografía Manrope, tamaños, espaciados, botones y menú móvil. El enlace activo continúa indicando la página actual. Fuente, line-height, caja, dimensiones y padding de la cabecera se fijan de forma explícita y no dependen de Bootstrap ni del tema de la página.

La carga de Bootstrap para el contenido de cada página se conserva. `tests/fixtures/bootstrap-3.4.1.min.css` es una copia de prueba del CSS oficial de Bootstrap 3.4.1 (`twbs/bootstrap`, tag `v3.4.1`, archivo `dist/css/bootstrap.min.css`); conserva su licencia MIT. No se incorpora a los archivos públicos del sitio.

`tests/header-browser.cjs` extrae la cabecera real de `index.php`, aplica los estilos y clases de Inicio, El servidor, Mercado, Fractura, Atlas, Rankings, Perfil y Mi cuenta, y compara estilos computados y geometría con/sin Bootstrap. Verifica 1920, 1366, 1024 y 390 px, visitante y usuario conectado, menú abierto/cerrado, Escape, foco, ausencia de desbordes y fallback sin JavaScript. La tarea `Shared header - page and Bootstrap parity` guarda capturas por página y ancho.
