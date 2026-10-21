# Núcleo compacto y fuentes de datos

- El arte y las animaciones aprobadas se conservan. La composición pasa de un ancho máximo de 1760 a 960 px; el núcleo tiene un máximo de 243 px en escritorio y 220 px en celular. Los emblemas acompañan la escala.
- La altura se reduce junto con la composición, conservando la pintura de fondo a todo el ancho y sus máscaras hacia el video y Furia.
- El contador inicial no muestra el dato del cron: espera la consulta actual del mismo recuento WebEngine (`MEMB_STAT.ConnectStat = 1`). Caché compartida de 15 segundos, refresco del Inicio cada 30 segundos. El nuevo nombre de caché descarta valores de la versión anterior.
- EXP, Master EXP y drop se consultan desde el mismo export GameServer que usa el Atlas, tanto al renderizar como al refrescar. Su actualización depende de sincronizar ese export.
- Se prueban cero real frente a cron atrasado en uno, cambios 100 → 0, SQL caído, export de rates actualizado, formato de 100 y 100.000 jugadores y ausencia de un contador viejo ante errores de red.
- El estado conserva el significado anterior de WebEngine: mantenimiento o disponibilidad de SQL; no se presenta una comprobación nueva del puerto GameServer.
- El script de brasas suministrado permanece idéntico.
