# Update only the Atlas exporter. Keep game files, private token and task settings.
[CmdletBinding()]
param([string]$Revision = 'beta', [ValidateSet('VPS')][string]$Equipo = 'VPS')
if ($Equipo -ne 'VPS') { throw 'SOLO VPS.' }
$ErrorActionPreference = 'Stop'
$atlasFolder = 'C:\MuServer43\AtlasSync'
$atlasPython = 'C:\Python313\python.exe'
$atlasToken = 'C:\MuServer43\AtlasPrivate\token'
$atlasRunner = Join-Path $atlasFolder 'run_atlas.cmd'
foreach ($atlasPath in @($atlasPython, $atlasToken, $atlasRunner)) {
    if (!(Test-Path -LiteralPath $atlasPath -PathType Leaf)) { throw "Falta el archivo: $atlasPath" }
}
if ($Revision -notmatch '^(main|beta|[a-f0-9]{40})$') { throw 'Revision invalida.' }
$atlasTask = Get-ScheduledTask -TaskName 'MU PANIC Atlas Sync' -ErrorAction SilentlyContinue
if ($atlasTask -and $atlasTask.State -eq 'Running') { throw 'Atlas Sync esta ejecutandose. Repeti cuando termine.' }
$atlasStage = Join-Path $env:TEMP ('panic-atlas-update-' + [guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Path $atlasStage | Out-Null
try {
    foreach ($atlasFile in @('sync_atlas.py','build_public_balance.py','atlas_event_bags.py','atlas_hunting.py','atlas_events.py','sync_atlas_events.py')) {
        Invoke-WebRequest "https://raw.githubusercontent.com/apereiranube/mupanic-web/$Revision/tools/$atlasFile" -OutFile (Join-Path $atlasStage $atlasFile)
        & $atlasPython -m py_compile (Join-Path $atlasStage $atlasFile)
        if ($LASTEXITCODE -ne 0) { throw "No se pudo validar: $atlasFile" }
    }
    # Keep the existing receiver and task wrapper. Do not redirect beta/production.
    $atlasCommand = Get-Content -LiteralPath $atlasRunner -Raw
    $atlasUrlMatch = [regex]::Match($atlasCommand, '--url\s+"(https://(?:beta\.)?mupanic\.com\.ar/(?:beta/)?templates/mupanic/api/atlas-sync\.php)"')
    if (-not $atlasUrlMatch.Success) { throw 'No se reconoce la URL del sincronizador. Sin cambios.' }
    $atlasUrl = $atlasUrlMatch.Groups[1].Value
    $atlasEventUrlMatch = [regex]::Match($atlasCommand, '(?m)^.*sync_atlas_events\.py.*--url\s+"(https://(?:beta\.)?mupanic\.com\.ar/(?:beta/)?templates/mupanic/api/atlas-events-sync\.php)"')
    $atlasDownloads = Join-Path ([Environment]::GetFolderPath('UserProfile')) 'Downloads'
    $atlasBackup = Join-Path $atlasDownloads ('MU_PANIC_ATLAS_SYNC_V3_RESPALDO_' + (Get-Date -Format 'yyyyMMdd_HHmmss') + '_' + [guid]::NewGuid().ToString('N').Substring(0,8))
    New-Item -ItemType Directory -Path $atlasBackup | Out-Null
    $atlasRecords = @()
    foreach ($atlasFile in @('sync_atlas.py','build_public_balance.py','atlas_event_bags.py','atlas_hunting.py','atlas_events.py','sync_atlas_events.py')) {
        $atlasOriginal = Join-Path $atlasFolder $atlasFile
        $beforeHash = $null
        if (Test-Path -LiteralPath $atlasOriginal -PathType Leaf) {
            Copy-Item -LiteralPath $atlasOriginal -Destination $atlasBackup
            $beforeHash = (Get-FileHash -LiteralPath $atlasOriginal -Algorithm SHA256).Hash
            if ((Get-FileHash -LiteralPath (Join-Path $atlasBackup $atlasFile) -Algorithm SHA256).Hash -ne $beforeHash) { throw 'El exportador cambio durante el backup. Sin cambios.' }
        }
        $afterHash = (Get-FileHash -LiteralPath (Join-Path $atlasStage $atlasFile) -Algorithm SHA256).Hash
        $atlasRecords += [pscustomobject]@{Name=$atlasFile;Before=$beforeHash;After=$afterHash}
    }
    $atlasRecords | ConvertTo-Json | Set-Content -LiteralPath (Join-Path $atlasBackup 'manifest.json') -Encoding UTF8
    @'
param([Parameter(Mandatory=$true)][ValidateSet('VPS')][string]$Equipo)
$ErrorActionPreference='Stop'
$root='C:\MuServer43\AtlasSync'
$task=Get-ScheduledTask -TaskName 'MU PANIC Atlas Sync' -ErrorAction SilentlyContinue
if($task -and $task.State -eq 'Running'){throw 'Atlas Sync esta ejecutandose. Repeti cuando termine.'}
$records=@(Get-Content -LiteralPath (Join-Path $PSScriptRoot 'manifest.json') -Raw | ConvertFrom-Json)
foreach($r in $records){
 if($r.Name -notin @('sync_atlas.py','build_public_balance.py','atlas_event_bags.py','atlas_hunting.py','atlas_events.py','sync_atlas_events.py')){throw 'Manifest no valido.'}
 $dest=Join-Path $root $r.Name
 $hash=if(Test-Path -LiteralPath $dest -PathType Leaf){(Get-FileHash -LiteralPath $dest -Algorithm SHA256).Hash}else{$null}
 if($hash -ne $r.Before -and $hash -ne $r.After){throw ('Hay cambios posteriores en '+$r.Name+'. Sin restaurar.')}
 if($r.Before -and (Get-FileHash -LiteralPath (Join-Path $PSScriptRoot $r.Name) -Algorithm SHA256).Hash -ne $r.Before){throw 'Backup alterado. Sin restaurar.'}
}
foreach($r in $records){
 $dest=Join-Path $root $r.Name
 if($r.Before){Copy-Item -LiteralPath (Join-Path $PSScriptRoot $r.Name) -Destination $dest -Force}else{if(Test-Path -LiteralPath $dest){Remove-Item -LiteralPath $dest -Force}}
}
Write-Host 'Exportador restaurado. No se modificaron el juego ni la copia publicada del Atlas.'
'@ | Set-Content -LiteralPath (Join-Path $atlasBackup 'REVERTIR.ps1') -Encoding UTF8
    Write-Host ('Backup del exportador: ' + $atlasBackup)
    Write-Host ('Restaurar: powershell.exe -NoProfile -ExecutionPolicy Bypass -File "' + (Join-Path $atlasBackup 'REVERTIR.ps1') + '" -Equipo VPS')
    foreach ($atlasRecord in $atlasRecords) {
        $atlasOriginal = Join-Path $atlasFolder $atlasRecord.Name
        $atlasHash = if (Test-Path -LiteralPath $atlasOriginal -PathType Leaf) { (Get-FileHash -LiteralPath $atlasOriginal -Algorithm SHA256).Hash } else { $null }
        if ($atlasHash -ne $atlasRecord.Before) { throw 'El exportador cambio despues del backup. Usa el restaurador si se copiaron archivos.' }
        Copy-Item -LiteralPath (Join-Path $atlasStage $atlasRecord.Name) -Destination $atlasOriginal -Force
        if ((Get-FileHash -LiteralPath $atlasOriginal -Algorithm SHA256).Hash -ne $atlasRecord.After) { throw 'La copia no se pudo verificar. Usa el restaurador.' }
    }
    & $atlasPython (Join-Path $atlasFolder 'sync_atlas.py') --server-root 'C:\MuServer43' --url $atlasUrl --token-file $atlasToken --force
    if ($LASTEXITCODE -ne 0) { throw 'El exportador se actualizo, pero no se confirmo la publicacion. El hosting conserva la ultima copia aceptada.' }
    if ($atlasEventUrlMatch.Success) {
        & $atlasPython (Join-Path $atlasFolder 'sync_atlas_events.py') --server-root 'C:\MuServer43' --url $atlasEventUrlMatch.Groups[1].Value --token-file $atlasToken --force
        if ($LASTEXITCODE -ne 0) { throw 'Spots y drops publicados; no se confirmo la actualizacion de eventos. Repeti la sincronizacion.' }
    }
    Write-Host 'Atlas actualizado y publicado. La tarea conserva su frecuencia. Configuracion del juego sin cambios.'
} finally {
    Remove-Item -LiteralPath $atlasStage -Recurse -Force
}
