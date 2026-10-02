param([string]$ServerRoot='C:\MuServer43',[ValidateRange(0,1000000)][int]$PriceCoins=25000,[switch]$AlignServerRates)
$ErrorActionPreference='Stop'
$root=(Resolve-Path -LiteralPath $ServerRoot).Path.TrimEnd('\')
$destination=Join-Path ([Environment]::GetFolderPath('Desktop')) ('MU_PANIC_VIP_PREPARADO_'+(Get-Date -Format 'yyyyMMdd_HHmmss')+'_'+[Guid]::NewGuid().ToString('N').Substring(0,6))
[IO.Directory]::CreateDirectory($destination) | Out-Null
$keys=@('AddExperienceRate','ItemDropRate','HelperStartCoin1','WarehouseFeeValue','CommandResetMoney','CustomPickRequireMoney','CustomDailyRewardEnable','CustomExclusiveGlowCoin1','CustomSmithItemDiscount')
$changes=@();$files=@()
$normalRates=@{}
if ($AlignServerRates) {
    $normalSource=Join-Path $root 'GameServer\Data\GameServerInfo - Common.dat'
    $normalText=[Text.Encoding]::GetEncoding(28591).GetString([IO.File]::ReadAllBytes($normalSource))
    foreach ($rate in @('AddExperienceRate','ItemDropRate')) {
        $matches=[regex]::Matches($normalText,'(?m)^[ \t]*'+$rate+'_AL0[ \t]*=[ \t]*(\d+)[ \t]*(?:(?:;|//)[^\r\n]*)?\r?$')
        if ($matches.Count -ne 1) { throw ('Tasa normal ausente o duplicada: '+$rate+'_AL0') }
        $normalRates[$rate]=$matches[0].Groups[1].Value
    }
}

foreach ($server in @('GameServer','GameServerCS')) {
    foreach ($name in @('Common','Command','Custom')) {
        $relative=$server+'\Data\GameServerInfo - '+$name+'.dat';$source=Join-Path $root $relative
        if (-not (Test-Path -LiteralPath $source)) { throw ('Falta '+$relative) }
        $bytes=[IO.File]::ReadAllBytes($source);$hasBom=$bytes.Length -ge 3 -and $bytes[0] -eq 239 -and $bytes[1] -eq 187 -and $bytes[2] -eq 191
        # Byte-preserving for legacy configs; ASCII settings are identical in both encodings.
        if ($hasBom) { $encoding=New-Object Text.UTF8Encoding($false,$true);$text=$encoding.GetString($bytes,3,$bytes.Length-3) }
        else { $encoding=[Text.Encoding]::GetEncoding(28591);$text=$encoding.GetString($bytes) }
        $new=$text
        foreach ($key in $keys) {
            $basePattern='(?m)^\s*'+[regex]::Escape($key)+'_AL0[ \t]*=[ \t]*(-?\d+)[ \t]*(?:(?:;|//)[^\r\n]*)?\r?$'
            $baseline=[regex]::Matches($text,$basePattern)
            if ($baseline.Count -eq 0) { continue }
            if ($baseline.Count -ne 1) { throw ('Parametro duplicado: '+$key+'_AL0') }
            $value=$baseline[0].Groups[1].Value
            $levels=1..3
            if ($AlignServerRates -and $server -eq 'GameServerCS' -and $name -eq 'Common' -and $normalRates.ContainsKey($key)) {
                $value=$normalRates[$key]
                $levels=0..3
            }
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
$proposal="// BORRADOR. NO INSTALAR: falta verificar EXP/drop reales, renovacion y entrega de 5000 WCoin C.`r`n// Index Exp+ Drop+ Days Coin1 Coin2 Coin3 VipName`r`n0 5 5 30 $priceText 0 0 `"VIP`"`r`nend`r`n"
[IO.File]::WriteAllText((Join-Path $destination 'CustomBuyVip.txt.proposed'),$proposal,[Text.Encoding]::ASCII)
$manifest=@{version=3;applied=$false;align_server_rates=[bool]$AlignServerRates;normal_rates=$normalRates;price_coins=if($PriceCoins -gt 0){$PriceCoins}else{$null};plan='VIP';account_level=1;days=30;exp_extra_proposed=5;drop_extra_proposed=5;included_wcoin_c=5000;included_wcoin_delivery_implemented=$false;rates_verified=$false;files=$files;changes=$changes}
[IO.File]::WriteAllText((Join-Path $destination 'cambios.json'),($manifest|ConvertTo-Json -Depth 8),(New-Object Text.UTF8Encoding($false)))
[IO.File]::WriteAllText((Join-Path $destination 'LEEME.txt'),@'
Este paquete es una propuesta, no una instalacion.
Un plan: VIP, 30 dias, extra normal EXP propuesto 5, extra drop propuesto 5.
Precio: 25000 WCoin C por defecto; el manifiesto conserva el precio solicitado.
Las 5000 WCoin C incluidas requieren integracion: esta tabla NO las entrega.
No anunciar las monedas incluidas como operativas hasta probar esa entrega.
Los valores AL1/AL2/AL3 seleccionados se igualan a AL0 para evitar ventajas acumuladas.
Se conservan las tasas normales de GameServer y Master EXP.
Con -AlignServerRates, GameServerCS copia EXP/drop base actuales de GameServer AL0
en sus niveles AL0-AL3. Sin esa opcion, conserva sus tasas normales anteriores. No se modifica ExperienceTable.
originals contiene copias byte por byte de los archivos de configuracion actuales.
No se modifican cuentas existentes, vencimientos, saldos, procedimientos, procesos ni tareas.
Precio pendiente solo si se indico -PriceCoins 0. No usar un borrador con PRECIO_PENDIENTE.
La compra de indice 0 fue comprobada: nivel SQL 1, Coin1 = WCoin C, 30 dias.
Falta medir EXP real y verificar el significado y alcance del extra de drop.
Los nombres Bronze/Prata/Ouro tambien deben corregirse en mensajes y cliente.
No borrar claves AL2/AL3 ni cambiar membresias existentes: solo retirar sus ofertas.
La renovacion del mismo nivel debe usar max(vencimiento, ahora); el procedimiento actual
suma desde el vencimiento incluso si esta en el pasado. La correccion SQL se revisa aparte.
NO copiar archivos proposed al servidor hasta cerrar estas verificaciones.
'@,[Text.Encoding]::UTF8)
$zip=$destination+'.zip';Compress-Archive -LiteralPath $destination -DestinationPath $zip
Write-Host ('Preparados '+$changes.Count+' ajustes. No aplicados. ZIP: '+$zip)
