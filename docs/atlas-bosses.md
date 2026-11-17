# Atlas · Bosses ahora

Sección `#bosses` del Atlas (menú "Bosses ahora"). Muestra si cada boss está vivo y cuánto falta para que vuelva.

## Datos
- Vista `[MuOnline43].[dbo].[MUPanicBossEstadoWebV1]` (solo SELECT para el rol de la web): ServerCode, MonsterClass, AliveCount, DeathUnix, RegenSeconds, StartUnix, SeenUnix, NowUnix.
- La llena el VPS (paquete `MU_PANIC_Bosses_v1`): `Bosses.lua` en los 3 GameServer anota cada minuto en `Data\MUPanicBosses\estado_<server>.txt`; la tarea `MU PANIC Bosses` (SYSTEM, cada minuto) lo pasa a SQL. RegenSeconds sale de Monster.txt al arrancar el GameServer.
- Server: 0 = Server 1, 1 = Server 2, 19 = Server Loren (GameServerCS).
- Horarios de invasiones: `inc/public-events.json` (invasion-0/1/2/3/8/9).

## Reglas de estado
- Dato viejo (> 3 min) = "Sin datos ahora".
- Reviven solos: vivo si AliveCount > 0; si murió, "Revive en" DeathUnix + RegenSeconds; vencido = "Por aparecer".
- Selupan: depende del ciclo de LaCleon, sin cuenta regresiva.
- Por horario: fuera de la ventana = próxima aparición; dentro = vivo (con servers), "Ya lo derrotaron" (muerte dentro de la ventana) o "ya no está".

## Archivos
`inc/atlas-bosses.php`, `css/atlas-bosses.css`, `js/atlas-bosses.js` (solo cuenta regresiva con la hora del servidor), link en `inc/guide.php`.
