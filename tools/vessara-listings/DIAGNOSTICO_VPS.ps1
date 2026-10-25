# Mercado de Vessara: ONLY SQL metadata, no item values, credentials or mutations.
# Ejecutar en el VPS (Windows) con PowerShell; base fija MuOnline43.
$ErrorActionPreference='Stop'
$cn=New-Object System.Data.SqlClient.SqlConnection 'Server=localhost;Database=MuOnline43;Integrated Security=True;Application Name=VessaraListingMetadata'
$cn.Open()
try {
 $queries=@(
 "SELECT DB_NAME() AS DbName,CONVERT(varchar(30),GETDATE(),126) AS ServerTime",
 "SELECT o.name,o.type_desc FROM sys.objects o WHERE o.schema_id=SCHEMA_ID('dbo') AND (o.name LIKE 'PanicMarket%' OR o.name LIKE '%Market%Featured%' OR o.name LIKE '%Market%Highlight%') ORDER BY o.name",
 "SELECT o.name AS ObjectName,c.column_id,c.name AS ColumnName,t.name AS SqlType,c.max_length,c.is_nullable FROM sys.objects o JOIN sys.columns c ON c.object_id=o.object_id JOIN sys.types t ON t.user_type_id=c.user_type_id WHERE o.schema_id=SCHEMA_ID('dbo') AND (o.name IN ('PanicMarketPersistentProbe','PanicMarketPublishedBrowse','PanicMarketBrowse','PanicMarketBrowseCatalog') OR o.name LIKE '%Market%Featured%' OR o.name LIKE '%Market%Highlight%') ORDER BY o.name,c.column_id"
 )
 $report=New-Object System.Text.StringBuilder
 [void]$report.AppendLine('VESSARA - METADATOS DE PUBLICACIONES - SOLO LECTURA')
 [void]$report.AppendLine('No incluye cuentas, codigos de item, seriales ni inventarios.')
 foreach($query in $queries){
  $cmd=$cn.CreateCommand();$cmd.CommandText=$query;$cmd.CommandTimeout=30
  $adapter=New-Object System.Data.SqlClient.SqlDataAdapter($cmd);$table=New-Object System.Data.DataTable
  try{$null=$adapter.Fill($table)}finally{$adapter.Dispose();$cmd.Dispose()}
  [void]$report.AppendLine('')
  [void]$report.AppendLine('CONSULTA: '+$query)
  if($table.Rows.Count -eq 0){[void]$report.AppendLine('(sin resultados)')}
  foreach($r in $table.Rows){
   $fields=@();foreach($c in $table.Columns){$fields+=($c.ColumnName+'='+[string]$r[$c.ColumnName])}
   [void]$report.AppendLine(($fields -join ' | '))
  }
 }
 $out=Join-Path ([Environment]::GetFolderPath('Desktop')) ('VESSARA_LISTINGS_DIAGNOSTICO_'+(Get-Date -Format 'yyyyMMdd_HHmmss')+'.txt')
 [IO.File]::WriteAllText($out,$report.ToString(),(New-Object System.Text.UTF8Encoding($false)))
 Write-Host "DIAGNOSTICO OK. No se modifico SQL. Archivo: $out" -ForegroundColor Green
}finally{$cn.Dispose()}
