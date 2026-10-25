# Economía en claro — versión amigable

Actualización de presentación de `/usercp/shopadmin/?eco=1`. Las seis pestañas siguen en la misma ruta. Se mantiene la cuenta `panic`, la sesión administrativa, el método GET y las consultas SELECT sobre las vistas existentes.

## Qué se ve ahora

- **Resumen:** una frase con lo ocurrido, cuatro cifras grandes, recorrido de los Eryns, tres señales explicadas y un gráfico pequeño de la plata que entró.
- **Juegos:** ocho tarjetas, premios con semáforo, cinco ganadores y cinco perdedores. Elegir un juego abre sus últimas jugadas; las tablas completas y otros gráficos quedan plegados.
- **Mercado Vessara:** ventas en Eryns y Zen separadas, con comisión, promedio, destacados y cobros pendientes. Top cinco compradores y vendedores por moneda.
- **Tienda y VIP:** frase de compras, tres cifras principales, cinco artículos más elegidos, VIP activos y cuántos vencen en siete días. Los VIP dados sin cobrar se muestran en amarillo solo cuando existen.
- **Recargas:** frase explicativa, tres cifras, gráfico y diez recargas recientes. El resto queda en Ver detalle.
- **Revisiones:** actualización de datos, cuentas sin explicar y avisos pendientes. El historial, las cuentas con más Eryns y mis pruebas están en Ver detalle.

Todas las tablas generales empiezan cerradas. Las ayudas «?» funcionan con mouse, teclado y toque; la ayuda se ajusta al ancho de la pantalla. En celular los bloques de contenido van en una columna. Los gráficos y tablas que están dentro de un desplegable se inicializan al abrirlo.

## Semáforos

Todos llevan palabra e icono SVG, además del color.

| Señal | Bien | Mirar | Revisar |
| --- | --- | --- | --- |
| Eryns que se gastan frente a los creados | Más de 40% | Entre 15% y 40% | Menos de 15% |
| Premios de todos los juegos juntos | 85% a 97% | 80% a menos de 85%, o más de 97% a 102% | Menos de 80% o más de 102% |
| Premio de un juego | 85% a 97% | Fuera de ese rango, hasta los límites de revisión | Menos de 70% o más de 110% |
| Cuentas sin explicar | Menos de 1% | 1% a 5% | Más de 5% |
| Actualización de datos | Hasta 15 minutos y carga correcta | — | Más de 15 minutos, error o falta de registro |

Si falta una base para calcular, se muestra «Sin datos» en gris: no se inventa un porcentaje ni una señal verde. La comparación contra un período con cero movimientos se explica con palabras, sin divisiones por cero.

Entradas: verde y ▲. Salidas normales: naranja y ▼. El rojo queda para problemas. Si los juegos devuelven más de lo apostado, se muestra una entrada, no una falsa salida. Las barras de gastos no usan anchos negativos.

## Decisiones para conservar el significado de los datos

No se modificó ninguna consulta SQL ni el repositorio de datos. Los nuevos textos, sumas, promedios de presentación, proporciones y listas cortas salen de los valores que ya devuelven sus métodos existentes. Se llaman métodos existentes para mostrar información en otras pestañas; no se agregó SQL nuevo. Se conservan los filtros y los valores internos de las URLs.

- **Revisión de cuentas:** la vista de cuadre es global y no separa pruebas. Por eso el semáforo usa la diferencia sin explicar y el saldo al cierre del último día del rango, ambos de todas las cuentas. Se indica que incluye mis pruebas; no se compara una diferencia global con el saldo exclusivo de cuentas reales. El saldo grande «Eryns en manos de jugadores» sigue siendo el de cuentas reales.
- **Recorrido de Eryns:** la diferencia entre recargas y gastos se describe como lo ocurrido «por estos movimientos». No se presenta como una explicación completa del saldo actual: pueden existir misiones, baúles, regalos y créditos manuales.
- **Destacados:** la cifra disponible es toda la comisión de las ventas destacadas. La frase dice «comisión en total», no «comisión extra», para evitar atribuir ese importe solo al recargo adicional.
- **Avisos:** las consultas existentes cargan hasta 500. Si se alcanza el límite, se informa «500 o más», no un total exacto. Los resueltos no se cuentan como pendientes. El semáforo no cambia al filtrar la tabla por estado.
- **Top de tienda:** cuenta operaciones distintas que incluyeron un artículo; no se inventan cantidades de unidades.
- **Agrupación:** el gráfico de plata muestra día, semana o mes según el filtro vigente. El título se ajusta a la selección.

## Archivos de la web modificados frente a la V2

Rutas relativas a la raíz del CMS (en Git llevan el prefijo `overlay/`):

1. `templates/mupanic/inc/admin-economy/bootstrap.php` — valores y estados vacíos comprensibles en las tablas.
2. `templates/mupanic/inc/admin-economy/entry.php` — carga de los helpers de presentación y explicación de errores.
3. `templates/mupanic/inc/admin-economy/layout.php` — textos generales, filtro de pruebas y pestaña Revisiones.
4. `templates/mupanic/inc/admin-economy/tabs/resumen.php`
5. `templates/mupanic/inc/admin-economy/tabs/juegos.php`
6. `templates/mupanic/inc/admin-economy/tabs/vessara.php`
7. `templates/mupanic/inc/admin-economy/tabs/tienda-vip.php`
8. `templates/mupanic/inc/admin-economy/tabs/recargas.php`
9. `templates/mupanic/inc/admin-economy/tabs/control.php` — el archivo y la URL conservan el nombre interno por compatibilidad; en pantalla dice Revisiones.
10. `templates/mupanic/css/admin-economy.css`
11. `templates/mupanic/js/admin-economy.js`

Archivo nuevo:

12. `templates/mupanic/inc/admin-economy/presentation.php` — iconos SVG, ayudas, frases, comparaciones, semáforos y diagrama.

Además se actualizan los manifiestos `scripts/economy-files.txt` y `scripts/economy-sha256.txt`, esta documentación, `docs/ECONOMIA.md` y las pruebas de presentación/navegador. El ZIP mantiene los archivos necesarios para una instalación completa, aunque varios no cambien en esta revisión.

**Sin cambios:** `repository.php`, `filters.php`, la autorización de shopadmin, las vistas, los permisos SQL, las recargas y el núcleo del CMS.

## Instalar y volver atrás

**Flujo habitual:** actualizar `beta` y ejecutar Deploy HEAD Commit. El deploy existente guarda los archivos que cambian antes de copiarlos, usando el manifiesto actualizado. Después abrir `/usercp/shopadmin/?eco=1`.

El ZIP es alternativo al deploy por Git: no hace falta usar ambos. Incluye `scripts/INSTALLAR_ECONOMIA.ps1` con Estado / Aplicar / Restaurar y el mismo respaldo privado de la versión anterior. Instrucciones de SSH, ubicación de respaldos y restauración: [ECONOMIA.md](ECONOMIA.md).

El SHA256 del ZIP se entrega por fuera del propio ZIP. El comando de verificación se suministra con la entrega. Restaurar exige la ruta exacta del respaldo y se detiene si encuentra cambios posteriores ajenos.

## Verificación

- Consultas y filtros comparados byte por byte contra la versión V2: iguales.
- Pruebas de los límites de semáforos, valores cero, falta de fotos, diferencias negativas, devolución de juegos, textos y ayudas.
- Seis pestañas en navegador a 1440 y 390 px con el CSS real del template y datos sintéticos; carga desde una función para reproducir el contexto del CMS.
- Desplegables cerrados, ayudas tocables y sin desborde, bloques móviles en una columna, carga de gráficos/tablas al abrir y detalle de juego seleccionado.
- Casos sin movimientos, carga atrasada, error de lectura, invitado, cuenta común, rechazo de POST y rutas inválidas.
- Instalación y restauración en carpetas aisladas con hashes, idempotencia, enlaces simbólicos y conflictos.

La validación visual usa datos sintéticos; esta sesión no consultó el SQL Server real. El usuario ya confirmó que la V2 funciona contra sus vistas.

```bash
php tests/economy.php
php tests/economy-presentation.php
python3 tests/economy-install.py
PHP_BIN=php CHROME_BIN=/ruta/chrome ECO_JQUERY_FIXTURE=/ruta/jquery.js node tests/economy-browser.cjs
```
