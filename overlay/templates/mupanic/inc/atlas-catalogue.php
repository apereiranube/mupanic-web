<?php
// A configured reward file alone does not establish an obtainable reward.
function panicAtlasCatalogue($wiki, $events) {
    $bags = []; $reachable = []; $routes = []; $population = [];
    foreach($wiki['maps'] as &$map) {
        foreach($map['monsters'] as &$mob) {
            if(preg_match('/ elite$/i', $mob['name'])) $mob['elite'] = true;
            $population[$mob['id']] = true;
        }
        unset($mob);
        foreach($map['spots'] as &$spot) {
            foreach($spot['monsters'] as &$mob) {
                if(preg_match('/ elite$/i', $mob['name'])) { $mob['elite'] = true; $spot['kind'] = 'super'; }
            }
            unset($mob);
        }
        unset($spot);
    }
    unset($map);
    foreach($wiki['eventBags'] ?? [] as $bag) $bags[$bag['id']] = $bag;
    $eventBags = ['double-goer'=>range(78,87), 'imperial-guardian'=>range(88,97),
        'kanturu'=>[46,47,48], 'tvt'=>[130], 'gvg'=>[131], 'battle-royale'=>[129],
        'demon-guardian'=>[168], 'marathon'=>[158,159,160], 'pvp-championship'=>[161,162],
        'crywolf'=>[44,45,107], 'castle-siege'=>[164,165,166,167],
        'quickly'=>[152], 'hide-and-seek'=>[132], 'run-and-catch'=>[133],
        'russian-roulette'=>[134], 'pvp'=>[135], 'kill-all'=>range(136,144)];
    foreach($events['events'] ?? [] as $event) {
        if(!$event['enabled']) continue;
        foreach(array_merge($event['bags'], $eventBags[$event['id']] ?? []) as $id) $reachable[$id] = true;
        foreach($event['monsters'] as $id) $population[$id] = true;
    }
    foreach($bags as $bag) if($bag['monster'] >= 0 && isset($population[$bag['monster']])) $reachable[$bag['id']] = true;
    // These map bosses have audited access guides, not a calendar switch.
    foreach([32,33] as $id) if(isset($bags[$id])) $reachable[$id] = true;
    foreach($wiki['drops'] as $i=>&$drop) {
        if($drop['id'] !== -1) continue;
        $id = $drop['bagId'] ?? $drop['variant'];
        $drop['bagId'] = $id;
        $routes[$id][] = $i;
        $reachable[$id] = true;
        if(isset($bags[$id])) {
            $drop['name'] = $bags[$id]['name'];
            $drop['name'] = preg_replace('/^(?:PANIC )?(?:EQUIPO DIRECTO|Equipo Directo) /i', 'Equipo: ', $drop['name']);
            $drop['name'] = preg_replace('/^(?:PANIC )?JOYERIA /i', 'Joyería: ', $drop['name']);
            $drop['name'] = str_replace(['AVANZADO_FALTANTE','NORMAL_INICIO','NORMAL_MEDIO','NORMAL_COMPLETA','EXCELLENT_UNA','EXCELLENT_DOS','INICIO','INTERMEDIO','SUPERIOR','ALTO','ARCHANGEL'],['avanzado','inicial','intermedia','normal completa','Excellent · una opción','Excellent · dos opciones','inicial','intermedio','superior','alto','Archangel'],$drop['name']);
            $bags[$id]['name'] = $drop['name'];
        }
    }
    unset($drop);
    // Only propagate an obtainable box when its level is compatible with a real reward.
    do {
        $previous = count($reachable); $items = [];
        foreach($wiki['drops'] as $drop) if($drop['id'] >= 0) $items[] = ['id'=>$drop['id'],'min'=>$drop['variant'],'max'=>$drop['variant']];
        foreach($reachable as $id=>$unused) {
            $bag = $bags[$id] ?? null;
            if(!$bag || !in_array($bag['format'],['standard','advanced'],true)) continue;
            foreach($bag['items'] as $item) $items[] = $item;
        }
        foreach($bags as $bag) {
            if($bag['item'] < 0) continue;
            foreach($items as $item) if($item['id'] === $bag['item'] && ($bag['variant'] === -1 || ($item['min'] <= $bag['variant'] && $item['max'] >= $bag['variant']))) {
                $reachable[$bag['id']] = true; break;
            }
        }
    } while(count($reachable) > $previous);
    $wiki['eventBags'] = [];
    foreach($bags as $id=>$bag) {
        if(!isset($reachable[$id])) continue;
        // Retired Lucky Coin exchanges and sealed-chest system.
        if(in_array($id,[70,71,72,100,101],true)) continue;
        if(isset($routes[$id])) { $bag['dropRoutes']=$routes[$id]; $bag['kind']='direct'; }
        $wiki['eventBags'][] = $bag;
    }
    // Disabled ItemDrop rows are never exported. Keep this guard for old snapshots.
    $wiki['drops'] = array_values(array_filter($wiki['drops'],function($drop) {
        return !in_array($drop['id'],[7268,7289,7290],true) && max($drop['rates']) > 0;
    }));
    // Re-index direct references after removing legacy retired rows.
    foreach($wiki['eventBags'] as &$bag) if(($bag['kind'] ?? '') === 'direct') {
        $bag['dropRoutes']=[];
        foreach($wiki['drops'] as $i=>$drop) if(($drop['bagId'] ?? null) === $bag['id']) $bag['dropRoutes'][]=$i;
    }
    unset($bag);
    return $wiki;
}
