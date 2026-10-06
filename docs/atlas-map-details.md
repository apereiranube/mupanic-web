# Exploración de mapas

El mismo componente `atlas-explorer.php` presenta los 30 mapas a partir del snapshot vigente. No cambia mapas, coordenadas, población, drops, cantidades ni tasas. El plano usa los recursos originales del cliente, con la misma proyección y la advertencia sobre alineación pendiente; el arte conceptual se conserva en la cabecera.

Los spots usan una grilla numerada sin scroll interno, vinculada a los puntos del plano. El perfil de zona calcula rango de niveles y población desde sus monstruos. Cada spot permite seleccionar un monstruo con retrato, nivel, vida y reaparición. La población completa usa un selector por nombre, conservando los enlaces `#mob-mapa-monstruo`. JavaScript muestra una ficha a la vez y respeta enlaces directos; sin JavaScript las fichas siguen presentes.

El adelanto muestra hasta tres objetos distintos de reglas elegibles, priorizando reglas del monstruo, del mapa y luego generales. No presenta premios garantizados ni suma probabilidades. Solo en ese adelanto se omiten los prefijos conocidos de balance REGIONAL/GLOBAL/PILAR1 de los nombres de joyas; el nombre exportado se conserva en el título y en la lista completa. Las reglas y sus tasas por cuenta permanecen intactas. Las listas de recompensas especiales están visibles junto a la ficha, sin exigir abrir los drops comunes.

Validación: apertura de los 30 mapas; ausencia de scroll interno en los spots; selección de zona y monstruo; selector de población completa; enlace profundo a un monstruo; filtro de cuenta Free/VIP con reglas pobladas; imágenes, navegación por teclado, cinco tamaños de pantalla y ausencia de desbordes horizontales. Eventos queda fuera de este cambio.

Se agregan 66 retratos reales de mobs desde MuOnline.Net, guardados como WebP sin pérdida con origen y hash en `tools/atlas-web-portrait-sources.json`. Las variantes de spawn del mismo monstruo de Vulcanus comparten su apariencia. Canon Trap y Laser Trap usan un símbolo de trampa identificado como tal; no se inventa una captura del cliente. El chequeo de cobertura incluye población y spots de todos los mapas.
