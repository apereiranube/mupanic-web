# Recargas por transferencia bancaria — beta

La tienda conserva Ualá y agrega transferencia bancaria como método alternativo. Se reutilizan el bot y el servicio de recargas del VPS. La cuenta administradora `panic`, con permiso de administrador en WebEngine, configura y revisa las transferencias desde `/usercp/shopadmin/#shop-bank`.

## Circuito

1. El comprador elige paquetes, selecciona transferencia e ingresa su CUIT. El servidor valida el dígito verificador y guarda importe, Eryns y datos bancarios de esa compra.
2. En «Mis transferencias» ve titular, banco, alias o CBU/CVU e importe exacto. Cambiar la cuenta receptora no cambia las instrucciones de compras anteriores.
3. Después de pagar, pulsa «Ya transferí · Preparar ticket». Recibe un código privado que vence en 30 minutos y un enlace al canal soporte existente de Discord.
4. En soporte pulsa «Recarga por transferencia» o usa `/recarga`. El bot solicita el código y abre un ticket privado para ese comprador y los roles de soporte existentes. El código vincula la compra con el usuario real de la interacción de Discord.
5. El comprador adjunta el comprobante en su ticket. El bot registra el enlace al mensaje; si estuvo desconectado, el comprador puede pulsar «Ya adjunté el comprobante» para reintentar.
6. `panic` abre el comprobante desde el panel web, verifica el ingreso real en el banco, ingresa la referencia única del movimiento y marca la confirmación. También puede rechazar una orden pendiente.
7. El servicio existente acredita Eryns con la cuenta desconectada del juego. Un comprobante adjunto nunca aprueba el pago automáticamente.

## Instalación en el VPS

Primero actualizar beta desde cPanel con «Update from Remote» y «Deploy HEAD Commit». Después ejecutar como administrador el instalador del paquete desde Descargas:

```powershell
.\Instalar_Transferencias.ps1 -Action Aplicar
```

El instalador comprueba la versión del `index.js` que se leyó del VPS, crea un respaldo verificado en Descargas y agrega el módulo al bot existente en `C:\MU_PANIC_BOT`. Agrega `/recarga` y el panel de transferencias; mantiene los demás comandos y tickets.

Actualiza el servicio existente en `C:\MuServer43\RechargeSync` y la rutina SQL de entregas. Conserva token, configuración y recibos anteriores. La migración admite ambos proveedores y conserva la compatibilidad con el servicio anterior. Sólo pausa las tareas `MU PANIC Discord Bot` y `MU PANIC Recharge Worker`, conserva su habilitación y reinicia las que estaban ejecutándose. Ejecuta una consulta del servicio al terminar. Los GameServers no necesitan reiniciarse.

Los secretos siguen en `C:\MuServer43\PaymentsPrivate`. El módulo usa el token HMAC del servicio existente para comunicarse con beta; no coloca el token de Discord en la web.

En el panel web completar titular, banco, alias o CBU/CVU y opcionalmente CUIT del titular. El ID del canal soporte se propone a partir de la conexión del bot. Revisar que el servidor y canal sean correctos, marcar «Ofrecer transferencias bancarias» y guardar. El bot y el servicio versión 3 deben haber reportado actividad en los últimos 3 minutos, en el mismo ambiente, para permitir compras.

## Prueba de aceptación

Comprobar primero el ambiente indicado en el panel. El ambiente `test` separa las órdenes, pero la aprobación ejecuta la acreditación SQL real en la cuenta elegida: usar una cuenta de prueba y un importe controlado.

Preparar una compra, verificar los datos bancarios, obtener un código, abrir el ticket y revisar sus permisos desde otro usuario sin rol de soporte. Adjuntar un comprobante de prueba y verificar que aparece en el panel sin acreditar. Confirmar el ingreso sólo cuando corresponda, usar una referencia única, desconectar la cuenta y comprobar el saldo. Reintentar el servicio debe conservar el mismo saldo; una orden rechazada no debe acreditar.

## Recuperación

El respaldo en Descargas incluye `RESTAURAR.ps1`. Ejecutarlo como administrador restaura los archivos anteriores si no hubo modificaciones posteriores. La restauración conserva el registro SQL y las órdenes para evitar perder evidencia o repetir entregas. La migración SQL conserva compatibilidad con el servicio anterior.

Si la creación de un canal queda con resultado incierto, el bot busca un canal con el mismo identificador antes de reutilizarlo. No crea otro canal automáticamente tras una reserva incierta: soporte debe revisar la compra. El comprador no debe repetir la transferencia. Los códigos se pueden renovar desde la web y se almacenan como hash en el registro privado.

El registro privado `/home/mupanic/payments-private/recharge-bank.json` guarda configuración, CUIT y órdenes; respaldarlo con los demás archivos privados. Los comprobantes permanecen en los tickets de Discord. No borrar tickets pendientes ni su evidencia. El registro admite hasta 5.000 órdenes; requiere una política de archivo antes de alcanzar ese límite.

## Validación del código

`tests/recharge-bank.php`: estados, CUIT, identidad, autorización, comprobante, referencia única, aislamiento de ambiente y recibos idempotentes. `tests/recharge-bank-bot.cjs`: firmas, permisos privados y recuperación del mismo canal. `tests/recharge-bank-browser.cjs`: selector, CUIT, importe y diseño a 1440/390/320 px. Las pruebas usan datos ficticios y no cobran ni conectan al Discord real. La ejecución de SQL y las tareas de Windows requieren la prueba de aceptación en el VPS.
