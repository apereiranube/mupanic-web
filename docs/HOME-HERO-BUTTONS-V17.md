# Botones ilustrados y alineación del hero

El hero reutiliza las dos artes transparentes V16 del cierre, con textos HTML e iconos separados y el mismo reflejo limitado por la silueta. No se generan imágenes nuevas ni se duplica su descarga. Los botones del hero tienen una escala algo menor que los del cierre.

Las reglas comunes se agrupan bajo `.ph-button-artwork`, compartidas por hero y CTA final. Las variantes de cuenta y descarga conservan sus enlaces; con sesión el primero muestra MI CUENTA. El header unificado permanece independiente.

En escritorio (>1100 px), el contenedor del hero pasa de 1320 a 1420 px de ancho máximo, con margen mínimo de 40 px. Esto acerca juntos título, bajada y botones entre 16 y 50 px al borde izquierdo según la pantalla. El video no se mueve. En celular se conservan los márgenes de lectura y los botones se apilan.

Pruebas: render como visitante y usuario conectado, enlaces, arte completo a proporción 3:1, textos dentro de la zona central, movimiento efectivo hacia la izquierda en escritorio y ausencia de desbordes. Se mantienen las revisiones del Inicio y de la cabecera compartida.
