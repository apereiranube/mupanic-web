# Extend the existing Atlas task. Keep its frequency, balance URL and private token.
[CmdletBinding()]
param([string]$Revision = 'beta')
$ErrorActionPreference = 'Stop'
if ($Revision -notmatch '^(beta|[a-f0-9]{40})$') { throw 'Revision invalida.' }
$atlasFolder = 'C:\MuServer43\AtlasSync'
$atlasPython = 'C:\Python313\python.exe'
$atlasToken = 'C:\MuServer43\AtlasPrivate\token'
$atlasRunner = Join-Path $atlasFolder 'run_atlas.cmd'
foreach ($atlasPath in @($atlasPython,$atlasToken,$atlasRunner)) {
    if (!(Test-Path -LiteralPath $atlasPath -PathType Leaf)) { throw "Falta el archivo: $atlasPath" }
}
$atlasTask = Get-ScheduledTask -TaskName 'MU PANIC Atlas Sync' -ErrorAction SilentlyContinue
if (!$atlasTask) { throw 'No se encontro la tarea existente de Atlas.' }
if ($atlasTask.State -eq 'Running') { throw 'Atlas esta ejecutandose. Repeti cuando termine.' }
$atlasStage = Join-Path $env:TEMP ('panic-event-sync-' + [guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Path $atlasStage | Out-Null
try {
    foreach ($atlasFile in @('atlas_events.py','sync_atlas_events.py')) {
        Invoke-WebRequest "https://raw.githubusercontent.com/apereiranube/mupanic-web/$Revision/tools/$atlasFile" -OutFile (Join-Path $atlasStage $atlasFile)
        & $atlasPython -m py_compile (Join-Path $atlasStage $atlasFile)
        if ($LASTEXITCODE -ne 0) { throw "No se pudo validar: $atlasFile" }
    }
    $atlasCommand = Get-Content -LiteralPath $atlasRunner -Raw
    if ($atlasCommand -notmatch 'sync_atlas.py' -or $atlasCommand -notmatch '(?im)^exit /b %errorlevel%\s*$') { throw 'No se reconoce el wrapper de Atlas. No se modifico.' }
    $atlasBackup = Join-Path 'C:\MuServer43\AtlasPrivate' ('events-exporter-backup-' + (Get-Date -Format 'yyyyMMdd_HHmmss'))
    New-Item -ItemType Directory -Path $atlasBackup | Out-Null
    foreach ($atlasFile in @('atlas_events.py','sync_atlas_events.py','run_atlas.cmd')) {
        $atlasOriginal = Join-Path $atlasFolder $atlasFile
        if (Test-Path -LiteralPath $atlasOriginal) { Copy-Item -LiteralPath $atlasOriginal -Destination $atlasBackup }
    }
    foreach ($atlasFile in @('atlas_events.py','sync_atlas_events.py')) {
        Copy-Item -LiteralPath (Join-Path $atlasStage $atlasFile) -Destination (Join-Path $atlasFolder $atlasFile) -Force
    }
    & $atlasPython (Join-Path $atlasFolder 'sync_atlas_events.py') --server-root 'C:\MuServer43' --url 'https://beta.mupanic.com.ar/templates/mupanic/api/atlas-events-sync.php' --token-file $atlasToken --force
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo publicar. Desplega beta en cPanel y repeti; la tarea original no se modifico.' }
    if ($atlasCommand -notmatch 'sync_atlas_events.py') {
        $atlasExtra = @'
set "atlasBalanceResult=%errorlevel%"
"C:\Python313\python.exe" "C:\MuServer43\AtlasSync\sync_atlas_events.py" --server-root "C:\MuServer43" --url "https://beta.mupanic.com.ar/templates/mupanic/api/atlas-events-sync.php" --token-file "C:\MuServer43\AtlasPrivate\token" >> "C:\MuServer43\AtlasPrivate\last-run.log" 2>&1
if not "%atlasBalanceResult%"=="0" exit /b %atlasBalanceResult%
exit /b %errorlevel%
'@
        $atlasCommand = [regex]::Replace($atlasCommand,'(?im)^exit /b %errorlevel%\s*$',{param($m) $atlasExtra})
        Set-Content -LiteralPath $atlasRunner -Value $atlasCommand -Encoding ASCII
    }
    Write-Host 'Eventos publicados en beta. La tarea existente conserva su frecuencia y la sincronizacion de drops.'
    Write-Host 'Configuracion del juego, procesos y credenciales sin cambios.'
} finally { Remove-Item -LiteralPath $atlasStage -Recurse -Force }
