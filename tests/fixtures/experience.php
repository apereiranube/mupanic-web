<?php
// Complete template render with isolated CMS stubs. Never connects to SQL.
error_reporting(E_ALL);
set_error_handler(function($severity,$message,$file,$line){throw new ErrorException($message,0,$severity,$file,$line);});
$template=realpath(__DIR__.'/../../overlay/templates/mupanic');
define('access',true);
define('__ROOT_DIR__',realpath(__DIR__.'/../../overlay').'/');
define('__BASE_URL__','/');
define('__PATH_TEMPLATE__','/templates/mupanic/');
define('__PATH_TEMPLATE_CSS__',__PATH_TEMPLATE__.'css/');
define('__PATH_TEMPLATE_JS__',__PATH_TEMPLATE__.'js/');
$_REQUEST=['page'=>($argv[1] ?? '')==='server'?'information':'','subpage'=>''];
$_GET=[];
function isLoggedIn(){return in_array('--logged',$GLOBALS['argv'],true);}
function LoadCacheData($file){return in_array('--missing-cache',$GLOBALS['argv'],true)?null:[[time()],['1|2|3|7']];}
function loadCache($file){return [];}
chdir($template);
include $template.'/index.php';
