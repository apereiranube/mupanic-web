<?php
// Isolated rendering of the production El servidor module; no WebEngine/SQL configuration.
define('access', true);
define('__PATH_TEMPLATE__', '/templates/mupanic/');
define('__BASE_URL__', '/');
ob_start();
include __DIR__.'/../../overlay/modules/information.php';
$module = ob_get_clean();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MU PANIC · El servidor</title><link rel="stylesheet" href="/templates/mupanic/css/style.css"><link rel="stylesheet" href="/templates/mupanic/css/information.css"></head><body class="is-inner is-server-info"><header class="site-header" style="height:80px"></header><main id="main-content"><section class="inner-content"><div class="shell"><div class="module-surface"><?php echo $module; ?></div></div></section></main><script src="/templates/mupanic/js/main.js"></script><script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js" defer></script><script src="/templates/mupanic/js/information.js" defer></script></body></html>
