-- MU PANIC - Premios por votar. Solo MuOnline43. Objetos nuevos: no modifica las tablas de recargas.
SET NOCOUNT ON;
SET XACT_ABORT ON;
BEGIN TRANSACTION;
DECLARE @Lock int;
EXEC @Lock=sys.sp_getapplock @Resource='MU_PANIC_VOTE_INSTALL',@LockMode='Exclusive',@LockOwner='Transaction',@LockTimeout=5000;
IF @Lock<0 BEGIN ROLLBACK; RAISERROR('No se pudo bloquear la instalacion.',16,1); RETURN; END;
IF OBJECT_ID('dbo.MUPanicVoteDeliveries','U') IS NULL
CREATE TABLE dbo.MUPanicVoteDeliveries (
    VoteID varchar(33) COLLATE Latin1_General_BIN2 NOT NULL PRIMARY KEY,
    Site varchar(20) NOT NULL,
    AccountID varchar(10) NOT NULL,
    Coins int NOT NULL CONSTRAINT CK_MUPanicVoteCoins CHECK(Coins BETWEEN 1 AND 1000),
    BeforeCoin int NOT NULL CONSTRAINT CK_MUPanicVoteBefore CHECK(BeforeCoin>=0),
    AfterCoin int NOT NULL,
    CreditedAt datetime2 NOT NULL,
    AcknowledgedAt datetime2 NULL,
    CONSTRAINT CK_MUPanicVoteDelta CHECK(AfterCoin-BeforeCoin=Coins)
);
IF OBJECT_ID('dbo.MUPanicApplyVoteReward','P') IS NULL EXEC('CREATE PROCEDURE dbo.MUPanicApplyVoteReward AS RETURN;');
IF OBJECT_ID('dbo.MUPanicAckVoteReward','P') IS NULL EXEC('CREATE PROCEDURE dbo.MUPanicAckVoteReward AS RETURN;');
COMMIT;
GO
ALTER PROCEDURE dbo.MUPanicApplyVoteReward
    @VoteID varchar(33),@Site varchar(20),@Account varchar(10),@Coins int
WITH EXECUTE AS CALLER
AS
BEGIN
    SET NOCOUNT ON; SET XACT_ABORT ON; SET LOCK_TIMEOUT 5000;
    IF @VoteID IS NULL OR LEN(@VoteID)<>33 OR LEFT(@VoteID,1)<>'V' OR @Site IS NULL OR LEN(@Site)<1 OR @Account IS NULL OR LEN(@Account)<1 OR @Coins IS NULL OR @Coins NOT BETWEEN 1 AND 1000
    BEGIN RAISERROR('Premio invalido.',16,1); RETURN; END;
    BEGIN TRY
        BEGIN TRANSACTION;
        DECLARE @Lock int,@Resource nvarchar(255);
        SET @Resource='MU_PANIC_RECHARGE_'+LOWER(@Account);
        EXEC @Lock=sys.sp_getapplock @Resource=@Resource,@LockMode='Exclusive',@LockOwner='Transaction',@LockTimeout=5000;
        IF @Lock<0 RAISERROR('No se pudo bloquear la entrega.',16,1);
        IF EXISTS(SELECT 1 FROM dbo.MUPanicVoteDeliveries WITH(UPDLOCK,HOLDLOCK) WHERE VoteID=@VoteID)
        BEGIN
            IF NOT EXISTS(SELECT 1 FROM dbo.MUPanicVoteDeliveries WHERE VoteID=@VoteID AND Site=@Site AND AccountID=@Account AND Coins=@Coins)
                RAISERROR('Premio ya registrado con otros datos.',16,1);
        END
        ELSE
        BEGIN
            DECLARE @Online tinyint,@BeforeC int,@BeforeP int,@BeforeG int;
            SELECT @Online=ConnectStat FROM dbo.MEMB_STAT WITH(UPDLOCK,HOLDLOCK) WHERE memb___id=@Account;
            IF @Online IS NULL OR @Online<>0
            BEGIN COMMIT; SELECT 'waiting_offline' AS Result; RETURN; END;
            SELECT @BeforeC=WCoinC,@BeforeP=WCoinP,@BeforeG=GoblinPoint
                FROM dbo.CashShopData WITH(UPDLOCK,HOLDLOCK) WHERE AccountID=@Account;
            IF @BeforeC IS NULL OR @BeforeP IS NULL OR @BeforeG IS NULL OR @BeforeC<0 OR @BeforeC>2147483647-@Coins
                RAISERROR('Saldo ausente o fuera de rango.',16,1);
            EXEC dbo.WZ_SetCoin @Account=@Account,@Name='',@Value1=@Coins,@Value2=0,@Value3=0;
            SET NOCOUNT ON; SET XACT_ABORT ON;
            IF NOT EXISTS(SELECT 1 FROM dbo.CashShopData WHERE AccountID=@Account AND WCoinC=@BeforeC+@Coins AND WCoinP=@BeforeP AND GoblinPoint=@BeforeG)
                RAISERROR('Resultado inesperado: se revierte la entrega.',16,1);
            INSERT dbo.MUPanicVoteDeliveries(VoteID,Site,AccountID,Coins,BeforeCoin,AfterCoin,CreditedAt)
                VALUES(@VoteID,@Site,@Account,@Coins,@BeforeC,@BeforeC+@Coins,SYSUTCDATETIME());
        END;
        COMMIT;
        SELECT 'credited' AS Result,VoteID,Site,AccountID,Coins,BeforeCoin,AfterCoin
            FROM dbo.MUPanicVoteDeliveries WHERE VoteID=@VoteID;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT>0 ROLLBACK;
        DECLARE @Message nvarchar(2048);
        SET @Message=ERROR_MESSAGE();
        RAISERROR('%s',16,1,@Message);
    END CATCH;
END;
GO
ALTER PROCEDURE dbo.MUPanicAckVoteReward @VoteID varchar(33)
WITH EXECUTE AS CALLER
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE dbo.MUPanicVoteDeliveries SET AcknowledgedAt=SYSUTCDATETIME() WHERE VoteID=@VoteID AND AcknowledgedAt IS NULL;
END;
GO
IF NOT EXISTS(SELECT 1 FROM sys.server_principals WHERE name=N'NT AUTHORITY\SYSTEM') CREATE LOGIN [NT AUTHORITY\SYSTEM] FROM WINDOWS;
IF NOT EXISTS(SELECT 1 FROM sys.database_principals WHERE name=N'NT AUTHORITY\SYSTEM') CREATE USER [NT AUTHORITY\SYSTEM] FOR LOGIN [NT AUTHORITY\SYSTEM];
GRANT EXECUTE ON dbo.MUPanicApplyVoteReward TO [NT AUTHORITY\SYSTEM];
GRANT EXECUTE ON dbo.MUPanicAckVoteReward TO [NT AUTHORITY\SYSTEM];
GRANT SELECT ON dbo.MUPanicVoteDeliveries TO [NT AUTHORITY\SYSTEM];
