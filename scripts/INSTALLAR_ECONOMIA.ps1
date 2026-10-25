[CmdletBinding()]
param(
    [ValidateSet('Estado','Aplicar','Restaurar')][string]$Accion='Estado',
    [Parameter(Mandatory=$true)][string]$CmsRoot,
    [string]$SshTarget,
    [string]$BackupPath
)
$ErrorActionPreference='Stop'
# cPanel hosts the website; this script never connects to SQL Server.
$PackageRoot=Split-Path -Parent $PSScriptRoot
$Installer=Join-Path $PSScriptRoot 'economy-install.sh'
if (!(Test-Path -LiteralPath $Installer -PathType Leaf)) { throw 'Falta economy-install.sh.' }
if ($Accion -eq 'Restaurar' -and [string]::IsNullOrWhiteSpace($BackupPath)) { throw 'Restaurar requiere -BackupPath con el respaldo exacto.' }
function Quote-Bash([string]$Value) {
    if ($Value -match "[\r\n]" -or $Value.Contains([char]0)) { throw 'Parametro invalido.' }
    return "'" + $Value.Replace("'", "'\''") + "'"
}
if ([string]::IsNullOrWhiteSpace($SshTarget)) {
    # Also usable under PowerShell on Linux or against an accessible local CMS.
    if (!(Get-Command bash -ErrorAction SilentlyContinue)) { throw 'Para cPanel desde Windows usá -SshTarget usuario@host con el acceso SSH del hosting.' }
    $Arguments=@($Installer,$Accion,$CmsRoot)
    if ($Accion -eq 'Restaurar') { $Arguments+=$BackupPath }
    & bash @Arguments
    if ($LASTEXITCODE -ne 0) { throw 'La operacion no se completo.' }
    exit 0
}
if ($SshTarget -notmatch '^[A-Za-z0-9_][A-Za-z0-9_.-]*@[A-Za-z0-9][A-Za-z0-9.-]*$') { throw 'Usá -SshTarget usuario@host. Para puerto y clave propios configurá ~/.ssh/config.' }
foreach ($Tool in @('ssh','scp')) { if (!(Get-Command $Tool -ErrorAction SilentlyContinue)) { throw "Falta OpenSSH: $Tool" } }
$RemoteDirectory=(& ssh -- $SshTarget 'umask 077; mktemp -d /tmp/mupanic-economy.XXXXXXXX').Trim()
if ($LASTEXITCODE -ne 0 -or $RemoteDirectory -notmatch '^/tmp/mupanic-economy\.[A-Za-z0-9]+$') { throw 'No se pudo preparar el hosting.' }
$LocalStage=Join-Path ([System.IO.Path]::GetTempPath()) ('mupanic-eco-' + [guid]::NewGuid().ToString('N'))
try {
    # Use the exact manifest even when invoked from the complete Git checkout.
    $LocalOverlay=Join-Path $LocalStage 'overlay'
    New-Item -ItemType Directory -Path $LocalOverlay -Force | Out-Null
    foreach ($Relative in Get-Content -LiteralPath (Join-Path $PSScriptRoot 'economy-files.txt')) {
        if ($Relative -notmatch '^templates/mupanic/' -or $Relative.Contains('..')) { throw 'Ruta fuera del alcance autorizado.' }
        $SourceFile=Join-Path (Join-Path $PackageRoot 'overlay') $Relative
        $StageFile=Join-Path $LocalOverlay $Relative
        New-Item -ItemType Directory -Path (Split-Path -Parent $StageFile) -Force | Out-Null
        Copy-Item -LiteralPath $SourceFile -Destination $StageFile
    }
    & scp -r -- $LocalOverlay "${SshTarget}:$RemoteDirectory/overlay"
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo enviar el modulo.' }
    & ssh -- $SshTarget ('mkdir -p ' + (Quote-Bash "$RemoteDirectory/scripts"))
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo preparar el instalador.' }
    foreach ($Name in @('economy-install.sh','economy-files.txt','economy-sha256.txt')) {
        & scp -- (Join-Path $PSScriptRoot $Name) "${SshTarget}:$RemoteDirectory/scripts/$Name"
        if ($LASTEXITCODE -ne 0) { throw "No se pudo enviar $Name" }
    }
    $Command='bash ' + (Quote-Bash "$RemoteDirectory/scripts/economy-install.sh") + ' ' + (Quote-Bash $Accion) + ' ' + (Quote-Bash $CmsRoot)
    if ($Accion -eq 'Restaurar') { $Command+=' ' + (Quote-Bash $BackupPath) }
    & ssh -- $SshTarget $Command
    if ($LASTEXITCODE -ne 0) { throw 'La operacion remota no se completo. Conservá la ruta del respaldo informada.' }
} finally {
    if (Test-Path -LiteralPath $LocalStage) { Remove-Item -LiteralPath $LocalStage -Recurse -Force }
    # Only the unique staging directory generated above; persistent backups stay intact.
    & ssh -- $SshTarget ('rm -rf -- ' + (Quote-Bash $RemoteDirectory))
}
