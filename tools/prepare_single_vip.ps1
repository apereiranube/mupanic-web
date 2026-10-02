param([string]$ServerRoot='C:\MuServer43',[ValidateRange(0,1000000)][int]$PriceCoins=25000,[switch]$AlignServerRates,[switch]$Apply,[switch]$StartServers)
$ErrorActionPreference='Stop'
if ($Apply -and $PriceCoins -ne 25000) { throw 'El plan aprobado cuesta exactamente 25000 WCoin C.' }
if ($StartServers -and -not $Apply) { throw 'StartServers requiere Apply.' }
if ($Apply -and @(Get-Process -Name 'GameServer','GameServerCS' -ErrorAction SilentlyContinue).Count -gt 0) { throw 'Cerra GameServer y GameServerCS antes de aplicar. No se modifico nada.' }
$root=(Resolve-Path -LiteralPath $ServerRoot).Path.TrimEnd('\')
$destination=Join-Path ([Environment]::GetFolderPath('Desktop')) ('MU_PANIC_VIP_PREPARADO_'+(Get-Date -Format 'yyyyMMdd_HHmmss')+'_'+[Guid]::NewGuid().ToString('N').Substring(0,6))
[IO.Directory]::CreateDirectory($destination) | Out-Null
$keys=@('HelperStartCoin1','WarehouseFeeValue','CommandResetMoney','CustomPickRequireMoney','CustomDailyRewardEnable','CustomExclusiveGlowCoin1','CustomSmithItemDiscount')
$changes=@();$files=@()
$normalRates=@{AddExperienceRate=15;ItemDropRate=25}
# AlignServerRates is retained for old commands; approved rates are always explicit.

foreach ($server in @('GameServer','GameServerCS')) {
    foreach ($name in @('Common','Command','Custom')) {
        $relative=$server+'\Data\GameServerInfo - '+$name+'.dat';$source=Join-Path $root $relative
        if (-not (Test-Path -LiteralPath $source)) { throw ('Falta '+$relative) }
        $bytes=[IO.File]::ReadAllBytes($source);$hasBom=$bytes.Length -ge 3 -and $bytes[0] -eq 239 -and $bytes[1] -eq 187 -and $bytes[2] -eq 191
        # Byte-preserving for legacy configs; ASCII settings are identical in both encodings.
        if ($hasBom) { $encoding=New-Object Text.UTF8Encoding($false,$true);$text=$encoding.GetString($bytes,3,$bytes.Length-3) }
        else { $encoding=[Text.Encoding]::GetEncoding(28591);$text=$encoding.GetString($bytes) }
        $new=$text
        if ($name -eq 'Common') {
            foreach ($rate in @('AddExperienceRate','ItemDropRate')) {
                foreach ($level in 0..3) {
                    $target=if ($rate -eq 'AddExperienceRate') {if ($level -eq 0) {15} else {20}} else {if ($level -eq 0) {25} else {30}}
                    $pattern='(?m)^([ \t]*'+$rate+'_AL'+$level+'[ \t]*=[ \t]*)(\d+)(?=[ \t\r\n;]|$)'
                    $found=[regex]::Matches($new,$pattern)
                    if ($found.Count -ne 1) { throw ('Falta o esta duplicada '+$rate+'_AL'+$level+' en '+$relative) }
                    if ($found[0].Groups[2].Value -eq [string]$target) { continue }
                    $changes+=@{file=$relative;key=$rate+'_AL'+$level;before=$found[0].Groups[2].Value;after=$target}
                    $span=$found[0].Groups[2]
                    $new=$new.Remove($span.Index,$span.Length).Insert($span.Index,[string]$target)
                }
            }
        }
        foreach ($key in $keys) {
            $basePattern='(?m)^\s*'+[regex]::Escape($key)+'_AL0[ \t]*=[ \t]*(-?\d+)[ \t]*(?:(?:;|//)[^\r\n]*)?\r?$'
            $baseline=[regex]::Matches($text,$basePattern)
            if ($baseline.Count -eq 0) { continue }
            if ($baseline.Count -ne 1) { throw ('Parametro duplicado: '+$key+'_AL0') }
            $value=$baseline[0].Groups[1].Value
            $levels=1..3
            foreach ($level in $levels) {
                $pattern='(?m)^([ \t]*'+[regex]::Escape($key)+'_AL'+$level+'[ \t]*=[ \t]*)(-?\d+)([ \t]*(?:(?:;|//)[^\r\n]*)?\r?)$'
                $match=[regex]::Matches($new,$pattern)
                if ($match.Count -ne 1) { throw ('Parametro ausente o duplicado: '+$relative+' '+$key+'_AL'+$level) }
                if ($match[0].Groups[2].Value -eq $value) { continue }
                $changes+=@{file=$relative;key=$key+'_AL'+$level;before=$match[0].Groups[2].Value;after=$value}
                # Replacement uses captures and literal digits; no shell interpolation.
                $new=[regex]::Replace($new,$pattern,('${1}'+$value+'${3}'))
            }
        }
        if ($new -ceq $text) { continue }
        $stage=Join-Path $destination ($relative+'.proposed')
        [IO.Directory]::CreateDirectory([IO.Path]::GetDirectoryName($stage)) | Out-Null
        $original=Join-Path $destination ('originals\'+$relative)
        [IO.Directory]::CreateDirectory([IO.Path]::GetDirectoryName($original)) | Out-Null
        [IO.File]::WriteAllBytes($original,$bytes)
        $newBytes=$encoding.GetBytes($new)
        if ($hasBom) { $newBytes=[byte[]](@(239,187,191)+$newBytes) }
        [IO.File]::WriteAllBytes($stage,$newBytes)
        $files+=@{file=$relative;sha256_before=(Get-FileHash -LiteralPath $source -Algorithm SHA256).Hash;sha256_proposed=(Get-FileHash -LiteralPath $stage -Algorithm SHA256).Hash}
    }
}
$buy=Join-Path $root 'Data\Custom\CustomBuyVip.txt'
if (-not (Test-Path -LiteralPath $buy)) { throw 'Falta CustomBuyVip.txt.' }
$originalBuy=Join-Path $destination 'originals\Data\Custom\CustomBuyVip.txt'
[IO.Directory]::CreateDirectory([IO.Path]::GetDirectoryName($originalBuy)) | Out-Null
Copy-Item -LiteralPath $buy -Destination $originalBuy
$priceText='PRECIO_PENDIENTE';if ($PriceCoins -gt 0) { $priceText=[string]$PriceCoins }
$proposal="// VIP unico. EXP/drop son tasas absolutas. Sin entrega automatica de monedas.`r`n// Index Exp+ Drop+ Days Coin1 Coin2 Coin3 VipName`r`n0 20 30 30 $priceText 0 0 `"VIP`"`r`nend`r`n"
[IO.File]::WriteAllText((Join-Path $destination 'CustomBuyVip.txt.proposed'),$proposal,[Text.Encoding]::ASCII)
$manifest=@{version=4;applied=$false;align_server_rates=[bool]$AlignServerRates;normal_rates=$normalRates;price_coins=if($PriceCoins -gt 0){$PriceCoins}else{$null};plan='VIP';account_level=1;days=30;exp_normal=15;exp_vip=20;drop_normal=25;drop_vip=30;included_wcoin_c=0;rates_configuration_verified=$true;files=$files;changes=$changes}
[IO.File]::WriteAllText((Join-Path $destination 'cambios.json'),($manifest|ConvertTo-Json -Depth 8),(New-Object Text.UTF8Encoding($false)))
[IO.File]::WriteAllText((Join-Path $destination 'LEEME.txt'),@'
Sin -Apply, este paquete es solo una propuesta. INSTALADO.txt confirma una aplicacion.
Un plan: VIP, 30 dias. Normal EXP 15/drop 25; VIP EXP 20/drop 30.
Precio: 25000 WCoin C por defecto; el manifiesto conserva el precio solicitado.
El plan no incluye monedas de regalo.
Los valores AL1/AL2/AL3 seleccionados se igualan a AL0 para evitar ventajas acumuladas.
Se conservan las tasas normales de GameServer y Master EXP.
Ambos GameServer usan las tasas aprobadas: normal 15/25 y VIP 20/30. No se modifica ExperienceTable.
originals contiene copias byte por byte de los archivos de configuracion actuales.
No se modifican cuentas existentes, vencimientos, saldos, procedimientos ni tareas.
-StartServers abre ambos GameServer despues de instalar.
Precio pendiente solo si se indico -PriceCoins 0. No usar un borrador con PRECIO_PENDIENTE.
La compra de indice 0 fue comprobada: nivel SQL 1, Coin1 = WCoin C, 30 dias.
Falta medir EXP real y verificar el significado y alcance del extra de drop.
Los nombres Bronze/Prata/Ouro tambien deben corregirse en mensajes y cliente.
No borrar claves AL2/AL3 ni cambiar membresias existentes: solo retirar sus ofertas.
La renovacion del mismo nivel debe usar max(vencimiento, ahora); el procedimiento actual
suma desde el vencimiento incluso si esta en el pasado. La correccion SQL se revisa aparte.
Con -Apply se instala la oferta y las tasas con respaldo.
'@,[Text.Encoding]::UTF8)
$zip=$destination+'.zip';Compress-Archive -LiteralPath $destination -DestinationPath $zip
Write-Host ('Preparados '+$changes.Count+' ajustes. No aplicados. ZIP: '+$zip)

if (-not $Apply) { return }
if ($PriceCoins -le 0) { throw 'No se puede instalar un precio pendiente.' }
if (@(Get-Process -Name 'GameServer','GameServerCS' -ErrorAction SilentlyContinue).Count -gt 0) { throw 'Se abrio un GameServer durante la preparacion. No se instalo nada.' }
if ($StartServers) {
    foreach ($server in @('GameServer','GameServerCS')) {
        if (-not (Test-Path -LiteralPath (Join-Path $root ($server+'\'+$server+'.exe')) -PathType Leaf)) { throw ('Falta '+$server+'.exe. No se instalo nada.') }
    }
}
$install=@()
foreach ($file in $files) {
    $install+=@{target=(Join-Path $root $file.file);original=(Join-Path $destination ('originals\'+$file.file));proposed=(Join-Path $destination ($file.file+'.proposed'));hash=$file.sha256_before}
}
$install+=@{target=$buy;original=$originalBuy;proposed=(Join-Path $destination 'CustomBuyVip.txt.proposed');hash=(Get-FileHash -LiteralPath $originalBuy -Algorithm SHA256).Hash}
# All originals already exist before the first active write. Detect concurrent edits.
foreach ($file in $install) {
    if ((Get-FileHash -LiteralPath $file.target -Algorithm SHA256).Hash -cne $file.hash) { throw ('Cambio concurrente: '+$file.target+'. No se instalo nada.') }
}
$written=@()
try {
    foreach ($file in $install) {
        if ((Get-FileHash -LiteralPath $file.target -Algorithm SHA256).Hash -cne $file.hash) { throw ('Cambio concurrente: '+$file.target) }
        $written+=,$file
        [IO.File]::WriteAllBytes($file.target,[IO.File]::ReadAllBytes($file.proposed))
        if ((Get-FileHash -LiteralPath $file.target -Algorithm SHA256).Hash -cne (Get-FileHash -LiteralPath $file.proposed -Algorithm SHA256).Hash) { throw ('No se pudo verificar '+$file.target) }
    }
} catch {
    foreach ($file in $written) { [IO.File]::WriteAllBytes($file.target,[IO.File]::ReadAllBytes($file.original)) }
    throw
}
$manifest.applied=$true
[IO.File]::WriteAllText((Join-Path $destination 'cambios.json'),($manifest|ConvertTo-Json -Depth 8),(New-Object Text.UTF8Encoding($false)))
[IO.File]::WriteAllText((Join-Path $destination 'INSTALADO.txt'),('VIP 30 dias / 25000 WCoin C. Normal EXP 15 drop 25; VIP EXP 20 drop 30. '+(Get-Date -Format o)),[Text.Encoding]::UTF8)
Write-Host ('INSTALADO: VIP / 30 dias / 25000 WCoin C. Respaldo: '+$destination+'\originals')
Write-Host 'No se alteraron cuentas, vencimientos ni saldos.'
if ($StartServers) {
    Start-Process -FilePath (Join-Path $root 'GameServer\GameServer.exe') -WorkingDirectory (Join-Path $root 'GameServer')
    Start-Sleep -Seconds 5
    Start-Process -FilePath (Join-Path $root 'GameServerCS\GameServerCS.exe') -WorkingDirectory (Join-Path $root 'GameServerCS')
    Write-Host 'Arranque solicitado para ambos GameServer. Revisa sus ventanas.'
}
