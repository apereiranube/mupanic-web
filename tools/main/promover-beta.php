<?php
declare(strict_types=1);

// MU PANIC: pasa la web completa de beta a mupanic.com.ar.
// Se instala solo en la auditoría protegida con Basic Auth. No toca SQL, pagos privados
// (/home/mupanic/payments-private), atlas-runtime ni tareas del VPS.
// Aplicar: archiva la landing de main fuera de la web, copia la instalación de beta a main,
// y beta pasa a redirigir a las personas a main (las API automáticas siguen respondiendo en beta).
// Restaurar: vuelve exactamente a como estaba.

const PROM_STATE = 'main-promoted-state.json';
const PROM_PROTECTED = ['beta','auditoria-web','.well-known','cgi-bin','.htaccess'];
const PROM_BETA_GUARD = "# MU PANIC: prevent accessing this subdomain through a main folder alias\nRewriteEngine On\nRewriteCond %{HTTP_HOST} !^beta\\.mupanic\\.com\\.ar(?::[0-9]+)?$ [NC]\nRewriteRule ^ - [R=404,END]\n";
const PROM_REDIRECT_MARK = '# MU PANIC BETA REDIRIGE A MAIN v1';

function promRedirect(): string {
    return PROM_REDIRECT_MARK."\n".
        "# Personas: GET/HEAD van a https://mupanic.com.ar/ con la misma ruta.\n".
        "# Workers, bot y avisos (API) siguen respondiendo en beta hasta actualizar sus direcciones.\n".
        "RewriteEngine On\n".
        "RewriteCond %{HTTP_HOST} ^beta\\.mupanic\\.com\\.ar(?::[0-9]+)?$ [NC]\n".
        "RewriteCond %{REQUEST_METHOD} ^(?:GET|HEAD)$\n".
        "RewriteCond %{REQUEST_URI} !^/(?:templates/mupanic/api/|api/|\\.well-known/)\n".
        "RewriteRule ^(.*)$ https://mupanic.com.ar/$1 [R=301,L]\n\n";
}

function promCheckPath(string $path): void {
    if (is_link($path)) throw new RuntimeException('Enlace simbólico rechazado: '.basename($path));
}

function promCopy(string $source, string $destination): void {
    promCheckPath($source); promCheckPath($destination);
    if (is_dir($source)) {
        if (!is_dir($destination) && !mkdir($destination,0755,true)) throw new RuntimeException('No se pudo crear: '.$destination);
        chmod($destination, fileperms($source) & 0777);
        foreach (new DirectoryIterator($source) as $entry) {
            if ($entry->isDot()) continue;
            promCopy($entry->getPathname(),$destination.'/'.$entry->getFilename());
        }
    } else {
        if (!is_file($source) || !copy($source,$destination)) throw new RuntimeException('No se pudo copiar: '.$source);
        chmod($destination, fileperms($source) & 0777);
        touch($destination, (int)filemtime($source));
    }
}

function promWrite(string $path,string $content,int $mode=0644): void {
    promCheckPath($path);
    $tmp=tempnam(dirname($path),'.prom-');
    if ($tmp===false) throw new RuntimeException('No se pudo preparar el archivo.');
    try {
        chmod($tmp,$mode);
        if (file_put_contents($tmp,$content)!==strlen($content) || !rename($tmp,$path)) throw new RuntimeException('No se pudo guardar: '.basename($path));
    } finally { if (is_file($tmp)) unlink($tmp); }
}

function promMove(string $source,string $destination): void {
    if (file_exists($destination) || is_link($source) || !rename($source,$destination)) throw new RuntimeException('No se pudo mover: '.basename($source));
}

function promSaveState(string $home,array $state): void {
    promWrite($home.'/'.PROM_STATE,json_encode($state,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),0600);
}

// Bloque del handler PHP que genera cPanel (versión de PHP). Se conserva si beta no trae uno.
function promHandler(string $htaccess): string {
    return preg_match('~# php -- BEGIN cPanel-generated handler.*?# php -- END cPanel-generated handler[^\n]*\n?~s',$htaccess,$m) ? $m[0] : '';
}

function promRootRules(string $betaOriginal,string $mainOriginal): string {
    $rules=str_replace(PROM_BETA_GUARD,'',$betaOriginal);
    if (stripos($rules,'beta\\.mupanic')!==false || stripos($rules,'beta.mupanic')!==false) throw new RuntimeException('Las reglas de beta mencionan su dominio: revisar a mano antes de promover.');
    $out="# MU PANIC MAIN PROMOVIDO v1 (copia de las reglas de beta)\n".$rules;
    $handler=promHandler($mainOriginal);
    if ($handler!=='' && promHandler($rules)==='') $out.="\n".$handler;
    return $out;
}

// Lo que se copia de beta: todo salvo .htaccess (se arma aparte).
function promBetaEntries(string $beta): array {
    $entries=[];
    foreach (scandir($beta) ?: [] as $name) {
        // Propios del hosting de beta (certificado del subdominio, cgi, logs): quedan en beta, no se copian.
        if (in_array($name,['.','..','.htaccess','.well-known','cgi-bin','error_log'],true)) continue;
        if (!preg_match('/^[A-Za-z0-9_.-]+$/D',$name)) throw new RuntimeException('Nombre no válido en beta: '.$name);
        if (in_array($name,PROM_PROTECTED,true)) throw new RuntimeException('Beta contiene una carpeta reservada de main: '.$name);
        $entries[]=$name;
    }
    return $entries;
}

function promPreflight(string $home): array {
    $root=$home.'/public_html'; $beta=$root.'/beta';
    foreach ([$home,$root,$beta,$beta.'/.htaccess',$root.'/.htaccess',$home.'/'.PROM_STATE] as $p) promCheckPath($p);
    if (realpath($home)!==$home || realpath($root)!==$root || realpath($beta)!==$beta) throw new RuntimeException('Rutas de main/beta no verificadas.');
    foreach (['index.php','includes/webengine.php','includes/config/webengine.json','templates/mupanic/index.php','templates/mupanic/inc/launch-config.php'] as $f) {
        if (!is_file($beta.'/'.$f)) throw new RuntimeException('Beta incompleta, falta: '.$f);
    }
    if (!is_dir($root.'/auditoria-web')) throw new RuntimeException('No se encontró la auditoría.');
    if (file_exists($home.'/'.PROM_STATE)) throw new RuntimeException('Main ya fue promovido (existe el estado). Restaurar antes de repetir.');
    $landing=$home.'/main-landing-state.json';
    if (!is_file($landing)) throw new RuntimeException('Main no está en modo landing: estado inesperado, no se toca nada.');
    $ls=json_decode((string)file_get_contents($landing),true,16,JSON_THROW_ON_ERROR);
    if (($ls['status'] ?? '')!=='active') throw new RuntimeException('La landing de main no está activa.');
    // La web completa en main exige el modo abierto en la plantilla de beta.
    if (!defined('access')) define('access',true);
    $cfg=require $beta.'/templates/mupanic/inc/launch-config.php';
    if (($cfg['productionMode'] ?? '')==='coming-soon') throw new RuntimeException('La plantilla de beta todavía tiene "coming-soon": desplegar primero la rama beta actualizada.');
    if (!is_writable($home) || !is_writable($root) || !is_writable($beta)) throw new RuntimeException('Sin permisos de escritura.');
    $entries=promBetaEntries($beta);
    $replace=array_values(array_filter($entries,static fn($n)=>file_exists($root.'/'.$n)));
    return ['entries'=>$entries,'replace'=>$replace];
}

function promApply(string $home): string {
    $root=$home.'/public_html'; $beta=$root.'/beta';
    $plan=promPreflight($home);
    $backup=$home.'/main-backups/promover-'.date('Ymd-His').'-'.bin2hex(random_bytes(4));
    if (!is_dir($home.'/main-backups') && !mkdir($home.'/main-backups',0700,true)) throw new RuntimeException('No se pudo crear main-backups.');
    if (!mkdir($backup.'/original',0700,true) || !mkdir($backup.'/prepared',0700,true)) throw new RuntimeException('No se pudo crear el respaldo.');
    // 1) Copia completa de beta en un lugar privado, antes de tocar main.
    foreach ($plan['entries'] as $name) promCopy($beta.'/'.$name,$backup.'/prepared/'.$name);
    $betaHt=is_file($beta.'/.htaccess')?(string)file_get_contents($beta.'/.htaccess'):'';
    $mainHt=is_file($root.'/.htaccess')?(string)file_get_contents($root.'/.htaccess'):'';
    $newRoot=promRootRules($betaHt,$mainHt);
    promWrite($backup.'/original/root-htaccess.txt',$mainHt,0600);
    promWrite($backup.'/original/beta-htaccess.txt',$betaHt,0600);
    $state=['version'=>1,'status'=>'preparing','backup'=>$backup,'entries'=>$plan['entries'],'replace'=>$plan['replace'],
        'moved'=>[],'placed'=>[],'rootHt'=>is_file($root.'/.htaccess'),'betaHt'=>is_file($beta.'/.htaccess'),'home'=>[]];
    promSaveState($home,$state);
    try {
        // 2) Archivar la landing (y lo de main que choca con beta) fuera de la web.
        foreach ($plan['replace'] as $name) {
            promMove($root.'/'.$name,$backup.'/original/'.$name);
            $state['moved'][]=$name; promSaveState($home,$state);
        }
        foreach (['main-services','main-landing-state.json'] as $name) {
            if (!file_exists($home.'/'.$name)) continue;
            promMove($home.'/'.$name,$backup.'/original/home-'.$name);
            $state['home'][]=$name; promSaveState($home,$state);
        }
        // 3) Poner la web de beta en main.
        foreach ($plan['entries'] as $name) {
            promMove($backup.'/prepared/'.$name,$root.'/'.$name);
            $state['placed'][]=$name; promSaveState($home,$state);
        }
        promWrite($root.'/.htaccess',$newRoot);
        // 4) Beta redirige a las personas; la API sigue respondiendo.
        promWrite($beta.'/.htaccess',promRedirect().$betaHt);
        $state['status']='active'; promSaveState($home,$state);
        return $backup;
    } catch (Throwable $error) {
        promRestore($home);
        throw $error;
    }
}

function promRestore(string $home): string {
    $state=json_decode((string)file_get_contents($home.'/'.PROM_STATE),true,16,JSON_THROW_ON_ERROR);
    $backup=$state['backup'] ?? '';
    if (!is_string($backup) || !str_starts_with($backup,$home.'/main-backups/promover-') || is_link($backup) || realpath($backup)!==$backup) throw new RuntimeException('Respaldo no válido.');
    $root=$home.'/public_html'; $beta=$root.'/beta';
    $displaced=$backup.'/promovido-'.date('Ymd-His').'-'.bin2hex(random_bytes(4)); mkdir($displaced,0700);
    $name_ok=static function($n){ if(!is_string($n) || !preg_match('/^[A-Za-z0-9_.-]+$/D',$n) || in_array($n,PROM_PROTECTED,true)) throw new RuntimeException('Entrada no válida en el estado.'); };
    foreach (array_reverse($state['placed'] ?? []) as $name) { $name_ok($name); if (file_exists($root.'/'.$name)) promMove($root.'/'.$name,$displaced.'/'.$name); }
    foreach (array_reverse($state['moved'] ?? []) as $name) { $name_ok($name); promMove($backup.'/original/'.$name,$root.'/'.$name); }
    foreach (array_reverse($state['home'] ?? []) as $name) {
        if (!in_array($name,['main-services','main-landing-state.json'],true)) throw new RuntimeException('Entrada no válida en el estado.');
        promMove($backup.'/original/home-'.$name,$home.'/'.$name);
    }
    if ($state['rootHt'] ?? false) promWrite($root.'/.htaccess',(string)file_get_contents($backup.'/original/root-htaccess.txt'));
    elseif (file_exists($root.'/.htaccess')) promMove($root.'/.htaccess',$displaced.'/root-htaccess.txt');
    if ($state['betaHt'] ?? false) promWrite($beta.'/.htaccess',(string)file_get_contents($backup.'/original/beta-htaccess.txt'));
    elseif (file_exists($beta.'/.htaccess')) promMove($beta.'/.htaccess',$displaced.'/beta-htaccess.txt');
    promMove($home.'/'.PROM_STATE,$displaced.'/state.json');
    return $backup;
}

if (defined('MUPANIC_PROMOVER_TEST')) return;

if (strtolower(explode(':',$_SERVER['HTTP_HOST'] ?? '')[0])!=='auditoria.mupanic.com.ar' ||
    realpath(__DIR__)!=='/home/mupanic/public_html/auditoria-web' ||
    (empty($_SERVER['REMOTE_USER']) && empty($_SERVER['PHP_AUTH_USER'])) || empty($_SERVER['HTTPS']) || $_SERVER['HTTPS']==='off') {
    http_response_code(403); exit('Acceso restringido.');
}
header('Cache-Control: no-store'); header('X-Robots-Tag: noindex, nofollow'); header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; frame-ancestors 'none'");
session_name('MUPanicPromoverBeta'); session_set_cookie_params(['secure'=>true,'httponly'=>true,'samesite'=>'Strict','path'=>'/']); session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$home='/home/mupanic';
$message=''; $error=''; $plan=null;
try {
    if (($_SERVER['REQUEST_METHOD'] ?? '')==='POST') {
        if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'],$_POST['csrf'])) throw new RuntimeException('Sesión vencida. Recargá la página.');
        $lock=fopen($home.'/main-promote.lock','c');
        if (!$lock || !flock($lock,LOCK_EX|LOCK_NB)) throw new RuntimeException('Otra operación está en curso.');
        try {
            if (($_POST['action'] ?? '')==='apply') {
                if (($_POST['confirm'] ?? '')!=='yes') throw new RuntimeException('Marcá la casilla de confirmación.');
                $message='Web de beta publicada en mupanic.com.ar. Respaldo privado: '.promApply($home);
            } elseif (($_POST['action'] ?? '')==='restore') {
                $message='Main restaurado (landing) desde '.promRestore($home);
            }
        } finally { flock($lock,LOCK_UN); fclose($lock); }
    }
} catch (Throwable $exception) { $error=$exception->getMessage(); }
$active=is_file($home.'/'.PROM_STATE);
if (!$active) { try { $plan=promPreflight($home); } catch (Throwable $exception) { $error=$error ?: $exception->getMessage(); } }
$e=static fn($s)=>htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');
?><!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Promover beta · MU PANIC</title>
<style>body{background:#101017;color:#eee;font:16px system-ui;max-width:960px;margin:40px auto;padding:20px}button{padding:15px;background:#a3f1be;border:0;font-weight:bold}pre{background:#20202a;padding:18px;white-space:pre-wrap}label{display:block;margin:22px 0}.error{color:#ff9696}</style>
<h1>Pasar la web de beta a mupanic.com.ar</h1>
<p>No se modifican SQL, pagos privados, atlas-runtime ni tareas del VPS. La landing actual se archiva fuera de la web y se puede restaurar.</p>
<?php if($message): ?><pre><?= $e($message) ?></pre><?php endif; ?><?php if($error): ?><p class="error"><?= $e($error) ?></p><?php endif; ?>
<?php if(!$active && $plan): ?>
<h2>Vista previa (no cambia nada)</h2>
<pre>Se copia de beta a main:
<?php foreach($plan['entries'] as $n) echo '  '.$e($n)."\n"; ?>
Se archiva de main (por la landing):
<?php foreach($plan['replace'] as $n) echo '  '.$e($n)."\n"; ?>  main-services, main-landing-state.json
Beta: las personas pasan a https://mupanic.com.ar/ (301). /templates/mupanic/api/ y /api/ siguen respondiendo en beta.</pre>
<form method="post"><input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="apply">
<label><input type="checkbox" name="confirm" value="yes" required> No hay una recarga en curso y la rama beta ya está desplegada con el modo abierto.</label>
<button>Publicar la web de beta en mupanic.com.ar</button></form>
<?php elseif($active): ?>
<p>Main está promovido. Si algo falla, restaurar vuelve a la landing y beta deja de redirigir.</p>
<form method="post"><input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="restore"><button>Restaurar la landing anterior</button></form>
<?php endif; ?></html>
