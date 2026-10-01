# Read-only collection of gameplay tables. Never copy SQL credentials or MainInfo.
param([string]$ServerRoot = 'C:\MuServer43')
$ErrorActionPreference = 'Stop'
$selected = @{}
$folders = @('Data\Monster', 'Data\MonsterSetBase', 'Data\Move',
    'Data\Item', 'Data\EventItemBag')
$specific = @('Data\MapManager.txt', 'Data\EventItemBagManager.txt',
    'Data\Util\ExperienceTable.txt', 'Data\Util\ResetTable.txt',
    'Data\Custom\CustomMonster.txt', 'Data\Custom\CustomMonsterTopHit.txt',
    'GameServer\Data\GameServerInfo - Common.dat',
    'GameServer\Data\GameServerInfo - ChaosMix.dat',
    'GameServer\Data\GameServerInfo - Monster.dat')
foreach ($folder in $folders) {
    $path = Join-Path $ServerRoot $folder
    if (Test-Path -LiteralPath $path -PathType Container) {
        Get-ChildItem -LiteralPath $path -Recurse -File -Filter '*.txt' |
            ForEach-Object { $selected[$_.FullName] = $_ }
    }
}
foreach ($relative in $specific) {
    $path = Join-Path $ServerRoot $relative
    if (Test-Path -LiteralPath $path -PathType Leaf) {
        $selected[$path] = Get-Item -LiteralPath $path
    }
}
# Public ID-to-model associations, if present in the client's configuration tools.
$toolsPath = Join-Path $ServerRoot 'Tools'
if (Test-Path -LiteralPath $toolsPath) {
    Get-ChildItem -LiteralPath $toolsPath -Recurse -File |
        Where-Object { $_.Name -in @('CustomMonster.txt','CustomNpc.txt','CustomMonsterName.txt') } |
        ForEach-Object { $selected[$_.FullName] = $_ }
}
if (-not $selected.Count) { throw 'No se encontraron tablas. Revisá -ServerRoot.' }
$tempAtlasServer = Join-Path ([IO.Path]::GetTempPath()) ('PANIC_SERVER_' + [guid]::NewGuid().ToString('N'))
$output = Join-Path ([Environment]::GetFolderPath('Desktop')) ('MU_PANIC_SERVIDOR_' + (Get-Date -Format 'yyyyMMdd_HHmmss') + '.zip')
try {
    New-Item -ItemType Directory -Path $tempAtlasServer | Out-Null
    foreach ($file in $selected.Values) {
        $relative = $file.FullName.Substring($ServerRoot.TrimEnd('\').Length).TrimStart('\')
        $destination = Join-Path $tempAtlasServer $relative
        New-Item -ItemType Directory -Path (Split-Path $destination -Parent) -Force | Out-Null
        Copy-Item -LiteralPath $file.FullName -Destination $destination
    }
    Compress-Archive -Path (Join-Path $tempAtlasServer '*') -DestinationPath $output
    Write-Host "Listo: $output"
    Write-Host "$($selected.Count) tablas. Subí este ZIP al chat. No se modificó el servidor."
} finally {
    if (Test-Path -LiteralPath $tempAtlasServer) { Remove-Item -LiteralPath $tempAtlasServer -Recurse -Force }
}
