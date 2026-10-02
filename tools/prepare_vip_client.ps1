param(
    [string]$GeneratorRoot='C:\MuServer43\Tools\MAIN_INFO v43 - Season 6',
    [string]$ClientRoot='C:\Cliente_louis update 43',
    [switch]$PrepareTestCopies,
    [switch]$BuildTestClient
)
$ErrorActionPreference='Stop'
if ($BuildTestClient) { $PrepareTestCopies=$true }
$root=(Resolve-Path -LiteralPath $GeneratorRoot).Path.TrimEnd('\')
$buy=Join-Path $root 'Common\CustomBuyVip.txt'
$message=Join-Path $root 'Common\CustomMessage.txt'
foreach ($path in @($buy,$message)) {
    if (-not (Test-Path -LiteralPath $path -PathType Leaf)) { throw ('Falta la tabla '+$path+'. No se modifico el generador.') }
}
$pairHashes=@{}
if ($PrepareTestCopies) {
    $client=(Resolve-Path -LiteralPath $ClientRoot).Path.TrimEnd('\')
    foreach ($pair in @(
        @('Main.exe','main.exe'),
        @('Main.dll','Premium\Main.dll'),
        @('main.premium','Premium\main.premium')
    )) {
        $clientFile=Join-Path $client $pair[0]
        $generatorFile=Join-Path $root $pair[1]
        foreach ($file in @($clientFile,$generatorFile)) {
            if (-not (Test-Path -LiteralPath $file -PathType Leaf)) { throw ('Falta '+$file) }
        }
        $clientHash=(Get-FileHash -LiteralPath $clientFile -Algorithm SHA256).Hash
        $generatorHash=(Get-FileHash -LiteralPath $generatorFile -Algorithm SHA256).Hash
        if ($clientHash -cne $generatorHash) { throw ('Cliente y generador no coinciden: '+$pair[0]+'. No se prepararon copias.') }
        $pairHashes[$pair[0]]=$clientHash
    }
    if (-not (Test-Path -LiteralPath (Join-Path $root 'GetMainInfo-Premium.exe') -PathType Leaf)) { throw 'Falta GetMainInfo-Premium.exe.' }
}
# Decode one byte per character so all untouched bytes, including UTF-8 text, survive.
$encoding=[Text.Encoding]::GetEncoding(28591)
$bytes=[IO.File]::ReadAllBytes($message);$text=$encoding.GetString($bytes)
$sets=@{
    0=@{5='VIP';6='Welcome, %s!';7='30 days / 25000 WCoin C';8='EXP 15 to 20 / drop 25 to 30';9='Buy %s';10='Price:';11='WC: %d';12='WP: %d';13='GP: %d';14='Clicking purchases VIP immediately.';26='Buy VIP';81='Free Account';82='VIP';83='VIP';84='VIP';202='Plan';203='EXP';204='Drop'}
    1=@{5='VIP';6='Bem-vindo, %s!';7='30 dias / 25000 WCoin C';8='EXP 15 a 20 / drop 25 a 30';9='Comprar %s';10='Preco:';11='WC: %d';12='WP: %d';13='GP: %d';14='Ao clicar, a compra e imediata.';26='Comprar VIP';81='Conta normal';82='VIP';83='VIP';84='VIP';202='Plano';203='EXP';204='Drop'}
    2=@{5='VIP';6='Bienvenido, %s!';7='30 dias / 25000 WCoin C';8='EXP 15 a 20 / drop 25 a 30';9='Comprar %s';10='Precio:';11='WC: %d';12='WP: %d';13='GP: %d';14='Al pulsar, compras el VIP directamente.';26='Comprar VIP';81='Cuenta normal';82='VIP';83='VIP';84='VIP';202='Plan';203='EXP';204='Drop'}
}
$state=@{section=-1};$seen=@{}
$new=[regex]::Replace($text,'(?m)^[^\r\n]*',{
    param($match)
    $line=$match.Value
    if ($line -match '^\s*([012])\s*$') {  $state.section=[int]$Matches[1] }
    elseif ($line -match '^\s*end\s*$') {  $state.section=-1 }
    $current= $state.section
    if ($null -ne $current -and $sets.ContainsKey($current) -and $line -match '^([ \t]*)(\d+)([ \t]+)"([^"\r\n]*)"([ \t]*)$') {
        $id=[int]$Matches[2];$prefix=$Matches[1]+$Matches[2]+$Matches[3];$old=$Matches[4];$suffix=$Matches[5]
        if ($sets[$current].ContainsKey($id)) {
            $key=[string]$current+':'+$id
            if ($seen.ContainsKey($key)) { throw ('Mensaje duplicado: '+$key) }
            $seen[$key]=$true;$value=$sets[$current][$id]
            $oldArgs=@([regex]::Matches($old,'%(?:I64)?[sdu]')|ForEach-Object{$_.Value})
            $newArgs=@([regex]::Matches($value,'%(?:I64)?[sdu]')|ForEach-Object{$_.Value})
            if (($oldArgs -join '|') -cne ($newArgs -join '|')) { throw ('Parametros incompatibles: '+$key) }
            return $prefix+'"'+$value+'"'+$suffix
        }
    }
    return $line
})
# The callback shares mutable hashtables for section and duplicate checks.
foreach ($lang in 0..2) {
    foreach ($id in $sets[$lang].Keys) {
        if (-not $seen.ContainsKey(([string]$lang+':'+$id))) { throw ('Falta el mensaje '+$lang+':'+$id) }
    }
}
$desktop=[Environment]::GetFolderPath('Desktop')
$out=Join-Path $desktop ('MU_PANIC_VIP_CLIENTE_PREPARADO_'+(Get-Date -Format 'yyyyMMdd_HHmmss')+'_'+[Guid]::NewGuid().ToString('N').Substring(0,6))
[IO.Directory]::CreateDirectory((Join-Path $out 'originals\Common'))|Out-Null
[IO.Directory]::CreateDirectory((Join-Path $out 'proposed\Common'))|Out-Null
Copy-Item -LiteralPath $buy -Destination (Join-Path $out 'originals\Common\CustomBuyVip.txt')
Copy-Item -LiteralPath $message -Destination (Join-Path $out 'originals\Common\CustomMessage.txt')
[IO.File]::WriteAllBytes((Join-Path $out 'proposed\Common\CustomMessage.txt'),$encoding.GetBytes($new))
$proposal="// VIP: tasas absolutas; el sufijo % pertenece a la UI nativa.`r`n// Index Exp+ Drop+ Days Coin1 Coin2 Coin3 VipName`r`n0 20 30 30 25000 0 0 `"VIP`"`r`nend`r`n"
[IO.File]::WriteAllText((Join-Path $out 'proposed\Common\CustomBuyVip.txt'),$proposal,[Text.Encoding]::ASCII)
$manifest=@{version=1;applied=$false;generator_root=$root;plan='VIP';days=30;price_wcoin_c=25000;exp_normal=15;exp_vip=20;drop_normal=25;drop_vip=30;rates_configuration_verified=$true;included_wcoin_delivery_implemented=$false;badge_supported=$false;message_sha256_before=(Get-FileHash -LiteralPath $message -Algorithm SHA256).Hash}
[IO.File]::WriteAllText((Join-Path $out 'manifest.json'),($manifest|ConvertTo-Json -Depth 4),(New-Object Text.UTF8Encoding($false)))
[IO.File]::WriteAllText((Join-Path $out 'LEEME.txt'),@'
BORRADOR. No se modifico el generador ni el cliente.
Una sola oferta VIP, 30 dias, precio 25000 WCoin C, valores visuales EXP 20 / drop 30. Son tasas, no +20% ni +30%.
Texto VIP en los tres idiomas; cuenta normal en AL0. No elimina niveles SQL.
La seccion espanola de las etiquetas VIP queda en espanol, con textos cortos.
No se anuncian las 5000 monedas incluidas: falta implementar y probar la entrega.
No se agrego una corona ni efectos. Esas opciones no figuran en estas tablas.
El generador NUEVO MAIN BETA no incluyo Common/CustomMessage.txt en el ZIP recibido.
No mezclar generadores ni copiar MainInfo.ini o archivos main.exe/main.dll antiguos.
No ejecutar generadores ni reemplazar main.premium hasta coordinar con el servidor.
Tasas configuradas: normal EXP 15 / drop 25; VIP EXP 20 / drop 30.
La UI nativa mantiene dos filas vacias y sufijos %: no se eliminan con estas tablas.
La entrega de 5000 y la renovacion vencida siguen pendientes de integracion.
'@,[Text.Encoding]::UTF8)
Compress-Archive -LiteralPath $out -DestinationPath ($out+'.zip')
Write-Host ('Borrador preparado. No instalado. ZIP: '+$out+'.zip')
if ($PrepareTestCopies) {
    $testRoot=$out+'_PRUEBA'
    foreach ($source in @($root,$client)) {
        if ($testRoot.StartsWith($source+'\',[StringComparison]::OrdinalIgnoreCase)) { throw 'La carpeta de prueba no puede estar dentro del origen.' }
    }
    $testGenerator=Join-Path $testRoot 'Generador'
    $testClient=Join-Path $testRoot 'Cliente'
    foreach ($copy in @(@($root,$testGenerator),@($client,$testClient))) {
        & robocopy.exe $copy[0] $copy[1] /E /COPY:DAT /DCOPY:DAT /R:1 /W:1 /XJ /NFL /NDL /NJH /NJS /NP | Out-Null
        if ($LASTEXITCODE -ge 8) { throw ('No se completo la copia a '+$copy[1]+'. Codigo robocopy: '+$LASTEXITCODE) }
    }
    foreach ($pair in @(@('Main.exe','main.exe'),@('Main.dll','Premium\Main.dll'),@('main.premium','Premium\main.premium'))) {
        foreach ($file in @((Join-Path $testClient $pair[0]),(Join-Path $testGenerator $pair[1]))) {
            if ((Get-FileHash -LiteralPath $file -Algorithm SHA256).Hash -cne $pairHashes[$pair[0]]) { throw ('La copia no coincide: '+$file) }
        }
    }
    foreach ($name in @('CustomBuyVip.txt','CustomMessage.txt')) {
        Copy-Item -LiteralPath (Join-Path $out ('proposed\Common\'+$name)) -Destination (Join-Path $testGenerator ('Common\'+$name)) -Force
    }
    [IO.File]::WriteAllText((Join-Path $testRoot 'LEEME.txt'),@'
COPIA DE PRUEBA. Los originales no se modificaron.
Generador/Common contiene el borrador VIP. No se ejecuto GetMainInfo-Premium.exe.
Cliente conserva el main.premium original: todavia no muestra el nuevo menu.
No compres VIP con estas tablas hasta coordinar su precio con el servidor.
El servidor debe tener la misma oferta de 25000 antes de comprar.
Las 5000 monedas incluidas todavia no se entregan automaticamente.
No distribuir esta copia ni mezclarla con NUEVO MAIN BETA.
'@,[Text.Encoding]::UTF8)
    Write-Host ('Copias de prueba listas en: '+$testRoot)
    if ($BuildTestClient) {
        $generated=Join-Path $testGenerator 'Premium\main.premium'
        # Only remove the output inside this new isolated copy. Originals are intact.
        Remove-Item -LiteralPath $generated -Force
        $generatorProcess=Start-Process -FilePath (Join-Path $testGenerator 'GetMainInfo-Premium.exe') -WorkingDirectory $testGenerator -PassThru
        Write-Host 'El generador se abrio. Si pide una tecla, presiona Enter en su ventana.'
        if (-not $generatorProcess.WaitForExit(120000)) { throw ('El generador sigue abierto. No se instalo su salida. Carpeta: '+$testGenerator) }
        if ($generatorProcess.ExitCode -ne 0 -or -not (Test-Path -LiteralPath $generated -PathType Leaf) -or (Get-Item -LiteralPath $generated).Length -lt 100) { throw 'El generador no produjo un main.premium valido. La copia del cliente conserva el anterior.' }
        Copy-Item -LiteralPath $generated -Destination (Join-Path $testClient 'main.premium') -Force
        if ((Get-FileHash -LiteralPath $generated -Algorithm SHA256).Hash -cne (Get-FileHash -LiteralPath (Join-Path $testClient 'main.premium') -Algorithm SHA256).Hash) { throw 'No se pudo verificar la copia de main.premium.' }
        $launcher=Join-Path $desktop 'MU_PANIC_CLIENTE_VIP_ACTUAL.txt'
        [IO.File]::WriteAllText($launcher,$testClient,(New-Object Text.UTF8Encoding($false)))
        Start-Process -FilePath explorer.exe -ArgumentList ('"'+$testClient+'"')
        Write-Host ('CLIENTE LISTO: '+$testClient+'\Main.exe')
        Write-Host ('Ruta guardada en: '+$launcher)
    } else { Write-Host 'El cliente original sigue intacto. Usa -BuildTestClient para generar e instalar en esta nueva copia.' }
}
