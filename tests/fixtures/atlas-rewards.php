<?php
// Render the actual Atlas and current public snapshot without CMS or SQL dependencies.
define('access', true);
define('__BASE_URL__', '/');
define('__PATH_TEMPLATE__', '/templates/mupanic/');
require __DIR__.'/../../overlay/templates/mupanic/inc/template.functions.php';
$isLogged = false;
require __DIR__.'/../../overlay/templates/mupanic/inc/atlas-runtime.php';
$publicBalance = panicAtlasBalance();
$isHome=false; $_REQUEST=['page'=>'info','subpage'=>'']; $community=['guildId'=>'1555229554920915057'];
$templateSource=file_get_contents(__DIR__.'/../../overlay/templates/mupanic/index.php');
preg_match('/<header class="site-header">.*?<\/header>/s',$templateSource,$navigation);
ob_start(); eval('?>'.$navigation[0]); $atlasNavigation=ob_get_clean();
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Atlas rewards validation</title><link rel="stylesheet" href="/templates/mupanic/css/style.css"><link rel="stylesheet" href="/templates/mupanic/css/atlas.css"></head><body class="is-inner is-wiki"><?php echo $atlasNavigation; ?><main><?php include __DIR__.'/../../overlay/templates/mupanic/inc/atlas-hero.php'; ?><section class="inner-content"><div class="shell"><div class="module-surface"><?php include __DIR__.'/../../overlay/templates/mupanic/inc/guide.php'; ?></div></div></section></main><script src="/templates/mupanic/js/atlas-search.js"></script><script src="/templates/mupanic/js/main.js"></script><script src="/templates/mupanic/js/atlas-ui.js"></script></body></html>
