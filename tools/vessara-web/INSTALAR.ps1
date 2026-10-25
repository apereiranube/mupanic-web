param([string]$Raiz='C:\MuServer43')
$ErrorActionPreference='Stop'
$manifest=Get-Content -LiteralPath (Join-Path $PSScriptRoot 'ARCHIVOS.json') -Raw | ConvertFrom-Json
foreach($f in $manifest){if((Get-FileHash -LiteralPath (Join-Path $PSScriptRoot $f.Name) -Algorithm SHA256).Hash -ne $f.SHA256){throw "Archivo alterado: $($f.Name)"}}
$cn=New-Object System.Data.SqlClient.SqlConnection 'Server=localhost;Database=MuOnline43;Integrated Security=True;Application Name=VessaraWebPublicRead'
$cn.Open();$tx=$null
function Scalar([string]$q){$cmd=$cn.CreateCommand();$cmd.CommandTimeout=30;$cmd.CommandText=$q;if($script:tx){$cmd.Transaction=$script:tx};try{return $cmd.ExecuteScalar()}finally{$cmd.Dispose()}}
function Run([string]$q){$cmd=$cn.CreateCommand();$cmd.CommandTimeout=30;$cmd.CommandText=$q;if($script:tx){$cmd.Transaction=$script:tx};try{$null=$cmd.ExecuteNonQuery()}finally{$cmd.Dispose()}}
try {
 foreach($t in @('PanicMarketBuyProbe','PanicMarketPersistentProbe','PanicMarketBrowseCatalog')){if([int](Scalar "SELECT COUNT(*) FROM sys.tables WHERE name='$t' AND schema_id=SCHEMA_ID('dbo')") -ne 1){throw "Falta dbo.$t. No se cambio nada."}}
 if((Scalar "SELECT DB_NAME()") -ne 'MuOnline43'){throw 'Base incorrecta.'}
 $backup=Join-Path $Raiz ('MU_PANIC_BACKUPS\VESSARA_WEB_'+(Get-Date -Format 'yyyyMMdd_HHmmss')+'_'+[guid]::NewGuid().ToString('N').Substring(0,6))
 $null=New-Item -ItemType Directory -Path $backup -Force
 $old=Scalar "SELECT OBJECT_DEFINITION(OBJECT_ID('dbo.PanicMarketPublicSales'))"
 if($old -and $old -ne [DBNull]::Value){[IO.File]::WriteAllText((Join-Path $backup 'VistaAnterior.sql'),[string]$old,[Text.Encoding]::UTF8)}
 $tx=$cn.BeginTransaction();$script:tx=$tx
 if([int](Scalar "SELECT COUNT(*) FROM sys.views WHERE name='PanicMarketPublicSales' AND schema_id=SCHEMA_ID('dbo')") -eq 0){Run "EXEC('CREATE VIEW dbo.PanicMarketPublicSales AS SELECT CONVERT(int,0) AS Placeholder')"}
 $view=Get-Content -LiteralPath (Join-Path $PSScriptRoot 'VISTA.sql') -Raw
 Run $view
 # This view contains only the explicitly public market projection.
 # Database users can read it through same-owner chaining; underlying tables remain protected.
 Run 'GRANT SELECT ON OBJECT::dbo.PanicMarketPublicSales TO public;'
 $real=[long](Scalar "SELECT COUNT_BIG(*) FROM dbo.PanicMarketBuyProbe WHERE Delivered IS NOT NULL AND State IN('DELIVERY_SAVED','CREDIT_ATTEMPTED','CLOSED')")
 $shown=[long](Scalar 'SELECT COUNT_BIG(*) FROM dbo.PanicMarketPublicSales')
 if($real -ne $shown){throw 'La vista no coincide con el registro de compras.'}
 if([int](Scalar "SELECT COUNT(*) FROM sys.columns WHERE object_id=OBJECT_ID('dbo.PanicMarketPublicSales')") -ne 9){throw 'Columnas publicas incorrectas.'}
 if([int](Scalar "SELECT COUNT(*) FROM sys.columns WHERE object_id=OBJECT_ID('dbo.PanicMarketPublicSales') AND name IN('AccountID','BuyerAccount','ItemCode','Inventory','BuyerBefore','BuyerAfter','Fee','Net')") -ne 0){throw 'La vista contiene campos privados.'}
 $tx.Commit();$tx.Dispose();$script:tx=$null;$tx=$null
 Write-Host "VESSARA_WEB_OK: $shown ventas confirmadas en la vista publica." -ForegroundColor Green
 Write-Host 'Consulta publica instalada. Inventarios, saldos y servidores no se modificaron.'
 Write-Host "Backup: $backup"
 Write-Host 'Actualiza y despliega la rama beta en cPanel. No reinicies los GS.'
 $cmd=$cn.CreateCommand();$cmd.CommandText='SELECT Currency AS Moneda,COUNT_BIG(*) AS Tratos,SUM(CONVERT(bigint,Price)) AS Volumen FROM dbo.PanicMarketPublicSales GROUP BY Currency ORDER BY Currency;'
 $adapter=New-Object System.Data.SqlClient.SqlDataAdapter($cmd);$dt=New-Object System.Data.DataTable;$null=$adapter.Fill($dt);$dt | Format-Table -AutoSize;$adapter.Dispose();$cmd.Dispose()
}finally{if($tx){try{$tx.Rollback()}catch{};$tx.Dispose()};$cn.Dispose()}
