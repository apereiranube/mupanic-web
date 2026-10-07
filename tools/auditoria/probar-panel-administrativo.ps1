$ErrorActionPreference = 'Stop'
Get-Command sqlcmd -ErrorAction Stop | Out-Null

# These statements reproduce the installed admin modules' database operations.
# Everything runs as the restricted audit login, inside a rolled-back transaction.
$prueba = @'
USE [MuOnline43_Auditoria];
IF DB_NAME()<>N'MuOnline43_Auditoria'
    THROW 50010, 'Destino incorrecto.', 1;
IF COALESCE(IS_MEMBER(N'db_owner'),1)<>0
   OR COALESCE(HAS_PERMS_BY_NAME(DB_NAME(),'DATABASE','CONTROL'),1)<>0
   OR COALESCE(HAS_PERMS_BY_NAME(DB_NAME(),'DATABASE','ALTER'),1)<>0
   OR COALESCE(HAS_PERMS_BY_NAME(DB_NAME(),'DATABASE','CREATE TABLE'),1)<>0
   OR COALESCE(HAS_PERMS_BY_NAME('dbo.CashShopData','OBJECT','UPDATE'),1)<>0
   OR COALESCE(HAS_PERMS_BY_NAME('dbo.WZ_SetCoin','OBJECT','EXECUTE'),1)<>0
    THROW 50011, 'Permisos administrativos generales o WCoin no permitidos.', 1;

DECLARE @Marca nvarchar(36)=CONVERT(nvarchar(36),NEWID());
DECLARE @Fecha bigint=DATEDIFF_BIG(second,CONVERT(datetime2,'19700101'),SYSUTCDATETIME());
DECLARE @Id int;

PRINT '--- CUENTAS, BLOQUEO Y DESBLOQUEO ---';
UPDATE dbo.MEMB_INFO SET memb__pwd=memb__pwd, mail_addr=mail_addr
WHERE memb___id=N'audtest01';
IF @@ROWCOUNT<>1 THROW 50012, 'No se encontro exactamente una cuenta ficticia.', 1;
UPDATE dbo.MEMB_INFO SET bloc_code=1 WHERE memb___id=N'audtest01';
IF NOT EXISTS (SELECT 1 FROM dbo.MEMB_INFO WHERE memb___id=N'audtest01' AND bloc_code=1)
    THROW 50013, 'Fallo el bloqueo de la cuenta ficticia.', 1;
INSERT INTO dbo.WEBENGINE_BAN_LOG (account_id,banned_by,ban_type,ban_date,ban_days,ban_reason)
VALUES (N'audtest01',N'auditoria',N'temporal',@Fecha,1,@Marca);
SET @Id=CONVERT(int,SCOPE_IDENTITY());
IF NOT EXISTS (SELECT 1 FROM dbo.WEBENGINE_BAN_LOG WHERE id=@Id AND ban_reason=@Marca)
    THROW 50014, 'Fallo la lectura del registro de bloqueo.', 1;
INSERT INTO dbo.WEBENGINE_BANS (account_id,banned_by,ban_date,ban_days,ban_reason)
VALUES (N'audtest01',N'auditoria',@Fecha,1,@Marca);
IF NOT EXISTS (SELECT 1 FROM dbo.WEBENGINE_BANS WHERE account_id=N'audtest01' AND ban_reason=@Marca)
    THROW 50015, 'Fallo la lectura del bloqueo temporal.', 1;
UPDATE dbo.MEMB_INFO SET bloc_code=0 WHERE memb___id=N'audtest01';
IF NOT EXISTS (SELECT 1 FROM dbo.MEMB_INFO WHERE memb___id=N'audtest01' AND bloc_code=0)
    THROW 50016, 'Fallo el desbloqueo de la cuenta ficticia.', 1;
DELETE dbo.WEBENGINE_BANS WHERE account_id=N'audtest01' AND ban_reason=@Marca;
DELETE dbo.WEBENGINE_BAN_LOG WHERE id=@Id AND ban_reason=@Marca;
PRINT 'OK: consultas y actualizaciones de cuenta; bloqueo, registro temporal y desbloqueo.';

PRINT '--- BLOQUEO DE IP WEB ---';
-- TEST-NET-3: documentation address, never a player's actual IP.
INSERT INTO dbo.WEBENGINE_BLOCKED_IP (block_ip,block_by,block_date)
VALUES (N'203.0.113.254',N'auditoria',@Fecha);
SET @Id=CONVERT(int,SCOPE_IDENTITY());
IF NOT EXISTS (SELECT 1 FROM dbo.WEBENGINE_BLOCKED_IP WHERE id=@Id AND block_ip=N'203.0.113.254')
    THROW 50017, 'Fallo la lectura del bloqueo de IP ficticia.', 1;
DELETE dbo.WEBENGINE_BLOCKED_IP WHERE id=@Id;
PRINT 'OK: alta, consulta y eliminacion de bloqueo de IP ficticia.';

PRINT '--- NOTICIAS Y TRADUCCIONES ---';
INSERT INTO dbo.WEBENGINE_NEWS (news_title,news_author,news_date,news_content,allow_comments)
VALUES (N'QXVkaXRvcmlh',N'auditoria',@Fecha,N'UHJ1ZWJh',0);
SET @Id=CONVERT(int,SCOPE_IDENTITY());
UPDATE dbo.WEBENGINE_NEWS SET news_title=N'UHJ1ZWJh',news_content=N'QXVkaXRvcmlh',
    news_author=N'auditoria',news_date=@Fecha,allow_comments=1 WHERE news_id=@Id;
IF NOT EXISTS (SELECT 1 FROM dbo.WEBENGINE_NEWS WHERE news_id=@Id AND allow_comments=1)
    THROW 50018, 'Fallo la edicion de noticia ficticia.', 1;
INSERT INTO dbo.WEBENGINE_NEWS_TRANSLATIONS (news_id,news_language,news_title,news_content)
VALUES (@Id,N'es',N'QXVkaXRvcmlh',N'UHJ1ZWJh');
UPDATE dbo.WEBENGINE_NEWS_TRANSLATIONS SET news_title=N'UHJ1ZWJh',news_content=N'QXVkaXRvcmlh'
WHERE news_id=@Id AND news_language=N'es';
IF NOT EXISTS (SELECT 1 FROM dbo.WEBENGINE_NEWS_TRANSLATIONS WHERE news_id=@Id AND news_language=N'es' AND news_title=N'UHJ1ZWJh')
    THROW 50019, 'Fallo la edicion de traduccion ficticia.', 1;
DELETE dbo.WEBENGINE_NEWS_TRANSLATIONS WHERE news_id=@Id;
DELETE dbo.WEBENGINE_NEWS WHERE news_id=@Id;
PRINT 'OK: creacion, lectura, edicion y eliminacion de noticias y traducciones.';

PRINT '--- TAREAS PROGRAMADAS WEB ---';
INSERT INTO dbo.WEBENGINE_CRON (cron_name,cron_description,cron_file_run,cron_run_time,cron_status,cron_protected,cron_file_md5)
VALUES (N'Auditoria SQL',@Marca,N'auditoria_no_ejecutar.php',3600,0,0,N'00000000000000000000000000000000');
SET @Id=CONVERT(int,SCOPE_IDENTITY());
UPDATE dbo.WEBENGINE_CRON SET cron_status=0,cron_last_run=NULL WHERE cron_id=@Id;
IF NOT EXISTS (SELECT 1 FROM dbo.WEBENGINE_CRON WHERE cron_id=@Id AND cron_status=0 AND cron_last_run IS NULL)
    THROW 50020, 'Fallo la gestion de tarea ficticia.', 1;
DELETE dbo.WEBENGINE_CRON WHERE cron_id=@Id;
PRINT 'OK: gestion SQL de tarea ficticia desactivada. No se ejecuto ningun cron.';

PRINT '--- EDICION ADMINISTRATIVA DE PERSONAJE Y MASTER ---';
UPDATE dbo.Character SET Class=Class,cLevel=cLevel,ResetCount=ResetCount,
    MasterResetCount=1,Money=Money,LevelUpPoint=LevelUpPoint,PkLevel=PkLevel,
    Strength=Strength,Dexterity=Dexterity,Vitality=Vitality,Energy=Energy,Leadership=Leadership
WHERE Name=N'audchar01' AND AccountID=N'audtest01';
IF @@ROWCOUNT<>1 THROW 50021, 'No se encontro el personaje ficticio.', 1;
IF NOT EXISTS (SELECT 1 FROM dbo.Character WHERE Name=N'audchar01' AND AccountID=N'audtest01' AND MasterResetCount=1)
    THROW 50022, 'Fallo la edicion administrativa de personaje.', 1;
UPDATE dbo.MasterSkillTree SET MasterLevel=401,MasterExperience=12345,MasterPoint=401 WHERE Name=N'audchar01';
IF @@ROWCOUNT<>1 THROW 50023, 'No se encontro el Master ficticio.', 1;
IF NOT EXISTS (SELECT 1 FROM dbo.MasterSkillTree WHERE Name=N'audchar01' AND MasterLevel=401 AND MasterExperience=12345 AND MasterPoint=401)
    THROW 50024, 'Fallo la edicion administrativa de Master.', 1;
PRINT 'OK: consultas y edicion administrativa de personaje y Master.';

PRINT '--- CONFIGURACIONES DE CREDITOS Y REGISTRO DE PLUGINS ---';
INSERT INTO dbo.WEBENGINE_CREDITS_CONFIG (config_title,config_database,config_table,config_credits_col,config_user_col,config_user_col_id,config_checkonline,config_display)
VALUES (@Marca,N'MuOnline',N'Character',N'Money',N'Name',N'character',0,0);
SET @Id=CONVERT(int,SCOPE_IDENTITY());
UPDATE dbo.WEBENGINE_CREDITS_CONFIG SET config_title=N'Auditoria ficticia' WHERE config_id=@Id;
IF NOT EXISTS (SELECT 1 FROM dbo.WEBENGINE_CREDITS_CONFIG WHERE config_id=@Id AND config_title=N'Auditoria ficticia')
    THROW 50025, 'Fallo la edicion de configuracion ficticia.', 1;
DELETE dbo.WEBENGINE_CREDITS_CONFIG WHERE config_id=@Id;
INSERT INTO dbo.WEBENGINE_PLUGINS (name,author,version,compatibility,folder,files,status,install_date,installed_by)
VALUES (@Marca,N'auditoria',N'0.0.0',N'1.2.7',N'auditoria_no_ejecutar',N'',0,@Fecha,N'auditoria');
SET @Id=CONVERT(int,SCOPE_IDENTITY());
UPDATE dbo.WEBENGINE_PLUGINS SET status=0 WHERE id=@Id;
IF NOT EXISTS (SELECT 1 FROM dbo.WEBENGINE_PLUGINS WHERE id=@Id AND status=0)
    THROW 50026, 'Fallo la lectura del registro de plugin ficticio.', 1;
DELETE dbo.WEBENGINE_PLUGINS WHERE id=@Id;
PRINT 'OK: gestion SQL de configuraciones y registro de plugin desactivado. No se instalo ni ejecuto ningun plugin.';

SELECT IS_MEMBER(N'db_owner') AS EsDbOwner,
    HAS_PERMS_BY_NAME(DB_NAME(),'DATABASE','CONTROL') AS ControlBase,
    HAS_PERMS_BY_NAME('dbo.CashShopData','OBJECT','UPDATE') AS ModificarWCoin;
'@

$consulta = @'
USE master;
SET NOCOUNT ON;
SET XACT_ABORT ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_NULLS ON;
SET ANSI_PADDING ON;
SET ANSI_WARNINGS ON;
SET ARITHABORT ON;
SET CONCAT_NULL_YIELDS_NULL ON;
SET NUMERIC_ROUNDABORT OFF;
IF COALESCE(CONVERT(nvarchar(60),DATABASEPROPERTYEX(N'MuOnline43_Auditoria','Status')),'')<>'ONLINE'
    THROW 50001, 'La copia de auditoria no esta ONLINE.', 1;
IF SUSER_ID(N'mupanic_auditoria') IS NULL
    THROW 50002, 'No existe el login de auditoria.', 1;
IF EXISTS (SELECT 1 FROM [MuOnline43].dbo.MEMB_INFO WHERE memb___id=N'audtest01')
   OR EXISTS (SELECT 1 FROM [MuOnline43].dbo.Character WHERE Name=N'audchar01')
    THROW 50003, 'Los nombres de prueba existen en produccion.', 1;
DECLARE @Impersonando bit=0;
BEGIN TRY
    BEGIN TRANSACTION;
    USE [MuOnline43_Auditoria];
    IF NOT EXISTS (SELECT 1 FROM sys.database_principals WHERE name=N'mupanic_auditoria' AND sid=SUSER_SID(N'mupanic_auditoria'))
        THROW 50004, 'El usuario SQL no corresponde al login esperado.', 1;
    IF NOT EXISTS (SELECT 1 FROM dbo.Character WHERE Name=N'audchar01' AND AccountID=N'audtest01')
        THROW 50005, 'Falta el personaje ficticio de la copia.', 1;
    GRANT UPDATE (bloc_code) ON dbo.MEMB_INFO TO [mupanic_auditoria];
    GRANT UPDATE (MasterResetCount) ON dbo.Character TO [mupanic_auditoria];
    GRANT UPDATE (MasterLevel) ON dbo.MasterSkillTree TO [mupanic_auditoria];
    GRANT INSERT,UPDATE,DELETE ON dbo.WEBENGINE_NEWS TO [mupanic_auditoria];
    GRANT INSERT,UPDATE,DELETE ON dbo.WEBENGINE_NEWS_TRANSLATIONS TO [mupanic_auditoria];
    GRANT INSERT,DELETE ON dbo.WEBENGINE_BAN_LOG TO [mupanic_auditoria];
    GRANT INSERT,DELETE ON dbo.WEBENGINE_BANS TO [mupanic_auditoria];
    GRANT INSERT,DELETE ON dbo.WEBENGINE_BLOCKED_IP TO [mupanic_auditoria];
    GRANT INSERT,UPDATE,DELETE ON dbo.WEBENGINE_CRON TO [mupanic_auditoria];
    GRANT INSERT,UPDATE,DELETE ON dbo.WEBENGINE_CREDITS_CONFIG TO [mupanic_auditoria];
    GRANT INSERT,UPDATE,DELETE ON dbo.WEBENGINE_PLUGINS TO [mupanic_auditoria];
    USE master;
    EXECUTE AS LOGIN='mupanic_auditoria';
    SET @Impersonando=1;
    IF COALESCE(HAS_DBACCESS(N'MuOnline43'),-1)<>0
        THROW 50006, 'Fallo el aislamiento de produccion.', 1;
    EXEC(N'__PRUEBA__');
    SELECT HAS_DBACCESS(N'MuOnline43') AS AccesoProduccion;
    REVERT;
    SET @Impersonando=0;
    ROLLBACK;
    PRINT 'PRUEBA TERMINADA: datos y permisos adicionales deshechos. Produccion sin cambios.';
END TRY
BEGIN CATCH
    IF @Impersonando=1 REVERT;
    IF @@TRANCOUNT>0 ROLLBACK;
    THROW;
END CATCH;
'@

$consulta = $consulta.Replace('__PRUEBA__', $prueba.Replace("'", "''"))
$consulta | sqlcmd -S localhost -E -I -b -x
if ($LASTEXITCODE -ne 0) {
    throw 'SQL informo un error. Los datos y permisos temporales no se confirmaron.'
}
