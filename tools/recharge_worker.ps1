param(
    [string]$SqlServer = '',
    [System.Management.Automation.PSCredential]$SqlCredential,
    [switch]$Install,
    [switch]$InstallTask,
    [switch]$Run,
    [switch]$Status,
    [ValidateSet('test','production')][string]$Environment = 'test',
    [string]$PrivateRoot = 'C:\MuServer43\PaymentsPrivate'
)
$ErrorActionPreference = 'Stop'
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
$endpoint = 'https://beta.mupanic.com.ar/templates/mupanic/api/recharge-worker.php'
$tokenPath = Join-Path $PrivateRoot 'recharge-worker-token'
$settingsPath = Join-Path $PrivateRoot 'recharge-worker.json'
$logPath = Join-Path $PrivateRoot 'recharge-worker.log'
if (([int]$Install.IsPresent + [int]$InstallTask.IsPresent + [int]$Run.IsPresent + [int]$Status.IsPresent) -gt 1) { throw 'Usa una sola opcion.' }
if (-not $Install -and (Test-Path -LiteralPath $settingsPath)) {
    $settings = [IO.File]::ReadAllText($settingsPath) | ConvertFrom-Json
    if (-not $SqlServer) { $SqlServer = [string]$settings.sql_server }
    if (-not $PSBoundParameters.ContainsKey('Environment')) { $Environment = [string]$settings.environment }
    if ($Environment -notin @('test','production')) { throw 'Ambiente local invalido.' }
}
function Write-RechargeLog([string]$Message) {
    $safe = [DateTime]::UtcNow.ToString('o') + ' ' + $Message
    Write-Host $Message
    if (Test-Path -LiteralPath $PrivateRoot) {
        if ((Test-Path -LiteralPath $logPath) -and (Get-Item -LiteralPath $logPath).Length -gt 2097152) { Move-Item -LiteralPath $logPath -Destination ($logPath + '.previous') -Force }
        Add-Content -LiteralPath $logPath -Value $safe -Encoding UTF8
    }
}
if ($Status -or (-not $Install -and -not $InstallTask -and -not $Run)) {
    Get-ScheduledTaskInfo -TaskName 'MU PANIC Recharge Worker' -ErrorAction SilentlyContinue | Format-List LastRunTime,LastTaskResult,NextRunTime
    if (Test-Path -LiteralPath $logPath) { Get-Content -LiteralPath $logPath -Tail 12 }
    return
}
if ($InstallTask) {
    $localScript = 'C:\MuServer43\RechargeSync\recharge_worker.ps1'
    if (-not (Test-Path -LiteralPath $localScript) -or -not (Test-Path -LiteralPath $settingsPath)) { throw 'Primero ejecuta -Install.' }
    $action = New-ScheduledTaskAction -Execute 'powershell.exe' -Argument ('-NoProfile -NonInteractive -ExecutionPolicy Bypass -File "' + $localScript + '" -Run') -WorkingDirectory 'C:\MuServer43\RechargeSync'
    $trigger = New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(1) -RepetitionInterval (New-TimeSpan -Minutes 1)
    $principal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount -RunLevel Highest
    $taskSettings = New-ScheduledTaskSettingsSet -MultipleInstances IgnoreNew -StartWhenAvailable -ExecutionTimeLimit (New-TimeSpan -Minutes 3)
    Register-ScheduledTask -TaskName 'MU PANIC Recharge Worker' -Action $action -Trigger $trigger -Principal $principal -Settings $taskSettings -Force | Out-Null
    Start-ScheduledTask -TaskName 'MU PANIC Recharge Worker'
    Write-Host 'Tarea creada: MU PANIC Recharge Worker. Intervalo: 1 minuto. Sigue funcionando al cerrar PowerShell.'
    return
}
function Open-RechargeConnection([string]$Instance, $Credential) {
    $builder = New-Object System.Data.SqlClient.SqlConnectionStringBuilder
    $builder['Data Source'] = $Instance
    $builder['Initial Catalog'] = 'MuOnline43'
    $builder['Connect Timeout'] = 5
    $builder['Application Name'] = 'MU PANIC Recharge Worker'
    if ($null -eq $Credential) { $builder['Integrated Security'] = $true }
    else {
        $builder['User ID'] = $Credential.UserName
        $builder['Password'] = $Credential.GetNetworkCredential().Password
    }
    $connection = New-Object System.Data.SqlClient.SqlConnection -ArgumentList ($builder.get_ConnectionString())
    try { $connection.Open(); return $connection }
    catch { $connection.Dispose(); return $null }
}
function Read-RechargeSql([string]$Sql, $Job = $null) {
    $command = $script:connection.CreateCommand()
    $command.CommandText = $Sql
    $command.CommandTimeout = 30
    if ($null -ne $Job) {
        $command.Parameters.Add('@OrderID',[System.Data.SqlDbType]::VarChar,38).Value = [string]$Job.id
        $command.Parameters.Add('@PaymentID',[System.Data.SqlDbType]::VarChar,120).Value = [string]$Job.payment_id
        $command.Parameters.Add('@Account',[System.Data.SqlDbType]::VarChar,10).Value = [string]$Job.account
        $command.Parameters.Add('@Coins',[System.Data.SqlDbType]::Int).Value = [int]$Job.coins
        $command.Parameters.Add('@PriceCents',[System.Data.SqlDbType]::Int).Value = [int]$Job.price_cents
        $command.Parameters.Add('@Environment',[System.Data.SqlDbType]::VarChar,10).Value = [string]$Job.environment
    }
    $table = New-Object System.Data.DataTable
    $reader = $null
    try { $reader = $command.ExecuteReader(); $table.Load($reader) }
    finally { if ($null -ne $reader) { $reader.Dispose() }; $command.Dispose() }
    return ,$table
}
function Get-RechargeSignature([string]$Message, [string]$Token) {
    $hmac = New-Object System.Security.Cryptography.HMACSHA256
    try {
        $hmac.Key = [Text.Encoding]::UTF8.GetBytes($Token)
        return ([BitConverter]::ToString($hmac.ComputeHash([Text.Encoding]::UTF8.GetBytes($Message)))).Replace('-','').ToLowerInvariant()
    } finally { $hmac.Dispose() }
}
function Invoke-RechargeBridge($Request) {
    if (-not (Test-Path -LiteralPath $tokenPath -PathType Leaf)) { throw 'Falta instalar recharge-worker-token.' }
    $token = [IO.File]::ReadAllText($tokenPath).Trim()
    if ($token -cnotmatch '^[a-f0-9]{64}$') { throw 'Token de prueba invalido.' }
    $Request.nonce = [Guid]::NewGuid().ToString('N')
    $body = $Request | ConvertTo-Json -Depth 6 -Compress
    $stamp = [string][DateTimeOffset]::UtcNow.ToUnixTimeSeconds()
    $headers = @{ 'X-Panic-Timestamp'=$stamp; 'X-Panic-Signature'=(Get-RechargeSignature ($stamp + "`n" + $body) $token) }
    # Fixed HTTPS destination, certificate verification enabled, no redirects.
    try { $response = Invoke-WebRequest -UseBasicParsing -Uri $endpoint -Method Post -Headers $headers -ContentType 'application/json; charset=utf-8' -Body ([Text.Encoding]::UTF8.GetBytes($body)) -MaximumRedirection 0 -TimeoutSec 65 }
    catch {
        $httpCode = 0
        if ($null -ne $_.Exception.Response) { $httpCode = [int]$_.Exception.Response.StatusCode }
        throw ('Consulta web fallida. HTTP ' + $httpCode + '. 401: token o reloj; 503: configuracion web; 0: conexion o timeout. No se modificaron monedas en esta consulta.')
    }
    $raw = [string]$response.Content
    if ($raw.Length -gt 8192) { throw 'Respuesta de prueba invalida.' }
    $expected = Get-RechargeSignature ($Request.nonce + "`n" + $raw) $token
    if ([string]$response.Headers['X-Panic-Signature'] -cne $expected) { throw 'Firma de respuesta invalida. No se acreditan monedas.' }
    $result = $raw | ConvertFrom-Json
    if ($result.nonce -cne $Request.nonce) { throw 'Respuesta de otra solicitud. No se acreditan monedas.' }
    return $result
}
function Confirm-RechargeReceipt($Row) {
    $receipt = @{ id=[string]$Row.OrderID; payment_id=[string]$Row.PaymentID; account=[string]$Row.AccountID; coins=[int]$Row.Coins; environment=[string]$Row.Environment; state='credited'; before_coin=[int]$Row.BeforeCoin; after_coin=[int]$Row.AfterCoin }
    $ack = Invoke-RechargeBridge @{ action='ack'; receipt=$receipt }
    if ($ack.state -cne 'credited' -or $ack.id -cne $receipt.id) { throw 'La web no confirmo el recibo SQL; se reintentara sin duplicar monedas.' }
    $command = $script:connection.CreateCommand()
    try {
        $command.CommandText='EXEC dbo.MUPanicAckRecharge @OrderID=@OrderID'
        $command.Parameters.Add('@OrderID',[System.Data.SqlDbType]::VarChar,38).Value=$receipt.id
        $command.ExecuteNonQuery() | Out-Null
    } finally { $command.Dispose() }
}
$installSql = @'
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
'@
$instances = @()
if ($SqlServer) { $instances = @($SqlServer) }
else {
    foreach ($registryPath in @('HKLM:\SOFTWARE\Microsoft\Microsoft SQL Server\Instance Names\SQL','HKLM:\SOFTWARE\WOW6432Node\Microsoft\Microsoft SQL Server\Instance Names\SQL')) {
        if (Test-Path $registryPath) {
            foreach ($property in (Get-ItemProperty $registryPath).PSObject.Properties) {
                if ($property.Name -notlike 'PS*') {
                    if ($property.Name -eq 'MSSQLSERVER') { $instances += 'localhost' }
                    else { $instances += ('localhost\' + $property.Name) }
                }
            }
        }
    }
    $instances += @('localhost','localhost\SQLEXPRESS')
    $instances = @($instances | Select-Object -Unique)
}
$script:connection = $null
foreach ($instance in $instances) {
    $script:connection = Open-RechargeConnection $instance $SqlCredential
    if ($null -ne $script:connection) { break }
}
if ($Install -and $null -eq $script:connection -and $null -eq $SqlCredential) {
    $SqlCredential = Get-Credential -Message 'SQL local: instalar recargas en MuOnline43. No se exportan credenciales.'
    if ($null -ne $SqlCredential) {
        foreach ($instance in $instances) {
            $script:connection = Open-RechargeConnection $instance $SqlCredential
            if ($null -ne $script:connection) { break }
        }
    }
}
if ($null -eq $script:connection) { throw 'No se pudo conectar a MuOnline43. No se modificaron saldos.' }
try {
    if ($Install) {
        foreach ($batch in [regex]::Split($installSql,'(?im)^GO\s*$')) {
            if ([string]::IsNullOrWhiteSpace($batch)) { continue }
            $command=$script:connection.CreateCommand()
            try { $command.CommandText=$batch; $command.CommandTimeout=60; $command.ExecuteNonQuery() | Out-Null } finally { $command.Dispose() }
        }
        [IO.Directory]::CreateDirectory($PrivateRoot) | Out-Null
        # Restrict persistent tokens/configuration to SYSTEM and local Administrators.
        $acl = New-Object Security.AccessControl.DirectorySecurity
        $acl.SetAccessRuleProtection($true,$false)
        foreach ($sidText in @('S-1-5-18','S-1-5-32-544')) {
            $sid = New-Object Security.Principal.SecurityIdentifier($sidText)
            $rule = New-Object Security.AccessControl.FileSystemAccessRule($sid,'FullControl','ContainerInherit,ObjectInherit','None','Allow')
            $acl.AddAccessRule($rule)
        }
        Set-Acl -LiteralPath $PrivateRoot -AclObject $acl
        if (-not (Test-Path -LiteralPath $tokenPath)) {
            $rng=[Security.Cryptography.RandomNumberGenerator]::Create()
            try { $bytes=New-Object byte[] 32; $rng.GetBytes($bytes) } finally { $rng.Dispose() }
            $token=([BitConverter]::ToString($bytes)).Replace('-','').ToLowerInvariant()
            [IO.File]::WriteAllText($tokenPath,$token,[Text.Encoding]::ASCII)
        }
        $localRoot='C:\MuServer43\RechargeSync'; [IO.Directory]::CreateDirectory($localRoot) | Out-Null
        $localScript=Join-Path $localRoot 'recharge_worker.ps1'
        if ($PSCommandPath -cne $localScript) { Copy-Item -LiteralPath $PSCommandPath -Destination $localScript -Force }
        $saved=@{ sql_server=[string]$instance; environment=$Environment } | ConvertTo-Json
        [IO.File]::WriteAllText($settingsPath,$saved,[Text.Encoding]::UTF8)
        $desktop=Join-Path ([Environment]::GetFolderPath('Desktop')) 'recharge-worker-token'
        Copy-Item -LiteralPath $tokenPath -Destination $desktop -Force
        Write-Host ('Instalado sin modificar saldos. Subi ' + $desktop + ' a /home/mupanic/payments-private/ en cPanel con permiso 600.')
        Write-Host 'Despues ejecuta este script con -InstallTask para activar la entrega automatica.'
        return
    }
    $mutex=New-Object Threading.Mutex($false,'Global\MU_PANIC_RECHARGE_WORKER')
    $owns=$false
    try {
        try { $owns=$mutex.WaitOne(0) } catch [Threading.AbandonedMutexException] { $owns=$true }
        if (-not $owns) { Write-RechargeLog 'Ya hay otra ejecucion activa.'; return }
        # SQL commits are recovered first, even if the gateway or last HTTP acknowledgment failed.
        $unacknowledged=Read-RechargeSql 'SELECT TOP(10) OrderID,PaymentID,AccountID,Environment,Coins,BeforeCoin,AfterCoin FROM dbo.MUPanicRechargeDeliveries WHERE AcknowledgedAt IS NULL ORDER BY CreditedAt'
        foreach ($row in $unacknowledged.Rows) { Confirm-RechargeReceipt $row }
        $result=Invoke-RechargeBridge @{ action='poll'; environment=$Environment }
        if ($null -eq $result.jobs -or @($result.jobs).Count -gt 3) { throw 'Cola firmada invalida.' }
        foreach ($job in @($result.jobs)) {
            if ([string]$job.id -cnotmatch '^PANIC-[a-f0-9]{32}$' -or [string]$job.payment_id -cnotmatch '^[A-Za-z0-9_-]{1,120}$' -or
                [string]$job.account -cnotmatch '^[A-Za-z0-9_]{1,10}$' -or $job.provider -cne 'uala_bis' -or $job.environment -cne $Environment -or
                ($job.coins -isnot [int] -and $job.coins -isnot [long]) -or $job.coins -lt 1 -or $job.coins -gt 1000000 -or ($job.price_cents -isnot [int] -and $job.price_cents -isnot [long]) -or $job.price_cents -ne $job.coins*100) { throw 'Orden firmada fuera de limites; no se acreditan monedas.' }
            $receipt=Read-RechargeSql 'EXEC dbo.MUPanicApplyRecharge @OrderID=@OrderID,@PaymentID=@PaymentID,@Account=@Account,@Coins=@Coins,@PriceCents=@PriceCents,@Environment=@Environment' $job
            if ($receipt.Rows.Count -ne 1) { throw 'Resultado SQL no confirmado; se conserva el registro.' }
            if ($receipt.Rows[0].Result -ceq 'waiting_offline') { Write-RechargeLog ('Cuenta conectada o estado desconocido: ' + [string]$job.account + '. Entrega pendiente.'); continue }
            if ($receipt.Rows[0].Result -cne 'credited') { throw 'Estado SQL inesperado.' }
            Confirm-RechargeReceipt $receipt.Rows[0]
            Write-RechargeLog ('Entrega ' + [string]$job.id + ' para ' + [string]$job.account + ': ' + $receipt.Rows[0].BeforeCoin + ' -> ' + $receipt.Rows[0].AfterCoin + ' WCoin C.')
        }
        Write-RechargeLog ('Consulta completada. Compras listas: ' + @($result.jobs).Count + '; verificaciones pendientes: ' + [string]$result.check_errors + '.')
    } finally { if ($owns) { $mutex.ReleaseMutex() }; $mutex.Dispose() }
} catch {
    Write-RechargeLog ('Ejecucion detenida: ' + $_.Exception.Message + ' La tarea volvera a consultar en su siguiente ejecucion.')
    throw
} finally { $script:connection.Dispose() }
