param([ValidateSet('Ensayar','Aplicar','Revertir')][string]$Modo='Ensayar')
$ErrorActionPreference='Stop'
Get-Command sqlcmd -ErrorAction Stop | Out-Null

# Fixed destinations and login. The default only rehearses in the audit copy.
$base = if ($Modo -eq 'Ensayar') { 'MuOnline43_Auditoria' } else { 'MuOnline43' }
$rol = 'MUPanicWebLimited20261007'
$lecturas = @('MEMB_INFO','MEMB_STAT','AccountCharacter','Character','MasterSkillTree','Guild','GuildMember','Gens_Rank','CashShopData','MuCastle_DATA','MuCastle_REG_SIEGE','MuCastle_SIEGE_GUILDLIST')
$escrituras = [ordered]@{
    MEMB_INFO=@('INSERT')
    WEBENGINE_FLA=@('INSERT','UPDATE','DELETE')
    WEBENGINE_PASSCHANGE_REQUEST=@('INSERT','DELETE')
    WEBENGINE_REGISTER_ACCOUNT=@('INSERT','DELETE')
    WEBENGINE_ACCOUNT_COUNTRY=@('INSERT','UPDATE')
    WEBENGINE_VOTES=@('INSERT','DELETE')
    WEBENGINE_VOTE_LOGS=@('INSERT')
    WEBENGINE_NEWS=@('INSERT','UPDATE','DELETE')
    WEBENGINE_NEWS_TRANSLATIONS=@('INSERT','UPDATE','DELETE')
    WEBENGINE_BAN_LOG=@('INSERT','DELETE')
    WEBENGINE_BANS=@('INSERT','DELETE')
    WEBENGINE_BLOCKED_IP=@('INSERT','DELETE')
    WEBENGINE_CRON=@('INSERT','UPDATE','DELETE')
    WEBENGINE_CREDITS_CONFIG=@('INSERT','UPDATE','DELETE')
    WEBENGINE_PLUGINS=@('INSERT','UPDATE','DELETE')
}
$columnas = [ordered]@{
    MEMB_INFO=@('memb__pwd','mail_addr','bloc_code')
    Character=@('cLevel','Class','Quest','Inventory','Money','LevelUpPoint','ResetCount','MasterResetCount','Strength','Dexterity','Vitality','Energy','Leadership','PkLevel','PkTime','MapNumber','MapPosX','MapPosY','MagicList')
    MasterSkillTree=@('MasterPoint','MasterExperience','MasterLevel')
}

# Read-only verification is generated from the SAME policy as the grants.
$otorgar = New-Object System.Text.StringBuilder
$verificar = New-Object System.Text.StringBuilder
[void]$verificar.AppendLine('DECLARE @Sonda int;')
foreach ($tabla in $lecturas) {
    [void]$otorgar.AppendLine("IF OBJECT_ID(N'dbo.$tabla',N'U') IS NULL THROW 50010,'Falta una tabla requerida: $tabla.',1;")
    [void]$otorgar.AppendLine("GRANT SELECT ON dbo.[$tabla] TO [$rol];")
    [void]$verificar.AppendLine("IF COALESCE(HAS_PERMS_BY_NAME('dbo.$tabla','OBJECT','SELECT'),0)<>1 THROW 50011,'Falta lectura: $tabla.',1;")
    [void]$verificar.AppendLine("SELECT TOP (0) @Sonda=1 FROM dbo.[$tabla];")
}
foreach ($tabla in $escrituras.Keys) {
    foreach ($permiso in $escrituras[$tabla]) {
        [void]$otorgar.AppendLine("GRANT $permiso ON dbo.[$tabla] TO [$rol];")
        [void]$verificar.AppendLine("IF COALESCE(HAS_PERMS_BY_NAME('dbo.$tabla','OBJECT','$permiso'),0)<>1 THROW 50012,'Falta $permiso en $tabla.',1;")
    }
}
foreach ($tabla in $columnas.Keys) {
    $lista = ($columnas[$tabla] | ForEach-Object { '['+$_+']' }) -join ','
    [void]$otorgar.AppendLine("GRANT UPDATE ($lista) ON dbo.[$tabla] TO [$rol];")
    foreach ($columna in $columnas[$tabla]) {
        [void]$verificar.AppendLine("IF COALESCE(HAS_PERMS_BY_NAME('dbo.$tabla','OBJECT','UPDATE','$columna','COLUMN'),0)<>1 THROW 50013,'Falta UPDATE en $tabla.$columna.',1;")
    }
}
# Existing WebEngine and ranking tables are read-only unless explicitly listed above.
[void]$otorgar.AppendLine(@'
DECLARE @Objeto sysname,@Grant nvarchar(max);
DECLARE lecturas CURSOR LOCAL FAST_FORWARD FOR
SELECT name FROM sys.tables WHERE schema_id=SCHEMA_ID(N'dbo') AND (name LIKE N'WEBENGINE[_]%' OR name LIKE N'Ranking%');
OPEN lecturas;
FETCH NEXT FROM lecturas INTO @Objeto;
WHILE @@FETCH_STATUS=0
BEGIN
    SET @Grant=N'GRANT SELECT ON dbo.'+QUOTENAME(@Objeto)+N' TO [MUPanicWebLimited20261007];';
    EXEC sys.sp_executesql @Grant;
    FETCH NEXT FROM lecturas INTO @Objeto;
END;
CLOSE lecturas;
DEALLOCATE lecturas;
'@)
# No rows returned: these reproduce the joins used by the installed country/online crons.
[void]$verificar.AppendLine(@'
SELECT TOP(0) @Sonda=LEN(c.Name)+LEN(p.country) FROM dbo.WEBENGINE_ACCOUNT_COUNTRY p JOIN dbo.Character c ON p.account=c.AccountID;
SELECT TOP(0) @Sonda=LEN(c.GameIDC) FROM dbo.MEMB_STAT s JOIN dbo.AccountCharacter c ON s.memb___id=c.Id WHERE s.ConnectStat=1;
SELECT TOP(0) @Sonda=1 FROM dbo.WEBENGINE_CRON;
SELECT TOP(0) @Sonda=1 FROM dbo.WEBENGINE_BANS;
IF COALESCE(IS_MEMBER('db_owner'),1)<>0
 OR COALESCE(HAS_PERMS_BY_NAME(DB_NAME(),'DATABASE','CONTROL'),1)<>0
 OR COALESCE(HAS_PERMS_BY_NAME(DB_NAME(),'DATABASE','ALTER'),1)<>0
 OR COALESCE(HAS_PERMS_BY_NAME(DB_NAME(),'DATABASE','CREATE TABLE'),1)<>0
 OR COALESCE(HAS_PERMS_BY_NAME('dbo.CashShopData','OBJECT','UPDATE'),1)<>0
 OR COALESCE(HAS_PERMS_BY_NAME('dbo.CashShopData','OBJECT','UPDATE','WCoinC','COLUMN'),1)<>0
 OR COALESCE(HAS_PERMS_BY_NAME('dbo.WZ_SetCoin','OBJECT','EXECUTE'),1)<>0
 OR COALESCE(HAS_PERMS_BY_NAME('dbo.MUPanicApplyRecharge','OBJECT','EXECUTE'),1)<>0
 OR COALESCE(HAS_PERMS_BY_NAME('dbo.MUPanicAckRecharge','OBJECT','EXECUTE'),1)<>0
    THROW 50014,'Permisos generales o de recargas no permitidos.',1;
SELECT DB_NAME() AS Base,IS_MEMBER('db_owner') AS EsDbOwner,
 HAS_PERMS_BY_NAME(DB_NAME(),'DATABASE','CONTROL') AS ControlBase,
 HAS_PERMS_BY_NAME('dbo.CashShopData','OBJECT','UPDATE') AS ModificarWCoin;
PRINT 'OK: lecturas, permisos de funciones web, panel y crons verificados sin cambiar filas.';
'@)

$cabecera = @'
USE master;
SET NOCOUNT ON;
SET XACT_ABORT ON;
SET LOCK_TIMEOUT 5000;
SET QUOTED_IDENTIFIER ON;
SET ANSI_NULLS ON;
SET ANSI_PADDING ON;
SET ANSI_WARNINGS ON;
SET ARITHABORT ON;
SET CONCAT_NULL_YIELDS_NULL ON;
SET NUMERIC_ROUNDABORT OFF;
IF SUSER_ID(N'mupanic_web') IS NULL OR COALESCE(IS_SRVROLEMEMBER(N'sysadmin',N'mupanic_web'),1)<>0
    THROW 50001,'Login web ausente o con permisos generales de servidor.',1;
IF EXISTS (SELECT 1 FROM sys.server_role_members WHERE member_principal_id=SUSER_ID(N'mupanic_web'))
    THROW 50002,'El login web tiene roles de servidor no esperados.',1;
IF EXISTS (SELECT 1 FROM sys.server_permissions WHERE grantee_principal_id=SUSER_ID(N'mupanic_web') AND NOT (state='G' AND permission_name IN ('CONNECT SQL','VIEW ANY DATABASE')))
    THROW 50003,'El login web tiene permisos de servidor no esperados.',1;
'@

# This reversal restores the reviewed baseline: db_owner + CONNECT, removing our role.
# It does not restore a database backup or alter accounts, balances, game or worker.
$revertir = @'
USE [MuOnline43];
SET NOCOUNT ON;
SET XACT_ABORT ON;
SET LOCK_TIMEOUT 5000;
BEGIN TRY
 BEGIN TRANSACTION;
 IF DATABASE_PRINCIPAL_ID(N'MUPanicWebLimited20261007') IS NULL
    THROW 50030,'No se encontro el rol de este cambio. No se revirtio nada.',1;
 IF NOT EXISTS (SELECT 1 FROM sys.database_role_members WHERE role_principal_id=DATABASE_PRINCIPAL_ID(N'MUPanicWebLimited20261007') AND member_principal_id=DATABASE_PRINCIPAL_ID(N'mupanic_web'))
    THROW 50031,'El usuario no tiene el rol de este cambio.',1;
 ALTER ROLE [db_owner] ADD MEMBER [mupanic_web];
 ALTER ROLE [MUPanicWebLimited20261007] DROP MEMBER [mupanic_web];
 DROP ROLE [MUPanicWebLimited20261007];
 USE [MuOnline43_Auditoria];
 REVOKE CONNECT FROM [mupanic_web];
 GRANT CONNECT TO [mupanic_web];
 USE [MuOnline43];
 COMMIT;
 PRINT 'REVERTIDO: restaurado db_owner del usuario web y su acceso anterior a la copia. Ninguna fila modificada.';
END TRY
BEGIN CATCH
 IF @@TRANCOUNT>0 ROLLBACK;
 THROW;
END CATCH;
'@

if ($Modo -eq 'Revertir') {
    $revertir | sqlcmd -S localhost -E -I -b -x
    if ($LASTEXITCODE -ne 0) { throw 'SQL informo un error. La reversion no se confirmo.' }
    exit
}

$plantilla = @'
USE [__BASE__];
IF COALESCE(CONVERT(nvarchar(60),DATABASEPROPERTYEX(DB_NAME(),'Status')),'')<>'ONLINE'
    THROW 50004,'La base esperada no esta ONLINE.',1;
IF NOT EXISTS (SELECT 1 FROM sys.database_principals WHERE name=N'mupanic_web' AND sid=SUSER_SID(N'mupanic_web'))
    THROW 50005,'El usuario no corresponde al login esperado.',1;
IF EXISTS (SELECT 1 FROM sys.databases WHERE name=DB_NAME() AND owner_sid=SUSER_SID(N'mupanic_web'))
    THROW 50018,'El login web es propietario de la base.',1;
IF DATABASE_PRINCIPAL_ID(N'MUPanicWebLimited20261007') IS NOT NULL
    THROW 50006,'El rol del cambio ya existe. No se modifica nada.',1;
IF (SELECT COUNT(*) FROM sys.database_role_members WHERE member_principal_id=DATABASE_PRINCIPAL_ID(N'mupanic_web'))<>1
 OR NOT EXISTS (SELECT 1 FROM sys.database_role_members WHERE member_principal_id=DATABASE_PRINCIPAL_ID(N'mupanic_web') AND role_principal_id=DATABASE_PRINCIPAL_ID(N'db_owner'))
    THROW 50007,'Los roles actuales no coinciden con la revision.',1;
IF EXISTS (SELECT 1 FROM sys.database_permissions WHERE grantee_principal_id=DATABASE_PRINCIPAL_ID(N'mupanic_web') AND NOT (class=0 AND permission_name='CONNECT' AND state='G'))
    THROW 50008,'Los permisos explicitos actuales no coinciden con la revision.',1;
IF EXISTS (SELECT 1 FROM sys.schemas WHERE principal_id=DATABASE_PRINCIPAL_ID(N'mupanic_web'))
 OR EXISTS (SELECT 1 FROM sys.objects WHERE principal_id=DATABASE_PRINCIPAL_ID(N'mupanic_web'))
 OR EXISTS (SELECT 1 FROM sys.database_principals WHERE owning_principal_id=DATABASE_PRINCIPAL_ID(N'mupanic_web'))
    THROW 50009,'El usuario posee objetos no previstos.',1;
DECLARE @Impersonando bit=0;
BEGIN TRY
 BEGIN TRANSACTION;
 CREATE ROLE [MUPanicWebLimited20261007] AUTHORIZATION [dbo];
 __GRANTS__
 ALTER ROLE [MUPanicWebLimited20261007] ADD MEMBER [mupanic_web];
 ALTER ROLE [db_owner] DROP MEMBER [mupanic_web];
 __CHECK__
 __FINISH__
END TRY
BEGIN CATCH
 IF @Impersonando=1 REVERT;
 IF @@TRANCOUNT>0 ROLLBACK;
 THROW;
END CATCH;
'@

if ($Modo -eq 'Ensayar') {
    $comprobar = "EXECUTE AS USER='mupanic_web'; SET @Impersonando=1;`n" + $verificar.ToString() + "`nREVERT; SET @Impersonando=0;"
    $finalizar = "ROLLBACK; PRINT 'ENSAYO TERMINADO: solo copia de auditoria. Roles y permisos restaurados. Produccion sin cambios.';"
} else {
    # Save the exact reversal BEFORE submitting the production permission change.
    $carpeta = Join-Path 'C:\MuServer43\MU_PANIC_BACKUPS' ('PERMISOS_WEB_'+(Get-Date -Format 'yyyyMMdd_HHmmss')+'_'+[guid]::NewGuid().ToString('N'))
    New-Item -ItemType Directory -Path $carpeta -ErrorAction Stop | Out-Null
    $ruta = Join-Path $carpeta 'REVERTIR_PERMISOS_WEB.sql'
    [IO.File]::WriteAllText($ruta,$revertir,[Text.Encoding]::UTF8)
    Write-Host ('Reversion guardada: '+$ruta)
    $seguro = $verificar.ToString().Replace("'","''")
    $comprobar = @"
 USE [MuOnline43_Auditoria];
 IF NOT EXISTS (SELECT 1 FROM sys.database_principals WHERE name=N'mupanic_web' AND sid=SUSER_SID(N'mupanic_web'))
    THROW 50015,'No se encontro el usuario original en la copia.',1;
 IF NOT EXISTS (SELECT 1 FROM sys.database_permissions WHERE grantee_principal_id=DATABASE_PRINCIPAL_ID(N'mupanic_web') AND class=0 AND permission_name='CONNECT' AND state='G')
 OR EXISTS (SELECT 1 FROM sys.database_permissions WHERE grantee_principal_id=DATABASE_PRINCIPAL_ID(N'mupanic_web') AND class=0 AND permission_name='CONNECT' AND state<>'G')
    THROW 50016,'El acceso a la copia tiene un estado no previsto.',1;
 DENY CONNECT TO [mupanic_web];
 USE master;
 EXECUTE AS LOGIN='mupanic_web';
 SET @Impersonando=1;
 EXEC(N'USE [MuOnline43]; $seguro');
 IF COALESCE(HAS_DBACCESS(N'MuOnline43_Auditoria'),-1)<>0
    THROW 50017,'El login web conserva acceso a la copia.',1;
 SELECT HAS_DBACCESS(N'MuOnline43_Auditoria') AS AccesoCopia;
 REVERT;
 SET @Impersonando=0;
 USE [MuOnline43];
"@
    $finalizar = "COMMIT; PRINT 'APLICADO: usuario web con permisos especificos, sin db_owner y sin acceso a la copia. Ninguna fila modificada. Worker y servidor sin cambios.';"
}
$sql = $cabecera + $plantilla.Replace('__BASE__',$base).Replace('__GRANTS__',$otorgar.ToString()).Replace('__CHECK__',$comprobar).Replace('__FINISH__',$finalizar)
$sql | sqlcmd -S localhost -E -I -b -x
if ($LASTEXITCODE -ne 0) { throw 'SQL informo un error. El cambio de permisos no se confirmo.' }
