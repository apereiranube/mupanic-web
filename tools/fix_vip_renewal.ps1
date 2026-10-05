param([string]$ServerRoot='C:\MuServer43',[string]$SqlServer='', [System.Management.Automation.PSCredential]$SqlCredential,[switch]$Apply)
$ErrorActionPreference='Stop'
$resolvedRoot=(Resolve-Path -LiteralPath $ServerRoot).Path.TrimEnd('\')
if ($Apply -and @(Get-Process -Name 'GameServer','GameServerCS' -ErrorAction SilentlyContinue).Count -gt 0) {throw 'Cerra ambos GameServer antes de corregir la renovacion.'}
function Open-VipConnection([string]$Instance,$Credential) {
    $builder=New-Object System.Data.SqlClient.SqlConnectionStringBuilder
    $builder['Data Source']=$Instance;$builder['Initial Catalog']='MuOnline43';$builder['Connect Timeout']=5
    $builder['Application Name']='MU PANIC VIP Renewal Migration'
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


if ($null -eq $script:connection) {throw 'No se pudo abrir MuOnline43. No se modifico SQL.'}
try {
    $read=$script:connection.CreateCommand()
    $read.CommandText="SELECT definition FROM sys.sql_modules WHERE object_id=OBJECT_ID(N'dbo.WZ_SetAccountLevel',N'P')"
    try {$original=$read.ExecuteScalar()} finally {$read.Dispose()}
    if ($null -eq $original -or $original -is [DBNull]) {throw 'No se pudo leer WZ_SetAccountLevel. No se modifico SQL.'}
    $proposed=@'
ALTER PROCEDURE [dbo].[WZ_SetAccountLevel]
@Account varchar(10), @AccountLevel int, @AccountExpireTime int
AS
BEGIN
-- MU_PANIC_RENEWAL_V1: membership only; never debit or grant coins here.
SET NOCOUNT ON;
SET XACT_ABORT ON;
DECLARE @CurrentLevel int, @Expiry smalldatetime, @Now datetime=GETDATE(), @OwnTransaction bit=0;
BEGIN TRY
    IF @@TRANCOUNT=0 BEGIN SET @OwnTransaction=1; BEGIN TRANSACTION; END
    ELSE SAVE TRANSACTION MuPanicVipRenewal;
    SELECT @CurrentLevel=AccountLevel,@Expiry=AccountExpireDate
    FROM dbo.MEMB_INFO WITH (UPDLOCK,HOLDLOCK) WHERE memb___id=@Account;
    IF @CurrentLevel IS NULL THROW 51000,'VIP account was not found.',1;
    IF @CurrentLevel<>@AccountLevel OR @Expiry<=@Now SET @Expiry=@Now;
    SET @Expiry=DATEADD(second,@AccountExpireTime,@Expiry);
    UPDATE dbo.MEMB_INFO SET AccountLevel=@AccountLevel,AccountExpireDate=@Expiry WHERE memb___id=@Account;
    IF @@ROWCOUNT<>1 THROW 51000,'VIP update failed.',1;
    IF @OwnTransaction=1 COMMIT TRANSACTION;
END TRY
BEGIN CATCH
    IF @OwnTransaction=1 AND XACT_STATE()<>0 ROLLBACK TRANSACTION;
    ELSE IF @OwnTransaction=0 AND XACT_STATE()=1 ROLLBACK TRANSACTION MuPanicVipRenewal;
    THROW;
END CATCH;
END
'@
    function Normalize-VipSql([string]$Value) {
        # SQL metadata may store CREATE even when installation uses ALTER.
        # Canonicalize only the leading DDL verb; keep the full body comparison.
        $header=[regex]::Replace($Value,'(?i)\A\s*(?:CREATE(?:\s+OR\s+ALTER)?|ALTER)\s+PROC(?:EDURE)?\b','CREATE PROCEDURE')
        return [regex]::Replace($header,'\s+','').ToLowerInvariant()
    }
    if ($original.Contains('MU_PANIC_RENEWAL_V1')) {
        if ((Normalize-VipSql $original) -cne (Normalize-VipSql $proposed)) {throw 'El procedimiento tiene la marca VIP pero su contenido no coincide. No se modifico SQL.'}
        Write-Host 'Renovacion ya corregida y verificada. No se modifico SQL.';return
    }
    # Refuse to overwrite a different vendor procedure or custom integration.
    $expected=@'
CREATE Procedure [dbo].[WZ_SetAccountLevel]
@Account varchar(10),
@AccountLevel int,
@AccountExpireTime int
AS
BEGIN

SET NOCOUNT ON
SET XACT_ABORT ON

DECLARE @CurrentAccountLevel int
DECLARE @CurrentAccountExpireDate smalldatetime

SELECT @CurrentAccountLevel=AccountLevel,@CurrentAccountExpireDate=AccountExpireDate FROM MEMB_INFO WHERE memb___id=@Account

IF(@CurrentAccountLevel = @AccountLevel)
BEGIN
	SET @CurrentAccountLevel = @CurrentAccountLevel

	SET @CurrentAccountExpireDate = DATEADD(second,@AccountExpireTime,@CurrentAccountExpireDate)
END
ELSE
BEGIN
	SET @CurrentAccountLevel = @AccountLevel

	SET @CurrentAccountExpireDate = DATEADD(second,@AccountExpireTime,getdate())
END

UPDATE MEMB_INFO SET AccountLevel=@CurrentAccountLevel,AccountExpireDate=@CurrentAccountExpireDate WHERE memb___id=@Account

SET NOCOUNT OFF
SET XACT_ABORT OFF

END

'@
    if ((Normalize-VipSql $original) -cne (Normalize-VipSql $expected)) {throw 'WZ_SetAccountLevel difiere del procedimiento auditado. No se reemplazo.'}
    Write-Host 'Correccion: una renovacion vencida suma desde ahora; una vigente conserva sus dias.'
    if (-not $Apply) {Write-Host 'Solo vista previa. Usa -Apply para instalar.';return}
    $backupRoot=Join-Path $resolvedRoot ('VipSqlBackups\'+(Get-Date -Format 'yyyyMMdd_HHmmss')+'_'+[Guid]::NewGuid().ToString('N').Substring(0,6))
    [IO.Directory]::CreateDirectory($backupRoot)|Out-Null
    [IO.File]::WriteAllText((Join-Path $backupRoot 'WZ_SetAccountLevel.original.sql'),$original,(New-Object Text.UTF8Encoding($false)))
    [IO.File]::WriteAllText((Join-Path $backupRoot 'WZ_SetAccountLevel.proposed.sql'),$proposed,(New-Object Text.UTF8Encoding($false)))
    $transaction=$script:connection.BeginTransaction()
    try {
        # Verify the original again inside the DDL transaction before alteration.
        $guard=$script:connection.CreateCommand();$guard.Transaction=$transaction
        $guard.CommandText="SELECT definition FROM sys.sql_modules WHERE object_id=OBJECT_ID(N'dbo.WZ_SetAccountLevel',N'P')"
        try {$current=$guard.ExecuteScalar()} finally {$guard.Dispose()}
        if ($current -cne $original) {throw 'El procedimiento cambio durante la preparacion.'}
        $alter=$script:connection.CreateCommand();$alter.Transaction=$transaction;$alter.CommandText=$proposed;$alter.CommandTimeout=30
        try {$alter.ExecuteNonQuery()|Out-Null} finally {$alter.Dispose()}
        $verify=$script:connection.CreateCommand();$verify.Transaction=$transaction
        $verify.CommandText="SELECT definition FROM sys.sql_modules WHERE object_id=OBJECT_ID(N'dbo.WZ_SetAccountLevel',N'P')"
        try {$definition=$verify.ExecuteScalar()} finally {$verify.Dispose()}
        if ($null -eq $definition -or $definition -is [DBNull] -or (Normalize-VipSql $definition) -cne (Normalize-VipSql $proposed)) {
            if ($null -ne $definition -and $definition -isnot [DBNull]) {
                [IO.File]::WriteAllText((Join-Path $backupRoot 'WZ_SetAccountLevel.received.sql'),[string]$definition,(New-Object Text.UTF8Encoding($false)))
            }
            throw ('No se pudo verificar el procedimiento instalado. Diagnostico: '+$backupRoot)
        }
        $transaction.Commit()
    } catch {
        $installationError=$_
        try {
            $transaction.Rollback()
            Write-Host 'Cambio SQL revertido; el instalador se detuvo antes de cambiar el precio.'
        } catch { Write-Warning 'No se pudo confirmar el rollback SQL. Conserva el respaldo y la salida completa.' }
        throw $installationError
    } finally {$transaction.Dispose()}
    Write-Host ('Renovacion corregida. Respaldo: '+$backupRoot)
    Write-Host 'No se ejecutaron compras ni cambios de cuenta o saldo.'
} finally {$script:connection.Dispose()}
