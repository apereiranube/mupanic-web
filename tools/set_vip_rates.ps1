param([string]$ServerRoot='C:\MuServer43',[switch]$Apply,[switch]$StartServers)
$ErrorActionPreference='Stop'
$root=(Resolve-Path -LiteralPath $ServerRoot).Path.TrimEnd('\')
$encoding=[Text.Encoding]::GetEncoding(28591)
$plans=@()
$report=@()
foreach ($server in @('GameServer','GameServerCS')) {
    $file=Join-Path $root ($server+'\Data\GameServerInfo - Common.dat')
    $bytes=[IO.File]::ReadAllBytes($file)
    $content=$encoding.GetString($bytes)
    foreach ($setting in @('AddExperienceRate','ItemDropRate')) {
        foreach ($level in 0..3) {
            $key=$setting+'_AL'+$level
            $pattern='(?m)^([ \t]*'+[regex]::Escape($key)+'[ \t]*=[ \t]*)(\d+)(?=[ \t\r\n;]|$)'
            $found=[regex]::Matches($content,$pattern)
            if ($found.Count -ne 1) { throw ('Falta o esta duplicada '+$key+' en '+$file+'. No se instalo nada.') }
            $value=if ($setting -eq 'AddExperienceRate') {if ($level -eq 0) {15} else {20}} else {if ($level -eq 0) {25} else {30}}
            $report+=[pscustomobject]@{Server=$server;Key=$key;Before=$found[0].Groups[2].Value;After=$value}
            # Replace only the numeric span; preserve every unrelated byte.
            $number=$found[0].Groups[2]
            $content=$content.Remove($number.Index,$number.Length).Insert($number.Index,[string]$value)
        }
    }
    $plans+=@{server=$server;file=$file;before=$bytes;after=$encoding.GetBytes($content);hash=(Get-FileHash -LiteralPath $file -Algorithm SHA256).Hash}
}
$report|Format-Table -AutoSize
if (-not $Apply) {Write-Host 'Solo vista previa. Usa -Apply para instalar con respaldo.';return}
if (@(Get-Process -Name 'GameServer','GameServerCS' -ErrorAction SilentlyContinue).Count -gt 0) {
    throw 'Cerra GameServer y GameServerCS antes de aplicar. No se modifico nada.'
}
if ($StartServers) {
    foreach ($plan in $plans) {
        if (-not (Test-Path -LiteralPath (Join-Path $root ($plan.server+'\'+$plan.server+'.exe')) -PathType Leaf)) {throw ('Falta el ejecutable '+$plan.server+'. No se modifico nada.')}
    }
}
$backupRoot=Join-Path $root ('VipRateBackups\'+(Get-Date -Format 'yyyyMMdd_HHmmss')+'_'+[Guid]::NewGuid().ToString('N').Substring(0,6))
[IO.Directory]::CreateDirectory($backupRoot)|Out-Null
foreach ($plan in $plans) {
    if ((Get-FileHash -LiteralPath $plan.file -Algorithm SHA256).Hash -cne $plan.hash) {throw ('El archivo cambio durante la preparacion: '+$plan.file)}
    [IO.File]::WriteAllBytes((Join-Path $backupRoot ($plan.server+'.dat')),$plan.before)
}
[IO.File]::WriteAllText((Join-Path $backupRoot 'changes.json'),($report|ConvertTo-Json -Depth 4),(New-Object Text.UTF8Encoding($false)))
$written=@()
try {
    foreach ($plan in $plans) {
        if ((Get-FileHash -LiteralPath $plan.file -Algorithm SHA256).Hash -cne $plan.hash) {throw ('El archivo cambio antes de instalar: '+$plan.file)}
        $written+=,$plan
        [IO.File]::WriteAllBytes($plan.file,$plan.after)
        if ([Convert]::ToBase64String([IO.File]::ReadAllBytes($plan.file)) -cne [Convert]::ToBase64String($plan.after)) {throw ('No se pudo verificar '+$plan.file)}
    }
} catch {
    foreach ($plan in $written) {[IO.File]::WriteAllBytes($plan.file,$plan.before)}
    throw
}
Write-Host ('Instalado en ambos GameServer. Respaldo: '+$backupRoot)
Write-Host 'Normal: EXP 15 / drop 25. VIP AL1-AL3: EXP 20 / drop 30.'
Write-Host 'No se modificaron Master EXP, tablas de drops, monedas ni cuentas.'
if ($StartServers) {
    Start-Process -FilePath (Join-Path $root 'GameServer\GameServer.exe') -WorkingDirectory (Join-Path $root 'GameServer')
    Start-Sleep -Seconds 5
    Start-Process -FilePath (Join-Path $root 'GameServerCS\GameServerCS.exe') -WorkingDirectory (Join-Path $root 'GameServerCS')
    Write-Host 'Se ejecuto el arranque de GameServer y GameServerCS. Revisa sus ventanas.'
} else {Write-Host 'Abri GameServer y GameServerCS para cargar los valores nuevos.'}
