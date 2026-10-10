# MU PANIC: avances de la comunidad

El bot consulta `community/discord-avances.json` por HTTPS al arrancar, cada cinco minutos y al abrir `/avances`. Recibe datos, nunca código. Solo incorpora campos id/title/text. Fuente fija; sin credenciales Git ni SQL. Conserva el último archivo válido si falla la consulta.

El contenido es público: incluir solo anuncios para jugadores, sin credenciales, datos personales, diagnósticos internos ni funcionalidades sin confirmar. Los anuncios de desarrollo deben aclarar que forman parte de las pruebas previas a la apertura.

Para un nuevo anuncio, agregar un objeto con ID único estable al array. No cambiar el ID de un anuncio ya enviado para republicarlo. No convertir commits técnicos automáticamente en anuncios. La fuente no accede a chats: el resumen se prepara editorialmente a partir de trabajo confirmado.

`/avances` es solo para administradores y muestra una vista previa privada. El botón Publicar envía únicamente el avance revisado. Dejar pendiente conserva el contenido. Registra el intento antes de enviar; en un envío incierto no reintenta automáticamente. El historial sobrevive reinicios.

La instalación es una sola vez sobre las versiones de novedades V1, V2 o V3 cuyo index coincide con el hash revisado. Respalda los módulos, reinicia el bot y restaura el original ante errores. Preserva configuración de canal, historial, credenciales y datos del juego. No envía anuncios durante la instalación. No requiere desplegar la web ni cambios en main.

Pruebas: Node syntax; interacciones simuladas de autorización, cancelación, publicación única y persistencia; sincronización concurrente, validación y cache frente a fallos. Verificación de PowerShell y Discord real pendiente en el VPS.
