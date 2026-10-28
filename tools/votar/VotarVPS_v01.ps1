# MU PANIC - Premios por votar en el VPS (v01). SOLO VPS.
# Agrega: tabla dbo.MUPanicVoteDeliveries + procedimientos de entrega en MuOnline43, el script vote_worker.ps1
# y la tarea programada "MU PANIC Vote Worker" (cada 1 minuto, como SYSTEM). No toca recargas, GameServer ni el juego.
param([ValidateSet('Estado','Aplicar','Restaurar')][string]$Accion = 'Estado')
$ErrorActionPreference = 'Stop'
$Here = Split-Path -Parent $PSCommandPath
$PrivateRoot = 'C:\MuServer43\PaymentsPrivate'
$SyncRoot = 'C:\MuServer43\RechargeSync'
$WorkerDest = Join-Path $SyncRoot 'vote_worker.ps1'
$TaskName = 'MU PANIC Vote Worker'
$Expected = @{
    'payload\vote_worker.ps1' = '1A52A0B57F08E1E714F4ADC57825CD427AB797CDA611B22375924DCDC65298A8'
    'payload\votos.sql'       = '34EEEB73D4F0A15C6DFE72AEB6E280368EBA435FFF9CC3FF8FC45E010D9A7F36'
}
$dl = (New-Object -ComObject Shell.Application).NameSpace('shell:Downloads').Self.Path
$Report = Join-Path $dl ('VotarVPS_v01-' + $Accion + '-' + (Get-Date -Format 'yyyyMMdd-HHmmss') + '.txt')
Start-Transcript -LiteralPath $Report | Out-Null

function Stop-Detenido([string]$Why) { Write-Host ('DETENIDO: ' + $Why + ' No se modifico nada mas.') -ForegroundColor Red; Stop-Transcript | Out-Null; exit 1 }
function Get-Sha([string]$Path) { return (Get-FileHash -LiteralPath $Path -Algorithm SHA256).Hash.ToUpperInvariant() }
function Open-Sql([System.Management.Automation.PSCredential]$Credential) {
    $instance = 'localhost'
    $settings = Join-Path $PrivateRoot 'recharge-worker.json'
    if (Test-Path -LiteralPath $settings) { $saved = [IO.File]::ReadAllText($settings) | ConvertFrom-Json; if ([string]$saved.sql_server) { $instance = [string]$saved.sql_server } }
    $b = New-Object System.Data.SqlClient.SqlConnectionStringBuilder
    $b['Data Source'] = $instance; $b['Initial Catalog'] = 'MuOnline43'; $b['Connect Timeout'] = 5; $b['Application Name'] = 'MU PANIC Votar instalador'
    if ($null -eq $Credential) { $b['Integrated Security'] = $true } else { $b['User ID'] = $Credential.UserName; $b['Password'] = $Credential.GetNetworkCredential().Password }
    $c = New-Object System.Data.SqlClient.SqlConnection -ArgumentList ($b.get_ConnectionString())
    try { $c.Open(); return $c } catch { $c.Dispose(); return $null }
}
function Get-SqlConnection {
    $c = Open-Sql $null
    if ($null -eq $c) {
        $cred = Get-Credential -Message 'SQL local MuOnline43 (usuario con permisos de administrador). No se guarda.'
        if ($null -ne $cred) { $c = Open-Sql $cred }
    }
    return $c
}
function Get-SqlValue($Conn, [string]$Sql) {
    $cmd = $Conn.CreateCommand(); try { $cmd.CommandText = $Sql; $cmd.CommandTimeout = 30; return $cmd.ExecuteScalar() } finally { $cmd.Dispose() }
}
function Show-Estado {
    Write-Host '--- Estado premios por votar ---' -ForegroundColor Cyan
    Write-Host ('Worker de recargas (clave compartida): ' + $(if (Test-Path -LiteralPath (Join-Path $PrivateRoot 'recharge-worker-token')) { 'instalado' } else { 'NO ENCONTRADO' }))
    if (Test-Path -LiteralPath $WorkerDest) {
        $ok = (Get-Sha $WorkerDest) -eq $Expected['payload\vote_worker.ps1']
        Write-Host ('vote_worker.ps1: presente' + $(if ($ok) { ' (version v01)' } else { ' (OTRA VERSION)' }))
    } else { Write-Host 'vote_worker.ps1: no instalado' }
    $task = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
    if ($null -eq $task) { Write-Host 'Tarea programada: no existe' }
    else { $info = Get-ScheduledTaskInfo -TaskName $TaskName; Write-Host ('Tarea programada: ' + $task.State + ' | ultima: ' + $info.LastRunTime + ' | resultado: ' + $info.LastTaskResult + ' | proxima: ' + $info.NextRunTime) }
    $conn = Open-Sql $null
    if ($null -ne $conn) {
        try {
            $exists = Get-SqlValue $conn "SELECT CASE WHEN OBJECT_ID('dbo.MUPanicVoteDeliveries','U') IS NULL THEN 0 ELSE 1 END"
            if ([int]$exists -eq 1) {
                $total = Get-SqlValue $conn 'SELECT COUNT(*) FROM dbo.MUPanicVoteDeliveries'
                $coins = Get-SqlValue $conn 'SELECT ISNULL(SUM(Coins),0) FROM dbo.MUPanicVoteDeliveries'
                $unack = Get-SqlValue $conn 'SELECT COUNT(*) FROM dbo.MUPanicVoteDeliveries WHERE AcknowledgedAt IS NULL'
                Write-Host ('SQL: premios entregados ' + $total + ' (' + $coins + ' Eryns); sin confirmar a la web: ' + $unack)
            } else { Write-Host 'SQL: tabla de premios no instalada' }
        } finally { $conn.Dispose() }
    } else { Write-Host 'SQL: no se pudo conectar con la sesion actual (solo lectura de estado).' }
    $log = Join-Path $PrivateRoot 'vote-worker.log'
    if (Test-Path -LiteralPath $log) { Write-Host '--- Ultimas lineas del log ---' -ForegroundColor Cyan; Get-Content -LiteralPath $log -Tail 12 }
}

$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) { Stop-Detenido 'Abri PowerShell como Administrador.' }

if ($Accion -eq 'Estado') { Show-Estado; Stop-Transcript | Out-Null; Write-Host ('Reporte: ' + $Report); exit 0 }

if ($Accion -eq 'Restaurar') {
    $task = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
    if ($null -ne $task) { Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false; Write-Host 'Tarea programada eliminada.' }
    if (Test-Path -LiteralPath $WorkerDest) {
        if ((Get-Sha $WorkerDest) -ne $Expected['payload\vote_worker.ps1']) { Stop-Detenido 'vote_worker.ps1 no es la version v01; no lo borro.' }
        Remove-Item -LiteralPath $WorkerDest -Force; Write-Host 'vote_worker.ps1 eliminado.'
    }
    Write-Host 'Listo: ya no se entregan premios por votar desde el VPS. La tabla SQL con los recibos se conserva (no afecta al juego).' -ForegroundColor Green
    Stop-Transcript | Out-Null; Write-Host ('Reporte: ' + $Report); exit 0
}

# ---------- Aplicar ----------
Write-Host '1/5 Verificando archivos del paquete...' -ForegroundColor Cyan
foreach ($rel in $Expected.Keys) {
    $p = Join-Path $Here $rel
    if (-not (Test-Path -LiteralPath $p)) { Stop-Detenido ('Falta ' + $rel + ' en el paquete.') }
    if ((Get-Sha $p) -ne $Expected[$rel]) { Stop-Detenido ($rel + ' no coincide con el hash esperado.') }
}
if (-not (Test-Path -LiteralPath (Join-Path $PrivateRoot 'recharge-worker-token'))) { Stop-Detenido 'No esta instalado el worker de recargas (falta su clave en C:\MuServer43\PaymentsPrivate).' }
if (-not (Test-Path -LiteralPath $SyncRoot)) { Stop-Detenido 'No existe C:\MuServer43\RechargeSync.' }
if ((Test-Path -LiteralPath $WorkerDest) -and (Get-Sha $WorkerDest) -ne $Expected['payload\vote_worker.ps1']) { Stop-Detenido 'Ya existe un vote_worker.ps1 de otra version.' }

Write-Host '2/5 Instalando tabla y procedimientos en MuOnline43 (objetos nuevos)...' -ForegroundColor Cyan
$conn = Get-SqlConnection
if ($null -eq $conn) { Stop-Detenido 'No se pudo conectar a MuOnline43.' }
try {
    $sql = [IO.File]::ReadAllText((Join-Path $Here 'payload\votos.sql'))
    foreach ($batch in [regex]::Split($sql,'(?im)^GO\s*$')) {
        if ([string]::IsNullOrWhiteSpace($batch)) { continue }
        $cmd = $conn.CreateCommand(); try { $cmd.CommandText = $batch; $cmd.CommandTimeout = 60; $cmd.ExecuteNonQuery() | Out-Null } finally { $cmd.Dispose() }
    }
    $check = Get-SqlValue $conn "SELECT CASE WHEN OBJECT_ID('dbo.MUPanicVoteDeliveries','U') IS NOT NULL AND OBJECT_ID('dbo.MUPanicApplyVoteReward','P') IS NOT NULL AND OBJECT_ID('dbo.MUPanicAckVoteReward','P') IS NOT NULL THEN 1 ELSE 0 END"
    if ([int]$check -ne 1) { Stop-Detenido 'Los objetos SQL no quedaron creados.' }
} finally { $conn.Dispose() }
Write-Host '   SQL OK.'

Write-Host '3/5 Copiando vote_worker.ps1...' -ForegroundColor Cyan
Copy-Item -LiteralPath (Join-Path $Here 'payload\vote_worker.ps1') -Destination $WorkerDest -Force
if ((Get-Sha $WorkerDest) -ne $Expected['payload\vote_worker.ps1']) { Stop-Detenido 'La copia de vote_worker.ps1 no quedo bien.' }

Write-Host '4/5 Creando la tarea programada (cada 1 minuto, como SYSTEM)...' -ForegroundColor Cyan
$action = New-ScheduledTaskAction -Execute 'powershell.exe' -Argument ('-NoProfile -NonInteractive -ExecutionPolicy Bypass -File "' + $WorkerDest + '" -Run') -WorkingDirectory $SyncRoot
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(1) -RepetitionInterval (New-TimeSpan -Minutes 1)
$principal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount -RunLevel Highest
$settings = New-ScheduledTaskSettingsSet -MultipleInstances IgnoreNew -StartWhenAvailable -ExecutionTimeLimit (New-TimeSpan -Minutes 3)
Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger -Principal $principal -Settings $settings -Force | Out-Null

Write-Host '5/5 Primera ejecucion de prueba...' -ForegroundColor Cyan
Start-ScheduledTask -TaskName $TaskName
$deadline = (Get-Date).AddSeconds(60)
do { Start-Sleep -Seconds 3; $t = Get-ScheduledTask -TaskName $TaskName } while ($t.State -eq 'Running' -and (Get-Date) -lt $deadline)
$info = Get-ScheduledTaskInfo -TaskName $TaskName
Write-Host ('Resultado de la tarea: ' + $info.LastTaskResult + ' (0 = OK)')
$log = Join-Path $PrivateRoot 'vote-worker.log'
if (Test-Path -LiteralPath $log) { Get-Content -LiteralPath $log -Tail 8 }
if ($info.LastTaskResult -eq 0) { Write-Host 'LISTO: los premios por votar ya se acreditan solos (con la cuenta desconectada).' -ForegroundColor Green }
else { Write-Host 'La tarea quedo creada pero la primera ejecucion fallo. Pasame este reporte.' -ForegroundColor Yellow }
Stop-Transcript | Out-Null
Write-Host ('Reporte: ' + $Report)
