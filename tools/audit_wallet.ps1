param(
    [string]$SqlServer = '',
    [System.Management.Automation.PSCredential]$SqlCredential
)
$ErrorActionPreference = 'Stop'
# READ ONLY. Database is deliberately fixed to the active MU PANIC database.
# No character names, account records, passwords or connection strings are exported.
function Open-WalletConnection([string]$Instance, $Credential) {
    $builder = New-Object System.Data.SqlClient.SqlConnectionStringBuilder
    # PowerShell adapts this builder as a dictionary: use SQL keywords explicitly.
    $builder['Data Source'] = $Instance
    $builder['Initial Catalog'] = 'MuOnline43'
    $builder['Connect Timeout'] = 5
    $builder['Application Name'] = 'MU PANIC Read Only WCoin Audit'
    if ($null -eq $Credential) { $builder['Integrated Security'] = $true }
    else {
        $builder['User ID'] = $Credential.UserName
        $builder['Password'] = $Credential.GetNetworkCredential().Password
    }
    $connection = New-Object System.Data.SqlClient.SqlConnection -ArgumentList ($builder.get_ConnectionString())
    try { $connection.Open(); return $connection }
    catch { $connection.Dispose(); return $null }
}
function Read-WalletTable([string]$Sql) {
    $command = $script:connection.CreateCommand()
    $command.CommandText = $Sql
    $command.CommandTimeout = 30
    $table = New-Object System.Data.DataTable
    $reader = $null
    try { $reader = $command.ExecuteReader(); $table.Load($reader) }
    finally { if ($null -ne $reader) { $reader.Dispose() }; $command.Dispose() }
    return ,$table
}
$instances = @()
if ($SqlServer) { $instances = @($SqlServer) }
else {
    foreach ($registryPath in @('HKLM:\SOFTWARE\Microsoft\Microsoft SQL Server\Instance Names\SQL', 'HKLM:\SOFTWARE\WOW6432Node\Microsoft\Microsoft SQL Server\Instance Names\SQL')) {
        if (Test-Path $registryPath) {
            foreach ($property in (Get-ItemProperty $registryPath).PSObject.Properties) {
                if ($property.Name -notlike 'PS*') {
                    if ($property.Name -eq 'MSSQLSERVER') { $instances += 'localhost' }
                    else { $instances += ('localhost\' + $property.Name) }
                }
            }
        }
    }
    $instances += @('localhost', 'localhost\SQLEXPRESS')
    $instances = @($instances | Select-Object -Unique)
}
$script:connection = $null
foreach ($instance in $instances) {
    $script:connection = Open-WalletConnection $instance $SqlCredential
    if ($null -ne $script:connection) { break }
}
if ($null -eq $script:connection -and $null -eq $SqlCredential) {
    Write-Host 'Windows authentication could not open MuOnline43. Enter SQL credentials locally; they are NOT exported.'
    $SqlCredential = Get-Credential -Message 'SQL login with SELECT permission on MuOnline43'
    if ($null -ne $SqlCredential) {
        foreach ($instance in $instances) {
            $script:connection = Open-WalletConnection $instance $SqlCredential
            if ($null -ne $script:connection) { break }
        }
    }
}
if ($null -eq $script:connection) { throw 'Could not connect to MuOnline43. Run again with -SqlServer YOUR_INSTANCE. No server files were changed.' }
try {
    $columns = Read-WalletTable @'
SELECT s.name AS schema_name, t.name AS table_name, c.name AS column_name,
       ty.name AS data_type, c.max_length, c.precision, c.scale, c.is_nullable,
       COALESCE((SELECT SUM(p.rows) FROM sys.partitions p WHERE p.object_id=t.object_id AND p.index_id IN (0,1)),0) AS approximate_rows
FROM sys.tables t
JOIN sys.schemas s ON s.schema_id=t.schema_id
JOIN sys.columns c ON c.object_id=t.object_id
JOIN sys.types ty ON ty.user_type_id=c.user_type_id
WHERE t.name LIKE '%CashShop%' OR t.name LIKE '%Coin%' OR t.name LIKE '%Wallet%'
   OR t.name IN ('MEMB_STAT','MEMB_INFO')
   OR c.name LIKE '%WCoin%' OR c.name LIKE '%Goblin%'
ORDER BY s.name,t.name,c.column_id
'@
    $indexes = Read-WalletTable @'
SELECT s.name AS schema_name, t.name AS table_name, i.name AS index_name,
       i.is_unique, i.is_primary_key, c.name AS column_name,
       ic.key_ordinal, ic.is_included_column
FROM sys.tables t
JOIN sys.schemas s ON s.schema_id=t.schema_id
JOIN sys.indexes i ON i.object_id=t.object_id
JOIN sys.index_columns ic ON ic.object_id=i.object_id AND ic.index_id=i.index_id
JOIN sys.columns c ON c.object_id=ic.object_id AND c.column_id=ic.column_id
WHERE t.name LIKE '%CashShop%' OR t.name LIKE '%Coin%' OR t.name LIKE '%Wallet%'
   OR t.name IN ('MEMB_STAT','MEMB_INFO')
ORDER BY s.name,t.name,i.name,ic.key_ordinal
'@
    # Names only: never export procedure definitions, account rows or balances.
    $procedures = Read-WalletTable @'
SELECT s.name AS schema_name, o.name AS object_name, o.type_desc
FROM sys.objects o
JOIN sys.schemas s ON s.schema_id=o.schema_id
LEFT JOIN sys.sql_modules m ON m.object_id=o.object_id
WHERE o.type IN ('P','TR') AND (o.name LIKE '%CashShop%' OR o.name LIKE '%WCoin%'
 OR m.definition LIKE '%CashShopData%' OR m.definition LIKE '%WCoinC%')
ORDER BY s.name,o.name
'@
    function Export-WalletRows($Table) {
        $rows = @()
        foreach ($row in $Table.Rows) {
            $item = [ordered]@{}
            foreach ($column in $Table.Columns) {
                $value = $row[$column.ColumnName]
                if ($value -is [DBNull]) { $value = $null }
                $item[$column.ColumnName] = $value
            }
            $rows += [pscustomobject]$item
        }
        return $rows
    }
    $report = [ordered]@{
        format = 'mupanic-wallet-audit-v1'
        database = 'MuOnline43'
        createdUtc = [DateTime]::UtcNow.ToString('o')
        note = 'Read only metadata. No account records, balances, secrets or SQL definitions.'
        columns = @(Export-WalletRows $columns)
        indexes = @(Export-WalletRows $indexes)
        procedures = @(Export-WalletRows $procedures)
    }
    $output = Join-Path ([Environment]::GetFolderPath('Desktop')) ('MU_PANIC_WALLET_AUDIT_' + (Get-Date -Format 'yyyyMMdd_HHmmss') + '.json')
    $json = $report | ConvertTo-Json -Depth 8
    [System.IO.File]::WriteAllText($output, $json, (New-Object System.Text.UTF8Encoding($false)))
    Write-Host ('Listo: ' + $output)
    Write-Host 'Subi este JSON al chat. No se modifico la base ni el servidor.'
} finally {
    $script:connection.Dispose()
}
