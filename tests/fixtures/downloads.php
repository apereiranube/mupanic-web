<?php
// Vista previa de /downloads/ sin CMS: la página real dentro del mismo envoltorio del sitio.
define('access', true);
define('__BASE_URL__', '/');
define('__PATH_TEMPLATE__', '/templates/mupanic/');
$tpl = __DIR__.'/../../overlay/templates/mupanic/';
$templateSource = file_get_contents($tpl.'index.php');
preg_match('/<header class="site-header">.*?<\/header>/s', $templateSource, $nav);
$_REQUEST = ['page' => 'downloads', 'subpage' => '']; $isLogged = false; $isHome = false;
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Descargas</title>
<link rel="stylesheet" href="/templates/mupanic/css/style.css"><link rel="stylesheet" href="/templates/mupanic/css/atlas.css"><link rel="stylesheet" href="/templates/mupanic/css/panic-ui.css"><link rel="stylesheet" href="/templates/mupanic/css/downloads.css"></head>
<body class="is-inner is-downloads"><main><section class="inner-content"><div class="shell"><div class="module-surface"><?php include $tpl.'inc/downloads.php'; ?></div></div></section></main></body></html>
