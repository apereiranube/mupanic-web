param([string]$ServerRoot='C:\MuServer43',[ValidateRange(0,1000000)][int]$PriceCoins=0)
$ErrorActionPreference='Stop'
$root=(Resolve-Path -LiteralPath $ServerRoot).Path.TrimEnd('\')
$destination=Join-Path ([Environment]::GetFolderPath('Desktop')) ('MU_PANIC_VIP_PREPARADO_'+(Get-Date -Format 'yyyyMMdd_HHmmss')+'_'+[Guid]::NewGuid().ToString('N').Substring(0,6))
[IO.Directory]::CreateDirectory($destination) | Out-Null
$keys=@('AddExperienceRate','ItemDropRate','HelperStartCoin1','WarehouseFeeValue','CommandResetMoney','CustomPickRequireMoney','CustomDailyRewardEnable','CustomExclusiveGlowCoin1','CustomSmithItemDiscount')
$changes=@();$files=@()
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
            foreach ($level in 1..3) {
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
$proposal="// BORRADOR. NO INSTALAR: falta verificar nivel, EXP real y renovacion.`r`n// Index Exp+ Drop+ Days Coin1 Coin2 Coin3 VipName`r`n0 10 0 30 $priceText 0 0 `"VIP PANIC`"`r`nend`r`n"
[IO.File]::WriteAllText((Join-Path $destination 'CustomBuyVip.txt.proposed'),$proposal,[Text.Encoding]::ASCII)
$manifest=@{version=1;applied=$false;price_coins=if($PriceCoins -gt 0){$PriceCoins}else{$null};plan='VIP PANIC';days=30;exp_extra_proposed=10;drop_extra_proposed=0;files=$files;changes=$changes}
[IO.File]::WriteAllText((Join-Path $destination 'cambios.json'),($manifest|ConvertTo-Json -Depth 8),(New-Object Text.UTF8Encoding($false)))
[IO.File]::WriteAllText((Join-Path $destination 'LEEME.txt'),@'
Este paquete es una propuesta, no una instalacion.
Un plan: VIP PANIC, 30 dias, extra normal EXP propuesto 10, extra drop 0.
Los valores AL1/AL2/AL3 seleccionados se igualan a AL0 para evitar ventajas acumuladas.
Se conservan las tasas de cuentas normales y Master EXP. No se modifica ExperienceTable.
originals contiene copias byte por byte de los archivos de configuracion actuales.
No se modifican cuentas existentes, vencimientos, saldos, procedimientos, procesos ni tareas.
Precio pendiente si no se indico -PriceCoins. No usar un borrador con PRECIO_PENDIENTE.
Falta confirmar el nivel SQL que escribe el juego y medir el resultado real de EXP.
La renovacion del mismo nivel debe usar max(vencimiento, ahora); el procedimiento actual
suma desde el vencimiento incluso si esta en el pasado. La correccion SQL se revisa aparte.
NO copiar archivos proposed al servidor hasta cerrar estas verificaciones.
'@,[Text.Encoding]::UTF8)
$zip=$destination+'.zip';Compress-Archive -LiteralPath $destination -DestinationPath $zip
Write-Host ('Preparados '+$changes.Count+' ajustes. No aplicados. ZIP: '+$zip)
