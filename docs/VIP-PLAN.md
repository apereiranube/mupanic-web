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
de comprobación: VIP PANIC, 30 días, +10% EXP normal, sin extra de drop ni bonus
de combate. No se definió precio. No se cambió configuración activa ni membresías.

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
