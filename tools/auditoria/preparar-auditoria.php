<?php
// One-time, password-protected staging preparation. Never activates WebEngine.
declare(strict_types=1);

function auditScrub(array $data): array {
    foreach ($data as $key => &$value) {
        if (is_array($value)) {
            $value = auditScrub($value);
        } elseif (preg_match('/password|passwd|secret|token|api.?key|smtp|sql_db_user|sql_db_pass/i', (string)$key)) {
            $value = is_bool($value) ? false : '';
        } elseif (is_string($value)) {
            $value = str_replace(['MuOnline43', '/home/mupanic/payments-private', 'www.mupanic.com.ar'], ['MuOnline43_Auditoria', '/home/mupanic/auditoria-payments-disabled', 'auditoria.mupanic.com.ar'], $value);
        }
    }
    unset($value);
    return $data;
}

function auditCopy(string $source, string $destination): int {
    if (is_link($source) || is_link($destination)) throw new RuntimeException('Enlace simbolico rechazado.');
    if (!is_dir($destination) && !mkdir($destination, 0700, true)) throw new RuntimeException('No se pudo crear una carpeta.');
    $count = 0;
    foreach (new DirectoryIterator($source) as $entry) {
        if ($entry->isDot()) continue;
        $name = $entry->getFilename();
        if ($entry->isLink()) throw new RuntimeException('La fuente contiene un enlace simbolico.');
        if (preg_match('/cache|logs?|backup|sessions?|payments|orders|uploads/i', $name)) continue;
        $target = $destination . '/' . $name;
        if (is_link($target)) throw new RuntimeException('Destino con enlace simbolico rechazado.');
        if ($entry->isDir()) {
            $count += auditCopy($entry->getPathname(), $target);
            continue;
        }
        if (!preg_match('/\.(php|json|js|css|html|txt|svg|png|jpe?g|webp|gif|ico|woff2?|ttf|eot)$/i', $name)) continue;
        $extension = strtolower($entry->getExtension());
        if (in_array($extension, ['php', 'json', 'js', 'html', 'txt'], true)) {
            $content = file_get_contents($entry->getPathname());
            if ($content === false) throw new RuntimeException('No se pudo leer un archivo.');
            if ($extension === 'json') {
                $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($data)) continue;
                $data = auditScrub($data);
                if ($name === 'webengine.json') {
                    $data['SQL_DB_NAME'] = 'MuOnline43_Auditoria';
                    $data['SQL_DB_USER'] = 'mupanic_auditoria';
                    $data['SQL_DB_PASS'] = '';
                    $data['SQL_USE_2_DB'] = false;
                    $data['SQL_DB_2_NAME'] = null;
                }
                $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            } else {
                $content = str_replace(['MuOnline43', '/home/mupanic/payments-private', 'www.mupanic.com.ar'], ['MuOnline43_Auditoria', '/home/mupanic/auditoria-payments-disabled', 'auditoria.mupanic.com.ar'], $content);
            }
            if (file_put_contents($target, $content, LOCK_EX) === false) throw new RuntimeException('No se pudo escribir un archivo.');
        } elseif (!copy($entry->getPathname(), $target)) {
            throw new RuntimeException('No se pudo copiar un recurso.');
        }
        chmod($target, 0600);
        $count++;
    }
    return $count;
}

if (defined('AUDITORIA_UNIT_TEST')) return;

ini_set('display_errors', '0');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
header('Content-Security-Policy: default-src \'none\'; style-src \'unsafe-inline\'; form-action \'self\'; frame-ancestors \'none\'');
$source = '/home/mupanic/public_html';
$destination = $source . '/auditoria-web';
$authenticated = !empty($_SERVER['REMOTE_USER']) || !empty($_SERVER['PHP_AUTH_USER']);
if (realpath(__DIR__) !== $destination || is_link(__DIR__) || !$authenticated || ($_SERVER['HTTP_HOST'] ?? '') !== 'auditoria.mupanic.com.ar') {
    http_response_code(403);
    exit('Este instalador solo funciona en el directorio de auditoria protegido.');
}
session_name('MUPanicAuditInstaller');
session_set_cookie_params(['secure' => true, 'httponly' => true, 'samesite' => 'Strict', 'path' => '/']);
if (!session_start()) {
    http_response_code(500);
    exit('No se pudo iniciar la sesion segura del instalador.');
}
set_time_limit(180);
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        exit('Formulario vencido. Volve a abrir el instalador.');
    }
    try {
        if (file_exists($destination . '/.auditoria-preparada')) throw new RuntimeException('La copia ya fue preparada.');
        foreach (['.htaccess', 'index.php', '.auditoria-preparada', '.auditoria-lock'] as $name) {
            if (is_link($destination . '/' . $name)) throw new RuntimeException('Destino no permitido.');
        }
        $lock = fopen($destination . '/.auditoria-lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) throw new RuntimeException('Ya hay una preparacion en curso.');
        if (file_exists($destination . '/.auditoria-preparada')) throw new RuntimeException('La copia ya fue preparada.');
        $htaccess = file_get_contents($destination . '/.htaccess');
        if ($htaccess === false || !preg_match('/^\s*AuthType\s+Basic/im', $htaccess) || !preg_match('/^\s*Require\s+(valid-user|user\s+)/im', $htaccess)) throw new RuntimeException('Falta la proteccion del directorio.');
        $rules = "\n# MU PANIC AUDITORIA: mantener cerrada hasta configurar y verificar SQL\nRewriteEngine On\nRewriteRule ^(?!(?:index|preparar-auditoria)\\.php$).*\\.php(?:/.*)?$ - [F,L,NC]\n<FilesMatch \"\\.(json|ini|log|sql|bak)$\">\nRequire all denied\n</FilesMatch>\nOptions -Indexes\n";
        if (strpos($htaccess, '# MU PANIC AUDITORIA:') === false && file_put_contents($destination . '/.htaccess', $htaccess . $rules, LOCK_EX) === false) throw new RuntimeException('No se pudo cerrar la copia.');
        $holding = '<?php http_response_code(503); header("Cache-Control: no-store"); header("X-Robots-Tag: noindex, nofollow"); ?><!doctype html><meta charset="utf-8"><title>Auditoria MU PANIC</title><h1>Copia de auditoria bloqueada</h1><p>Los archivos se prepararon. Falta configurar y verificar el usuario SQL exclusivo de prueba. No se conecta a la base ni ejecuta pagos o correos.</p>';
        if (file_put_contents($destination . '/index.php', $holding, LOCK_EX) === false) throw new RuntimeException('No se pudo crear la pantalla de espera.');
        $count = 0;
        foreach (['includes', 'modules', 'templates', 'admincp', 'css', 'js', 'img', 'assets', 'fonts', 'language', 'languages'] as $folder) {
            if (is_dir($source . '/' . $folder)) $count += auditCopy($source . '/' . $folder, $destination . '/' . $folder);
        }
        if (file_put_contents($destination . '/.auditoria-preparada', date(DATE_ATOM), LOCK_EX) === false) throw new RuntimeException('No se pudo marcar la copia.');
        $message = 'Listo: ' . $count . ' archivos preparados. La copia sigue bloqueada. La base actual y los permisos SQL no se modificaron.';
        flock($lock, LOCK_UN);
        fclose($lock);
    } catch (Throwable $error) {
        http_response_code(500);
        $message = 'Preparacion detenida: ' . $error->getMessage() . ' La copia no se activo.';
    }
}
?><!doctype html>
<html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>Preparar auditoria MU PANIC</title>
<style>body{background:#10121b;color:#eee;font:18px system-ui;max-width:720px;margin:60px auto;padding:24px}button{padding:16px;background:#8655ef;color:white;border:0;border-radius:8px;font:inherit}p{line-height:1.6}</style>
<h1>Preparar copia de auditoria</h1>
<p>Copia los archivos actuales a este directorio. Conserva la proteccion con contraseña, elimina las credenciales SQL de la copia y deja la web de prueba bloqueada.</p>
<p>La web actual sigue funcionando. Este paso no ejecuta consultas SQL, pagos ni correos.</p>
<?php if ($message !== ''): ?><p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
<?php if (!file_exists($destination . '/.auditoria-preparada')): ?><form method="post"><input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>"><button>Preparar copia</button></form><?php endif; ?>
</html>
