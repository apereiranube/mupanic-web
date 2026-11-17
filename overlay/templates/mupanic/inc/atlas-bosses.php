<?php
if(!defined('access') or !access) die();
/*
 * MU PANIC · Atlas · Bosses ahora: si cada boss está vivo y cuánto falta para que vuelva.
 * Datos en vivo: vista [MuOnline43].[dbo].[MUPanicBossEstadoWebV1] (solo SELECT). La llena el VPS
 * (Bosses.lua en los 3 GameServer + tarea "MU PANIC Bosses"). Horarios de invasiones: public-events.json.
 * Ver docs/atlas-bosses.md.
 */
require_once(__DIR__.'/atlas-event-runtime.php');

function panicBossesRead() {
    $out = ['ok'=>false, 'now'=>time(), 'rows'=>[]];
    try {
        $db = Connection::Database('MuOnline');
        if(!$db) return $out;
        $rows = $db->query_fetch("SELECT ServerCode AS s,MonsterClass AS c,AliveCount AS a,DeathUnix AS d,RegenSeconds AS r,SeenUnix AS v,NowUnix AS n FROM [MuOnline43].[dbo].[MUPanicBossEstadoWebV1]");
        $out['ok'] = true;
        if(!is_array($rows)) return $out;
        foreach($rows as $r) {
            foreach(['s','c','a','d','r','v','n'] as $k) if(!isset($r[$k]) || !preg_match('/^[0-9]{1,12}$/D', (string)$r[$k])) continue 2;
            $out['now'] = (int)$r['n'];
            $out['rows'][(int)$r['s']][(int)$r['c']] = ['alive'=>(int)$r['a'], 'death'=>(int)$r['d'], 'regen'=>(int)$r['r'], 'seen'=>(int)$r['v']];
        }
    } catch(Throwable $e) { $out['ok'] = false; }
    return $out;
}

/** Próximos inicios (unix) de un evento programado, con las filas de public-events.json. */
function panicBossesStarts($schedule, $now, $tz) {
    $list = [];
    $base = (new DateTimeImmutable('@'.$now))->setTimezone($tz)->setTime(0, 0, 0);
    for($day = -1; $day <= 2; $day++) {
        $d = $base->modify(($day >= 0 ? '+' : '').$day.' day');
        foreach($schedule as $row) {
            if($row[0] > 0 && (int)$d->format('Y') !== $row[0]) continue;
            if($row[1] > 0 && (int)$d->format('n') !== $row[1]) continue;
            if($row[2] > 0 && (int)$d->format('j') !== $row[2]) continue;
            if($row[3] > 0 && (int)$d->format('w') + 1 !== $row[3]) continue;
            $hours = $row[4] < 0 ? range(0, 23) : [$row[4]];
            foreach($hours as $h) $list[] = $d->setTime($h, max(0, $row[5]), max(0, $row[6]))->getTimestamp();
        }
    }
    sort($list);
    return $list;
}

$bossLive = panicBossesRead();
$bossNow = $bossLive['now'];
$bossTz = new DateTimeZone('America/Argentina/Buenos_Aires');
$bossFresh = 180;
$bossServerNames = [0=>'Server 1', 1=>'Server 2', 19=>'Server Loren'];
$bossMap = function($id, $fallback) use($mapNames) { return $mapNames[$id] ?? $fallback; };
$bossImg = function($class) use($wiki) {
    $f = $wiki['portraits'][(string)$class]['file'] ?? null;
    return ($f && is_file(__DIR__.'/../'.$f)) ? __PATH_TEMPLATE__.$f : null;
};
$bossClock = function($unix) use($bossTz, $bossNow) {
    $d = (new DateTimeImmutable('@'.$unix))->setTimezone($bossTz);
    $today = (new DateTimeImmutable('@'.$bossNow))->setTimezone($bossTz)->format('Y-m-d');
    $tomorrow = (new DateTimeImmutable('@'.($bossNow + 86400)))->setTimezone($bossTz)->format('Y-m-d');
    $day = $d->format('Y-m-d') === $today ? 'hoy' : ($d->format('Y-m-d') === $tomorrow ? 'mañana' : $d->format('d/m'));
    return $day.' '.$d->format('H:i');
};
/** Estado de un boss que revive solo, en un server. */
$bossRegenState = function($server, $class, $kind = 'regen') use($bossLive, $bossNow, $bossFresh) {
    $r = $bossLive['rows'][$server][$class] ?? null;
    if(!$r || $bossNow - $r['seen'] > $bossFresh) return ['k'=>'unknown', 't'=>'Sin datos ahora'];
    if($r['alive'] > 0) return ['k'=>'alive', 't'=>'Vivo'];
    if($kind === 'event') return ['k'=>'dead', 't'=>'Derrotado · vuelve con el ciclo del evento'];
    if($r['death'] > 0 && $r['death'] + $r['regen'] > $bossNow) return ['k'=>'dead', 't'=>'Revive en', 'until'=>$r['death'] + $r['regen']];
    if($r['death'] > 0) return ['k'=>'soon', 't'=>'Por aparecer'];
    return ['k'=>'unknown', 't'=>'No está en el mapa'];
};

$bossRegen = [
    ['name'=>'Kundun', 'class'=>275, 'map'=>$bossMap(36, 'Kalima 7'), 'servers'=>[0, 1], 'kind'=>'regen', 'note'=>'El jefe final de Kalima.'],
    ['name'=>'Erohim', 'class'=>295, 'map'=>$bossMap(31, 'Land of Trials'), 'servers'=>[19], 'kind'=>'regen', 'note'=>'Guardián de Land of Trials.'],
    ['name'=>'Dark Elf', 'class'=>412, 'map'=>$bossMap(42, 'Refugio'), 'servers'=>[19], 'kind'=>'regen', 'note'=>'El elfo oscuro del Refugio.'],
    ['name'=>'Selupan', 'class'=>459, 'map'=>$bossMap(58, 'LaCleon'), 'servers'=>[0, 1], 'kind'=>'event', 'note'=>'Reina de LaCleon: vuelve con el ciclo del evento.'],
];
$bossIllusions = [[1,161,24],[2,181,25],[3,189,26],[4,197,27],[5,267,28],[6,338,29]];

$bossEvents = [];
foreach(panicAtlasEvents()['events'] as $e) $bossEvents[$e['id']] = $e;
$bossScheduled = [
    ['event'=>'invasion-8', 'name'=>'Medusa', 'class'=>561, 'map'=>$bossMap(56, 'Swamp of Calmness')],
    ['event'=>'invasion-9', 'name'=>'Señor de la Fractura', 'class'=>625, 'map'=>$bossMap(63, 'Vulcanus')],
    ['event'=>'invasion-2', 'name'=>'Great Golden Dragon', 'class'=>501, 'map'=>'Invasión de dorados'],
    ['event'=>'invasion-1', 'name'=>'Red Dragon', 'class'=>42, 'map'=>'Invasión'],
    ['event'=>'invasion-0', 'name'=>'Skeleton King', 'class'=>55, 'map'=>'Invasión'],
    ['event'=>'invasion-3', 'name'=>'White Wizard', 'class'=>135, 'map'=>'Invasión'],
];
$bossScheduledState = function($b) use($bossEvents, $bossLive, $bossNow, $bossFresh, $bossTz, $bossServerNames) {
    $e = $bossEvents[$b['event']] ?? null;
    if(!$e || !$e['enabled'] || $e['mode'] !== 'scheduled' || !$e['schedule']) return null;
    $dur = (int)round($e['durationMinutes'] * 60);
    $starts = panicBossesStarts($e['schedule'], $bossNow, $bossTz);
    $current = null; $next = null;
    foreach($starts as $s) {
        if($s <= $bossNow && $bossNow < $s + $dur) $current = $s;
        if($s > $bossNow && $next === null) $next = $s;
    }
    $hours = [];
    foreach($e['schedule'] as $r) $hours[] = ($r[4] < 0 ? 'cada hora :' : str_pad($r[4], 2, '0', STR_PAD_LEFT).':').str_pad(max(0, $r[5]), 2, '0', STR_PAD_LEFT);
    $out = ['hours'=>implode(' · ', array_unique($hours)), 'dur'=>(int)round($dur / 60), 'next'=>$next];
    if($current === null) return $out + ['k'=>'wait', 't'=>'Próxima aparición', 'until'=>$next];
    $alive = []; $dead = false; $fresh = false;
    foreach($bossLive['rows'] as $server => $classes) {
        $r = $classes[$b['class']] ?? null;
        if(!$r || $bossNow - $r['seen'] > $bossFresh) continue;
        $fresh = true;
        if($r['alive'] > 0) $alive[] = $bossServerNames[$server] ?? ('Server '.$server);
        if($r['death'] >= $current) $dead = true;
    }
    if($alive) return $out + ['k'=>'alive', 't'=>'Vivo ahora', 'where'=>implode(' · ', $alive), 'until'=>$current + $dur, 'untilLabel'=>'Se retira en'];
    if($dead) return $out + ['k'=>'dead', 't'=>'Ya lo derrotaron', 'until'=>$next, 'untilLabel'=>'Vuelve'];
    if(!$fresh) return $out + ['k'=>'unknown', 't'=>'En curso · sin datos ahora', 'until'=>$current + $dur, 'untilLabel'=>'Termina en'];
    return $out + ['k'=>'soon', 't'=>'En curso · ya no está', 'until'=>$next, 'untilLabel'=>'Vuelve'];
};
$bossVer = function($rel) { $p = __DIR__.'/../'.$rel; return is_file($p) ? substr(hash_file('sha256', $p), 0, 12) : '0'; };
$bossCountdown = function($until, $label = '') use($bossClock) {
    if(!$until) return '';
    return '<span class="bos-timer"'.($label !== '' ? ' data-label="'.panicWikiEscape($label).'"' : '').' data-until="'.(int)$until.'" title="'.panicWikiEscape($bossClock($until)).'">'.panicWikiEscape($bossClock($until)).'</span>';
};
?>
<link href="<?php echo __PATH_TEMPLATE_CSS__; ?>atlas-bosses.css?v=<?php echo $bossVer('css/atlas-bosses.css'); ?>" rel="stylesheet">
<section id="bosses" class="wiki-section" data-wiki-search data-boss-now="<?php echo (int)$bossNow; ?>">
    <div class="atlas-section-heading"><div><span class="eyebrow">EN VIVO</span><h2>Bosses ahora.</h2></div></div>
    <p>Mirá si cada boss está vivo y cuánto falta para que vuelva, antes de salir a buscarlo. Hora de Argentina.</p>
    <?php if(!$bossLive['ok']) { ?><p class="bos-alert">Ahora no podemos leer el estado de los bosses. Probá de nuevo en unos minutos.</p><?php } ?>

    <h3 class="bos-group">Reviven solos</h3>
    <div class="bos-grid">
    <?php foreach($bossRegen as $b) { $img = $bossImg($b['class']); ?>
        <article class="bos-card" data-wiki-search>
            <div class="bos-head"><?php if($img) { ?><img src="<?php echo panicWikiEscape($img); ?>" alt="" width="96" height="96" loading="lazy"><?php } ?><div><h4><?php echo panicWikiEscape($b['name']); ?></h4><span class="bos-map"><?php echo panicWikiEscape($b['map']); ?></span></div></div>
            <ul class="bos-servers">
            <?php foreach($b['servers'] as $s) { $st = $bossRegenState($s, $b['class'], $b['kind']); ?>
                <li class="bos-st bos-st--<?php echo $st['k']; ?>"><?php if(count($b['servers']) > 1) { ?><span class="bos-srv"><?php echo $bossServerNames[$s]; ?></span><?php } ?><span class="bos-dot" aria-hidden="true"></span><span class="bos-txt"><?php echo panicWikiEscape($st['t']); ?></span><?php echo $bossCountdown($st['until'] ?? 0); ?></li>
            <?php } ?>
            </ul>
            <p class="bos-note"><?php echo panicWikiEscape($b['note']); ?></p>
        </article>
    <?php } ?>
        <article class="bos-card bos-card--wide" data-wiki-search>
            <div class="bos-head"><div><h4>Ilusiones de Kundun</h4><span class="bos-map">Kalima 1 a 6</span></div></div>
            <table class="bos-table"><thead><tr><th>Mapa</th><th>Server 1</th><th>Server 2</th></tr></thead><tbody>
            <?php foreach($bossIllusions as $il) { ?><tr><td><?php echo panicWikiEscape($bossMap($il[2], 'Kalima '.$il[0])); ?></td><?php foreach([0, 1] as $s) { $st = $bossRegenState($s, $il[1]); ?><td class="bos-st bos-st--<?php echo $st['k']; ?>"><span class="bos-dot" aria-hidden="true"></span><span class="bos-txt"><?php echo panicWikiEscape($st['t']); ?></span><?php echo $bossCountdown($st['until'] ?? 0); ?></td><?php } ?></tr><?php } ?>
            </tbody></table>
        </article>
    </div>

    <h3 class="bos-group">Salen por horario</h3>
    <div class="bos-grid">
    <?php foreach($bossScheduled as $b) { $st = $bossScheduledState($b); if(!$st) continue; $img = $bossImg($b['class']); ?>
        <article class="bos-card" data-wiki-search>
            <div class="bos-head"><?php if($img) { ?><img src="<?php echo panicWikiEscape($img); ?>" alt="" width="96" height="96" loading="lazy"><?php } ?><div><h4><?php echo panicWikiEscape($b['name']); ?></h4><span class="bos-map"><?php echo panicWikiEscape($b['map']); ?></span></div></div>
            <ul class="bos-servers"><li class="bos-st bos-st--<?php echo $st['k']; ?>"><span class="bos-dot" aria-hidden="true"></span><span class="bos-txt"><?php echo panicWikiEscape($st['t']); ?><?php if(!empty($st['where'])) echo ' · '.panicWikiEscape($st['where']); ?></span><?php echo $bossCountdown($st['until'] ?? 0, $st['untilLabel'] ?? ''); ?></li></ul>
            <p class="bos-note">Horario: <?php echo panicWikiEscape($st['hours']); ?> · dura <?php echo (int)$st['dur']; ?> min.</p>
        </article>
    <?php } ?>
    </div>
    <p class="bos-foot">El estado se toma cada minuto del servidor. <a href="#bosses" data-boss-refresh>Actualizar ahora</a></p>
</section>
<script src="<?php echo __PATH_TEMPLATE_JS__; ?>atlas-bosses.js?v=<?php echo $bossVer('js/atlas-bosses.js'); ?>" defer></script>
