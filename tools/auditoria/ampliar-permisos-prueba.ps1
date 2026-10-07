$ErrorActionPreference = 'Stop'
Get-Command sqlcmd -ErrorAction Stop | Out-Null

# Fixed audit database and principal. No production grants or data changes.
$consulta = @'
USE master;
SET NOCOUNT ON;
SET XACT_ABORT ON;
IF SUSER_ID(N'mupanic_auditoria') IS NULL
    THROW 50001, 'No existe el login de auditoria.', 1;
IF COALESCE(CONVERT(nvarchar(60), DATABASEPROPERTYEX(N'MuOnline43_Auditoria', 'Status')), '') <> 'ONLINE'
    THROW 50002, 'La copia de auditoria no esta ONLINE.', 1;

DECLARE @Impersonando bit = 0;
BEGIN TRY
    BEGIN TRANSACTION;
    USE [MuOnline43_Auditoria];
    IF NOT EXISTS (SELECT 1 FROM sys.database_principals WHERE name=N'mupanic_auditoria' AND sid=SUSER_SID(N'mupanic_auditoria'))
        THROW 50003, 'El usuario de la copia no corresponde al login de auditoria.', 1;

    GRANT UPDATE (
        cLevel, Class, Quest, Inventory, Money, LevelUpPoint, ResetCount,
        Strength, Dexterity, Vitality, Energy, Leadership,
        PkLevel, PkTime, MapNumber, MapPosX, MapPosY, MagicList
    ) ON dbo.Character TO [mupanic_auditoria];

    GRANT UPDATE (MasterPoint, MasterExperience)
        ON dbo.MasterSkillTree TO [mupanic_auditoria];
    GRANT INSERT, DELETE ON dbo.WEBENGINE_VOTES TO [mupanic_auditoria];
    GRANT INSERT ON dbo.WEBENGINE_VOTE_LOGS TO [mupanic_auditoria];

    USE master;
    EXECUTE AS LOGIN = 'mupanic_auditoria';
    SET @Impersonando = 1;

    IF COALESCE(HAS_DBACCESS(N'MuOnline43'), -1) <> 0
        THROW 50004, 'El aislamiento de produccion fallo.', 1;

    EXEC(N'
        USE [MuOnline43_Auditoria];
        IF COALESCE(IS_MEMBER(''db_owner''), 1) <> 0
           OR COALESCE(HAS_PERMS_BY_NAME(DB_NAME(), ''DATABASE'', ''CONTROL''), 1) <> 0
           OR COALESCE(HAS_PERMS_BY_NAME(DB_NAME(), ''DATABASE'', ''ALTER''), 1) <> 0
           OR COALESCE(HAS_PERMS_BY_NAME(DB_NAME(), ''DATABASE'', ''CREATE TABLE''), 1) <> 0
           OR COALESCE(HAS_PERMS_BY_NAME(''dbo.WZ_SetCoin'', ''OBJECT'', ''EXECUTE''), 1) <> 0
           OR COALESCE(HAS_PERMS_BY_NAME(''dbo.CashShopData'', ''OBJECT'', ''UPDATE''), 1) <> 0
            THROW 50005, ''Permisos administrativos o WCoin no permitidos.'', 1;

        UPDATE dbo.Character SET cLevel=cLevel, Class=Class, Quest=NULL,
            Inventory=NULL, Money=Money, LevelUpPoint=LevelUpPoint,
            ResetCount=ResetCount, Strength=Strength, Dexterity=Dexterity,
            Vitality=Vitality, Energy=Energy, Leadership=Leadership,
            PkLevel=PkLevel, PkTime=PkTime, MapNumber=MapNumber,
            MapPosX=MapPosX, MapPosY=MapPosY, MagicList=NULL
        WHERE 1=0;
        UPDATE dbo.MasterSkillTree SET MasterPoint=MasterPoint,
            MasterExperience=MasterExperience WHERE 1=0;

        PRINT ''OK: consultas de personajes y Master verificadas con cero filas.'';
        SELECT
            IS_MEMBER(''db_owner'') AS EsDbOwner,
            HAS_PERMS_BY_NAME(DB_NAME(), ''DATABASE'', ''CONTROL'') AS ControlBase,
            HAS_PERMS_BY_NAME(''dbo.CashShopData'', ''OBJECT'', ''UPDATE'') AS ModificarWCoin;
    ');

    SELECT HAS_DBACCESS(N'MuOnline43') AS AccesoProduccion;
    REVERT;
    SET @Impersonando = 0;
    COMMIT;
    PRINT 'OK: permisos ampliados SOLO en MuOnline43_Auditoria. Ninguna fila actualizada.';
END TRY
BEGIN CATCH
    IF @Impersonando=1 REVERT;
    IF @@TRANCOUNT>0 ROLLBACK;
    THROW;
END CATCH;
'@

$consulta | sqlcmd -S localhost -E -b -x
if ($LASTEXITCODE -ne 0) {
    throw 'La comprobacion SQL fallo. Los nuevos permisos no se confirmaron.'
}

# Read task metadata only: never print action arguments, tokens or settings.
$tarea = Get-ScheduledTask -TaskName 'MU PANIC Recharge Worker' -ErrorAction SilentlyContinue
if ($null -ne $tarea) {
    [pscustomobject]@{
        Tarea = $tarea.TaskName
        UsuarioWindows = $tarea.Principal.UserId
        PasaCredencialSQL = [bool](($tarea.Actions | Where-Object { $_.Arguments -match '(?i)-SqlCredential\b' }).Count)
    } | Format-List
} else {
    Write-Host 'No se encontro la tarea MU PANIC Recharge Worker.'
}
