param([ValidateSet('VPS','PC')][string]$Target='VPS')
$ErrorActionPreference='Stop'
[Net.ServicePointManager]::SecurityProtocol=[Net.SecurityProtocolType]::Tls12
$desktop=[Environment]::GetFolderPath('Desktop')
$package=Join-Path $desktop ('MU_PANIC_VIP_INSTALADOR_'+(Get-Date -Format 'yyyyMMdd_HHmmss')+'_'+[Guid]::NewGuid().ToString('N').Substring(0,6))
[IO.Directory]::CreateDirectory($package)|Out-Null
$base='https://raw.githubusercontent.com/apereiranube/mupanic-web/3e3f072ebb17cdbc19df403c4098af1d97cd7170/tools/'
$hashes=@{
    'prepare_single_vip.ps1'='B73394EFB0F90758D4C19064389ECC1FFBE7A29FD7E590EA1267BC215DFE3195'
    'prepare_vip_client.ps1'='7051DBDFD32C8E5A576C63E6C127DB2FF1B9CB7989151C7A7AFEDE3EC71F0E39'
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
