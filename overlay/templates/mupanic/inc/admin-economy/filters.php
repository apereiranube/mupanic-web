<?php
if (!defined('PANIC_ECO_ADMIN') || PANIC_ECO_ADMIN !== true) { http_response_code(403); exit; }
function panicEcoFilters($input, $now=null) {
    $tz=new DateTimeZone('America/Argentina/Buenos_Aires'); $today=($now?:new DateTimeImmutable('now',$tz))->setTimezone($tz)->setTime(0,0);
    $scalar=function($key,$default='') use ($input) { if (!array_key_exists($key,$input)) return $default; if(!is_string($input[$key])) throw new InvalidArgumentException('Filtro inválido.'); return $input[$key]; };
    $range=$scalar('rango','7'); $group=$scalar('agrupar','dia'); $tab=$scalar('eco_tab','resumen');
    if(!in_array($range,['hoy','ayer','7','30','mes','personalizado'],true) || !in_array($group,['dia','semana','mes'],true) || !in_array($tab,['resumen','juegos','vessara','tienda-vip','recargas','control'],true)) throw new InvalidArgumentException('Filtro inválido.');
    $end=$today->modify('+1 day'); $start=$today;
    if($range==='ayer') { $start=$today->modify('-1 day'); $end=$today; }
    elseif($range==='7' || $range==='30') $start=$today->modify('-'.((int)$range-1).' days');
    elseif($range==='mes') $start=$today->modify('first day of this month');
    elseif($range==='personalizado') {
        $dates=[]; foreach(['desde','hasta'] as $k) { $v=$scalar($k); $d=DateTimeImmutable::createFromFormat('!Y-m-d',$v,$tz); if(!$d || $d->format('Y-m-d')!==$v || (int)$d->format('Y')<2000 || (int)$d->format('Y')>2100) throw new InvalidArgumentException('Revisá las fechas del período.'); $dates[]=$d; }
        [$start,$last]=$dates; $end=$last->modify('+1 day');
    }
    if($end<=$start || $start->diff($end)->days>3660) throw new InvalidArgumentException('Elegí un rango válido de hasta 10 años.');
    $days=$start->diff($end)->days; $tests=$scalar('pruebas','0'); if(!in_array($tests,['0','1'],true)) throw new InvalidArgumentException('Filtro de pruebas inválido.');
    $query=['eco'=>'1','eco_tab'=>$tab,'rango'=>$range,'agrupar'=>$group,'desde'=>$start->format('Y-m-d'),'hasta'=>$end->modify('-1 day')->format('Y-m-d'),'pruebas'=>$tests];
    return ['start'=>$start,'end'=>$end,'previous'=>$start->modify('-'.$days.' days'),'tests'=>$tests==='1','group'=>$group,'tab'=>$tab,'query'=>$query,'now'=>($now?:new DateTimeImmutable('now',$tz))->setTimezone($tz)->format('Y-m-d\TH:i:s')];
}
