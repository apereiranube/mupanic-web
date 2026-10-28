# MU PANIC - Premios por votar (SOLO VPS). Lo ejecuta la tarea "MU PANIC Vote Worker" cada 1 minuto.
# Pide a la web los premios pendientes, los acredita en MuOnline43 (solo con la cuenta desconectada) y confirma el recibo.
param(
    [switch]$Run,
    [string]$PrivateRoot = 'C:\MuServer43\PaymentsPrivate',
    [string]$Endpoint = 'https://mupanic.com.ar/templates/mupanic/api/votar-worker.php'
)
$ErrorActionPreference = 'Stop'
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
if ($Endpoint -cne 'https://mupanic.com.ar/templates/mupanic/api/votar-worker.php' -and $Endpoint -notmatch '^http://127\.0\.0\.1:[0-9]{2,5}/') { throw 'Destino no permitido.' }
$tokenPath = Join-Path $PrivateRoot 'recharge-worker-token'
$settingsPath = Join-Path $PrivateRoot 'recharge-worker.json'
$logPath = Join-Path $PrivateRoot 'vote-worker.log'

function Write-VoteLog([string]$Message) {
    Write-Host $Message
    if (Test-Path -LiteralPath $PrivateRoot) {
        if ((Test-Path -LiteralPath $logPath) -and (Get-Item -LiteralPath $logPath).Length -gt 2097152) { Move-Item -LiteralPath $logPath -Destination ($logPath + '.previous') -Force }
        Add-Content -LiteralPath $logPath -Value ([DateTime]::Now.ToString('yyyy-MM-dd HH:mm:ss') + ' ' + $Message) -Encoding UTF8
    }
}
function Get-VoteHmac([string]$Message, [string]$Key) {
    $hmac = New-Object System.Security.Cryptography.HMACSHA256
    try {
        $hmac.Key = [Text.Encoding]::UTF8.GetBytes($Key)
        return ([BitConverter]::ToString($hmac.ComputeHash([Text.Encoding]::UTF8.GetBytes($Message)))).Replace('-','').ToLowerInvariant()
    } finally { $hmac.Dispose() }
}
function Get-VoteKey {
    if (-not (Test-Path -LiteralPath $tokenPath -PathType Leaf)) { throw 'Falta recharge-worker-token (el worker de recargas tiene que estar instalado).' }
    $token = [IO.File]::ReadAllText($tokenPath).Trim()
    if ($token -cnotmatch '^[a-f0-9]{64}$') { throw 'Token invalido.' }
    # Misma derivacion que la web: hash_hmac('sha256','votar',token)
    return (Get-VoteHmac 'votar' $token)
}
function Invoke-VoteBridge($Request) {
    $key = Get-VoteKey
    $Request.nonce = [Guid]::NewGuid().ToString('N')
    $body = $Request | ConvertTo-Json -Depth 6 -Compress
    $stamp = [string][DateTimeOffset]::UtcNow.ToUnixTimeSeconds()
    $headers = @{ 'X-Panic-Timestamp'=$stamp; 'X-Panic-Signature'=(Get-VoteHmac ($stamp + "`n" + $body) $key) }
    try { $response = Invoke-WebRequest -UseBasicParsing -Uri $Endpoint -Method Post -Headers $headers -ContentType 'application/json; charset=utf-8' -Body ([Text.Encoding]::UTF8.GetBytes($body)) -MaximumRedirection 0 -TimeoutSec 60 }
    catch {
        $httpCode = 0
        if ($null -ne $_.Exception.Response) { $httpCode = [int]$_.Exception.Response.StatusCode }
        throw ('Consulta web fallida. HTTP ' + $httpCode + '. 401: clave o reloj; 503: web sin configurar; 0: conexion. No se modificaron Eryns.')
    }
    $raw = [string]$response.Content
    if ($raw.Length -gt 16384) { throw 'Respuesta demasiado grande.' }
    $signature = [string]$response.Headers['X-Panic-Signature']
    if ($signature -cne (Get-VoteHmac ($Request.nonce + "`n" + $raw) $key)) { throw 'Firma de respuesta invalida. No se acreditan Eryns.' }
    $result = $raw | ConvertFrom-Json
    if ($result.nonce -cne $Request.nonce) { throw 'Respuesta de otra solicitud.' }
    return $result
}
function Open-VoteConnection {
    $instance = 'localhost'
    if (Test-Path -LiteralPath $settingsPath) {
        $saved = [IO.File]::ReadAllText($settingsPath) | ConvertFrom-Json
        if ([string]$saved.sql_server) { $instance = [string]$saved.sql_server }
    }
    $builder = New-Object System.Data.SqlClient.SqlConnectionStringBuilder
    $builder['Data Source'] = $instance
    $builder['Initial Catalog'] = 'MuOnline43'
    $builder['Connect Timeout'] = 5
    $builder['Application Name'] = 'MU PANIC Vote Worker'
    $builder['Integrated Security'] = $true
    $connection = New-Object System.Data.SqlClient.SqlConnection -ArgumentList ($builder.get_ConnectionString())
    $connection.Open()
    return $connection
}
function Read-VoteSql([string]$Sql, [hashtable]$Params) {
    $command = $script:VoteConn.CreateCommand()
    $command.CommandText = $Sql
    $command.CommandTimeout = 30
    if ($null -ne $Params) {
        foreach ($name in $Params.Keys) {
            $value = $Params[$name]
            if ($value -is [int]) { $command.Parameters.Add($name,[System.Data.SqlDbType]::Int).Value = $value }
            else { $command.Parameters.Add($name,[System.Data.SqlDbType]::VarChar,40).Value = [string]$value }
        }
    }
    $table = New-Object System.Data.DataTable
    $reader = $null
    try { $reader = $command.ExecuteReader(); $table.Load($reader) }
    finally { if ($null -ne $reader) { $reader.Dispose() }; $command.Dispose() }
    return ,$table
}
function ConvertTo-VoteReceipt($Row) {
    return @{ id=[string]$Row.VoteID; account=[string]$Row.AccountID; coins=[int]$Row.Coins; state='credited'; before_coin=[int]$Row.BeforeCoin; after_coin=[int]$Row.AfterCoin }
}
function Confirm-VoteReceipts($Receipts) {
    if (@($Receipts).Count -eq 0) { return 0 }
    $ack = Invoke-VoteBridge @{ action='ack'; results=@($Receipts) }
    $done = 0
    $ids = @($ack.ids)
    if ($null -eq $ack.ids -and [int]$ack.acknowledged -eq @($Receipts).Count) { $ids = @($Receipts | ForEach-Object { $_.id }) }
    foreach ($id in $ids) {
        if ([string]$id -cnotmatch '^V[a-f0-9]{32}$') { continue }
        Read-VoteSql 'EXEC dbo.MUPanicAckVoteReward @VoteID=@VoteID' @{ '@VoteID'=[string]$id } | Out-Null
        $done++
    }
    return $done
}

if (-not $Run) {
    Get-ScheduledTaskInfo -TaskName 'MU PANIC Vote Worker' -ErrorAction SilentlyContinue | Format-List LastRunTime,LastTaskResult,NextRunTime
    if (Test-Path -LiteralPath $logPath) { Get-Content -LiteralPath $logPath -Tail 15 }
    return
}

$mutex = New-Object Threading.Mutex($false,'Global\MU_PANIC_VOTE_WORKER')
$owns = $false
$script:VoteConn = $null
try {
    try { $owns = $mutex.WaitOne(0) } catch [Threading.AbandonedMutexException] { $owns = $true }
    if (-not $owns) { Write-VoteLog 'Ya hay otra ejecucion activa.'; return }
    $script:VoteConn = Open-VoteConnection
    # 1) Recibos ya acreditados en SQL que la web todavia no confirmo (por si se corto la conexion).
    $pending = Read-VoteSql 'SELECT TOP(20) VoteID,AccountID,Coins,BeforeCoin,AfterCoin FROM dbo.MUPanicVoteDeliveries WHERE AcknowledgedAt IS NULL ORDER BY CreditedAt' $null
    $old = @(); foreach ($row in $pending.Rows) { $old += ,(ConvertTo-VoteReceipt $row) }
    $recovered = Confirm-VoteReceipts $old
    # 2) Premios nuevos.
    $result = Invoke-VoteBridge @{ action='poll' }
    $items = @($result.items)
    if ($null -eq $result.items -or $items.Count -gt 20) { throw 'Cola invalida.' }
    $receipts = @(); $waiting = 0
    foreach ($item in $items) {
        if ([string]$item.id -cnotmatch '^V[a-f0-9]{32}$' -or [string]$item.account -cnotmatch '^[A-Za-z0-9_]{1,10}$' -or [string]$item.site -cnotmatch '^[a-z0-9]{1,20}$' -or
            ($item.coins -isnot [int] -and $item.coins -isnot [long]) -or $item.coins -lt 1 -or $item.coins -gt 1000) { throw 'Premio fuera de limites; no se acredita nada.' }
        $row = Read-VoteSql 'EXEC dbo.MUPanicApplyVoteReward @VoteID=@VoteID,@Site=@Site,@Account=@Account,@Coins=@Coins' @{ '@VoteID'=[string]$item.id; '@Site'=[string]$item.site; '@Account'=[string]$item.account; '@Coins'=[int]$item.coins }
        if ($row.Rows.Count -ne 1) { throw 'Resultado SQL no confirmado.' }
        if ($row.Rows[0].Result -ceq 'waiting_offline') { $waiting++; continue }
        if ($row.Rows[0].Result -cne 'credited') { throw 'Estado SQL inesperado.' }
        $receipts += ,(ConvertTo-VoteReceipt $row.Rows[0])
        Write-VoteLog ('Premio ' + [string]$item.id + ' (' + [string]$item.site + ') para ' + [string]$item.account + ': ' + $row.Rows[0].BeforeCoin + ' -> ' + $row.Rows[0].AfterCoin + ' Eryns.')
    }
    $confirmed = Confirm-VoteReceipts $receipts
    if ($items.Count -gt 0 -or $recovered -gt 0) { Write-VoteLog ('Listo. Pendientes en la web: ' + $items.Count + '; acreditados: ' + $receipts.Count + '; confirmados: ' + ($confirmed + $recovered) + '; esperando que se desconecten: ' + $waiting + '.') }
} catch {
    Write-VoteLog ('Ejecucion detenida: ' + $_.Exception.Message + ' Se reintenta en el proximo minuto.')
    throw
} finally {
    if ($null -ne $script:VoteConn) { $script:VoteConn.Dispose() }
    if ($owns) { $mutex.ReleaseMutex() }
    $mutex.Dispose()
}
