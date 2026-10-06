# Update only the Atlas exporter. Keep game files, private token and task settings.
[CmdletBinding()]
param([string]$Revision = 'main')
$ErrorActionPreference = 'Stop'
$atlasFolder = 'C:\MuServer43\AtlasSync'
$atlasPython = 'C:\Python313\python.exe'
$atlasToken = 'C:\MuServer43\AtlasPrivate\token'
$atlasRunner = Join-Path $atlasFolder 'run_atlas.cmd'
foreach ($atlasPath in @($atlasPython, $atlasToken, $atlasRunner)) {
    if (!(Test-Path -LiteralPath $atlasPath -PathType Leaf)) { throw "Falta el archivo: $atlasPath" }
}
if ($Revision -notmatch '^(main|[a-f0-9]{40})$') { throw 'Revision invalida.' }
$atlasTask = Get-ScheduledTask -TaskName 'MU PANIC Atlas Sync' -ErrorAction SilentlyContinue
if ($atlasTask -and $atlasTask.State -eq 'Running') { throw 'Atlas Sync esta ejecutandose. Repeti cuando termine.' }
$atlasStage = Join-Path $env:TEMP ('panic-atlas-update-' + [guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Path $atlasStage | Out-Null
try {
    foreach ($atlasFile in @('sync_atlas.py','build_public_balance.py','atlas_event_bags.py')) {
        Invoke-WebRequest "https://raw.githubusercontent.com/apereiranube/mupanic-web/$Revision/tools/$atlasFile" -OutFile (Join-Path $atlasStage $atlasFile)
        & $atlasPython -m py_compile (Join-Path $atlasStage $atlasFile)
        if ($LASTEXITCODE -ne 0) { throw "No se pudo validar: $atlasFile" }
    }
    # The existing task runs this wrapper. Route future snapshots to the updated main receiver.
    $atlasCommand = Get-Content -LiteralPath $atlasRunner -Raw
    $atlasCommand = [regex]::Replace($atlasCommand, '--url\s+"https://(?:beta\.)?mupanic\.com\.ar/(?:beta/)?templates/mupanic/api/atlas-sync\.php"', '--url "https://mupanic.com.ar/templates/mupanic/api/atlas-sync.php"')
    if ($atlasCommand -notmatch '--url\s+"https://mupanic\.com\.ar/templates/mupanic/api/atlas-sync\.php"') { throw 'No se reconoce la URL del sincronizador. Se conservaron los backups.' }
    $atlasBackup = Join-Path 'C:\MuServer43\AtlasPrivate' ('exporter-backup-' + (Get-Date -Format 'yyyyMMdd_HHmmss'))
    New-Item -ItemType Directory -Path $atlasBackup | Out-Null
    foreach ($atlasFile in @('sync_atlas.py','build_public_balance.py','atlas_event_bags.py','run_atlas.cmd')) {
        $atlasOriginal = Join-Path $atlasFolder $atlasFile
        if (Test-Path -LiteralPath $atlasOriginal) { Copy-Item -LiteralPath $atlasOriginal -Destination $atlasBackup }
    }
    foreach ($atlasFile in @('sync_atlas.py','build_public_balance.py','atlas_event_bags.py')) {
        Copy-Item -LiteralPath (Join-Path $atlasStage $atlasFile) -Destination (Join-Path $atlasFolder $atlasFile) -Force
    }
    Set-Content -LiteralPath $atlasRunner -Value $atlasCommand -Encoding ASCII
    & $atlasPython (Join-Path $atlasFolder 'sync_atlas.py') --server-root 'C:\MuServer43' --url 'https://mupanic.com.ar/templates/mupanic/api/atlas-sync.php' --token-file $atlasToken --force
    if ($LASTEXITCODE -ne 0) { throw 'El exportador se actualizo, pero el hosting rechazo la publicacion. Verifica que main este desplegado y repeti.' }
    Write-Host 'Atlas actualizado y publicado. La tarea conserva su frecuencia. Configuracion del juego sin cambios.'
} finally {
    Remove-Item -LiteralPath $atlasStage -Recurse -Force
}
