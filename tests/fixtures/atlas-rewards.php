<?php
// Render the actual Atlas and current public snapshot without CMS or SQL dependencies.
define('access', true);
define('__BASE_URL__', '/');
define('__PATH_TEMPLATE__', '/templates/mupanic/');
require __DIR__.'/../../overlay/templates/mupanic/inc/atlas-runtime.php';
$publicBalance = panicAtlasBalance();
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Atlas rewards validation</title><link rel="stylesheet" href="/templates/mupanic/css/style.css"><link rel="stylesheet" href="/templates/mupanic/css/atlas.css"></head><body class="is-inner is-wiki"><main class="inner-content"><div class="shell"><div class="module-surface"><?php include __DIR__.'/../../overlay/templates/mupanic/inc/guide.php'; ?></div></div></main><script src="/templates/mupanic/js/atlas-search.js"></script><script src="/templates/mupanic/js/main.js"></script></body></html>
