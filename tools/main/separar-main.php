<?php
declare(strict_types=1);

// Installed only in the Basic-auth protected audit directory. No SQL or payment writes.
function mainApiNames(): array {
    return ['recharge-worker.php','recharge-hook.php','uala-pilot-worker.php','uala-pilot-hook.php','atlas-sync.php','atlas-events-sync.php','atlas-events.php'];
}
function mainCopy(string $source, string $destination, bool $staticOnly=false): void {
    if (is_link($source) || is_link($destination)) throw new RuntimeException('Enlace simbolico rechazado.');
    if (is_dir($source)) {
        if (!is_dir($destination) && !mkdir($destination,0755,true)) throw new RuntimeException('No se pudo crear el destino.');
        foreach (new DirectoryIterator($source) as $entry) {
            if ($entry->isDot()) continue;
            mainCopy($entry->getPathname(),$destination.'/'.$entry->getFilename(),$staticOnly);
        }
    } else {
        if ($staticOnly && !preg_match('/\.(?:css|js|png|jpe?g|webp|gif|svg|ico|woff2?|ttf|eot|avif|mp4|webm)$/i',$source)) return;
        if (!is_file($source) || !copy($source,$destination)) throw new RuntimeException('No se pudo copiar un archivo.');
        chmod($destination,0644);
    }
}
function mainWrite(string $path,string $content,int $mode=0644): void {
    if (is_link($path)) throw new RuntimeException('Destino con enlace simbolico.');
    $tmp=tempnam(dirname($path),'.main-');
    if ($tmp===false) throw new RuntimeException('No se pudo preparar el archivo.');
    try {
        chmod($tmp,$mode);
        if (file_put_contents($tmp,$content)!==strlen($content) || !rename($tmp,$path)) throw new RuntimeException('No se pudo guardar el archivo.');
    } finally { if (is_file($tmp)) unlink($tmp); }
}
function mainMove(string $source,string $destination): void {
    if (file_exists($destination) || is_link($source) || !rename($source,$destination)) throw new RuntimeException('No se pudo archivar/restaurar: '.basename($source));
}
function mainEntry(string $home): string {
    $backend=var_export($home.'/main-services/inc',true);
    return "<?php\n// MU PANIC: landing independiente de WebEngine y SQL.\ndefine('access',true);\ndefine('__BASE_URL__','https://www.mupanic.com.ar/');\ndefine('__PATH_TEMPLATE__','https://www.mupanic.com.ar/templates/mupanic/');\nheader('X-Content-Type-Options: nosniff');\n\$launchConfig=require $backend.'/launch-config.php';\nrequire $backend.'/launch-page.php';\n";
}
function mainRules(): string {
    $apis=implode('|',array_map(static fn($s)=>preg_quote($s,'~'),mainApiNames()));
    return "# MU PANIC LANDING ONLY v1\nOptions -Indexes\nDirectoryIndex index.php\nRewriteEngine On\n".
        // Only the known child subdomains bypass main rules; alternate Host headers do not.
        "RewriteCond %{HTTP_HOST} !^(?:beta|auditoria)\\.mupanic\\.com\\.ar(?::[0-9]+)?$ [NC]\nRewriteRule ^(?:$|index\\.php)$ - [END]\n".
        "RewriteCond %{HTTP_HOST} !^(?:beta|auditoria)\\.mupanic\\.com\\.ar(?::[0-9]+)?$ [NC]\nRewriteRule ^templates/mupanic/api/(?:$apis)$ - [END]\n".
        "RewriteCond %{HTTP_HOST} !^(?:beta|auditoria)\\.mupanic\\.com\\.ar(?::[0-9]+)?$ [NC]\nRewriteRule ^templates/mupanic/(?:css|js|img)/[A-Za-z0-9_./-]+\\.(?:css|js|png|jpe?g|webp|gif|svg|ico|woff2?|ttf|eot|avif|mp4|webm)$ - [END,NC]\n".
        "RewriteCond %{HTTP_HOST} !^(?:beta|auditoria)\\.mupanic\\.com\\.ar(?::[0-9]+)?$ [NC]\nRewriteRule ^(?:favicon\\.ico|robots\\.txt|sitemap\\.xml|\\.well-known/acme-challenge/[A-Za-z0-9_-]+)$ - [END]\n".
        "RewriteCond %{HTTP_HOST} !^(?:beta|auditoria)\\.mupanic\\.com\\.ar(?::[0-9]+)?$ [NC]\nRewriteRule ^ - [R=404,END]\n";
}
function mainGuard(string $original): string {
    // Preserve hosting-generated PHP handlers/settings, remove legacy routing by
    // leaving it unreachable on the apex host through the END allowlist above.
    return mainRules()."\n# Previous hosting configuration retained below\n".$original;
}
function mainChildGuard(string $host,string $original): string {
    return "# MU PANIC: prevent accessing this subdomain through a main folder alias\nRewriteEngine On\nRewriteCond %{HTTP_HOST} !^".
        str_replace('.','\\.',$host)."(?::[0-9]+)?$ [NC]\nRewriteRule ^ - [R=404,END]\n".$original;
}
function mainPreflight(string $home,string $source): array {
    $root=$home.'/public_html';
    foreach ([$home,$root,$source,$root.'/templates',$root.'/index.php',$root.'/.htaccess',$home.'/main-services',$home.'/main-landing-state.json'] as $path) {
        if (is_link($path)) throw new RuntimeException('Enlace simbolico rechazado.');
    }
    if (realpath($home)!==$home || realpath($root)!==$root || !is_file($root.'/beta/index.php')) throw new RuntimeException('Rutas de main/beta no verificadas.');
    foreach (['inc/launch-page.php','inc/launch-config.php','inc/atlas-runtime.php','inc/community-config.php','inc/public-balance.json','css/launch.css','js/launch.js'] as $file) {
        if (!is_file($source.'/'.$file)) throw new RuntimeException('Falta dependencia: '.$file);
    }
    foreach (mainApiNames() as $file) if (!is_file($source.'/api/'.$file)) throw new RuntimeException('Falta endpoint conservado: '.$file);
    $unknown=[];
    foreach (glob($source.'/api/*') ?: [] as $path) if (is_file($path) && !in_array(basename($path),mainApiNames(),true)) $unknown[]=basename($path);
    if ($unknown) throw new RuntimeException('Endpoints adicionales: revisar antes de separar: '.implode(', ',$unknown));
    if (!is_writable($home) || !is_writable($root)) throw new RuntimeException('Sin permisos de escritura.');
    return array_values(array_diff(scandir($root) ?: [],['.','..']));
}
function mainPrepare(string $home,string $source,string $work): void {
    mainCopy($source.'/inc',$work.'/services/inc');
    mainCopy($source.'/api',$work.'/services/api');
    chmod($work.'/services',0700);
    mkdir($work.'/public/api',0755,true);
    foreach (['css','js','img'] as $directory) if (is_dir($source.'/'.$directory)) mainCopy($source.'/'.$directory,$work.'/public/'.$directory,true);
    foreach (mainApiNames() as $file) {
        $target=var_export($home.'/main-services/api/'.$file,true);
        mainWrite($work.'/public/api/'.$file,"<?php\nrequire $target;\n");
    }
    // Render before changing anything: missing artwork/config is an abort, not a
    // broken production release. This bootstrap contains no WebEngine include.
    if (!defined('access')) define('access',true);
    if (!defined('__BASE_URL__')) define('__BASE_URL__','https://www.mupanic.com.ar/');
    if (!defined('__PATH_TEMPLATE__')) define('__PATH_TEMPLATE__','https://www.mupanic.com.ar/templates/mupanic/');
    $launchConfig=require $work.'/services/inc/launch-config.php';
    ob_start();
    try { require $work.'/services/inc/launch-page.php'; $html=(string)ob_get_contents(); }
    finally { ob_end_clean(); }
    if (!str_contains($html,'PRÓXIMAMENTE') || !str_contains($html,'</html>')) throw new RuntimeException('La landing no se pudo renderizar.');
    preg_match_all('~https://www\.mupanic\.com\.ar/templates/mupanic/([^"\s<>?]+)~',$html,$matches);
    foreach ($matches[1] as $asset) if (!is_file($work.'/public/'.$asset)) throw new RuntimeException('Falta recurso de la landing: '.$asset);
    mainWrite($work.'/rendered-preview.html',$html,0600);
}
function mainApply(string $home): string {
    $root=$home.'/public_html'; $source=$root.'/templates/mupanic';
    mainPreflight($home,$source);
    if (!is_file($root.'/index.php')) throw new RuntimeException('No se encontro el index original de main.');
    if (file_exists($home.'/main-landing-state.json') || file_exists($home.'/main-services')) throw new RuntimeException('Main ya esta separado o existe un destino previo.');
    $backup=$home.'/main-backups/landing-'.date('Ymd-His').'-'.bin2hex(random_bytes(4));
    if (!mkdir($backup,0700,true)) throw new RuntimeException('No se pudo crear el respaldo privado.');
    chmod($home.'/main-backups',0700);
    mkdir($backup.'/original',0700);
    mainPrepare($home,$source,$backup.'/prepared');
    $originalHt=is_file($root.'/.htaccess')?(string)file_get_contents($root.'/.htaccess'):'';
    $archive=['admincp','api','includes','modules','install','img','templates','index.php','index.html','index.htm','default.php','LICENSE','README.md','MU_PANIC_UPSTREAM.md'];
    $planned=array_values(array_filter($archive,static fn($name)=>file_exists($root.'/'.$name)));
    $state=['version'=>1,'status'=>'preparing','backup'=>$backup,'planned'=>$planned,'moved'=>[],'children'=>[],'originalHtExists'=>is_file($root.'/.htaccess')];
    foreach (['beta'=>'beta.mupanic.com.ar','auditoria-web'=>'auditoria.mupanic.com.ar'] as $name=>$host) {
        if (!is_dir($root.'/'.$name) || is_link($root.'/'.$name) || is_link($root.'/'.$name.'/.htaccess')) throw new RuntimeException('Subdominio no verificado.');
        $exists=is_file($root.'/'.$name.'/.htaccess');
        mainWrite($backup.'/original/'.$name.'-htaccess.txt',$exists?(string)file_get_contents($root.'/'.$name.'/.htaccess'):'',0600);
        $state['children'][$name]=['host'=>$host,'exists'=>$exists];
    }
    mainWrite($backup.'/original/htaccess.txt',$originalHt,0600);
    mainWrite($home.'/main-landing-state.json',json_encode($state,JSON_THROW_ON_ERROR),0600);
    try {
        // The allowlist closes legacy routes before the core is relocated.
        mainWrite($root.'/.htaccess',mainGuard($originalHt));
        foreach ($state['children'] as $name=>$child) mainWrite($root.'/'.$name.'/.htaccess',mainChildGuard($child['host'],(string)file_get_contents($backup.'/original/'.$name.'-htaccess.txt')));
        mainMove($backup.'/prepared/services',$home.'/main-services');
        foreach ($archive as $name) {
            $from=$root.'/'.$name;
            if (!file_exists($from)) continue;
            mainMove($from,$backup.'/original/'.$name);
            $state['moved'][]=$name;
            mainWrite($home.'/main-landing-state.json',json_encode($state,JSON_THROW_ON_ERROR),0600);
            if ($name==='templates') {
                mkdir($root.'/templates',0755);
                mainMove($backup.'/prepared/public',$root.'/templates/mupanic');
            }
            if ($name==='index.php') mainWrite($root.'/index.php',mainEntry($home));
        }
        $state['status']='active';
        mainWrite($home.'/main-landing-state.json',json_encode($state,JSON_THROW_ON_ERROR),0600);
        return $backup;
    } catch (Throwable $error) {
        mainRestore($home);
        throw $error;
    }
}
function mainRestore(string $home): string {
    $state=json_decode((string)file_get_contents($home.'/main-landing-state.json'),true,16,JSON_THROW_ON_ERROR);
    $backup=$state['backup'] ?? '';
    if (!is_string($backup) || !str_starts_with($backup,$home.'/main-backups/landing-') || is_link($backup) || realpath($backup)!==$backup) throw new RuntimeException('Respaldo no valido.');
    $root=$home.'/public_html';
    $displaced=$backup.'/separated-'.bin2hex(random_bytes(4)); mkdir($displaced,0700);
    foreach (array_reverse($state['planned'] ?? $state['moved']) as $name) {
        if (!preg_match('/^[A-Za-z0-9_.-]+$/D',$name)) throw new RuntimeException('Entrada de respaldo no valida.');
        if (!file_exists($backup.'/original/'.$name)) continue;
        if (file_exists($root.'/'.$name)) mainMove($root.'/'.$name,$displaced.'/'.$name);
        mainMove($backup.'/original/'.$name,$root.'/'.$name);
    }
    if ($state['originalHtExists']) mainWrite($root.'/.htaccess',(string)file_get_contents($backup.'/original/htaccess.txt'));
    else { if (file_exists($root.'/.htaccess')) mainMove($root.'/.htaccess',$displaced.'/htaccess.txt'); }
    foreach ($state['children'] ?? [] as $name=>$child) {
        if (!in_array($name,['beta','auditoria-web'],true)) throw new RuntimeException('Subdominio de respaldo no valido.');
        if ($child['exists']) mainWrite($root.'/'.$name.'/.htaccess',(string)file_get_contents($backup.'/original/'.$name.'-htaccess.txt'));
        elseif (is_file($root.'/'.$name.'/.htaccess')) mainMove($root.'/'.$name.'/.htaccess',$displaced.'/'.$name.'-htaccess.txt');
    }
    if (is_dir($home.'/main-services')) mainMove($home.'/main-services',$displaced.'/services');
    mainMove($home.'/main-landing-state.json',$displaced.'/state.json');
    return $backup;
}
function mainRefresh(string $home,string $source): void {
    $state=json_decode((string)file_get_contents($home.'/main-landing-state.json'),true,16,JSON_THROW_ON_ERROR);
    if (($state['status'] ?? '')!=='active') throw new RuntimeException('Separacion incompleta: restaurar antes de desplegar.');
    if (!str_starts_with((string)file_get_contents($home.'/public_html/.htaccess'),'# MU PANIC LANDING ONLY v1')) throw new RuntimeException('La proteccion de main fue modificada.');
    mainPreflight($home,$source);
    $work=$home.'/main-backups/refresh-'.date('Ymd-His').'-'.bin2hex(random_bytes(4)); mkdir($work,0700,true);
    mainPrepare($home,$source,$work);
    mainMove($home.'/main-services',$work.'/previous-services');
    try {
        mainMove($work.'/services',$home.'/main-services');
        mainMove($home.'/public_html/templates/mupanic',$work.'/previous-public');
        mainMove($work.'/public',$home.'/public_html/templates/mupanic');
    } catch (Throwable $error) {
        if (is_dir($home.'/main-services')) mainMove($home.'/main-services',$work.'/failed-services');
        mainMove($work.'/previous-services',$home.'/main-services');
        if (is_dir($work.'/previous-public') && !file_exists($home.'/public_html/templates/mupanic')) mainMove($work.'/previous-public',$home.'/public_html/templates/mupanic');
        throw $error;
    }
    mainWrite($home.'/public_html/index.php',mainEntry($home));
}

if (defined('MUPANIC_MAIN_TEST')) return;
if (PHP_SAPI==='cli') {
    if (($argv[1] ?? '')!=='--refresh' || empty($argv[2])) { fwrite(STDERR,"Uso: --refresh /ruta/repositorio\n"); exit(1); }
    try { mainRefresh('/home/mupanic',rtrim($argv[2],'/').'/overlay/templates/mupanic'); echo "Landing independiente actualizada. WebEngine no se reinstalo.\n"; }
    catch (Throwable $error) { fwrite(STDERR,$error->getMessage()."\n"); exit(1); }
    exit;
}
if (strtolower(explode(':',$_SERVER['HTTP_HOST'] ?? '')[0])!=='auditoria.mupanic.com.ar' ||
    realpath(__DIR__)!=='/home/mupanic/public_html/auditoria-web' ||
    (empty($_SERVER['REMOTE_USER']) && empty($_SERVER['PHP_AUTH_USER'])) || empty($_SERVER['HTTPS']) || $_SERVER['HTTPS']==='off') {
    http_response_code(403); exit('Acceso restringido.');
}
header('Cache-Control: no-store'); header('X-Robots-Tag: noindex, nofollow'); header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; frame-ancestors 'none'");
session_name('MUPanicMainIsolation'); session_set_cookie_params(['secure'=>true,'httponly'=>true,'samesite'=>'Strict','path'=>'/']); session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$message=''; $error='';
try {
    if (($_SERVER['REQUEST_METHOD'] ?? '')==='POST') {
        if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'],$_POST['csrf'])) throw new RuntimeException('Sesion vencida. Recarga la pagina.');
        $lock=fopen('/home/mupanic/main-landing.lock','c');
        if (!$lock || !flock($lock,LOCK_EX|LOCK_NB)) throw new RuntimeException('Otra operacion esta en curso.');
        try {
            if (($_POST['action'] ?? '')==='apply') {
                if (($_POST['cron_checked'] ?? '')!=='yes') throw new RuntimeException('Primero revisar tareas de cPanel y endpoints externos.');
                $message='Main separado. Respaldo privado: '.mainApply('/home/mupanic');
            } elseif (($_POST['action'] ?? '')==='restore') $message='Instalacion anterior restaurada desde '.mainRestore('/home/mupanic');
        } finally { flock($lock,LOCK_UN); fclose($lock); }
    }
} catch (Throwable $exception) { $error=$exception->getMessage(); }
$active=is_file('/home/mupanic/main-landing-state.json');
$root='/home/mupanic/public_html';
$e=static fn($s)=>htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');
?><!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Separar main · MU PANIC</title>
<style>body{background:#101017;color:#eee;font:16px system-ui;max-width:960px;margin:40px auto;padding:20px}button{padding:15px;background:#a3f1be;border:0;font-weight:bold}pre{background:#20202a;padding:18px;white-space:pre-wrap}label{display:block;margin:22px 0}.error{color:#ff9696}</style>
<h1>Main: landing independiente</h1><p>Beta y auditoria se conservan. No se modifican SQL, pagos privados ni tareas del VPS.</p>
<?php if($message): ?><pre><?= $e($message) ?></pre><?php endif; ?><?php if($error): ?><p class="error"><?= $e($error) ?></p><?php endif; ?>
<h2>Inventario de main</h2><pre><?php foreach(scandir($root) ?: [] as $name) if(!in_array($name,['.','..'],true)) echo $e($name)."\n"; ?></pre>
<h2>API antigua de WebEngine (se archivara)</h2><pre><?php foreach(glob($root.'/api/*') ?: [] as $path) echo $e(basename($path))."\n"; ?></pre>
<h2>Endpoints conservados con la misma URL</h2><pre><?php foreach(mainApiNames() as $name) echo '/templates/mupanic/api/'.$e($name)."\n"; ?></pre>
<p>La instalacion vieja se archiva fuera de public_html. Se conservan recursos estaticos y servicios de recargas/Atlas. Las rutas antiguas de main devolveran 404; los accesos a cuentas quedan en beta.</p>
<p>Antes de aplicar: comprobar en cPanel que ninguna tarea usa /public_html/api o /public_html/includes. Si la usa, hay que ajustar esa tarea primero. No marcar la casilla hasta verificarlo.</p>
<form method="post"><input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf']) ?>">
<?php if(!$active): ?><input type="hidden" name="action" value="apply"><label><input type="checkbox" name="cron_checked" value="yes" required> Se revisaron las tareas de cPanel y los endpoints externos: no dependen del core viejo de main.</label><button>Archivar web anterior y separar landing</button>
<?php else: ?><input type="hidden" name="action" value="restore"><button>Restaurar instalacion anterior de main</button><?php endif; ?></form></html>
