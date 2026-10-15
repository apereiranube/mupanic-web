# Salón de la Fama y guía de Fractura V34

El Salón presenta la conquista semanal en una cámara ceremonial, con estandartes, placas de semanas cerradas y pedestales para laureados vigentes. Las reglas, premios, cobro y escala de rangos pertenecen al Atlas: `/info/#fractura`.

## Arte

Dos imágenes originales generadas con la herramienta integrada de imágenes: paisaje 1536×1024 y una composición vertical 1024×1536. Dirección: salón de piedra negra y bronce envejecido, estandarte azul/plata y rojo/obsidiana, trono vacío, espacio para inscripciones; sin texto ni interfaz incrustada. Se exportaron a WebP. La interfaz y las cifras siguen siendo HTML accesible y datos reales.

## Datos y despliegue

1. Ejecutar SOLO VPS el paquete `MU_PANIC_VISTAS_WEB_FRACTURA_VPS_V34.zip`. Publica cuatro vistas específicas y otorga SELECT sobre ellas a public, siguiendo el modelo existente de FactionTotalsV6. No otorga acceso directo a tablas internas. Los GameServer pueden continuar funcionando.
2. Actualizar y desplegar HEAD de beta desde cPanel.
3. Verificar `/fractura/#salon-de-la-fama` y `/info/#fractura`.

Vistas: `MUPanicFracturaWeekCurrentV34`, `MUPanicFracturaWeekHistoryV34`, `MUPanicFracturaLaureatesV34`, `MUPanicFracturaWeekRulesV34`. Los lectores usan SELECT simples. No se ejecutan cierres ni entregas desde la web. Se separan estado desconocido, historial vacío, empate y ganador. No se rellenan errores con ceros. El Atlas consulta premios actuales o muestra una referencia fechada del 14/10/2026.

## Verificación

PHP: sintaxis, lectores con dobles de conexión, separación del contenido técnico, fechas, empate, ganador y escape de nombres. Navegador Chromium a 320, 390 y 1440 píxeles: ocho escenarios de muestra, carga de arte, ausencia de desbordes y superposiciones. Los nombres y resultados de las fixtures son sintéticos y no forman parte de los datos del juego.

SQL: análisis de las cuatro definiciones, columnas públicas permitidas, exclusión de pruebas y laureados entregados/vigentes. PowerShell: análisis sintáctico y manifiesto de hashes. No se ejecutaron Windows ni SQL Server en la preparación; la instalación y lectura real quedan por comprobar en el VPS y hosting.
