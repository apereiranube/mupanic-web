<?php
// Public launcher projection. No account, character or address fields are returned.
require_once __DIR__.'/atlas-event-runtime.php';

function panicLauncherNext(array $rows, DateTimeImmutable $now) {
    $now=$now->setTimezone(new DateTimeZone('America/Argentina/Buenos_Aires'));
    $day=$now->setTime(0,0,0);
    // Match the 370-day search horizon and wildcard semantics of atlas-ui.js.
    for($d=0;$d<370;$d++,$day=$day->modify('+1 day')) {
        $best=null;
        foreach($rows as $r) {
            if(count($r)!==7) continue;
            foreach(['Y','n','j','w'] as $i=>$format) {
                $value=(int)$day->format($format)+($i===3?1:0);
                if($r[$i]>=0 && $r[$i]!==$value) continue 2;
            }
            $hours=$r[4]<0?range(0,23):[$r[4]];
            $minutes=$r[5]<0?range(0,59):[$r[5]];
            foreach($hours as $h) foreach($minutes as $m) {
                $candidate=$day->setTime($h,$m,max(0,$r[6]));
                if($candidate>$now && ($best===null || $candidate<$best)) $best=$candidate;
            }
        }
        if($best!==null) return $best;
    }
    return null;
}

function panicLauncherEvents(DateTimeImmutable $now) {
    $snapshot=panicAtlasEvents();
    if(!panicAtlasEventsValid($snapshot)) return [];
    $guides=json_decode(file_get_contents(__DIR__.'/atlas-event-guides.json'),true)['guides'] ?? [];
    $spanish=[
        'blood-castle'=>'Castillo Sangriento','devil-square'=>'Plaza del Diablo',
        'chaos-castle'=>'Castillo del Caos','illusion-temple'=>'Templo de la Ilusión',
        'tvt'=>'Equipo contra Equipo','gvg'=>'Clan contra Clan','battle-royale'=>'Batalla Campal',
        'demon-guardian'=>'Guardián Demoníaco','king-of-mu'=>'Rey de MU','marathon'=>'Maratón',
        'pvp-championship'=>'Campeonato PvP','moss'=>'Mercader Moss',
        'invasion-0'=>'Rey Esqueleto','invasion-1'=>'Dragón Rojo','invasion-2'=>'Dragón Dorado',
        'invasion-3'=>'Mago Blanco','invasion-4'=>'Año Nuevo','invasion-5'=>'Conejos',
        'invasion-6'=>'Verano','invasion-7'=>'Santa Maldito',
    ];
    $result=[];
    foreach($snapshot['events'] as $event) {
        // Identical visibility to the Atlas: arenas are manual even in old snapshots.
        if(!$event['enabled'] || $event['mode']!=='scheduled' || preg_match('/^arena-[0-7]$/D',$event['id'])) continue;
        $next=panicLauncherNext($event['schedule'],$now);
        if($next===null) continue;
        $result[]=['nombre'=>$spanish[$event['id']] ?? ($guides[$event['id']]['displayName'] ?? $event['name']), 'proximo'=>$next->format(DateTimeInterface::ATOM)];
    }
    usort($result,function($a,$b){return strcmp($a['proximo'],$b['proximo']) ?: strcmp($a['nombre'],$b['nombre']);});
    return array_slice($result,0,10);
}

function panicLauncherReadState($root) {
    $offline=['online'=>0,'servidor'=>'offline'];
    try {
        // Deliberately avoid the full CMS bootstrap: sessions, plugins and maintenance
        // redirects must never turn the API into HTML or trigger unrelated writes.
        if(!defined('__PATH_CONFIGS__')) define('__PATH_CONFIGS__',$root.'/includes/config/');
        if(!defined('__PATH_CACHE__')) define('__PATH_CACHE__',$root.'/includes/cache/');
        if(!defined('WEBENGINE_DATABASE_ERRORLOG')) define('WEBENGINE_DATABASE_ERRORLOG',$root.'/includes/logs/database_errors.log');
        require_once $root.'/includes/functions.php';
        $config=webengineConfigs();
        if(!is_array($config) || !array_key_exists('system_active',$config)) return $offline;
        if(!$config['system_active']) return ['online'=>0,'servidor'=>'mantenimiento'];
        require_once $root.'/includes/classes/class.database.php';
        require_once $root.'/includes/classes/class.connection.php';
        // Same connection and count as WebEngine includes/cron/server_info.php,
        // read now instead of depending on that cron having run.
        $db=Connection::Database('Me_MuOnline');
        $row=$db->query_fetch_single('SELECT COUNT(*) AS totalOnline FROM [MEMB_STAT] WHERE [ConnectStat] = 1');
        $count=$row['totalOnline'] ?? null;
        if(!is_scalar($count) || !preg_match('/^[0-9]{1,9}$/D',(string)$count)) return $offline;
        return ['online'=>(int)$count,'servidor'=>'online'];
    } catch(Throwable $error) { return $offline; }
}

function panicLauncherCachedState($root, $directory=null, $reader=null, $now=null) {
    $offline=['online'=>0,'servidor'=>'offline'];
    $now=$now ?? time();
    $directory=$directory ?? sys_get_temp_dir().'/mupanic-launcher-'.hash('sha256',$root);
    if(is_link($directory) || (!is_dir($directory) && !@mkdir($directory,0700,true))) return $offline;
    $path=$directory.'/state-v2.json';
    if(is_link($path)) return $offline;
    $handle=@fopen($path,'c+');
    if(!$handle) return $offline; // Never fall back to an uncached SQL request.
    try {
        if(!flock($handle,LOCK_EX)) return $offline;
        @chmod($path,0600);
        $old=json_decode(stream_get_contents($handle),true);
        if(is_array($old) && isset($old['at'],$old['state']['online'],$old['state']['servidor']) &&
            is_int($old['at']) && $old['at']<=$now && $now-$old['at']<15 &&
            is_int($old['state']['online']) && $old['state']['online']>=0 &&
            in_array($old['state']['servidor'],['online','offline','mantenimiento'],true)) {
            return ['online'=>$old['state']['online'],'servidor'=>$old['state']['servidor']];
        }
        $state=($reader ?? 'panicLauncherReadState')($root);
        rewind($handle);ftruncate($handle,0);
        fwrite($handle,json_encode(['at'=>$now,'state'=>$state]));fflush($handle);
        return $state;
    } finally { flock($handle,LOCK_UN);fclose($handle); }
}
