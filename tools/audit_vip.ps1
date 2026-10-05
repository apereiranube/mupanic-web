param(
    [string]$ServerRoot = 'C:\MuServer43',
    [string]$SqlServer = '',
    [System.Management.Automation.PSCredential]$SqlCredential
)
$ErrorActionPreference='Stop'
# Reads configuration and SQL metadata only. Never executes game procedures.
# Writes the report and an inactive proposal to a new Desktop folder.
$resolvedRoot=(Resolve-Path -LiteralPath $ServerRoot).Path.TrimEnd('\')
$desktop=[Environment]::GetFolderPath('Desktop')
$outRoot=Join-Path $desktop ('MU_PANIC_VIP_AUDIT_' + (Get-Date -Format 'yyyyMMdd_HHmmss') + '_' + [Guid]::NewGuid().ToString('N').Substring(0,6))
[IO.Directory]::CreateDirectory($outRoot) | Out-Null
$report=[ordered]@{ version=1; database='MuOnline43'; created_utc=[DateTime]::UtcNow.ToString('o'); configuration=@(); tables=@(); modules=@(); sql_available=$false; sql_error=$null; sql_clock=$null; levels=@() }
$utf8=New-Object Text.UTF8Encoding($false)
function Write-VipJson([string]$Name,$Value) {
    [IO.File]::WriteAllText((Join-Path $outRoot $Name),($Value | ConvertTo-Json -Depth 12),$utf8)
}
foreach ($server in @('GameServer','GameServerCS')) {
    $data=Join-Path $resolvedRoot ($server + '\Data')
    if (-not (Test-Path -LiteralPath $data)) { continue }
    foreach ($file in Get-ChildItem -LiteralPath $data -File -Filter 'GameServerInfo*.dat') {
        $settings=@()
        foreach ($line in [IO.File]::ReadAllLines($file.FullName)) {
            # Export only simple VIP/account-level settings, never connection settings.
            if ($line -match '^\s*([A-Za-z0-9_]*(?:Vip|AccountLevelName|_AL[0-3])[A-Za-z0-9_]*)\s*=\s*(.*?)\s*(?:;.*|//.*)?$') {
                $key=$matches[1];$value=$matches[2].Trim().Trim('"')
                if ($value -match '^[A-Za-z0-9 _.,+/-]{1,100}$') { $settings+=@{key=$key;value=$value} }
            }
        }
        if ($settings.Count -gt 0) { $report.configuration+=@{file=$file.FullName.Substring($resolvedRoot.Length+1);settings=$settings} }
    }
}
$dataRoot=Join-Path $resolvedRoot 'Data'
if (Test-Path -LiteralPath $dataRoot) {
    foreach ($file in Get-ChildItem -LiteralPath $dataRoot -Recurse -File) {
        if ($file.Extension -notin @('.txt','.dat','.ini') -or $file.Name -notmatch '^(?:CustomBuyVip|CustomItemVip|ExperienceTable|MasterExperienceTable)\.(txt|dat|ini)$') { continue }
        if ($file.Length -gt 524288) { $report.tables+=@{file=$file.FullName.Substring($resolvedRoot.Length+1);error='FILE_TOO_LARGE'};continue }
        $report.tables+=@{file=$file.FullName.Substring($resolvedRoot.Length+1);content=[IO.File]::ReadAllText($file.FullName)}
    }
}
function Open-VipConnection([string]$Instance,$Credential) {
    $builder=New-Object System.Data.SqlClient.SqlConnectionStringBuilder
    $builder['Data Source']=$Instance;$builder['Initial Catalog']='MuOnline43';$builder['Connect Timeout']=5
    $builder['Application Name']='MU PANIC Read Only VIP Audit'
    if ($null -eq $Credential) { $builder['Integrated Security']=$true }
    else { $builder['User ID']=$Credential.UserName;$builder['Password']=$Credential.GetNetworkCredential().Password }
    $connection=New-Object System.Data.SqlClient.SqlConnection -ArgumentList ($builder.get_ConnectionString())
    try { $connection.Open();return $connection } catch { $connection.Dispose();return $null }
}
function Read-VipRows([string]$Sql) {
    $command=$script:connection.CreateCommand();$command.CommandText=$Sql;$command.CommandTimeout=30
    $reader=$null;$rows=@()
    try {
        $reader=$command.ExecuteReader()
        while ($reader.Read()) {
            $row=[ordered]@{}
            for ($i=0;$i -lt $reader.FieldCount;$i++) {
                $value=$reader.GetValue($i)
                if ($value -is [DBNull]) { $value=$null }
                elseif ($value -is [DateTime]) { $value=$value.ToString('yyyy-MM-ddTHH:mm:ss') }
                $row[$reader.GetName($i)]=$value
            }
            $rows+=$row
        }
    } finally { if ($null -ne $reader) { $reader.Dispose() };$command.Dispose() }
    return ,$rows
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
if ($null -eq $script:connection) { $report.sql_error='SQL_UNAVAILABLE';Write-Host 'No se pudo leer SQL. El informe conserva la configuracion de archivos.' }
else {
    try {
        $report.sql_available=$true
        $report.sql_clock=Read-VipRows 'SELECT DB_NAME() AS DatabaseName,GETDATE() AS ServerLocalTime,GETUTCDATE() AS ServerUtcTime,DATEDIFF(MINUTE,GETUTCDATE(),GETDATE()) AS UtcOffsetMinutes'
        # Aggregate counts only: no account names, passwords, wallets or characters.
        $report.levels=Read-VipRows 'SELECT AccountLevel,COUNT_BIG(*) AS Accounts, SUM(CASE WHEN AccountExpireDate>GETDATE() THEN 1 ELSE 0 END) AS FutureExpiry FROM dbo.MEMB_INFO GROUP BY AccountLevel'
        $report.schema=Read-VipRows "SELECT c.name AS ColumnName,t.name AS SqlType,c.max_length AS MaxLength,c.is_nullable AS Nullable FROM sys.columns c JOIN sys.types t ON t.user_type_id=c.user_type_id WHERE c.object_id=OBJECT_ID('dbo.MEMB_INFO') AND c.name IN ('memb___id','AccountLevel','AccountExpireDate')"
        $report.modules=Read-VipRows @'
SELECT TOP(50) SCHEMA_NAME(o.schema_id) AS SchemaName,o.name AS ObjectName,o.type_desc AS ObjectType,
 CASE WHEN LEN(m.definition)<=40000 THEN m.definition ELSE NULL END AS Definition,
 CASE WHEN LEN(m.definition)>40000 THEN 1 ELSE 0 END AS TooLarge
FROM sys.objects o LEFT JOIN sys.sql_modules m ON m.object_id=o.object_id
WHERE o.is_ms_shipped=0 AND (o.name LIKE '%Vip%' OR o.name LIKE '%AccountLevel%' OR
 (m.definition LIKE '%AccountLevel%' AND m.definition LIKE '%AccountExpireDate%'))
ORDER BY o.name
'@
    } catch { $report.sql_error='SQL_READ_FAILED';Write-Host 'Una lectura SQL no estuvo disponible. No se modifico la base.' }
    finally { $script:connection.Dispose() }
}
Write-VipJson 'diagnostico-vip.json' $report
$proposal=[ordered]@{ name='VIP PANIC'; days=30; normal_exp_extra_proposed=10; drop_extra_proposed=0; price_wcoin_c=$null; account_level=$null; enabled=$false; exp_calculation_verified=$false; renewal_verified=$false; note='Borrador. No instalar ni vender hasta confirmar calculo de EXP, nivel SQL, renovacion y precio.' }
Write-VipJson 'vip-propuesta-INACTIVA.json' $proposal
[IO.File]::WriteAllText((Join-Path $outRoot 'LEEME.txt'),@'
MU PANIC - revision VIP
Solo se leyeron archivos y SQL de MuOnline43. No se ejecutaron procedimientos de juego.
No se cambiaron niveles, vencimientos, monedas, tablas, servicios ni tareas.
La propuesta es un borrador inactivo: 30 dias, +10 EXP normal propuesto, 0 extra drop.
El precio, el nivel SQL y la regla de renovacion quedan pendientes de verificacion.
No reemplazar CustomBuyVip.txt con este borrador.
La verificacion de EXP real necesitara una prueba controlada dentro del juego.
'@,$utf8)
$zip=$outRoot+'.zip'
Compress-Archive -LiteralPath $outRoot -DestinationPath $zip
Write-Host ('LISTO. Subi este ZIP al chat: ' + $zip)
Write-Host 'El servidor y las cuentas no fueron modificados.'
