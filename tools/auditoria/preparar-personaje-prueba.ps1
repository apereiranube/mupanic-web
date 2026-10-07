$ErrorActionPreference = 'Stop'
Get-Command sqlcmd -ErrorAction Stop | Out-Null

# Windows integrated authentication. Fixed audit destination; no production writes.
$consulta = @'
USE master;
SET NOCOUNT ON;
SET XACT_ABORT ON;
-- Session options required by XML methods and computed/filtered indexes.
SET QUOTED_IDENTIFIER ON;
SET ANSI_NULLS ON;
SET ANSI_PADDING ON;
SET ANSI_WARNINGS ON;
SET ARITHABORT ON;
SET CONCAT_NULL_YIELDS_NULL ON;
SET NUMERIC_ROUNDABORT OFF;
IF COALESCE(CONVERT(nvarchar(60), DATABASEPROPERTYEX(N'MuOnline43_Auditoria', 'Status')), '') <> 'ONLINE'
    THROW 50001, 'La copia de auditoria no esta ONLINE.', 1;
IF SUSER_ID(N'mupanic_auditoria') IS NULL
    THROW 50002, 'No existe el login de auditoria.', 1;
IF EXISTS (SELECT 1 FROM [MuOnline43].dbo.MEMB_INFO WHERE memb___id=N'audtest01')
   OR EXISTS (SELECT 1 FROM [MuOnline43].dbo.Character WHERE Name=N'audchar01')
    THROW 50003, 'Los nombres de prueba existen en produccion. No se realizaron cambios.', 1;

USE [MuOnline43_Auditoria];
IF DB_NAME() <> N'MuOnline43_Auditoria'
    THROW 50004, 'Destino incorrecto.', 1;
IF NOT EXISTS (SELECT 1 FROM dbo.MEMB_INFO WHERE memb___id=N'audtest01')
    THROW 50005, 'No existe la cuenta audtest01 en la copia.', 1;
IF NOT EXISTS (SELECT 1 FROM sys.database_principals WHERE name=N'mupanic_auditoria' AND sid=SUSER_SID(N'mupanic_auditoria'))
    THROW 50006, 'El usuario SQL de la copia no corresponde al login esperado.', 1;

DECLARE @Impersonando bit=0;
BEGIN TRY
    BEGIN TRANSACTION;
    IF EXISTS (SELECT 1 FROM dbo.Character WHERE Name=N'audchar01' AND AccountID<>N'audtest01')
        THROW 50007, 'El nombre de prueba pertenece a otra cuenta de la copia.', 1;

    IF NOT EXISTS (SELECT 1 FROM dbo.Character WHERE Name=N'audchar01')
    BEGIN
        IF NOT EXISTS (SELECT 1 FROM dbo.Character)
            THROW 50008, 'La copia no contiene un personaje para usar como plantilla.', 1;

        -- Preserve required vendor fields, overriding all test identity/game state.
        -- Inventory, spells and quests are emptied; no copied items are delivered.
        DECLARE @Columnas nvarchar(max), @Valores nvarchar(max), @Sql nvarchar(max);
        SELECT @Columnas=STUFF((SELECT N','+QUOTENAME(name) FROM sys.columns
            WHERE object_id=OBJECT_ID(N'dbo.Character') AND is_identity=0 AND is_computed=0
              AND system_type_id<>189 AND generated_always_type=0
            ORDER BY column_id FOR XML PATH(''), TYPE).value('.', 'nvarchar(max)'),1,1,N'');
        SELECT @Valores=STUFF((SELECT N','+CASE
                   WHEN name=N'Name' THEN N'N''audchar01'''
                   WHEN name=N'AccountID' THEN N'N''audtest01'''
                   WHEN name=N'cLevel' THEN N'400'
                   WHEN name=N'Class' THEN N'19'
                   WHEN name=N'Money' THEN N'2000000000'
                   WHEN name=N'LevelUpPoint' THEN N'1000'
                   WHEN name IN (N'Strength',N'Dexterity',N'Vitality',N'Energy') THEN N'1000'
                   WHEN name IN (N'Leadership',N'ResetCount',N'MasterResetCount',N'PkCount',N'MapNumber') THEN N'0'
                   WHEN name=N'PkLevel' THEN N'6'
                   WHEN name=N'PkTime' THEN N'3600'
                   WHEN name IN (N'MapPosX',N'MapPosY') THEN N'125'
                   WHEN name IN (N'Inventory',N'MagicList',N'Quest') THEN CASE WHEN is_nullable=1 THEN N'NULL' ELSE N'0x' END
                   WHEN system_type_id=36 THEN N'NEWID()'
                   ELSE QUOTENAME(name)
               END
        FROM sys.columns
        WHERE object_id=OBJECT_ID(N'dbo.Character') AND is_identity=0 AND is_computed=0
          AND system_type_id<>189 AND generated_always_type=0
        ORDER BY column_id FOR XML PATH(''), TYPE).value('.', 'nvarchar(max)'),1,1,N'');

        SET @Sql=N'INSERT INTO dbo.Character ('+@Columnas+N') SELECT TOP (1) '+@Valores+N' FROM dbo.Character ORDER BY Name;';
        EXEC sys.sp_executesql @Sql;
        PRINT 'OK: personaje ficticio audchar01 creado SOLO en la copia.';
    END
    ELSE PRINT 'El personaje ficticio ya existe. Se conserva su estado y las pruebas realizadas.';

    IF NOT EXISTS (SELECT 1 FROM dbo.MasterSkillTree WHERE Name=N'audchar01')
    BEGIN
        INSERT INTO dbo.MasterSkillTree (Name, MasterLevel, MasterExperience, MasterPoint, MasterSkill)
        VALUES (N'audchar01', 400, 0, 400,
            CASE WHEN COLUMNPROPERTY(OBJECT_ID(N'dbo.MasterSkillTree'), N'MasterSkill', 'AllowsNull')=1 THEN NULL ELSE 0x END);
        PRINT 'OK: fila Master del personaje ficticio creada en la copia.';
    END

    -- Verify actual visibility as the web login without modifying game data.
    USE master;
    EXECUTE AS LOGIN='mupanic_auditoria';
    SET @Impersonando=1;
    IF COALESCE(HAS_DBACCESS(N'MuOnline43'), -1)<>0
        THROW 50009, 'El aislamiento de produccion fallo.', 1;
    EXEC(N'
        USE [MuOnline43_Auditoria];
        IF COALESCE(IS_MEMBER(''db_owner''),1)<>0
           OR COALESCE(HAS_PERMS_BY_NAME(DB_NAME(),''DATABASE'',''CONTROL''),1)<>0
           OR COALESCE(HAS_PERMS_BY_NAME(''dbo.CashShopData'',''OBJECT'',''UPDATE''),1)<>0
            THROW 50010, ''Se detectaron permisos no permitidos.'', 1;
        IF NOT EXISTS (SELECT 1 FROM dbo.Character WHERE Name=''audchar01'' AND AccountID=''audtest01'')
           OR NOT EXISTS (SELECT 1 FROM dbo.MasterSkillTree WHERE Name=''audchar01'')
            THROW 50011, ''La web no puede leer el personaje y su Master.'', 1;
        SELECT Name AS Personaje, AccountID AS Cuenta, cLevel AS Nivel, Money AS Zen,
            LevelUpPoint AS Puntos, ResetCount AS Resets
        FROM dbo.Character WHERE Name=''audchar01'' AND AccountID=''audtest01'';
    ');
    SELECT HAS_DBACCESS(N'MuOnline43') AS AccesoProduccion;
    REVERT;
    SET @Impersonando=0;
    COMMIT;
    PRINT 'OK: personaje y Master preparados. Produccion y sus permisos no se modificaron.';
END TRY
BEGIN CATCH
    IF @Impersonando=1 REVERT;
    IF @@TRANCOUNT>0 ROLLBACK;
    THROW;
END CATCH;
'@

$consulta | sqlcmd -S localhost -E -I -b -x
if ($LASTEXITCODE -ne 0) {
    throw 'SQL informo un error. La preparacion del personaje no se confirmo.'
}
