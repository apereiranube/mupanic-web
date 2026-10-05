param([string[]]$Roots=@('C:\Cliente_louis update 43','C:\MuServer43\GameServer','C:\MuServer43\GameServerCS','C:\MuServer43\Data'))
$ErrorActionPreference='Stop'
$files=@()
foreach ($root in $Roots) {
    if (-not (Test-Path -LiteralPath $root -PathType Container)) { continue }
    foreach ($file in @(Get-ChildItem -LiteralPath $root -Recurse -File -Filter '*.lua')) {
        $text=[IO.File]::ReadAllText($file.FullName)
        # Inventory only. Never export SQL connections, tokens, or script bodies.
        $apis=@([regex]::Matches($text,'\b(?:BridgeFunctionAttach|CheckCustomWindow|CloseCustomWindow|UserSetAccountLevel|ObjectAddCoin|ObjectSubCoin|SQLQuery|GameServerProtocol|ClientProtocol|MainInterfaceProcThread)\b') | ForEach-Object {$_.Value} | Select-Object -Unique)
        $loads=@([regex]::Matches($text,'(?m)^[ \t]*(?:require|dofile)[ \t]*\(?[ \t]*["''][A-Za-z0-9_./\\ -]+["''][ \t]*\)?[ \t]*;?[ \t]*$') | ForEach-Object {$_.Value.Trim()})
        $files+=@{path=$file.FullName;bytes=$file.Length;sha256=(Get-FileHash -LiteralPath $file.FullName -Algorithm SHA256).Hash;apis=$apis;loads=$loads}
    }
}
$out=Join-Path ([Environment]::GetFolderPath('Desktop')) ('MU_PANIC_VIP_INTERFAZ_'+(Get-Date -Format 'yyyyMMdd_HHmmss')+'.json')
[IO.File]::WriteAllText($out,(@{version=1;roots=$Roots;lua_files=$files}|ConvertTo-Json -Depth 8),(New-Object Text.UTF8Encoding($false)))
Write-Host ('Inventario listo: '+$out)
Write-Host ('Archivos Lua encontrados: '+$files.Count+'. No se modifico el cliente ni el servidor; no se exportaron credenciales.')
