# Exporta solo arte de minimapas y tablas de recetas/ítems del cliente.
# No modifica el cliente ni copia executables, MainInfo, configuración o credenciales.
param([string]$ClientRoot = 'C:\Cliente_louis update 43')
$ErrorActionPreference = 'Stop'
$clientData = Join-Path $ClientRoot 'Data'
if (-not (Test-Path -LiteralPath $clientData -PathType Container)) { throw "No existe $clientData. Indicá la carpeta de tu cliente con -ClientRoot." }
$allowedImageExtensions = @('.ozj','.ozt','.png','.jpg','.jpeg','.tga','.dds','.bmp')
$atlasFiles = @(Get-ChildItem -LiteralPath $clientData -Recurse -File | Where-Object {
    $relative = $_.FullName.Substring($clientData.Length).TrimStart('\')
    $isImage = $_.Extension.ToLowerInvariant() -in $allowedImageExtensions
    $isMapFolder = $relative -match '(^|\\)(MiniMap|Maps)(\\|$)'
    $isNamedMap = $_.BaseName -match '^(mini[_-]?map\d*|map\d*|world\d+)$'
    ($isImage -and ($isMapFolder -or $isNamedMap)) -or ($_.Name -match '^(Mix|Item)\.bmd$' -and $relative -match '^Local\\')
})
if ($atlasFiles.Count -eq 0) { throw 'No se encontraron minimapas ni Mix.bmd. Revisá la ruta del cliente.' }
$totalBytes = ($atlasFiles | Measure-Object -Property Length -Sum).Sum
if ($totalBytes -gt 30MB) { throw 'La selección supera 30 MB. Enviá primero el listado de archivos para elegir el arte correcto.' }
$tempRoot = Join-Path ([IO.Path]::GetTempPath()) ('MU_PANIC_ATLAS_' + [guid]::NewGuid().ToString('N'))
$output = Join-Path ([Environment]::GetFolderPath('Desktop')) ('MU_PANIC_ATLAS_CLIENTE_' + (Get-Date -Format 'yyyyMMdd_HHmmss') + '.zip')
try {
    New-Item -ItemType Directory -Path $tempRoot | Out-Null
    foreach ($file in $atlasFiles) {
        $relative = $file.FullName.Substring($clientData.Length).TrimStart('\')
        $destination = Join-Path $tempRoot $relative
        New-Item -ItemType Directory -Path (Split-Path $destination -Parent) -Force | Out-Null
        Copy-Item -LiteralPath $file.FullName -Destination $destination
    }
    Compress-Archive -Path (Join-Path $tempRoot '*') -DestinationPath $output
    Write-Host "Listo: $output"
    Write-Host "$($atlasFiles.Count) archivos públicos de arte y tablas. Subí ese ZIP al chat."
} finally {
    if (Test-Path -LiteralPath $tempRoot) { Remove-Item -LiteralPath $tempRoot -Recurse -Force }
}
