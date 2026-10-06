<?php
// Isolated rendering of the production module; no WebEngine/SQL configuration.
define('access', true);
define('__PATH_TEMPLATE__', '/templates/mupanic/');
define('__BASE_URL__', '/');
ob_start();
include __DIR__.'/../../overlay/modules/information.php';
$module = ob_get_clean();
$start = strpos($module, '<section class="server-features"');
$end = strpos($module, '<section class="server-atlas-showcase"', $start);
if($start === false || $end === false) throw new RuntimeException('Systems section not found');
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MU PANIC · Sistemas</title><link rel="stylesheet" href="/templates/mupanic/css/style.css"><link rel="stylesheet" href="/templates/mupanic/css/information.css"></head><body class="is-server-info"><main><?php echo substr($module, $start, $end-$start); ?></main><script src="/templates/mupanic/js/main.js"></script></body></html>
