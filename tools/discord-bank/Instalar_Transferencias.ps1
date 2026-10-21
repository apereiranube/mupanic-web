param(
    [ValidateSet('Aplicar','Restaurar','Estado')][string]$Action='Estado',
    [string]$Backup=''
)
$ErrorActionPreference='Stop'
$bot='C:\MU_PANIC_BOT'
$worker='C:\MuServer43\RechargeSync\recharge_worker.ps1'
$private='C:\MuServer43\PaymentsPrivate'
$taskNames=@('MU PANIC Discord Bot','MU PANIC Recharge Worker')
function FileHash([string]$Path) { (Get-FileHash -LiteralPath $Path -Algorithm SHA256).Hash }
function Downloads {
    $p=Join-Path $env:USERPROFILE 'Downloads'
    try {
        $r=Get-ItemProperty -LiteralPath 'HKCU:\Software\Microsoft\Windows\CurrentVersion\Explorer\User Shell Folders'
        $v=$r.'{374DE290-123F-4565-9164-39C4925E467B}'
        if(-not [string]::IsNullOrWhiteSpace($v)) { $p=[Environment]::ExpandEnvironmentVariables($v) }
    } catch {}
    return $p
}
function PauseTasks {
    # Capture descendants before stopping the runner (node uses relative .\index.js).
    $processes=@(Get-CimInstance Win32_Process)
    $ids=@($processes | Where-Object { $_.CommandLine -and ($_.CommandLine -like '*C:\MU_PANIC_BOT\RUN_BOT_24X7.ps1*' -or $_.CommandLine -like '*C:\MuServer43\RechargeSync\recharge_worker.ps1*') } | ForEach-Object { [int]$_.ProcessId })
    do {
        $children=@($processes | Where-Object { [int]$_.ParentProcessId -in $ids -and [int]$_.ProcessId -notin $ids } | ForEach-Object { [int]$_.ProcessId })
        $ids+= $children
    } while($children.Count)
    foreach($n in $taskNames) { Disable-ScheduledTask -TaskName $n | Out-Null; Stop-ScheduledTask -TaskName $n }
    foreach($processId in $ids) { Stop-Process -Id $processId -Force -ErrorAction SilentlyContinue }
    $processes | Where-Object { $_.Name -eq 'node.exe' -and $_.CommandLine -like '*C:\MU_PANIC_BOT*index.js*' } | ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue }
}
function RestartTasks($states) {
    foreach($s in $states) {
        if($s.Enabled) { Enable-ScheduledTask -TaskName $s.Name | Out-Null }
        if($s.Running) { Start-ScheduledTask -TaskName $s.Name }
    }
}
if($Action -eq 'Estado') {
    foreach($n in $taskNames) {
        Get-ScheduledTask -TaskName $n | Select-Object TaskName,State
        Get-ScheduledTaskInfo -TaskName $n | Select-Object LastRunTime,LastTaskResult,NextRunTime
    }
    Write-Host ('Extension de transferencias: '+(Test-Path -LiteralPath (Join-Path $bot 'transferencias.js')))
    exit 0
}
$states=@();$mutated=$false;$backupDir=$null;$tasksPaused=$false
try {
    foreach($n in $taskNames) {
        $t=Get-ScheduledTask -TaskName $n
        $states+= [pscustomobject]@{Name=$n;Enabled=[bool]$t.Settings.Enabled;Running=($t.State -eq 'Running')}
    }
    if($Action -eq 'Restaurar') {
        if(-not $Backup) { throw 'Indica la carpeta de respaldo con -Backup.' }
        $entries=ConvertFrom-Json -InputObject (Get-Content -LiteralPath (Join-Path $Backup 'archivos.json') -Raw)
        $allowed=@((Join-Path $bot 'index.js'),(Join-Path $bot 'transferencias.js'),$worker)
        foreach($e in $entries) {
            if($e.Target -notin $allowed) { throw 'Ruta de respaldo invalida.' }
            if((FileHash $e.Target) -ne $e.NewHash) { throw ('Hay cambios posteriores en '+$e.Target+'. No se sobreescribieron.') }
            if($e.Existed -and (FileHash (Join-Path $Backup $e.BackupFile)) -ne $e.OldHash) { throw 'Respaldo invalido.' }
        }
        $tasksPaused=$true
        PauseTasks
        foreach($e in $entries) {
            if($e.Existed) { Copy-Item -LiteralPath (Join-Path $Backup $e.BackupFile) -Destination $e.Target -Force }
            else { Remove-Item -LiteralPath $e.Target }
        }
        Write-Host 'Archivos anteriores restaurados. Las ordenes y los recibos SQL se conservaron.' -ForegroundColor Green
        return
    }
    foreach($p in @((Join-Path $bot 'index.js'),$worker,(Join-Path $private 'recharge-worker.json'),(Join-Path $private 'recharge-worker-token'))) {
        if(-not (Test-Path -LiteralPath $p -PathType Leaf)) { throw ('Falta '+$p) }
    }
    $node=(Get-Command node.exe -ErrorAction SilentlyContinue).Source
    if(-not $node) { $node='C:\Program Files\nodejs\node.exe' }
    if(-not (Test-Path -LiteralPath $node)) { throw 'No se encontro Node.js.' }
    $indexPath=Join-Path $bot 'index.js'
    $oldIndex=[IO.File]::ReadAllText($indexPath)
    if($oldIndex.Contains('MU_PANIC_TRANSFERENCIAS_V1')) { throw 'Esta extension ya esta instalada. Usa -Action Estado.' }
    $sha=[Security.Cryptography.SHA256]::Create()
    try { $canonicalHash=[BitConverter]::ToString($sha.ComputeHash([Text.Encoding]::UTF8.GetBytes($oldIndex))).Replace('-','') } finally { $sha.Dispose() }
    if($canonicalHash -ne 'EF8B00260581537207B360DE58F8446AB679709346D7D15450DCDEF40FAF3F5D') { throw 'El bot cambio desde la lectura. No se modifico nada; envia una lectura actualizada.' }
    $needles=@('  await guild.commands.set(commands);','    if (await require("./novedades").handle(interaction)) return;','client.login(TOKEN);')
    foreach($n in $needles) { if(([regex]::Matches($oldIndex,[regex]::Escape($n))).Count -ne 1) { throw 'La estructura del bot es diferente.' } }
    $newIndex=$oldIndex.Replace($needles[0],"  commands.push(...require('./transferencias').commands);`r`n"+$needles[0])
    $newIndex=$newIndex.Replace($needles[1],"    if (await require('./transferencias').handle(interaction, client)) return;`r`n"+$needles[1])
    $newIndex=$newIndex.Replace($needles[2],"// MU_PANIC_TRANSFERENCIAS_V1`r`nrequire('./transferencias').install(client);`r`n"+$needles[2])
    $backupDir=Join-Path (Downloads) ('MU_PANIC_BACKUP_TRANSFERENCIAS_'+(Get-Date -Format 'yyyyMMdd_HHmmss')+'_'+[guid]::NewGuid().ToString('N').Substring(0,6))
    New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
    $stage=Join-Path $backupDir 'preparado';New-Item -ItemType Directory -Path $stage | Out-Null
    $stagedIndex=Join-Path $stage 'index.js'
    [IO.File]::WriteAllText($stagedIndex,$newIndex,(New-Object Text.UTF8Encoding($false)))
    & $node --check $stagedIndex
    if($LASTEXITCODE -ne 0) { throw 'La sintaxis del bot no paso la verificacion.' }
    & $node --check (Join-Path $PSScriptRoot 'transferencias.js')
    if($LASTEXITCODE -ne 0) { throw 'La extension no paso la verificacion.' }
    $workerSource=Join-Path $PSScriptRoot 'recharge_worker.ps1'
    $parseTokens=$null;$parseErrors=$null
    [Management.Automation.Language.Parser]::ParseFile($workerSource,[ref]$parseTokens,[ref]$parseErrors) | Out-Null
    if($parseErrors.Count -gt 0) { throw 'La sintaxis del servicio no paso la verificacion.' }
    $plans=@(
        @{Target=$indexPath;Source=$stagedIndex;BackupFile='index-original.js'},
        @{Target=(Join-Path $bot 'transferencias.js');Source=(Join-Path $PSScriptRoot 'transferencias.js');BackupFile='transferencias-original.js'},
        @{Target=$worker;Source=$workerSource;BackupFile='recharge_worker-original.ps1'}
    )
    $entries=@()
    foreach($plan in $plans) {
        $exists=Test-Path -LiteralPath $plan.Target -PathType Leaf
        $oldHash=$null
        if($exists) {
            $oldHash=FileHash $plan.Target
            Copy-Item -LiteralPath $plan.Target -Destination (Join-Path $backupDir $plan.BackupFile)
            if((FileHash (Join-Path $backupDir $plan.BackupFile)) -ne $oldHash) { throw 'No se pudo verificar el respaldo.' }
        }
        $entries+= [pscustomobject]@{Target=$plan.Target;Existed=$exists;OldHash=$oldHash;NewHash=(FileHash $plan.Source);BackupFile=$plan.BackupFile}
    }
    ConvertTo-Json -InputObject $entries -Depth 5 | Set-Content -LiteralPath (Join-Path $backupDir 'archivos.json') -Encoding UTF8
    Copy-Item -LiteralPath $PSCommandPath -Destination (Join-Path $backupDir 'Instalar_Transferencias.ps1')
    $restore='& (Join-Path $PSScriptRoot "Instalar_Transferencias.ps1") -Action Restaurar -Backup $PSScriptRoot'
    Set-Content -LiteralPath (Join-Path $backupDir 'RESTAURAR.ps1') -Value $restore -Encoding UTF8
    $tasksPaused=$true
    PauseTasks
    $settings=ConvertFrom-Json -InputObject (Get-Content -LiteralPath (Join-Path $private 'recharge-worker.json') -Raw)
    if($settings.environment -notin @('test','production') -or -not $settings.sql_server) { throw 'Configuracion del servicio invalida.' }
    # Backward-compatible SQL migration; never recreates orders, balances or token.
    & powershell.exe -NoProfile -ExecutionPolicy Bypass -File $workerSource -Install -UpgradeExisting -SqlServer $settings.sql_server -Environment $settings.environment
    if($LASTEXITCODE -ne 0) { throw 'No se pudo actualizar el SQL de entregas. Los archivos del bot se conservaron.' }
    $mutated=$true
    foreach($plan in $plans) { Copy-Item -LiteralPath $plan.Source -Destination $plan.Target -Force }
    foreach($e in $entries) { if((FileHash $e.Target) -ne $e.NewHash) { throw ('Fallo de verificacion: '+$e.Target) } }
    Write-Host 'Extension instalada. No se tocaron .env, token, credenciales SQL ni GameServers.' -ForegroundColor Green
    Write-Host ('Respaldo y RESTAURAR.ps1: '+$backupDir)
} catch {
    if($mutated -and $backupDir) {
        foreach($e in $entries) {
            if($e.Existed) { Copy-Item -LiteralPath (Join-Path $backupDir $e.BackupFile) -Destination $e.Target -Force }
            elseif(Test-Path -LiteralPath $e.Target) { Remove-Item -LiteralPath $e.Target }
        }
    }
    throw
} finally {
    if($tasksPaused) { RestartTasks $states }
}
if($Action -eq 'Aplicar') { Start-ScheduledTask -TaskName 'MU PANIC Recharge Worker' }
