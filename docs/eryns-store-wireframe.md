# ERYNS — wireframe y copy final

## 1. Hero

Theryon ocupa el lado derecho de una escena grande. El texto va a la izquierda;
en celular, encima de la escena. El fondo usa la ilustración épica del servidor.

**Titular:** Monturas legendarias. Compañeros que evolucionan.

**Apoyo:** Elegí tu próximo objetivo en PANIC.

**CTA:** Conseguí tus Eryns.

## 2. Vitrina de deseo

**Título:** ¿Con cuál querés entrar en batalla?

Tres productos: **Theryon**, **Nerathys** y **Vaeraxes**, cada uno con su arte
completo, nombre, título y una ventana de detalle dentro de la tienda.

Se confirmó dejar las monturas como **Próximamente**. Sus precios no se inventan.
Al definir precio y habilitar cada producto, el copy pasa a **X Eryns** y el
botón a **Recargar para este →**. El selector elige el paquete de menor importe
que alcanza el costo; si ninguno alcanza por sí solo, usa unidades de un pack
sin superar los límites existentes. El objetivo queda indicado en la tienda.

## 3. Packs ilustrados

**Título:** Elegí tu pack de Eryns.

**Apoyo móvil:** Deslizá para ver los packs →

Cada tarjeta muestra:

- Una bolsa, cofre o cofre legendario, creciendo en presencia visual.
- **X Eryns**, como total recibido.
- **+N% EXTRA**, calculado con el regalo real del catálogo.
- **B + R de regalo**, separando cantidad base y bono.
- **$P ARS**, siempre visible.
- **Elegir pack**, que cambia a selección marcada cuando está elegido.

El recomendado tiene borde dorado con un brillo suave. **MEJOR VALOR** solo se
muestra si su cantidad recibida por peso supera estrictamente a los demás;
si empatan, la etiqueta es **RECOMENDADO** y respeta el destacado configurado.
No se usa «Más elegido» sin datos de ventas.

Al abrir la página se preselecciona el recomendado aunque el controlador
inicialice todas las cantidades en cero. Esto solo prepara la selección visual:
no crea compras. Una tienda cerrada sigue cerrada y un carrito inválido sigue
bloqueado. Una selección enviada al crear una compra se conserva.

No se activa la escalera de ejemplo +5/+10/+15/+20. Los bonos se leen del
catálogo real. Con el ejemplo visto en la captura, 20.000 + 2.000 entrega
22.000 Eryns por $20.000 ARS: **+10% EXTRA**.

## 4. Resumen de compra

**Encabezado:** Recibís en tu cuenta.

**Total:** X Eryns.

**Regalo, si existe:** B + R de regalo.

**Precio:** Total a pagar · $P ARS.

**CTA:** Pagar con Ualá →

**Apoyo:** Continuás en Ualá para completar el pago.

Si existe un pack superior con bono real, se puede ofrecer **Recibí R Eryns
extra**, indicando claramente el importe del pack y dejando la elección manual.
No se incrementa el carrito automáticamente.

«¿Querés otra cantidad? Combiná paquetes» permite mantener el flujo de compras
múltiples. El precio y el regalo se recalculan. En celular, una barra fija muestra
cantidad, importe y un botón que envía el mismo formulario de pago.

## 5. Confianza

- **Pago con Ualá:** Completás el pago en su página.
- **Eryns para tu cuenta:** Los comparten tus personajes.
- **Acreditación automática:** Desconectate del juego para recibirla.

## 6. Final

**Mis compras** · Historial y estado de tus recargas.

**¿Cómo recibo mis Eryns?** · Pago, entrega y preguntas frecuentes.

Ambos bloques son desplegables. Los estados se siguen consultando aunque el
historial esté cerrado. «Mis compras» desde arriba abre el historial dentro de
la misma página. Los términos quedan en una línea discreta bajo la compra.

**Cierre:** Cada recarga ayuda a mantener y desarrollar MU PANIC.
Gracias por construir este mundo con nosotros.

## Archivos de integración

- `overlay/templates/mupanic/inc/recharge.php`: HTML renderizado con el catálogo,
  la sesión, CSRF, nonce y el controlador Ualá existentes.
- `overlay/templates/mupanic/css/recharge.css`: diseño y microanimaciones,
  con adaptación móvil y preferencia de movimiento reducido.
- `overlay/templates/mupanic/js/recharge.js`: selección, totales, bonos,
  objetivo de producto, ventanas de detalles, pago e historial.
- `overlay/templates/mupanic/inc/recharge-storefront.json`: productos de la
  vitrina; precio `null` y disponibilidad `false` hasta definirlos.

Se integra en beta, conservando identificadores internos, precios, credenciales,
SQL, worker, validaciones y acreditación. Main conserva su landing.
