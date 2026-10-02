param([ValidateSet('Before','After')][string]$Mode='Before',[ValidatePattern('^[A-Za-z0-9_]{1,10}$')][string]$Account='pruebacoin',[string]$SqlServer='', [System.Management.Automation.PSCredential]$SqlCredential)
$ErrorActionPreference='Stop'
$resolvedRoot='C:\MuServer43'
$desktop=[Environment]::GetFolderPath('Desktop')
$baselineFile=Join-Path $desktop ('MU_PANIC_VIP_TEST_'+$Account+'.json')
function Open-VipConnection([string]$Instance,$Credential) {
    $builder=New-Object System.Data.SqlClient.SqlConnectionStringBuilder
    $builder['Data Source']=$Instance;$builder['Initial Catalog']='MuOnline43';$builder['Connect Timeout']=5
    $builder['Application Name']='MU PANIC Read Only VIP Audit'
    if ($null -eq $Credential) { $builder['Integrated Security']=$true }
    else { $builder['User ID']=$Credential.UserName;$builder['Password']=$Credential.GetNetworkCredential().Password }
    $connection=New-Object System.Data.SqlClient.SqlConnection -ArgumentList ($builder.get_ConnectionString())
    try { $connection.Open();return $connection } catch { $connection.Dispose();return $null }
}
$instances=@()
if ($SqlServer) { $instances+= $SqlServer }
else {
    $workerSettings=Join-Path $resolvedRoot 'PaymentsPrivate\recharge-worker.json'
    if (Test-Path -LiteralPath $workerSettings) {
        try { $settings=[IO.File]::ReadAllText($workerSettings) | ConvertFrom-Json;if ($settings.sql_server) { $instances+=[string]$settings.sql_server } } catch {}
    }
    foreach ($registry in @('HKLM:\SOFTWARE\Microsoft\Microsoft SQL Server\Instance Names\SQL','HKLM:\SOFTWARE\WOW6432Node\Microsoft\Microsoft SQL Server\Instance Names\SQL')) {
        if (Test-Path $registry) {
            foreach ($property in (Get-ItemProperty $registry).PSObject.Properties) {
                if ($property.Name -like 'PS*') { continue }
                if ($property.Name -eq 'MSSQLSERVER') { $instances+='localhost' } else { $instances+=('localhost\'+$property.Name) }
            }
        }
    }
    $instances+=@('localhost','localhost\SQLEXPRESS')
}
$script:connection=$null
foreach ($instance in @($instances | Select-Object -Unique)) { $script:connection=Open-VipConnection $instance $SqlCredential;if ($null -ne $script:connection) { break } }

if ($null -eq $script:connection) { throw 'No se pudo leer MuOnline43. No se cambiaron cuentas.' }
try {
    $command=$script:connection.CreateCommand()
    $command.CommandText=@'
SELECT m.AccountLevel,m.AccountExpireDate,c.WCoinC,c.WCoinP,c.GoblinPoint,s.ConnectStat,GETDATE() AS SqlLocalTime
FROM dbo.MEMB_INFO m JOIN dbo.CashShopData c ON c.AccountID=m.memb___id
LEFT JOIN dbo.MEMB_STAT s ON s.memb___id=m.memb___id WHERE m.memb___id=@Account
'@
    $command.CommandTimeout=15
    $command.Parameters.Add('@Account',[System.Data.SqlDbType]::VarChar,10).Value=$Account
    $reader=$null;$row=$null
    try {
        $reader=$command.ExecuteReader()
        if ($reader.Read()) {
            $row=[ordered]@{account=$Account;captured_utc=[DateTime]::UtcNow.ToString('o')}
            for ($i=0;$i -lt $reader.FieldCount;$i++) {
                $value=$reader.GetValue($i)
                if ($value -is [DBNull]) {$value=$null} elseif ($value -is [DateTime]) {$value=$value.ToString('yyyy-MM-ddTHH:mm:ss')}
                $row[$reader.GetName($i)]=$value
            }
        }
    } finally {if ($null -ne $reader) {$reader.Dispose()};$command.Dispose()}
    if ($null -eq $row) {throw 'No se encontro la cuenta con saldo. No se modifico nada.'}
    if ($Mode -eq 'Before') {
        $vipTable='C:\MuServer43\Data\Custom\CustomBuyVip.txt'
        if (-not (Test-Path -LiteralPath $vipTable) -or [IO.File]::ReadAllText($vipTable) -notmatch '(?m)^[ \t]*0[ \t]+10[ \t]+10[ \t]+30[ \t]+10[ \t]+0[ \t]+0[ \t]+"Vip Bronze"[ \t]*\r?$') {throw 'La tabla Bronze cambio. No hagas la compra de prueba hasta revisar el nuevo precio.'}

        if ($row.ConnectStat -ne 1) {throw ('Entra al juego con '+$Account+' y vuelve a ejecutar -Mode Before.')}
        if ($row.AccountLevel -ne 0) {throw 'Para comprobar el primer nivel usa una cuenta sin VIP. Esta cuenta ya tiene otro nivel.'}
        if ($row.WCoinC -lt 10) {throw 'La cuenta necesita al menos 10 WCoin C para la prueba del precio actual. No se modifico el saldo.'}
        if (Test-Path -LiteralPath $baselineFile) {Copy-Item -LiteralPath $baselineFile -Destination ($baselineFile+'.backup_'+[Guid]::NewGuid().ToString('N'))}
        [IO.File]::WriteAllText($baselineFile,($row|ConvertTo-Json -Depth 4),(New-Object Text.UTF8Encoding($false)))
        Write-Host ('ANTES guardado: '+$Account+' | nivel '+$row.AccountLevel+' | '+$row.WCoinC+' WCoin C.')
        Write-Host 'Ahora compra SOLO Bronze una vez en MENU > corona. Despues ejecuta -Mode After.'
    } else {
        if (-not (Test-Path -LiteralPath $baselineFile)) {throw 'Falta la lectura Before.'}
        $before=[IO.File]::ReadAllText($baselineFile)|ConvertFrom-Json
        if ($before.account -cne $Account -or [DateTimeOffset]::UtcNow.Subtract([DateTimeOffset]::Parse($before.captured_utc)).TotalMinutes -gt 15) {throw 'La lectura anterior no corresponde a esta cuenta o tiene mas de 15 minutos.'}
        $result=[ordered]@{before=$before;after=$row;spent_wcoin_c=([long]$before.WCoinC-[long]$row.WCoinC);delta_wcoin_p=([long]$row.WCoinP-[long]$before.WCoinP);delta_goblin=([long]$row.GoblinPoint-[long]$before.GoblinPoint);days_from_sql_now=([DateTime]::Parse($row.AccountExpireDate).Subtract([DateTime]::Parse($row.SqlLocalTime)).TotalDays)}
        $out=Join-Path $desktop ('MU_PANIC_VIP_RESULTADO_'+$Account+'_'+(Get-Date -Format 'yyyyMMdd_HHmmss')+'.json')
        [IO.File]::WriteAllText($out,($result|ConvertTo-Json -Depth 6),(New-Object Text.UTF8Encoding($false)))
        Write-Host ('DESPUES: nivel '+$row.AccountLevel+' | WCoin C gastados: '+$result.spent_wcoin_c+' | dias restantes: '+[Math]::Round($result.days_from_sql_now,3))
        Write-Host ('Subi este JSON: '+$out)
        Write-Host 'La prueba consulta los datos; no acredita, descuenta ni revierte monedas por SQL.'
    }
} finally {$script:connection.Dispose()}
