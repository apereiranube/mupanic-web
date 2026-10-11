<?php
$directory=sys_get_temp_dir().'/vessara-test-'.bin2hex(random_bytes(8));mkdir($directory,0700);define('__PATH_CACHE__',$directory);
require __DIR__.'/../overlay/templates/mupanic/inc/market-data.php';
class Connection { public static function Database($name){throw new RuntimeException('Private connection detail');} }
function check($v,$m){if(!$v)throw new Exception($m);echo "PASS: $m\n";}
try {
 $d=['schema'=>1,'updated'=>time(),'metrics'=>['total'=>'2','today'=>'1','zen'=>'100','eryns'=>'50'],'sales'=>[],'days'=>[]];
 file_put_contents($directory.'/mupanic-vessara-v2.json',json_encode($d));
 check(panicMarketRead()['metrics']['total']==='2','fresh cache avoids database');
 $d['updated']=time()-20;file_put_contents($directory.'/mupanic-vessara-v2.json',json_encode($d));
 check(panicMarketRead()['stale']===true,'temporary failure labels previous data stale');
 $d['updated']=time()-86401;file_put_contents($directory.'/mupanic-vessara-v2.json',json_encode($d));
 check(panicMarketRead()===null,'expired cache does not masquerade as current activity');
}finally{unlink($directory.'/mupanic-vessara-v2.json');rmdir($directory);}
