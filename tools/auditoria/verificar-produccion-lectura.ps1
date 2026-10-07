$ErrorActionPreference = 'Stop'
Get-Command sqlcmd -ErrorAction Stop | Out-Null

# Read-only catalog/session queries. No grants, role changes, DML or worker execution.
$consulta = @'
USE master;
SET NOCOUNT ON;
IF SUSER_ID(N'mupanic_web') IS NULL
    THROW 50001, 'No existe el login mupanic_web.', 1;
IF COALESCE(CONVERT(nvarchar(60),DATABASEPROPERTYEX(N'MuOnline43','Status')),'')<>'ONLINE'
    THROW 50002, 'Produccion no esta ONLINE.', 1;
PRINT '--- LOGIN WEB Y ROLES DE SERVIDOR ---';
SELECT name AS LoginSQL,default_database_name AS BasePredeterminada,is_disabled AS Deshabilitado
FROM sys.server_principals WHERE name=N'mupanic_web';
SELECT r.name AS RolServidor FROM sys.server_role_members m
JOIN sys.server_principals r ON r.principal_id=m.role_principal_id
JOIN sys.server_principals u ON u.principal_id=m.member_principal_id WHERE u.name=N'mupanic_web';
PRINT '--- CONEXIONES ACTUALES DEL LOGIN WEB ---';
SELECT host_name AS Equipo,program_name AS Programa,DB_NAME(database_id) AS Base
FROM sys.dm_exec_sessions WHERE login_name=N'mupanic_web' AND is_user_process=1;

USE [MuOnline43];
PRINT '--- ROLES Y PERMISOS EXPLICITOS EN PRODUCCION ---';
SELECT r.name AS RolBase FROM sys.database_role_members m
JOIN sys.database_principals r ON r.principal_id=m.role_principal_id
JOIN sys.database_principals u ON u.principal_id=m.member_principal_id WHERE u.name=N'mupanic_web';
SELECT p.state_desc AS Estado,p.permission_name AS Permiso,p.class_desc AS Clase,
    CASE WHEN p.class=1 THEN OBJECT_SCHEMA_NAME(p.major_id)+N'.'+OBJECT_NAME(p.major_id)
         WHEN p.class=3 THEN SCHEMA_NAME(p.major_id) ELSE NULL END AS Objeto,
    CASE WHEN p.class=1 AND p.minor_id>0 THEN COL_NAME(p.major_id,p.minor_id) ELSE NULL END AS Columna
FROM sys.database_permissions p
WHERE p.grantee_principal_id=DATABASE_PRINCIPAL_ID(N'mupanic_web');
PRINT '--- PROPIEDADES DEL USUARIO WEB ---';
SELECT N'Esquema' AS Tipo,name AS Nombre FROM sys.schemas WHERE principal_id=DATABASE_PRINCIPAL_ID(N'mupanic_web')
UNION ALL
SELECT N'Objeto',SCHEMA_NAME(schema_id)+N'.'+name FROM sys.objects WHERE principal_id=DATABASE_PRINCIPAL_ID(N'mupanic_web')
UNION ALL
SELECT N'Rol',name FROM sys.database_principals WHERE type='R' AND owning_principal_id=DATABASE_PRINCIPAL_ID(N'mupanic_web');

PRINT '--- TAREAS WEB ACTIVAS (SOLO NOMBRES) ---';
SELECT cron_name AS Tarea,cron_file_run AS Archivo,cron_run_time AS Intervalo
FROM dbo.WEBENGINE_CRON WHERE cron_status=1 ORDER BY cron_id;
PRINT '--- CONFIGURACIONES DE CREDITOS ACTUALES (SIN SALDOS) ---';
SELECT config_title AS Configuracion,config_database AS Base,config_table AS Tabla,
    config_credits_col AS Columna,config_user_col AS Identificador
FROM dbo.WEBENGINE_CREDITS_CONFIG;
PRINT '--- PLUGINS ACTIVOS ---';
SELECT name AS Plugin,folder AS Carpeta FROM dbo.WEBENGINE_PLUGINS WHERE status=1;

USE master;
DECLARE @Impersonando bit=0;
BEGIN TRY
    EXECUTE AS LOGIN='mupanic_web';
    SET @Impersonando=1;
    EXEC(N'USE [MuOnline43];
        SELECT IS_MEMBER(''db_owner'') AS EsDbOwner,
            HAS_PERMS_BY_NAME(DB_NAME(),''DATABASE'',''CONTROL'') AS ControlBase,
            HAS_PERMS_BY_NAME(''dbo.CashShopData'',''OBJECT'',''UPDATE'') AS ModificarWCoin;');
    SELECT HAS_DBACCESS(N'MuOnline43_Auditoria') AS AccesoCopiaDesdeLoginProduccion;
    REVERT;
    SET @Impersonando=0;
END TRY
BEGIN CATCH
    IF @Impersonando=1 REVERT;
    THROW;
END CATCH;
PRINT 'REVISION TERMINADA: solo lectura. Ningun permiso ni dato modificado.';
'@

$consulta | sqlcmd -S localhost -E -I -b -x
if ($LASTEXITCODE -ne 0) { throw 'SQL informo un error durante la revision de solo lectura.' }

# Inspect the installed worker text without executing it or printing its contents.
$tarea = Get-ScheduledTask -TaskName 'MU PANIC Recharge Worker' -ErrorAction SilentlyContinue
if ($null -eq $tarea) {
    Write-Host 'No se encontro la tarea MU PANIC Recharge Worker.'
} else {
    $info = Get-ScheduledTaskInfo -TaskName $tarea.TaskName
    $acciones = @($tarea.Actions)
    $pasaCredencial = [bool](@($acciones | Where-Object { $_.Arguments -match '(?i)-SqlCredential\b' }).Count)
    $resultado = [ordered]@{
        Tarea = $tarea.TaskName
        UsuarioWindows = $tarea.Principal.UserId
        UltimoResultado = $info.LastTaskResult
        PasaCredencialSQL = $pasaCredencial
        WorkerInstaladoReconocido = $false
        IntegradaSinCredencial = 'No comprobado'
        ReferenciaLoginWeb = 'No comprobado'
    }
    if ($acciones.Count -eq 1 -and $acciones[0].Arguments -match '(?i)-File\s+(?:"([^"]+)"|([^\s]+))') {
        $ruta = if ($Matches[1]) { $Matches[1] } else { $Matches[2] }
        $ruta = [IO.Path]::GetFullPath($ruta)
        $esperada = 'C:\MuServer43\RechargeSync\recharge_worker.ps1'
        if ($ruta -ieq $esperada -and (Test-Path -LiteralPath $ruta -PathType Leaf)) {
            $texto = [IO.File]::ReadAllText($ruta)
            $resultado.WorkerInstaladoReconocido = $true
            $resultado.IntegradaSinCredencial = [bool]($texto -match '(?is)if\s*\(\s*\$null\s*-eq\s*\$Credential\s*\)\s*\{\s*\$builder\s*\[\s*[''"]Integrated Security[''"]\s*\]\s*=\s*\$true')
            $resultado.ReferenciaLoginWeb = [bool]($texto -match '(?i)\bmupanic_web\b')
        }
    }
    [pscustomobject]$resultado | Format-List
}
