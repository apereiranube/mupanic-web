param(
    [string]$SqlServer = '',
    [System.Management.Automation.PSCredential]$SqlCredential,
    [switch]$Install,
    [switch]$Run,
    [switch]$Revert,
    [ValidateRange(0,1800)][int]$WatchSeconds = 0,
    [string]$PrivateRoot = 'C:\MuServer43\PaymentsPrivate'
)
$ErrorActionPreference = 'Stop'
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
$endpoint = 'https://beta.mupanic.com.ar/templates/mupanic/api/uala-pilot-worker.php'
$tokenPath = Join-Path $PrivateRoot 'sandbox-worker-token'
if (([int]$Install.IsPresent + [int]$Run.IsPresent + [int]$Revert.IsPresent) -gt 1) { throw 'Usa solo una opcion: -Install, -Run o -Revert.' }
function Open-PilotConnection([string]$Instance, $Credential) {
    $builder = New-Object System.Data.SqlClient.SqlConnectionStringBuilder
    $builder['Data Source'] = $Instance
    $builder['Initial Catalog'] = 'MuOnline43'
    $builder['Connect Timeout'] = 5
    $builder['Application Name'] = 'MU PANIC Uala Single Sandbox Pilot'
    if ($null -eq $Credential) { $builder['Integrated Security'] = $true }
    else {
        $builder['User ID'] = $Credential.UserName
        $builder['Password'] = $Credential.GetNetworkCredential().Password
    }
    $connection = New-Object System.Data.SqlClient.SqlConnection -ArgumentList ($builder.get_ConnectionString())
    try { $connection.Open(); return $connection }
    catch { $connection.Dispose(); return $null }
}
function Read-PilotSql([string]$Sql, $Job = $null) {
    $command = $script:connection.CreateCommand()
    $command.CommandText = $Sql
    $command.CommandTimeout = 30
    if ($null -ne $Job) {
        $command.Parameters.Add('@OrderID',[System.Data.SqlDbType]::VarChar,38).Value = [string]$Job.id
        $command.Parameters.Add('@PaymentID',[System.Data.SqlDbType]::VarChar,120).Value = [string]$Job.payment_id
    }
    $table = New-Object System.Data.DataTable
    $reader = $null
    try { $reader = $command.ExecuteReader(); $table.Load($reader) }
    finally { if ($null -ne $reader) { $reader.Dispose() }; $command.Dispose() }
    return ,$table
}
function Get-PilotSignature([string]$Message, [string]$Token) {
    $hmac = New-Object System.Security.Cryptography.HMACSHA256
    try {
        $hmac.Key = [Text.Encoding]::UTF8.GetBytes($Token)
        return ([BitConverter]::ToString($hmac.ComputeHash([Text.Encoding]::UTF8.GetBytes($Message)))).Replace('-','').ToLowerInvariant()
    } finally { $hmac.Dispose() }
}
function Invoke-PilotBridge($Request) {
    if (-not (Test-Path -LiteralPath $tokenPath -PathType Leaf)) { throw 'Falta instalar sandbox-worker-token.' }
    $token = [IO.File]::ReadAllText($tokenPath).Trim()
    if ($token -cnotmatch '^[a-f0-9]{64}$') { throw 'Token de prueba invalido.' }
    $Request.nonce = [Guid]::NewGuid().ToString('N')
    $body = $Request | ConvertTo-Json -Depth 6 -Compress
    $stamp = [string][DateTimeOffset]::UtcNow.ToUnixTimeSeconds()
    $headers = @{ 'X-Panic-Timestamp'=$stamp; 'X-Panic-Signature'=(Get-PilotSignature ($stamp + "`n" + $body) $token) }
    # Fixed HTTPS destination, certificate verification enabled, no redirects.
    try { $response = Invoke-WebRequest -UseBasicParsing -Uri $endpoint -Method Post -Headers $headers -ContentType 'application/json; charset=utf-8' -Body ([Text.Encoding]::UTF8.GetBytes($body)) -MaximumRedirection 0 -TimeoutSec 45 }
    catch {
        $httpCode = 0
        if ($null -ne $_.Exception.Response) { $httpCode = [int]$_.Exception.Response.StatusCode }
        throw ('Consulta web fallida. HTTP ' + $httpCode + '. 401: token o reloj; 503: configuracion web; 0: conexion o timeout. No se modificaron monedas en esta consulta.')
    }
    $raw = [string]$response.Content
    if ($raw.Length -gt 8192) { throw 'Respuesta de prueba invalida.' }
    $expected = Get-PilotSignature ($Request.nonce + "`n" + $raw) $token
    if ([string]$response.Headers['X-Panic-Signature'] -cne $expected) { throw 'Firma de respuesta invalida. No se acreditan monedas.' }
    $result = $raw | ConvertFrom-Json
    if ($result.nonce -cne $Request.nonce) { throw 'Respuesta de otra solicitud. No se acreditan monedas.' }
    return $result
}
function Confirm-PilotReceipt($Row) {
    $receipt = @{ id=[string]$Row.OrderID; payment_id=[string]$Row.PaymentID; account='pruebacoin'; coins=1000; state=[string]$Row.State }
    $ack = Invoke-PilotBridge @{ action='ack'; receipt=$receipt }
    if ($ack.state -cne $receipt.state) { throw 'La web no confirmo el recibo SQL. El registro evita repetir la acreditacion.' }
}
$installSql = @'
SET NOCOUNT ON;
SET XACT_ABORT ON;
BEGIN TRY
    BEGIN TRANSACTION;
    DECLARE @Lock int;
    EXEC @Lock=sys.sp_getapplock @Resource='MU_PANIC_UALA_PILOT',@LockMode='Exclusive',@LockOwner='Transaction',@LockTimeout=5000;
    IF @Lock<0 RAISERROR('No se pudo bloquear la prueba.',16,1);
    IF OBJECT_ID('dbo.MUPanicUalaSandboxPilot','U') IS NULL
    CREATE TABLE dbo.MUPanicUalaSandboxPilot (
        PilotKey tinyint NOT NULL PRIMARY KEY CHECK (PilotKey=1),
        OrderID varchar(38) NOT NULL UNIQUE,
        PaymentID varchar(120) NOT NULL UNIQUE,
        AccountID varchar(10) NOT NULL CHECK (AccountID='pruebacoin'),
        Coins int NOT NULL CHECK (Coins=1000),
        State varchar(10) NOT NULL CHECK (State IN ('credited','reverted')),
        BeforeCoin int NOT NULL,
        AfterCoin int NOT NULL,
        CreditedAt datetime2 NOT NULL,
        RevertedAt datetime2 NULL
    );
    COMMIT TRANSACTION;
    SELECT 'installed' AS Result;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT>0 ROLLBACK TRANSACTION;
    DECLARE @Message nvarchar(2048)=ERROR_MESSAGE();
    RAISERROR('%s',16,1,@Message);
END CATCH;
'@
$creditSql = @'
SET NOCOUNT ON;
SET XACT_ABORT ON;
SET LOCK_TIMEOUT 5000;
BEGIN TRY
    BEGIN TRANSACTION;
    DECLARE @Lock int;
    EXEC @Lock=sys.sp_getapplock @Resource='MU_PANIC_UALA_PILOT',@LockMode='Exclusive',@LockOwner='Transaction',@LockTimeout=5000;
    IF @Lock<0 RAISERROR('No se pudo bloquear la prueba.',16,1);
    IF EXISTS (SELECT 1 FROM dbo.MUPanicUalaSandboxPilot WITH (UPDLOCK,HOLDLOCK) WHERE PilotKey=1)
    BEGIN
        IF NOT EXISTS (SELECT 1 FROM dbo.MUPanicUalaSandboxPilot WHERE PilotKey=1 AND OrderID=@OrderID AND PaymentID=@PaymentID)
            RAISERROR('Ya se ejecuto otra compra de prueba. No se acreditan mas monedas.',16,1);
    END
    ELSE
    BEGIN
        DECLARE @Online tinyint,@BeforeC int,@BeforeP int,@BeforeG int;
        SELECT @Online=ConnectStat FROM dbo.MEMB_STAT WITH (UPDLOCK,HOLDLOCK) WHERE memb___id='pruebacoin';
        IF @Online IS NULL OR @Online<>0 RAISERROR('Desconecta pruebacoin del juego para entregar las monedas.',16,1);
        SELECT @BeforeC=WCoinC,@BeforeP=WCoinP,@BeforeG=GoblinPoint
        FROM dbo.CashShopData WITH (UPDLOCK,HOLDLOCK) WHERE AccountID='pruebacoin';
        IF @BeforeC IS NULL OR @BeforeC<0 OR @BeforeC>2147482647 RAISERROR('Saldo ausente o fuera de rango.',16,1);
        EXEC dbo.WZ_SetCoin @Account='pruebacoin',@Name='',@Value1=1000,@Value2=0,@Value3=0;
        SET NOCOUNT ON;
        SET XACT_ABORT ON;
        IF NOT EXISTS (SELECT 1 FROM dbo.CashShopData WHERE AccountID='pruebacoin' AND WCoinC=@BeforeC+1000 AND WCoinP=@BeforeP AND GoblinPoint=@BeforeG)
            RAISERROR('Resultado inesperado. Se revierte la entrega.',16,1);
        INSERT dbo.MUPanicUalaSandboxPilot (PilotKey,OrderID,PaymentID,AccountID,Coins,State,BeforeCoin,AfterCoin,CreditedAt)
        VALUES (1,@OrderID,@PaymentID,'pruebacoin',1000,'credited',@BeforeC,@BeforeC+1000,SYSUTCDATETIME());
    END;
    COMMIT TRANSACTION;
    SELECT OrderID,PaymentID,State,BeforeCoin,AfterCoin FROM dbo.MUPanicUalaSandboxPilot WHERE PilotKey=1;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT>0 ROLLBACK TRANSACTION;
    DECLARE @Message nvarchar(2048)=ERROR_MESSAGE();
    RAISERROR('%s',16,1,@Message);
END CATCH;
'@
$revertSql = @'
SET NOCOUNT ON;
SET XACT_ABORT ON;
SET LOCK_TIMEOUT 5000;
BEGIN TRY
    BEGIN TRANSACTION;
    DECLARE @Lock int;
    EXEC @Lock=sys.sp_getapplock @Resource='MU_PANIC_UALA_PILOT',@LockMode='Exclusive',@LockOwner='Transaction',@LockTimeout=5000;
    IF @Lock<0 RAISERROR('No se pudo bloquear la prueba.',16,1);
    IF NOT EXISTS (SELECT 1 FROM dbo.MUPanicUalaSandboxPilot WITH (UPDLOCK,HOLDLOCK) WHERE PilotKey=1)
        RAISERROR('No hay monedas de esta prueba para retirar.',16,1);
    IF EXISTS (SELECT 1 FROM dbo.MUPanicUalaSandboxPilot WHERE PilotKey=1 AND State='credited')
    BEGIN
        DECLARE @Online tinyint,@BeforeC int,@BeforeP int,@BeforeG int;
        SELECT @Online=ConnectStat FROM dbo.MEMB_STAT WITH (UPDLOCK,HOLDLOCK) WHERE memb___id='pruebacoin';
        IF @Online IS NULL OR @Online<>0 RAISERROR('Desconecta pruebacoin antes de retirar las monedas.',16,1);
        SELECT @BeforeC=WCoinC,@BeforeP=WCoinP,@BeforeG=GoblinPoint
        FROM dbo.CashShopData WITH (UPDLOCK,HOLDLOCK) WHERE AccountID='pruebacoin';
        IF @BeforeC IS NULL OR @BeforeC<1000 RAISERROR('Saldo insuficiente: no se retira ni se deja saldo negativo.',16,1);
        EXEC dbo.WZ_SetCoin @Account='pruebacoin',@Name='',@Value1=-1000,@Value2=0,@Value3=0;
        SET NOCOUNT ON;
        SET XACT_ABORT ON;
        IF NOT EXISTS (SELECT 1 FROM dbo.CashShopData WHERE AccountID='pruebacoin' AND WCoinC=@BeforeC-1000 AND WCoinP=@BeforeP AND GoblinPoint=@BeforeG)
            RAISERROR('Resultado inesperado. Se revierte la retirada.',16,1);
        UPDATE dbo.MUPanicUalaSandboxPilot SET State='reverted',RevertedAt=SYSUTCDATETIME() WHERE PilotKey=1;
    END;
    COMMIT TRANSACTION;
    SELECT OrderID,PaymentID,State,BeforeCoin,AfterCoin FROM dbo.MUPanicUalaSandboxPilot WHERE PilotKey=1;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT>0 ROLLBACK TRANSACTION;
    DECLARE @Message nvarchar(2048)=ERROR_MESSAGE();
    RAISERROR('%s',16,1,@Message);
END CATCH;
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
    $script:connection = Open-PilotConnection $instance $SqlCredential
    if ($null -ne $script:connection) { break }
}
if ($null -eq $script:connection -and $null -eq $SqlCredential) {
    $SqlCredential = Get-Credential -Message 'SQL local: prueba Uala en MuOnline43. No se exportan credenciales.'
    if ($null -ne $SqlCredential) {
        foreach ($instance in $instances) {
            $script:connection = Open-PilotConnection $instance $SqlCredential
            if ($null -ne $script:connection) { break }
        }
    }
}
if ($null -eq $script:connection) { throw 'No se pudo conectar a MuOnline43. No se modificaron saldos.' }
try {
    if ($Install) {
        Read-PilotSql $installSql | Out-Null
        [IO.Directory]::CreateDirectory($PrivateRoot) | Out-Null
        if (-not (Test-Path -LiteralPath $tokenPath)) {
            $rng = [Security.Cryptography.RandomNumberGenerator]::Create()
            try { $bytes = New-Object byte[] 32; $rng.GetBytes($bytes) } finally { $rng.Dispose() }
            $token = ([BitConverter]::ToString($bytes)).Replace('-','').ToLowerInvariant()
            $file = [IO.File]::Open($tokenPath,[IO.FileMode]::CreateNew,[IO.FileAccess]::Write,[IO.FileShare]::None)
            try { $data = [Text.Encoding]::ASCII.GetBytes($token); $file.Write($data,0,$data.Length); $file.Flush($true) } finally { $file.Dispose() }
        }
        $desktop = Join-Path ([Environment]::GetFolderPath('Desktop')) 'sandbox-worker-token'
        Copy-Item -LiteralPath $tokenPath -Destination $desktop -Force
        Write-Host ('Preparado. Subi este archivo privado a cPanel /home/mupanic/payments-private/ con permiso 600: ' + $desktop)
        Write-Host 'Se creo el registro de prueba. No se acreditaron monedas ni se crearon cobros.'
        return
    }
    if (-not $Run -and -not $Revert) {
        $balance = Read-PilotSql "SELECT WCoinC FROM dbo.CashShopData WHERE AccountID='pruebacoin'"
        if ($balance.Rows.Count -eq 1) { Write-Host ('pruebacoin: ' + $balance.Rows[0].WCoinC + ' WCoin C. Solo consulta.') }
        Write-Host 'Opciones: -Install prepara; -Run entrega la compra aprobada; -Revert retira exactamente las 1000 monedas de esta prueba.'
        return
    }
    # A dedicated table is mandatory. No marker deletion can enable a second delivery.
    $installed = Read-PilotSql "SELECT OBJECT_ID('dbo.MUPanicUalaSandboxPilot','U') AS TableID"
    if ($installed.Rows[0].TableID -is [DBNull]) { throw 'Primero ejecuta -Install.' }
    if ($Revert) {
        $receipt = Read-PilotSql $revertSql
        Confirm-PilotReceipt $receipt.Rows[0]
        Write-Host 'Retiradas las 1000 monedas de la prueba (o ya estaban retiradas). El registro se conserva y no permite volver a acreditarlas.'
        return
    }
    $deadline = [DateTime]::UtcNow.AddSeconds($WatchSeconds)
    $bridgeFailures = 0
    do {
        # Recover a committed SQL receipt even if HTTP acknowledgment previously failed.
        $existing = Read-PilotSql 'SELECT OrderID,PaymentID,State,BeforeCoin,AfterCoin FROM dbo.MUPanicUalaSandboxPilot WHERE PilotKey=1'
        if ($existing.Rows.Count -eq 1) {
            Confirm-PilotReceipt $existing.Rows[0]
            Write-Host ('Prueba ya registrada: ' + $existing.Rows[0].State + '. No se sumaron monedas nuevamente.')
            break
        }
        try { $result = Invoke-PilotBridge @{ action='poll' } }
        catch {
            $bridgeFailures++
            Write-Host $_.Exception.Message
            if ($WatchSeconds -eq 0 -or $bridgeFailures -ge 3 -or [DateTime]::UtcNow -ge $deadline) { throw 'Consulta detenida. Conserva el registro y envia el codigo mostrado; no reinstales ni cambies el token.' }
            Write-Host 'Reintento de consulta en 15 segundos. No se ejecuta SQL de entrega.'
            Start-Sleep -Seconds 15
            continue
        }
        if ($null -ne $result.error -and [string]$result.error -ne '') {
            if ([string]$result.error -cnotmatch '^[A-Z0-9_]{1,64}$') { throw 'Diagnostico firmado invalido. No se acreditan monedas.' }
            $bridgeFailures++
            Write-Host ('La web no pudo verificar Uala. Codigo: ' + [string]$result.error)
            if ($WatchSeconds -eq 0 -or $bridgeFailures -ge 3 -or [DateTime]::UtcNow -ge $deadline) { throw 'Verificacion detenida sin entregar monedas. Envia el codigo mostrado.' }
            Start-Sleep -Seconds 15
            continue
        }
        $bridgeFailures = 0
        if ($null -ne $result.job) {
            $job = $result.job
            if ($job.account -cne 'pruebacoin' -or $job.environment -cne 'test' -or $job.coins -ne 1000 -or $job.price_cents -ne 100000 -or
                [string]$job.id -cnotmatch '^PANIC-[a-f0-9]{32}$' -or [string]$job.payment_id -cnotmatch '^[A-Za-z0-9_-]{1,120}$') { throw 'Orden fuera de esta prueba. No se acreditan monedas.' }
            $receipt = Read-PilotSql $creditSql $job
            if ($receipt.Rows.Count -ne 1) { throw 'Resultado SQL no confirmado. Reejecutar consulta es seguro: el registro SQL evita duplicados.' }
            Confirm-PilotReceipt $receipt.Rows[0]
            Write-Host ('Entrega confirmada para pruebacoin: ' + $receipt.Rows[0].BeforeCoin + ' -> ' + $receipt.Rows[0].AfterCoin + ' WCoin C. No se uso dinero real.')
            break
        }
        Write-Host ('Esperando el pago de prueba. Estado web: ' + [string]$result.payment_state + '. No se entregaron monedas en esta consulta.')
        if ($WatchSeconds -eq 0 -or [DateTime]::UtcNow -ge $deadline) { break }
        Start-Sleep -Seconds 15
    } while ([DateTime]::UtcNow -lt $deadline)
} finally { $script:connection.Dispose() }
