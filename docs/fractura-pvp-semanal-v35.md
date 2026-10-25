# PvP válido en la competencia semanal V35

El servidor suma al bando la contribución nativa positiva de cada baja válida contra la facción contraria en Vulcanus 63. No duplica el premio personal ni descuenta puntos semanales a la víctima. Boss oficial conserva 1000 puntos. Las misiones personales no cuentan.

La cuota existente de misma IP conserva sus dos bajas premiadas por día Argentina, compartidas entre cuentas, personajes y ambos sentidos. Las IP diferentes no consumen esa cuota. El filtro por IP no detecta a una misma persona usando conexiones distintas.

El registro de todas las IP empieza al instalar V35. Las filas anteriores permanecen fuera del nuevo cómputo y no se reconstruyen bajas no registradas. Las semanas cerradas y sus recompensas congeladas se conservan.

El servidor registra OnUserDie mediante el callback ya existente, y confirma el pago nativo mediante el trigger correlacionado de Gens ya verificado en V27B. Solo las filas WeeklyEligible=1, KillerHandled=1, Allowed=1 y NativeGain de 1 a 15 suman. El mismo evento persistente no se registra dos veces. Una función SQL común calcula tanto la vista del marcador como el cierre oficial. El cierre espera hasta 120 segundos a pagos recientes pendientes. Los NPC de ambos bandos muestran los totales semanales y los avisos explican boss + PvP.

## Despliegue

1. SOLO VPS: detener ambos GameServer e instalar `MU_PANIC_PVP_SEMANA_VPS_V35.zip`. Incluye las cuatro vistas públicas V34; no instalar V34 aparte después. Acepta Weekly.lua V32 o V33, conservando la variante de distinciones instalada. Sin parche PC.
2. Reiniciar ambos servidores y realizar bajas en Vulcanus; comprobar marcador en NPC y recolectar V35.
3. Publicar la rama beta desde cPanel. La web consulta el resultado del servidor; no acredita ni cierra semanas.

La instalación crea respaldos en Descargas y ejecuta la autoprueba SQL bajo savepoint, revertida sin premios ni cierre real. Si falla, revierte SQL y archivos. El restaurador verifica hashes y conserva historial, cuotas, contribución y semanas ya cerradas. Restaura el criterio anterior para el futuro; no borra resultados. Las columnas de auditoría quedan conservadas.

Validación local: Lua con dispatcher real y APIs nativas simuladas; rutas de misma/distinta IP, mapa, familia y fallo SQL, NPC de las variantes V32/V33; análisis PowerShell/T-SQL y hashes. Las pruebas SQL reales y la validación en juego quedan pendientes de la instalación en el VPS.
