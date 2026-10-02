# VIP PANIC: diagnóstico y propuesta

Estado: borrador sin compra habilitada. La web administrativa y el servicio de
recargas versión 2 están operativos; el usuario confirmó LastTaskResult 0 y el
panel recibió un contacto correcto. La compra de VIP es una integración distinta.

Configuración real leída por el administrador el 1/10/2026:
`Data/Custom/CustomBuyVip.txt` ofrece índices 0/1/2 Bronze/Prata/Ouro, respectivamente
10/20/30 de Exp+ y Drop+, 30 días, Coin1 10/15/20 y Coin2/Coin3 0.
`CustomItemVip.txt` está vacío (solo comentarios y end).
`CustomItemVipSwitch=1` figura en GameServer y GameServerCS Custom.dat. El panel
v2 no lo detectó porque inicialmente examinaba Common/Command, no Custom.

Las capturas del juego confirman el menú MENU → corona → Comprar VIP y la UI
muestra extras como porcentajes. Fin abre Hunting Log en este cliente: el comentario
End de la tabla no coincide con el atajo real. La UI advierte que el extra no
funciona con Master EXP ni party; falta validar el comportamiento real. La cuenta
del personaje Juanita se muestra Bronze con vencimiento 20/11/2026 19:48; no inferir
que sea panic, ni asumir que índice 0 corresponde a nivel SQL 0.

El usuario solicita dejar un único VIP con beneficios moderados. Propuesta pendiente
de comprobación: VIP, 30 días, +5% EXP normal y +5% drop, sin bonus de combate.
Precio definido: 25.000 WCoin C. Incluye 5.000 WCoin C por compra o renovación.
La entrega de esas monedas aún no está implementada ni soportada por la tabla nativa. No se cambió configuración activa ni membresías.

`tools/audit_vip.ps1` lee solo settings VIP/AL0–AL3, tablas CustomBuyVip/CustomItemVip
/ExperienceTable/MasterExperienceTable, tipos de las columnas de membresía, reloj
SQL y definiciones de procedimientos/funciones relacionados. Exporta cantidades
agregadas por nivel, nunca cuentas ni credenciales. Usa exclusivamente MuOnline43.
Guarda un ZIP local con el diagnóstico y un JSON de propuesta deshabilitada.
No ejecuta procedimientos, no hace DDL/DML, no instala tareas ni reinicia servicios.

Para completar la integración hay que verificar:

- Que el extra de CustomBuyVip se aplica como porcentaje y cómo se combina con
  ExperienceTable, EXP por resets y valores AL0–AL3, para evitar acumular ventajas.
- El nivel real que escribe cada compra: comparar antes y después en una cuenta
  de prueba; nunca deducir AccountLevel a partir de índices sin evidencia.
- Si renueva desde ahora o desde el vencimiento y qué hace al cambiar de nivel.
- En qué moneda debita Coin1 y si preserva WCoinP/GoblinPoint.
- Que la web use una sola operación SQL atómica/idempotente para descontar saldo
  y extender VIP, espere desconexión y registre antes/después, sin saldo negativo.
- Precio definido por el usuario, coherente entre web y menú del juego.

Hasta resolver esas condiciones, no publicar una compra VIP funcional ni alterar
los VIP ya otorgados. El reporte estático no prueba la fórmula del ejecutable.

## Hallazgos del diagnóstico recibido (1/10/2026 21:34 ARG)

SQL se leyó correctamente y su reloj local es UTC-3. El mismo nivel se renueva
sumando segundos a AccountExpireDate incluso si está vencido, según
WZ_SetAccountLevel. Un cambio de nivel usa GETDATE(). WZ_GetAccountLevel vuelve
al nivel 0 cuando está vencido; no invocarlo para un diagnóstico de solo lectura,
porque ese procedimiento hace UPDATE. El borrador de compra web deberá evitar
renovar sobre una fecha pasada y bloquear cambios que hagan perder tiempo vigente.

Diferencias reales: EXP GS AL0=15 y AL1–AL3=18; drop GS AL0=50 y AL1/2/3=60/70/75;
reset cuesta 20M de zen normal y 15M/10M/10M VIP. También difieren HelperStartCoin1,
WarehouseFeeValue, CustomPickRequireMoney, CustomDailyRewardEnable,
CustomExclusiveGlowCoin1 y CustomSmithItemDiscount en GS/CS. Master EXP y las
probabilidades de ChaosMix exportadas no difieren. No deducir la EXP final solo
con estas tasas: el ejecutable puede combinar la tabla y parámetros de otra forma.

prepare_single_vip.ps1 genera únicamente copias propuestas y originales en un
nuevo ZIP de Escritorio. Iguala esos nueve grupos a AL0 y ofrece solo índice 0
con EXP 5 / drop 5 / 30 días / VIP. No modifica AL0 ni ExperienceTable.
Precio predeterminado: 25.000 WCoin C; un precio cero genera PRECIO_PENDIENTE.
El borrador no es instalable hasta verificar tasas, renovación y monedas incluidas. No trae opción Apply ni cambia el SQL.

check_vip_purchase.ps1 captura antes/después, con SELECT directo y parámetro de
cuenta, en MuOnline43. Before exige cuenta sin VIP, conectada y saldo >=10. Usa
pruebacoin por defecto. After conserva nivel, vencimiento y delta de las tres
monedas y compara contra el reloj SQL. No ejecuta una compra ni revierte saldo:
la compra Bronze se realiza una sola vez desde el menú del cliente por el usuario.
La lectura Before se conserva con respaldo al repetirla y vence a los 15 minutos.

## Compra comprobada y cierre pendiente (1/10/2026 21:47 ARG)

La prueba manual de pruebacoin confirmó índice de compra 0 → AccountLevel 1.
WCoin C pasó de 931 a 921, mientras WCoin P y Goblin Point quedaron en cero.
AccountExpireDate pasó a 31/10/2026 21:47, 30 días desde el reloj local SQL.
Esta evidencia confirma la primera activación, no las tasas efectivas ni la renovación.
El mensaje del juego dijo Oro aunque la oferta decía Bronze: corregir el nombre
en su fuente real, sin deducir por ese texto un cambio a nivel 3.

Retirar ofertas Bronze/Prata/Ouro de CustomBuyVip y dejar solo VIP; conservar
las claves AL0–AL3 que necesita el servidor. No borrar niveles SQL, cuentas ni
vencimientos existentes. La normalización de los nueve grupos evita ventajas
actuales de EXP, drop, zen/reset y otras que exceden el nuevo paquete.

El 5 de Drop+ requiere verificar si es relativo o puntos de tasa y a qué drops
aplica. No anunciar +5% de jewels, bolsas o eventos sin evidencia. Master EXP
y party siguen pendientes de validación; no prometer esos extras.

La tabla nativa permite cobrar Coin1, pero no tiene una columna de devolución
de monedas. Las 5.000 incluidas deben vincularse a un identificador único de
compra y entregarse exactamente una vez, también al renovar. No inferir compras
a partir de cambios de AccountLevel o fecha; podrían ser cambios administrativos.
En compra con saldo exigir 25.000 disponibles, descontar 25.000 y entregar 5.000
en una operación registrada: neto -20.000. En venta directa por ARS, cobrar
25.000 ARS y entregar VIP +5.000, sin convertir además el precio en 25.000 monedas.
No habilitar devolución web si la compra nativa no tiene integración equivalente.

Insignia web posible; insignia en juego pendiente de soporte del cliente concreto.
No modificar ejecutables ni prometer una corona compatible sin comprobarlo.

## Enrutamiento confirmado (1/10/2026 22:07 ARG)

MapServerInfo.dat deriva mapas 30,31,34,41,42,79 desde códigos 0 y 1 al 19.
El Common.dat original adjunto confirma ServerCode 19 en GameServerCS y 0
en GameServer. Por tanto la diferencia de tasas afecta mapas realmente asignados
a GameServerCS, no un archivo aislado. GameServerCS usa EXP 1000/drop 100;
GameServer AL0 usa EXP 15/drop 50. El usuario confirmó EXP 15 como base correcta
y señaló que aún va a reducir drop 50. No elegir otra tasa de drop por su cuenta.

prepare_single_vip.ps1 -AlignServerRates genera un borrador que copia las dos
tasas AL0 ACTUALES de GameServer a AL0-AL3 de GameServerCS. No fija drop 50
en el código, para respetar futuros ajustes. En el diagnóstico presente agrega
ocho cambios de GameServerCS a los 44 anteriores: 52 en total. Sigue sin aplicar
configuraciones, reiniciar procesos ni implementar regalos/renovaciones.
El ZIP v2 recibido y verificado conserva tasas GameServerCS 1000/100: no instalarlo
como configuración final. Se verificaron los 12 hashes del manifiesto y que las
únicas diferencias de sus seis archivos son los 44 cambios declarados.

## Tablas de cliente recibidas (1/10/2026 22:20 ARG)

El ZIP contiene Common/CustomBuyVip idénticos en ambos generadores: índices
0/1/2, EXP/drop 10/20/30, precios Coin1 10/15/25 y nombres Bronze/Prata/Ouro.
El precio de la tercera oferta difiere del servidor auditado (20).
Solo Generador_0 (MAIN_INFO v43 - Season 6) aportó CustomMessage.txt, UTF-8
sin BOM. La sección 2 mantiene mensajes VIP portugueses y nombres ingleses.
Generador_1 (NUEVO MAIN BETA) solo aportó CustomBuyVip y CustomMessageGremory.
No sustituir su configuración de mensajes por la del otro generador sin conocer
su formato y archivos reales; no asumir qué generador produjo el cliente activo.

prepare_vip_client.ps1 prepara copias originales y propuestas del generador
seleccionado; no modifica ni ejecuta el generador y no reemplaza main.emu.
Una oferta VIP; cambios limitados a etiquetas VIP por idioma (5–14,26,81–84,
202–204), preservando IDs, placeholders, bytes y demás textos. No incluye nuevas
coronas ni modifica RankUser, ya que el paquete no aportó una tabla compatible.
La copia de mensajes es únicamente un borrador del generador que tenga ambos
archivos. No habilita las monedas incluidas ni demuestra tasas efectivas.
