<?php
require __DIR__.'/../overlay/templates/mupanic/inc/launcher-data.php';
function check($yes,$message) { if(!$yes) throw new Exception($message); }
$zone=new DateTimeZone('America/Argentina/Buenos_Aires');
$now=new DateTimeImmutable('2026-10-16T21:00:00-03:00');
function nextDate($row,$now) { $date=panicLauncherNext([$row],$now); return $date?$date->format(DateTimeInterface::ATOM):null; }
check(nextDate([-1,-1,-1,-1,21,0,0],$now)==='2026-10-17T21:00:00-03:00','strict future and day rollover');
check(nextDate([-1,-1,-1,1,10,0,0],$now)==='2026-10-18T10:00:00-03:00','Sunday=1');
check(nextDate([-1,-1,-1,-1,-1,18,0],$now)==='2026-10-16T21:18:00-03:00','wildcard hour');
check(nextDate([-1,-1,-1,-1,-1,-1,0],$now)==='2026-10-16T21:01:00-03:00','wildcard minute');
check(nextDate([2026,2,30,-1,10,0,0],$now)===null,'invalid/past date');
check(nextDate([2027,1,1,-1,0,0,0],$now)==='2027-01-01T00:00:00-03:00','year rollover');
check(nextDate([-1,-1,-1,-1,21,0,5],$now)==='2026-10-16T21:00:05-03:00','seconds');
$dir=sys_get_temp_dir().'/launcher-test-'.bin2hex(random_bytes(8));
$calls=0;$reader=function($root)use(&$calls){$calls++;return ['online'=>7,'servidor'=>'online'];};
try {
 $a=panicLauncherCachedState('/test',$dir,$reader,100);
 $b=panicLauncherCachedState('/test',$dir,$reader,144);
 check($a===$b && $calls===1,'cache hit prevents SQL');
 panicLauncherCachedState('/test',$dir,$reader,145);
 check($calls===2,'45 second TTL');
 $failure=function($root)use(&$calls){$calls++;return ['online'=>0,'servidor'=>'offline'];};
 $a=panicLauncherCachedState('/test',$dir,$failure,190);
 $b=panicLauncherCachedState('/test',$dir,$reader,200);
 check($a===$b && $a['online']===0 && $calls===3,'failure cached, no stale online');
 file_put_contents($dir.'/state.json','broken');
 panicLauncherCachedState('/test',$dir,$reader,201);
 check($calls===4,'corrupt cache refresh');
} finally { @unlink($dir.'/state.json');@rmdir($dir); }
$events=panicLauncherEvents($now);
check(count($events)<=10 && count($events)>0,'limited agenda');
$last='';foreach($events as $e) {
 check(array_keys($e)===['nombre','proximo'],'public fields only');
 check($e['proximo']>$now->format(DateTimeInterface::ATOM) && $e['proximo']>=$last,'future sorted');
 check(substr($e['proximo'],-6)==='-03:00','timezone');$last=$e['proximo'];
}
echo "Launcher: schedule, cache, failure and schema checks passed\n";
