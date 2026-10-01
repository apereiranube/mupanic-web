# Run in an elevated Windows PowerShell on the MU PANIC VPS.
[CmdletBinding()]
param([ValidateRange(5,1440)][int]$IntervalMinutes = 30)
$ErrorActionPreference = 'Stop'
$atlasPython = 'C:\Python313\python.exe'
$atlasRoot = 'C:\MuServer43'
$atlasTaskName = 'MU PANIC Atlas Sync'
foreach ($atlasPath in @($atlasPython, "$atlasRoot\AtlasSync\sync_atlas.py", "$atlasRoot\AtlasPrivate\token")) {
    if (-not (Test-Path -LiteralPath $atlasPath -PathType Leaf)) { throw "Falta el archivo: $atlasPath" }
}
$atlasRunner = "$atlasRoot\AtlasSync\run_atlas.cmd"
@'
@echo off
echo %date% %time% > "C:\MuServer43\AtlasPrivate\last-run.log"
"C:\Python313\python.exe" "C:\MuServer43\AtlasSync\sync_atlas.py" --server-root "C:\MuServer43" --url "https://mupanic.com.ar/beta/templates/mupanic/api/atlas-sync.php" --token-file "C:\MuServer43\AtlasPrivate\token" >> "C:\MuServer43\AtlasPrivate\last-run.log" 2>&1
exit /b %errorlevel%
'@ | Set-Content -LiteralPath $atlasRunner -Encoding ASCII
$atlasAction = New-ScheduledTaskAction -Execute "$env:SystemRoot\System32\cmd.exe" -Argument '/d /c C:\MuServer43\AtlasSync\run_atlas.cmd' -WorkingDirectory "$atlasRoot\AtlasSync"
$atlasTrigger = New-ScheduledTaskTrigger -Once -At ((Get-Date).AddMinutes(1)) -RepetitionInterval (New-TimeSpan -Minutes $IntervalMinutes)
$atlasPrincipal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount -RunLevel Highest
$atlasSettings = New-ScheduledTaskSettingsSet -StartWhenAvailable -MultipleInstances IgnoreNew -ExecutionTimeLimit (New-TimeSpan -Minutes 3)
Register-ScheduledTask -TaskName $atlasTaskName -Action $atlasAction -Trigger $atlasTrigger -Principal $atlasPrincipal -Settings $atlasSettings -Description "Public Atlas configuration sync every $IntervalMinutes minutes" -Force | Out-Null
Start-ScheduledTask -TaskName $atlasTaskName
Write-Host "Tarea creada: $atlasTaskName. Intervalo: $IntervalMinutes minutos."
Write-Host 'Registro de la ultima ejecucion: C:\MuServer43\AtlasPrivate\last-run.log'
Write-Host 'Comprobar el resultado cuando termine con: Get-ScheduledTaskInfo -TaskName "MU PANIC Atlas Sync"'
