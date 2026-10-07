<?php
define('access', true);
define('__BASE_URL__', 'https://beta.mupanic.com.ar/');
define('__PATH_TEMPLATE__', 'https://beta.mupanic.com.ar/templates/mupanic/');
$_GET['preview'] = 'launch';
if(getenv('LAUNCH_TEST_DATE')) {
    $launchConfig = require __DIR__.'/../../overlay/templates/mupanic/inc/launch-config.php';
    $launchConfig['launchAt'] = '2026-10-31T20:00:00-03:00';
    $launchConfig['launchDateLabel'] = '31 OCTUBRE · 20:00 ARG';
    require __DIR__.'/../../overlay/templates/mupanic/inc/launch-page.php';
} else {
    require __DIR__.'/../../overlay/templates/mupanic/index.php';
}
