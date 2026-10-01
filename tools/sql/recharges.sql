-- MuOnline43 only. Separate from the one-off Uala pilot ledger.
SET NOCOUNT ON;
SET XACT_ABORT ON;
BEGIN TRANSACTION;
DECLARE @Lock int;
EXEC @Lock=sys.sp_getapplock @Resource='MU_PANIC_RECHARGE_INSTALL',@LockMode='Exclusive',@LockOwner='Transaction',@LockTimeout=5000;
IF @Lock<0 BEGIN ROLLBACK; RAISERROR('No se pudo bloquear la instalacion.',16,1); RETURN; END;
IF OBJECT_ID('dbo.MUPanicRechargeDeliveries','U') IS NULL
CREATE TABLE dbo.MUPanicRechargeDeliveries (
    OrderID varchar(38) COLLATE Latin1_General_BIN2 NOT NULL PRIMARY KEY,
    PaymentID varchar(120) COLLATE Latin1_General_BIN2 NOT NULL,
    Provider varchar(20) NOT NULL CHECK(Provider='uala_bis'),
    Environment varchar(10) NOT NULL CHECK(Environment IN ('test','production')),
    AccountID varchar(10) NOT NULL,
    Coins int NOT NULL CHECK(Coins BETWEEN 1 AND 1000000),
    PriceCents int NOT NULL,
    BeforeCoin int NOT NULL CHECK(BeforeCoin>=0),
    AfterCoin int NOT NULL,
    CreditedAt datetime2 NOT NULL,
    AcknowledgedAt datetime2 NULL,
    CONSTRAINT UQ_MUPanicRechargePayment UNIQUE(Environment,Provider,PaymentID),
    CONSTRAINT CK_MUPanicRechargePrice CHECK(PriceCents=Coins*100),
    CONSTRAINT CK_MUPanicRechargeBalance CHECK(AfterCoin>=BeforeCoin),
    CONSTRAINT CK_MUPanicRechargeDelta CHECK(AfterCoin-BeforeCoin=Coins)
);
IF OBJECT_ID('dbo.MUPanicApplyRecharge','P') IS NULL EXEC('CREATE PROCEDURE dbo.MUPanicApplyRecharge AS RETURN;');
IF OBJECT_ID('dbo.MUPanicAckRecharge','P') IS NULL EXEC('CREATE PROCEDURE dbo.MUPanicAckRecharge AS RETURN;');
COMMIT;
GO
ALTER PROCEDURE dbo.MUPanicApplyRecharge
    @OrderID varchar(38),@PaymentID varchar(120),@Account varchar(10),@Coins int,@PriceCents int,@Environment varchar(10)
WITH EXECUTE AS OWNER
AS
BEGIN
    SET NOCOUNT ON; SET XACT_ABORT ON; SET LOCK_TIMEOUT 5000;
    IF LEN(@OrderID)<>38 OR LEFT(@OrderID,6)<>'PANIC-' OR LEN(@PaymentID)<1 OR LEN(@Account)<1 OR
        @Coins NOT BETWEEN 1 AND 1000000 OR @PriceCents<>@Coins*100 OR @Environment NOT IN ('test','production')
    BEGIN RAISERROR('Orden invalida.',16,1); RETURN; END;
    BEGIN TRY
        BEGIN TRANSACTION;
        DECLARE @Lock int,@Resource nvarchar(255);
        SET @Resource='MU_PANIC_RECHARGE_'+LOWER(@Account);
        EXEC @Lock=sys.sp_getapplock @Resource=@Resource,@LockMode='Exclusive',@LockOwner='Transaction',@LockTimeout=5000;
        IF @Lock<0 RAISERROR('No se pudo bloquear la entrega.',16,1);
        IF EXISTS(SELECT 1 FROM dbo.MUPanicRechargeDeliveries WITH(UPDLOCK,HOLDLOCK) WHERE OrderID=@OrderID)
        BEGIN
            IF NOT EXISTS(SELECT 1 FROM dbo.MUPanicRechargeDeliveries WHERE OrderID=@OrderID AND PaymentID=@PaymentID AND
                AccountID=@Account AND Coins=@Coins AND PriceCents=@PriceCents AND Environment=@Environment)
                RAISERROR('Orden ya registrada con otros datos.',16,1);
        END
        ELSE
        BEGIN
            IF EXISTS(SELECT 1 FROM dbo.MUPanicRechargeDeliveries WITH(UPDLOCK,HOLDLOCK) WHERE Environment=@Environment AND Provider='uala_bis' AND PaymentID=@PaymentID)
                RAISERROR('Pago ya acreditado.',16,1);
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
            INSERT dbo.MUPanicRechargeDeliveries(OrderID,PaymentID,Provider,Environment,AccountID,Coins,PriceCents,BeforeCoin,AfterCoin,CreditedAt)
                VALUES(@OrderID,@PaymentID,'uala_bis',@Environment,@Account,@Coins,@PriceCents,@BeforeC,@BeforeC+@Coins,SYSUTCDATETIME());
        END;
        COMMIT;
        SELECT 'credited' AS Result,OrderID,PaymentID,Environment,AccountID,Coins,BeforeCoin,AfterCoin
            FROM dbo.MUPanicRechargeDeliveries WHERE OrderID=@OrderID;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT>0 ROLLBACK;
        DECLARE @Message nvarchar(2048)=ERROR_MESSAGE(); RAISERROR('%s',16,1,@Message);
    END CATCH;
END;
GO
ALTER PROCEDURE dbo.MUPanicAckRecharge @OrderID varchar(38)
WITH EXECUTE AS OWNER
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE dbo.MUPanicRechargeDeliveries SET AcknowledgedAt=SYSUTCDATETIME() WHERE OrderID=@OrderID AND AcknowledgedAt IS NULL;
END;
GO
-- Scheduled task runs under SYSTEM. Grant only the delivery/ack procedures and ledger reads.
IF NOT EXISTS(SELECT 1 FROM sys.server_principals WHERE name=N'NT AUTHORITY\SYSTEM') CREATE LOGIN [NT AUTHORITY\SYSTEM] FROM WINDOWS;
IF NOT EXISTS(SELECT 1 FROM sys.database_principals WHERE name=N'NT AUTHORITY\SYSTEM') CREATE USER [NT AUTHORITY\SYSTEM] FOR LOGIN [NT AUTHORITY\SYSTEM];
GRANT EXECUTE ON dbo.MUPanicApplyRecharge TO [NT AUTHORITY\SYSTEM];
GRANT EXECUTE ON dbo.MUPanicAckRecharge TO [NT AUTHORITY\SYSTEM];
GRANT SELECT ON dbo.MUPanicRechargeDeliveries TO [NT AUTHORITY\SYSTEM];
