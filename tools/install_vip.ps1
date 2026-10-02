param([ValidateSet('VPS','PC')][string]$Target='VPS')
$ErrorActionPreference='Stop'
[Net.ServicePointManager]::SecurityProtocol=[Net.SecurityProtocolType]::Tls12
$desktop=[Environment]::GetFolderPath('Desktop')
$package=Join-Path $desktop ('MU_PANIC_VIP_INSTALADOR_'+(Get-Date -Format 'yyyyMMdd_HHmmss')+'_'+[Guid]::NewGuid().ToString('N').Substring(0,6))
[IO.Directory]::CreateDirectory($package)|Out-Null
$base='https://raw.githubusercontent.com/apereiranube/mupanic-web/a08c1877759e59a731bd79c4204739d2430b81bf/tools/'
$hashes=@{
    'prepare_single_vip.ps1'='9D88C3BF1852AEF8F52CA9215FC0172B06625323E2B3BF01C2D47ECDD6855800'
    'prepare_vip_client.ps1'='3DE41560FA4B7F1B3E65745A073811C43C61557278DC821641492A4AB5A6D6B5'
    'fix_vip_renewal.ps1'='E397B833ABAD229E3075B73EDADAB2D2255EC1DA69433F8D88334F5D4C1428EE'
}
foreach ($name in $hashes.Keys) {
    $file=Join-Path $package $name
    Invoke-WebRequest -UseBasicParsing -Uri ($base+$name) -OutFile $file
    if ((Get-FileHash -LiteralPath $file -Algorithm SHA256).Hash -cne $hashes[$name]) {throw ('No coincide la descarga de '+$name+'. No se instalo nada.')}
}
if ($Target -eq 'VPS') {
    if (@(Get-Process -Name 'GameServer','GameServerCS' -ErrorAction SilentlyContinue).Count -gt 0) {throw 'Cerra GameServer y GameServerCS, y vuelve a ejecutar este instalador con -Target VPS.'}
    & (Join-Path $package 'fix_vip_renewal.ps1') -Apply
    & (Join-Path $package 'prepare_single_vip.ps1') -Apply -StartServers
    Write-Host 'VPS: oferta de 25000 WC instalada y renovacion corregida. Revisa las ventanas de ambos GameServer.'
} else {
    & (Join-Path $package 'prepare_vip_client.ps1') -BuildTestClient
    Write-Host 'PC: copia del cliente preparada. La carpeta se abrio en el Explorador.'
}
