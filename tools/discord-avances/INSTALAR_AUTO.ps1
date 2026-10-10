param([string]$Raiz='C:\MU_PANIC_BOT')
$ErrorActionPreference='Stop'
[Net.ServicePointManager]::SecurityProtocol=[Net.SecurityProtocolType]::Tls12
$taskName='MU PANIC Discord Bot'
$index=Join-Path $Raiz 'index.js'
$expected='EF8B00260581537207B360DE58F8446AB679709346D7D15450DCDEF40FAF3F5D'
if((Get-FileHash -LiteralPath $index -Algorithm SHA256).Hash -ne $expected){throw 'El index.js no coincide con la version revisada. No se modifico nada.'}
$task=Get-ScheduledTask -TaskName $taskName -ErrorAction Stop
if($task.State -ne 'Running'){throw 'El bot debe estar Running antes de instalar. No se modifico nada.'}
$node=(Get-Command node.exe -ErrorAction Stop).Source
$stage=Join-Path $env:TEMP ('MU_PANIC_AVANCES_AUTO_'+[guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Path $stage | Out-Null
$hashes=@{
 'novedades.js'='95F3DADA11B6DDBCF4838760134F7E601947299A317B6C6D4C212F88843BA6DC'
 'avances.js'='819606E30B61FBCBB067364EE9FE3928C7CD0B38872A9D045222A5B4801DEB84'
 'avances-remote.js'='2A0676A1414F64656E30171E3F6194B0F2F422EDA4300ADFDBE7E384B421748B'
}
try {
 foreach($name in $hashes.Keys){
  $out=Join-Path $stage $name
  Invoke-WebRequest -UseBasicParsing -Uri ('https://raw.githubusercontent.com/apereiranube/mupanic-web/beta/tools/discord-avances/'+$name) -OutFile $out
  if((Get-FileHash -LiteralPath $out -Algorithm SHA256).Hash -ne $hashes[$name]){throw "No coincide la validacion de $name. No se modifico nada."}
  & $node --check $out
  if($LASTEXITCODE -ne 0){throw "Error de sintaxis en $name. No se modifico nada."}
 }
 $backup=Join-Path $Raiz ('BACKUPS\AVANCES_AUTO_'+(Get-Date -Format 'yyyyMMdd_HHmmss')+'_'+[guid]::NewGuid().ToString('N').Substring(0,8))
 New-Item -ItemType Directory -Path $backup -Force | Out-Null
 $originals=@()
 foreach($name in $hashes.Keys){$current=Join-Path $Raiz $name;if(Test-Path -LiteralPath $current){Copy-Item -LiteralPath $current -Destination (Join-Path $backup $name);$originals+=$name}}
 # El respaldo registra cuales archivos existian; no incluye credenciales.
 $originals | ConvertTo-Json | Set-Content (Join-Path $backup 'archivos-originales.json') -Encoding UTF8
 try {
  Stop-ScheduledTask -TaskName $taskName
  $limit=(Get-Date).AddSeconds(15)
  do {Start-Sleep -Milliseconds 300;$state=(Get-ScheduledTask -TaskName $taskName).State}while($state -eq 'Running' -and (Get-Date) -lt $limit)
  if($state -eq 'Running'){throw 'La tarea no se detuvo.'}
  $still=Get-CimInstance Win32_Process -Filter "Name='node.exe'" | Where-Object { $_.CommandLine -and ($_.CommandLine -like ('*'+$Raiz+'*')) }
  if($still){throw 'Quedo un proceso Node del bot activo. No se inicia otra instancia.'}
  foreach($name in $hashes.Keys){Copy-Item -LiteralPath (Join-Path $stage $name) -Destination (Join-Path $Raiz $name) -Force}
  Start-ScheduledTask -TaskName $taskName
  Start-Sleep -Seconds 3
  if((Get-ScheduledTask -TaskName $taskName).State -ne 'Running'){throw 'El bot no quedo Running.'}
  Write-Host "INSTALADO: avances conectados a Git. Backup: $backup"
  Write-Host 'Usa /avances en Discord. Elegi canal solo la primera vez. No se publico ningun anuncio.'
 } catch {
  $reason=$_.Exception.Message
  Stop-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
  foreach($name in $hashes.Keys){$target=Join-Path $Raiz $name;if($originals -contains $name){Copy-Item -LiteralPath (Join-Path $backup $name) -Destination $target -Force}elseif(Test-Path -LiteralPath $target){Remove-Item -LiteralPath $target -Force}}
  if((Get-ScheduledTask -TaskName $taskName).State -ne 'Running'){Start-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue}
  throw "Original restaurado. Motivo: $reason"
 }
} finally { if(Test-Path -LiteralPath $stage){Remove-Item -LiteralPath $stage -Recurse -Force} }
