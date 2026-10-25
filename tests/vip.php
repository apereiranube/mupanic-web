<?php
define('access',true);define('__BASE_URL__','https://test.invalid/');define('__PATH_TEMPLATE__','https://test.invalid/templates/mupanic/');
require __DIR__.'/../overlay/templates/mupanic/inc/account.php';
function isLoggedIn(){return true;}
class Connection {
    static $rows=[];static $params;static $sql;
    static function Database($name){if($name!=='MuOnline')throw new Exception('Wrong alias');return new self;}
    function query_fetch($sql,$params){self::$sql=$sql;self::$params=$params;return self::$rows;}
}
function renderVip($rows,$account='TestAcct') {
    Connection::$rows=$rows;$_SESSION=['username'=>$account];
    ob_start();include __DIR__.'/../overlay/templates/mupanic/inc/vip.php';return ob_get_clean();
}
$vip=renderVip([['active'=>'1','expiry'=>'2026-10-31 23:31:00']]);
if(!str_contains($vip,'VIP activo') || !str_contains($vip,'31/10/2026') || Connection::$params!==['TestAcct'] || !str_contains(Connection::$sql,'AccountExpireDate > GETDATE()'))throw new Exception('Membership must use parameterized account and SQL clock');
if(str_contains(Connection::$sql,'UPDATE')||str_contains(Connection::$sql,'EXEC'))throw new Exception('Membership read must not execute setter/getter');
foreach([['active'=>0,'expiry'=>'2026-09-30 23:20:00'],['active'=>'0','expiry'=>'2026-10-31 23:31:00']] as $row) {
    $normal=renderVip([$row]);if(!str_contains($normal,'Cuenta normal')||str_contains($normal,'✦ VIP activo'))throw new Exception('Inactive account rendered VIP');
}
foreach([[],[['active'=>1,'expiry'=>'invalid']],[['active'=>1,'expiry'=>'2026-10-31 23:31:00'],['active'=>0,'expiry'=>'2026-10-31 23:31:00']]] as $rows) {
    if(!str_contains(renderVip($rows),'No pudimos consultar tu VIP'))throw new Exception('Unknown membership must not be rendered normal');
}
$invalid=renderVip([],"<script>");if(str_contains($invalid,'<script>'))throw new Exception('Unescaped account');
if(!str_contains($vip,'[COMPLETAR]')||str_contains($vip,'25.000')||str_contains($vip,'type="submit"')||str_contains($vip,'EXP 20'))throw new Exception('Do not advertise undelivered bonus or open legacy CMS checkout');
echo "VIP tests passed: current/expired/unknown membership, SQL clock, escaping, shared presentation, pending price, read-only page.\n";
