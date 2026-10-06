# Read-only export of event gameplay configuration. No SQL, token or executable files.
param([string]$ServerRoot = 'C:\MuServer43')
$ErrorActionPreference = 'Stop'
$atlasEventFiles = @{}
$atlasEventRoot = Join-Path $ServerRoot 'Data\Event'
if (Test-Path -LiteralPath $atlasEventRoot) {
    Get-ChildItem -LiteralPath $atlasEventRoot -Recurse -File |
        Where-Object { $_.Extension -in @('.dat','.txt') } |
        ForEach-Object { $atlasEventFiles[$_.FullName] = $_ }
}
$atlasEventBags = Join-Path $ServerRoot 'Data\EventItemBag'
if (Test-Path -LiteralPath $atlasEventBags) {
    Get-ChildItem -LiteralPath $atlasEventBags -Recurse -File -Filter '*.txt' |
        ForEach-Object { $atlasEventFiles[$_.FullName] = $_ }
}
foreach ($relative in @('GameServer\Data\GameServerInfo - Event.dat','Data\EventItemBagManager.txt','Data\Custom\CustomArena.txt','Data\Custom\CustomEventDrop.txt','Data\MonsterSetBase\Invasion\InvasionSetBase.txt')) {
    $atlasEventPath = Join-Path $ServerRoot $relative
    if (Test-Path -LiteralPath $atlasEventPath -PathType Leaf) { $atlasEventFiles[$atlasEventPath] = Get-Item -LiteralPath $atlasEventPath }
}
if (!(Test-Path -LiteralPath (Join-Path $ServerRoot 'GameServer\Data\GameServerInfo - Event.dat'))) { throw 'No se encontro la configuracion de eventos. Revisa -ServerRoot.' }
$atlasEventStage = Join-Path $env:TEMP ('panic-atlas-events-' + [guid]::NewGuid().ToString('N'))
$atlasEventZip = Join-Path ([Environment]::GetFolderPath('Desktop')) ('MU_PANIC_EVENTOS_' + (Get-Date -Format 'yyyyMMdd_HHmmss') + '.zip')
try {
    New-Item -ItemType Directory -Path $atlasEventStage | Out-Null
    foreach ($file in $atlasEventFiles.Values) {
        $relative = $file.FullName.Substring($ServerRoot.TrimEnd('\').Length).TrimStart('\')
        $target = Join-Path $atlasEventStage $relative
        New-Item -ItemType Directory -Path (Split-Path $target -Parent) -Force | Out-Null
        Copy-Item -LiteralPath $file.FullName -Destination $target
    }
    Compress-Archive -Path (Join-Path $atlasEventStage '*') -DestinationPath $atlasEventZip
    Write-Host "Listo: $atlasEventZip"
    Write-Host "$($atlasEventFiles.Count) archivos de eventos y recompensas. No se modifico el servidor ni se exportaron credenciales."
} finally {
    if (Test-Path -LiteralPath $atlasEventStage) { Remove-Item -LiteralPath $atlasEventStage -Recurse -Force }
}
