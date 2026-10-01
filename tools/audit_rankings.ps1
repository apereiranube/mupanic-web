param(
    [string]$SqlServer = '',
    [System.Management.Automation.PSCredential]$SqlCredential,
    [string]$ServerRoot = 'C:\MuServer43'
)
$ErrorActionPreference = 'Stop'
# READ ONLY. Database is deliberately fixed to the active MU PANIC database.
# No character names, account records, passwords or connection strings are exported.
function Open-RankingConnection([string]$Instance, $Credential) {
    $builder = New-Object System.Data.SqlClient.SqlConnectionStringBuilder
    $builder.DataSource = $Instance
    $builder.InitialCatalog = 'MuOnline43'
    $builder.ConnectTimeout = 5
    $builder.ApplicationName = 'MU PANIC Read Only Ranking Audit'
    if ($null -eq $Credential) { $builder.IntegratedSecurity = $true }
    else {
        $builder.UserID = $Credential.UserName
        $builder.Password = $Credential.GetNetworkCredential().Password
    }
    $connection = New-Object System.Data.SqlClient.SqlConnection $builder.ConnectionString
    try { $connection.Open(); return $connection }
    catch { $connection.Dispose(); return $null }
}
function Read-RankingTable([string]$Sql) {
    $command = $script:connection.CreateCommand()
    $command.CommandText = $Sql
    $command.CommandTimeout = 30
    $table = New-Object System.Data.DataTable
    $reader = $null
    try { $reader = $command.ExecuteReader(); $table.Load($reader) }
    finally { if ($null -ne $reader) { $reader.Dispose() }; $command.Dispose() }
    return ,$table
}
function Quote-SqlIdentifier([string]$Name) { return '[' + $Name.Replace(']', ']]') + ']' }
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
    $script:connection = Open-RankingConnection $instance $SqlCredential
    if ($null -ne $script:connection) { break }
}
if ($null -eq $script:connection -and $null -eq $SqlCredential) {
    Write-Host 'Windows authentication could not open MuOnline43. Enter SQL credentials locally; they are NOT exported.'
    $SqlCredential = Get-Credential -Message 'SQL login with SELECT permission on MuOnline43'
    if ($null -ne $SqlCredential) {
        foreach ($instance in $instances) {
            $script:connection = Open-RankingConnection $instance $SqlCredential
            if ($null -ne $script:connection) { break }
        }
    }
}
if ($null -eq $script:connection) { throw 'Could not connect to MuOnline43. Run again with -SqlServer YOUR_INSTANCE. No server files were changed.' }
try {
    $schema = Read-RankingTable @'
SELECT s.name AS schema_name, t.name AS table_name, c.name AS column_name,
       ty.name AS data_type, c.max_length, c.is_nullable,
       COALESCE((SELECT SUM(p.rows) FROM sys.partitions p WHERE p.object_id=t.object_id AND p.index_id IN (0,1)),0) AS approximate_rows
FROM sys.tables t
JOIN sys.schemas s ON s.schema_id=t.schema_id
JOIN sys.columns c ON c.object_id=t.object_id
JOIN sys.types ty ON ty.user_type_id=c.user_type_id
ORDER BY s.name,t.name,c.column_id
'@
    $candidates = @($schema.Rows | Where-Object {
        $_.table_name -match '(?i)Character|Rank|Event|Blood|Devil|Chaos|Duel|Boss|Monster|PvP|Achievement|Gens|Score|Arena' -or
        $_.column_name -match '(?i)Reset|PKCount|BloodCastle|DevilSquare|ChaosCastle|BossKill|DuelWin'
    })
    $columns = @($candidates | ForEach-Object {
        [ordered]@{ schema=[string]$_.schema_name; table=[string]$_.table_name; column=[string]$_.column_name;
            type=[string]$_.data_type; maxBytes=[int]$_.max_length; approximateRows=[long]$_.approximate_rows }
    })
    $metrics = @()
    foreach ($column in $candidates) {
        if ($column.data_type -notin @('tinyint','smallint','int','bigint','decimal','numeric','float','real')) { continue }
        if ($column.column_name -notmatch '(?i)Reset|PKCount|PKLevel|Kill|Death|Win|Lose|Loss|Score|Point|Blood|Devil|Chaos|Duel|Boss|Monster|Achievement|Gens|Arena|Event') { continue }
        # Sample at most 5000 rows. This is an existence audit, not a public leaderboard.
        $qualified = (Quote-SqlIdentifier $column.schema_name) + '.' + (Quote-SqlIdentifier $column.table_name)
        $field = Quote-SqlIdentifier $column.column_name
        $sql = "SELECT COUNT_BIG(*) AS sample_rows, COALESCE(SUM(CASE WHEN metric>0 THEN CAST(1 AS bigint) ELSE CAST(0 AS bigint) END),0) AS positive_rows, MAX(metric) AS maximum FROM (SELECT TOP (5000) CONVERT(float,$field) AS metric FROM $qualified) sample"
        $item = [ordered]@{schema=[string]$column.schema_name;table=[string]$column.table_name;column=[string]$column.column_name;sampleLimit=5000}
        try {
            $result = Read-RankingTable $sql
            $item['sampleRows'] = [long]$result.Rows[0].sample_rows
            $item['positiveRows'] = [long]$result.Rows[0].positive_rows
            $item['maximum'] = if ($result.Rows[0].maximum -is [DBNull]) { $null } else { [double]$result.Rows[0].maximum }
        } catch { $item['status'] = 'Column could not be sampled; check permissions or data type.' }
        $metrics += $item
    }
    $settings = @()
    foreach ($relativePath in @('Data\Custom\CustomRanking.txt','Data\Custom\CustomAchievements.txt','GameServer\Data\GameServerInfo - Common.dat','GameServerCS\Data\GameServerInfo - Common.dat')) {
        $path = Join-Path $ServerRoot $relativePath
        if (-not (Test-Path -LiteralPath $path)) { continue }
        if ($relativePath -like '*Common.dat') {
            $lines = @(Get-Content -LiteralPath $path | Where-Object { $_ -match '(?i)^\s*(WriteEventLog|DuelSwitch|DuelMaxScore|GensSystemKiller\w*|CustomRanking\w*|CustomAchievements\w*)\s*=' })
        } else { $lines = @(Get-Content -LiteralPath $path | Select-Object -First 220) }
        $settings += [ordered]@{file=$relativePath;lines=$lines}
    }
    $report = [ordered]@{format='mupanic-ranking-audit-v1';database='MuOnline43';createdUtc=[DateTime]::UtcNow.ToString('o');
        note='Read-only metadata and aggregate samples. No identities or credentials. Positive sample counts do not prove that game counters still update.';
        columns=$columns;metrics=$metrics;settings=$settings}
    $desktop = [Environment]::GetFolderPath('Desktop')
    $destination = Join-Path $desktop ('MU_PANIC_RANKINGS_AUDIT_' + (Get-Date -Format 'yyyyMMdd_HHmmss') + '.json')
    $report | ConvertTo-Json -Depth 8 | Set-Content -LiteralPath $destination -Encoding UTF8
    Write-Host ('Audit ready: ' + $destination)
    Write-Host ('Candidate columns: ' + $columns.Count + '; aggregate samples: ' + $metrics.Count)
    Write-Host 'Upload the JSON. No database, game configuration or game processes were changed.'
} finally { $script:connection.Dispose() }
