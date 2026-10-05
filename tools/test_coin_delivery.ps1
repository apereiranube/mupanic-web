param(
    [string]$SqlServer = '',
    [System.Management.Automation.PSCredential]$SqlCredential,
    [switch]$AddOneCoin,
    [string]$PrivateRoot = 'C:\MuServer43\PaymentsPrivate'
)
$ErrorActionPreference = 'Stop'
# Default is read only. -AddOneCoin adds ONE WCoin C to pruebacoin only.
# No passwords or connection strings are exported.
function Open-WalletConnection([string]$Instance, $Credential) {
    $builder = New-Object System.Data.SqlClient.SqlConnectionStringBuilder
    # PowerShell adapts this builder as a dictionary: use SQL keywords explicitly.
    $builder['Data Source'] = $Instance
    $builder['Initial Catalog'] = 'MuOnline43'
    $builder['Connect Timeout'] = 5
    $builder['Application Name'] = 'MU PANIC Controlled One WCoin Test'
    if ($null -eq $Credential) { $builder['Integrated Security'] = $true }
    else {
        $builder['User ID'] = $Credential.UserName
        $builder['Password'] = $Credential.GetNetworkCredential().Password
    }
    $connection = New-Object System.Data.SqlClient.SqlConnection -ArgumentList ($builder.get_ConnectionString())
    try { $connection.Open(); return $connection }
    catch { $connection.Dispose(); return $null }
}
function Read-WalletTable([string]$Sql) {
    $command = $script:connection.CreateCommand()
    $command.CommandText = $Sql
    $command.CommandTimeout = 10
    $command.Parameters.Add('@Account', [System.Data.SqlDbType]::VarChar, 10).Value = 'pruebacoin'
    $table = New-Object System.Data.DataTable
    $reader = $null
    try { $reader = $command.ExecuteReader(); $table.Load($reader) }
    finally { if ($null -ne $reader) { $reader.Dispose() }; $command.Dispose() }
    return ,$table
}
$instances = @()
if ($SqlServer) { $instances = @($SqlServer) }
else {
    foreach ($registryPath in @('HKLM:\SOFTWARE\Microsoft\Microsoft SQL Server\Instance Names\SQL', 'HKLM:\SOFTWARE\WOW6432Node\Microsoft\Microsoft SQL Server\Instance Names\SQL')) {
        if (Test-Path $registryPath) {
            foreach ($property in (Get-ItemProperty $registryPath).PSObject.Properties) {
                if ($property.Name -notlike 'PS*') {
                    if ($property.Name -eq 'MSSQLSERVER') { $instances += 'localhost' }
                    else { $instances += ('localhost\' + $property.Name) }
                }
            }
        }
    }
    $instances += @('localhost', 'localhost\SQLEXPRESS')
    $instances = @($instances | Select-Object -Unique)
}
$script:connection = $null
foreach ($instance in $instances) {
    $script:connection = Open-WalletConnection $instance $SqlCredential
    if ($null -ne $script:connection) { break }
}
if ($null -eq $script:connection -and $null -eq $SqlCredential) {
    Write-Host 'Windows authentication could not open MuOnline43. Enter SQL credentials locally; they are NOT exported.'
    $SqlCredential = Get-Credential -Message 'SQL login for the authorized pruebacoin test'
    if ($null -ne $SqlCredential) {
        foreach ($instance in $instances) {
            $script:connection = Open-WalletConnection $instance $SqlCredential
            if ($null -ne $script:connection) { break }
        }
    }
}
if ($null -eq $script:connection) { throw 'Could not connect to MuOnline43. Run again with -SqlServer YOUR_INSTANCE. No balances were changed.' }
try {
    $snapshot = Read-WalletTable @'
SELECT c.AccountID, c.WCoinC, c.WCoinP, c.GoblinPoint, s.ConnectStat
FROM dbo.CashShopData c
LEFT JOIN dbo.MEMB_STAT s ON s.memb___id=c.AccountID
WHERE c.AccountID=@Account
'@
    if ($snapshot.Rows.Count -ne 1) { throw 'No existe el saldo de pruebacoin. Entra al juego y abre el Cash Shop antes de probar. No se creo ni modifico ningun saldo.' }
    $before = $snapshot.Rows[0]
    Write-Host ('Cuenta: pruebacoin | WCoin C: ' + $before.WCoinC + ' | ConnectStat: ' + $before.ConnectStat)
    if (-not $AddOneCoin) { Write-Host 'Solo consulta. No se modifico el saldo.'; return }
    if ($before.ConnectStat -is [DBNull] -or [int]$before.ConnectStat -ne 1) { throw 'La prueba requiere pruebacoin conectada al juego. No se modifico el saldo.' }

    # Reserve the attempt BEFORE SQL. A crash/timeout leaves this marker in place.
    # It blocks replay; never remove automatically when the result is uncertain.
    [System.IO.Directory]::CreateDirectory($PrivateRoot) | Out-Null
    $marker = Join-Path $PrivateRoot 'pruebacoin-one-coin-test.json'
    $reservation = $null
    try { $reservation = [System.IO.File]::Open($marker, [System.IO.FileMode]::CreateNew, [System.IO.FileAccess]::Write, [System.IO.FileShare]::None) }
    catch { throw ('Prueba ya reservada o no se pudo crear el registro. NO se sumaron monedas en esta ejecucion. Usa la consulta sin -AddOneCoin. Registro: ' + $marker) }
    try {
        $reserved = [ordered]@{ account='pruebacoin'; state='reserved'; amount=1; observedBefore=[int]$before.WCoinC; createdUtc=[DateTime]::UtcNow.ToString('o') }
        $bytes = [System.Text.Encoding]::UTF8.GetBytes(($reserved | ConvertTo-Json))
        $reservation.Write($bytes,0,$bytes.Length)
        $reservation.Flush($true)
    } finally { $reservation.Dispose() }

    $result = Read-WalletTable @'
SET NOCOUNT ON;
SET XACT_ABORT ON;
SET LOCK_TIMEOUT 5000;
BEGIN TRY
    BEGIN TRANSACTION;
    DECLARE @Online tinyint, @BeforeC int, @BeforeP int, @BeforeG int;
    SELECT @Online=ConnectStat FROM dbo.MEMB_STAT WITH (UPDLOCK,HOLDLOCK) WHERE memb___id=@Account;
    IF @Online IS NULL OR @Online<>1 RAISERROR('pruebacoin debe estar conectada.',16,1);
    SELECT @BeforeC=WCoinC,@BeforeP=WCoinP,@BeforeG=GoblinPoint
    FROM dbo.CashShopData WITH (UPDLOCK,HOLDLOCK) WHERE AccountID=@Account;
    IF @BeforeC IS NULL OR @BeforeC<0 OR @BeforeC>=2147483647
        RAISERROR('Saldo ausente o fuera de rango.',16,1);
    EXEC dbo.WZ_SetCoin @Account=@Account,@Name='',@Value1=1,@Value2=0,@Value3=0;
    SET NOCOUNT ON;
    SET XACT_ABORT ON;
    DECLARE @AfterC int, @AfterP int, @AfterG int;
    SELECT @AfterC=WCoinC,@AfterP=WCoinP,@AfterG=GoblinPoint FROM dbo.CashShopData WHERE AccountID=@Account;
    IF @AfterC IS NULL OR @AfterC<>@BeforeC+1 OR @AfterP<>@BeforeP OR @AfterG<>@BeforeG
        RAISERROR('Resultado inesperado. Se revierte la prueba.',16,1);
    COMMIT TRANSACTION;
    SELECT @BeforeC AS BeforeWCoinC,@AfterC AS AfterWCoinC;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT>0 ROLLBACK TRANSACTION;
    DECLARE @Message nvarchar(2048)=ERROR_MESSAGE();
    RAISERROR('%s',16,1,@Message);
END CATCH;
'@
    if ($result.Rows.Count -ne 1) { throw ('Resultado no confirmado. NO repitas la suma. Consulta el saldo. Registro: ' + $marker) }
    $receipt = [ordered]@{ account='pruebacoin'; state='sql_committed'; amount=1; before=[int]$result.Rows[0].BeforeWCoinC; after=[int]$result.Rows[0].AfterWCoinC; completedUtc=[DateTime]::UtcNow.ToString('o') }
    [System.IO.File]::WriteAllText($marker,($receipt | ConvertTo-Json),(New-Object System.Text.UTF8Encoding($false)))
    Write-Host ('Prueba aplicada: ' + $receipt.before + ' -> ' + $receipt.after + ' WCoin C. WCoin P y Goblin Points no cambiaron.')
    Write-Host 'Comproba el saldo en el juego: cerrar y abrir tienda; luego salir por completo y volver a entrar. No compres ni gastes monedas durante la prueba.'
    Write-Host ('Registro: ' + $marker)
} finally {
    $script:connection.Dispose()
}
