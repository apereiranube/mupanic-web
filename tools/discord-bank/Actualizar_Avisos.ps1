param(
    [ValidateSet('Aplicar','Restaurar')][string]$Action='Aplicar',
    [string]$Backup=''
)
$ErrorActionPreference='Stop'
$bot='C:\MU_PANIC_BOT'
$target=Join-Path $bot 'transferencias.js'
$source=Join-Path $PSScriptRoot 'transferencias.js'
$taskName='MU PANIC Discord Bot'
$oldHash='E03E2008598DCC39729D0AB840DAFE4050B85589411C87FC39262D899C4BDDA9'
$newHash='87E054C2C3548678890EFAEA56E788C1493543C6C3660F07495EB8A0D550BF34'
function FileHash([string]$Path){(Get-FileHash -LiteralPath $Path -Algorithm SHA256).Hash}
function Downloads {
    $p=Join-Path $env:USERPROFILE 'Downloads'
    try {
        $r=Get-ItemProperty -LiteralPath 'HKCU:\Software\Microsoft\Windows\CurrentVersion\Explorer\User Shell Folders'
        $v=$r.'{374DE290-123F-4565-9164-39C4925E467B}'
        if($v){$p=[Environment]::ExpandEnvironmentVariables($v)}
    }catch{}
    return $p
}
if(-not(Test-Path -LiteralPath $target)){throw 'Primero debe estar instalada la extension de transferencias V1.'}
if($Action -eq 'Aplicar') {
    if((FileHash $target) -eq $newHash){Write-Host 'Los avisos ya estan actualizados.' -ForegroundColor Green;return}
    if((FileHash $target) -ne $oldHash){throw 'El modulo del bot cambio. No se sobreescribio; hace falta revisar esa version.'}
    if((FileHash $source) -ne $newHash){throw 'El paquete esta incompleto o modificado.'}
    if(-not([IO.File]::ReadAllText((Join-Path $bot 'index.js')).Contains('MU_PANIC_TRANSFERENCIAS_V1'))){throw 'No se encontro la integracion original de transferencias.'}
}else{
    if(-not $Backup){throw 'Indica la carpeta de respaldo con -Backup.'}
    $source=Join-Path $Backup 'transferencias-anterior.js'
    if((FileHash $target) -ne $newHash -or (FileHash $source) -ne $oldHash){throw 'Los archivos cambiaron. No se sobreescribieron.'}
}
$node=(Get-Command node.exe -ErrorAction SilentlyContinue).Source
if(-not $node){$node='C:\Program Files\nodejs\node.exe'}
& $node --check $source
if($LASTEXITCODE -ne 0){throw 'La sintaxis del modulo no es valida.'}
$task=Get-ScheduledTask -TaskName $taskName
$enabled=[bool]$task.Settings.Enabled
$running=$task.State -eq 'Running'
$rollback=$null;$paused=$false;$changed=$false
if($Action -eq 'Aplicar'){
    $backupDir=Join-Path (Downloads) ('MU_PANIC_BACKUP_AVISOS_'+(Get-Date -Format 'yyyyMMdd_HHmmss')+'_'+[guid]::NewGuid().ToString('N').Substring(0,6))
    New-Item -ItemType Directory -Path $backupDir | Out-Null
    $rollback=Join-Path $backupDir 'transferencias-anterior.js'
    Copy-Item -LiteralPath $target -Destination $rollback
    if((FileHash $rollback) -ne $oldHash){throw 'No se pudo verificar el respaldo.'}
    Copy-Item -LiteralPath $PSCommandPath -Destination (Join-Path $backupDir 'Actualizar_Avisos.ps1')
    Set-Content -LiteralPath (Join-Path $backupDir 'RESTAURAR.ps1') -Encoding UTF8 -Value '& (Join-Path $PSScriptRoot "Actualizar_Avisos.ps1") -Action Restaurar -Backup $PSScriptRoot'
}
try {
    # Capture children before stopping the runner, which starts node with relative .\index.js.
    $processes=@(Get-CimInstance Win32_Process)
    $ids=@($processes | Where-Object {$_.CommandLine -like '*C:\MU_PANIC_BOT\RUN_BOT_24X7.ps1*'} | ForEach-Object {[int]$_.ProcessId})
    do {
        $children=@($processes | Where-Object {[int]$_.ParentProcessId -in $ids -and [int]$_.ProcessId -notin $ids} | ForEach-Object {[int]$_.ProcessId})
        $ids+=$children
    }while($children.Count)
    $paused=$true
    Disable-ScheduledTask -TaskName $taskName | Out-Null
    Stop-ScheduledTask -TaskName $taskName
    foreach($processId in $ids){Stop-Process -Id $processId -Force -ErrorAction SilentlyContinue}
    $processes | Where-Object {$_.Name -eq 'node.exe' -and $_.CommandLine -like '*C:\MU_PANIC_BOT*index.js*'} | ForEach-Object {Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue}
    $changed=$true
    Copy-Item -LiteralPath $source -Destination $target -Force
    $expected=if($Action -eq 'Aplicar'){$newHash}else{$oldHash}
    if((FileHash $target) -ne $expected){throw 'No se pudo verificar el modulo instalado.'}
    Write-Host 'Modulo de transferencias actualizado.' -ForegroundColor Green
    if($backupDir){Write-Host ('Respaldo y RESTAURAR.ps1: '+$backupDir)}
}catch{
    if($changed -and $rollback){Copy-Item -LiteralPath $rollback -Destination $target -Force}
    throw
}finally{
    if($paused){
        if($enabled){Enable-ScheduledTask -TaskName $taskName | Out-Null}
        if($running){Start-ScheduledTask -TaskName $taskName}
    }
}
