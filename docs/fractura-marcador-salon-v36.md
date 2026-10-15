# Pantallas semanales y Salón de ganadores

Los altares conservan su arte y fuente tipo pantalla, pero muestran únicamente la competencia semanal: puntos de boss oficial + PvP confirmado en el servidor V35B. El conteo se identifica como puntos de esta semana, con la hora de cierre Argentina. No mezcla contribución personal acumulada ni cantidad de integrantes con el marcador.

El Salón de la Fama conserva el arte ceremonial y presenta la última semana cerrada, los resultados finales anteriores y los laureados vigentes. Los estandartes son símbolos de los bandos; no muestran contadores de la semana en curso. Sin cierres confirmados se muestra el estado de espera, sin declarar un ganador.

La página y el endpoint `fractura/?fractura_week=1` usan el lector semanal existente y un payload `schema=2, kind=weekly`. JavaScript actualiza cada 60 segundos y al volver a la pestaña, valida el tipo de marcador y rechaza los datos acumulados del endpoint anterior. Un fallo conserva el último valor visible y avisa que está sin actualizar; no inventa ceros. El cierre y la pausa tienen etiquetas explícitas. El endpoint acumulado `fractura_data=1` permanece disponible para compatibilidad, fuera de estas pantallas.

La web solo consulta. No cambia SQL, puntos, premios, cuotas por IP, Lua ni el cierre V35B. Despliegue: actualizar y publicar beta desde cPanel; no requiere parche PC/VPS.

Validación: PHP y contratos de los lectores; JavaScript con respuestas semanales, datos acumulados rechazados, cierre y cambio de semana; conservación de números grandes y manejo de fallos; navegador a 320/390/1440/1920 px con componentes y arte reales, SQL simulado. Pendiente: confirmar el despliegue en el hosting y la actualización con una baja real de PvP.
