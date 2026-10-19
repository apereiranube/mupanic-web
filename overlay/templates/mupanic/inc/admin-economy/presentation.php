<?php
if (!defined('PANIC_ECO_ADMIN') || PANIC_ECO_ADMIN !== true) { http_response_code(403); exit; }
// Presentation only: these helpers consume values returned by the existing repository.
function panicEcoIcon($name) {
    $paths=[
        'money'=>'M3 6h18v12H3z M3 10c3 0 4-1 4-4 M17 6c0 3 1 4 4 4 M3 14c3 0 4 1 4 4 M17 18c0-3 1-4 4-4 M12 9v6 M10 12h4',
        'coin'=>'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18 M12 7v10 M9 9h4a2 2 0 0 1 0 4h-2a2 2 0 0 0 0 4h4',
        'chest'=>'M4 10V8a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v2 M3 10h18v10H3z M3 14h7 M14 14h7 M10 12h4v5h-4z',
        'dice'=>'M6 3h12a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3 M8 7v2 M16 7v2 M12 11v2 M8 15v2 M16 15v2',
        'cart'=>'M2 3h3l3 12h11l3-8H6 M9 19h.01 M18 19h.01',
        'crown'=>'M3 6l5 4 4-7 4 7 5-4-2 13H5z M6 15h12',
        'scale'=>'M12 3v18 M8 21h8 M4 7h16 M5 7l-3 7h6z M19 7l-3 7h6z',
        'alert'=>'M12 3l10 18H2z M12 9v5 M12 17h.01',
        'check'=>'M5 12l4 4L20 5', 'up'=>'M12 20V4 M5 11l7-7 7 7', 'down'=>'M12 4v16 M5 13l7 7 7-7',
        'clock'=>'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18 M12 7v5l4 2', 'minus'=>'M5 12h14', 'chevron'=>'M6 9l6 6 6-6'
    ];
    return '<svg class="eco-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="'.($paths[$name]??$paths['coin']).'"/></svg>';
}
function panicEcoHelp($text) {
    static $index=0; $id='eco-help-'.(++$index);
    return '<span class="eco-help"><button type="button" class="eco-help-button" aria-label="Ver explicación" aria-expanded="false" aria-describedby="'.$id.'">?</button><span class="eco-tooltip" id="'.$id.'" role="tooltip">'.panicEcoEscape($text).'</span></span>';
}
function panicEcoSentence($text,$help='') { echo '<div class="eco-story"><p>'.panicEcoEscape($text).'</p>'.($help?panicEcoHelp($help):'').'</div>'; }
function panicEcoSum($rows,$key) { $sum=0.0; foreach($rows as $row) $sum+=(float)($row[$key]??0); return $sum; }
function panicEcoRatio($part,$total) { return $part!==null && $total!==null && (float)$total>0?(float)$part/(float)$total*100:null; }
function panicEcoComparison($value,$previous,$period='el período anterior') {
    if($value===null || $previous===null) return 'Todavía no hay una referencia para comparar.';
    if((float)$previous==0.0) return (float)$value==0.0?'Sin movimiento, igual que antes.':'Antes no hubo movimiento para comparar.';
    $difference=((float)$value-(float)$previous)/abs((float)$previous)*100;
    if(abs($difference)<0.05) return 'Igual que '.$period.'.';
    return ($difference>0?'▲ ':'▼ ').panicEcoNumber(abs($difference),1).'% '.($difference>0?'más':'menos').' que '.$period.'.';
}
function panicEcoPeriodWords($filters) { $days=$filters['start']->diff($filters['end'])->days; return $days===1?'el día anterior':($days===7?'los 7 días anteriores':($days===30?'los 30 días anteriores':'los '.$days.' días anteriores')); }
function panicEcoRangeWords($filters) { $days=$filters['start']->diff($filters['end'])->days; return $days===1?'En estas fechas':'En estos '.$days.' días'; }
function panicEcoPeriodLabel($key) {
    $key=(string)$key;
    if(preg_match('/^(\d{4})-S(\d{2})$/D',$key,$m)) return 'Semana '.(int)$m[2].' · '.$m[1];
    if(preg_match('/^\d{4}-\d{2}-\d{2}$/D',$key)) return substr($key,8,2).'/'.substr($key,5,2).'/'.substr($key,0,4);
    if(preg_match('/^(\d{4})-(\d{2})$/D',$key,$m)) { $months=['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];return ($months[(int)$m[2]-1]??$m[2]).' '.$m[1]; }
    return $key;
}
function panicEcoMetric($title,$value,$help,$options=[]) {
    $flow=$options['flow']??''; $prefix=$options['prefix']??''; $suffix=$options['suffix']??'';
    echo '<article class="eco-metric"><div class="eco-metric-head"><span class="eco-metric-icon">'.panicEcoIcon($options['icon']??'coin').'</span><h3>'.panicEcoEscape($title).'</h3>'.panicEcoHelp($help).'</div>';
    if(!empty($options['lead'])) echo '<p class="eco-metric-lead">'.panicEcoEscape($options['lead']).'</p>';
    echo '<strong class="eco-metric-value'.($flow?' eco-amount--'.$flow:'').'">'.($flow==='in'?'▲ ':($flow==='out'?'▼ ':'')).panicEcoEscape($prefix.panicEcoNumber($value,$options['decimals']??0).$suffix).'</strong>';
    if(isset($options['comparison'])) echo '<small>'.panicEcoEscape($options['comparison']).'</small>';
    echo '</article>';
}
function panicEcoEmpty($tests=false) {
    echo '<div class="eco-empty-friendly">'.panicEcoIcon('clock').'<div><strong>'.($tests?'Todavía no hay movimientos en estas fechas.':'Todavía no hay movimiento de jugadores reales en estas fechas.').'</strong><p>'.($tests?'Probá ampliar el rango de fechas. La carga se actualiza cada 5 minutos.':'Activá «Ver también mis pruebas» para ver tus pruebas.').'</p></div></div>';
}
function panicEcoDetailStart($copy='Tablas, fechas y movimientos completos',$open=false,$id='') { echo '<details class="eco-detail"'.($open?' open':'').($id?' id="'.panicEcoEscape($id).'"':'').'><summary><span>Ver detalle</span><small>'.panicEcoEscape($copy).'</small>'.panicEcoIcon('chevron').'</summary><div class="eco-detail-body">'; }
function panicEcoDetailEnd() { echo '</div></details>'; }
function panicEcoVerdict($kind,$value,$perGame=false) {
    if($value===null || !is_finite((float)$value)) return ['tone'=>'quiet','label'=>'Sin datos'];
    $n=(float)$value;
    if($kind==='spending') $tone=$n>40?'good':($n>=15?'watch':'bad');
    elseif($kind==='payout') $tone=$n>=85 && $n<=97?'good':($perGame?($n<70 || $n>110?'bad':'watch'):($n<80 || $n>102?'bad':'watch'));
    else { $n=abs($n);$tone=$n<1?'good':($n<=5?'watch':'bad'); }
    return ['tone'=>$tone,'label'=>['good'=>'Bien','watch'=>'Mirar','bad'=>'Revisar'][$tone]];
}
function panicEcoSignal($title,$tone,$value,$sentence,$help='') {
    $labels=['good'=>'Bien','watch'=>'Mirar','bad'=>'Revisar','quiet'=>'Sin datos']; $icons=['good'=>'check','watch'=>'alert','bad'=>'alert','quiet'=>'minus'];
    echo '<article class="eco-signal eco-signal--'.panicEcoEscape($tone).'"><div class="eco-signal-head"><h3>'.panicEcoEscape($title).'</h3>'.($help?panicEcoHelp($help):'').'</div><span class="eco-badge">'.panicEcoIcon($icons[$tone]??'minus').panicEcoEscape($labels[$tone]??'Sin datos').'</span><strong class="eco-signal-value">'.panicEcoEscape($value).'</strong><p>'.panicEcoEscape($sentence).'</p></article>';
}
function panicEcoLoadedTone($state) { return !$state || ($state['Resultado']??'')!=='OK' || ($state['MinutosDesdeUltima']??null)===null || (float)$state['MinutosDesdeUltima']>15?'bad':'good'; }
function panicEcoGameNames() { return ['RULETA'=>'Ruleta','TRAGAMONEDAS'=>'Tragamonedas','MAYOR_MENOR'=>'Mayor o menor','DRAGON'=>'Vuelo del dragón','JOYAS'=>'Lluvia de joyas','TESORO'=>'Tesoro de Kalima','TORRE'=>'Torre del Caos','CARRERA'=>'Carrera de monstruos']; }
function panicEcoFriendlyValue($key,$value) {
    if($value===null) return '—';
    $names=panicEcoGameNames();
    if($key==='Juego' || $key==='Fuente') return $names[$value]??(['TIENDA'=>'Tienda','VIP'=>'VIP','MERCADO'=>'Mercado'][$value]??$value);
    if($key==='Periodo') return panicEcoPeriodLabel($value);
    if($key==='Prueba') return (int)$value===1?'Mis pruebas':'Jugador real';
    if(in_array($key,['Destacada','Liquidada'],true)) return (int)$value===1?'Sí':'No';
    if($key==='Nivel') return ['ERROR'=>'Revisar','REVISAR'=>'Mirar','RESUELTO'=>'Resuelto'][$value]??$value;
    if($key==='Resultado') return $value==='OK'?'Actualizado':'Revisar';
    if($key==='Evento') return ['COMPRA'=>'Compra','VIP_COMPRA'=>'Compra de VIP','VIP_SIN_COBRO'=>'VIP dado sin cobrar'][$value]??ucfirst(strtolower(str_replace('_',' ',(string)$value)));
    if($key==='Ambiente') return $value==='test'?'Mis pruebas':($value==='production'?'Pagos reales':$value);
    if($key==='Detalle') return str_replace(['producto=','indice=','|'],['Producto: ','Artículo: ',' · '],(string)$value);
    return $value;
}
function panicEcoRankList($title,$rows,$valueKey,$unit='Eryns',$flow='',$empty='Todavía no hay movimientos para mostrar.') {
    echo '<section class="eco-rank"><h3>'.panicEcoEscape($title).'</h3>'; if(!$rows) echo '<p class="eco-note">'.panicEcoEscape($empty).'</p>'; else { echo '<ol>'; foreach(array_slice($rows,0,5) as $index=>$row) echo '<li><span class="eco-rank-pos">'.($index+1).'</span><span class="eco-rank-name">'.panicEcoEscape($row['Cuenta']).'</span><strong class="eco-amount--'.panicEcoEscape($flow).'">'.($flow==='in'?'▲ ':($flow==='out'?'▼ ':'')).panicEcoNumber(abs((float)$row[$valueKey])).' '.panicEcoEscape($unit).'</strong></li>';echo '</ol>'; } echo '</section>';
}
function panicEcoCashRows($rows) { $out=[];foreach($rows as $row) { $key=$row['Periodo'];if(!isset($out[$key]))$out[$key]=['Periodo'=>$key,'Pesos'=>0];$out[$key]['Pesos']+=(float)$row['Pesos']; }ksort($out);return array_values($out); }
function panicEcoLatestCheck($rows) {
    if(!$rows) return ['amount'=>null,'percent'=>null,'day'=>null];$r=$rows[0];
    return ['amount'=>$r['NoIdentificado'],'percent'=>panicEcoRatio($r['NoIdentificado']===null?null:abs((float)$r['NoIdentificado']),$r['SaldoFin']),'day'=>$r['DiaAR']];
}
function panicEcoFlow($created,$circulation,$sources) {
    $total=array_sum($sources);$positive=array_sum(array_map(function($v){return max(0,(float)$v);},$sources));
    echo '<section class="eco-panel eco-flow-panel"><div class="eco-section-heading"><span class="eco-section-icon">'.panicEcoIcon('coin').'</span><div><h3>Así se movieron los Eryns</h3><p>Entraron por recargas, quedaron en manos de jugadores y se gastaron.</p></div></div><div class="eco-flow">';
    echo '<article class="eco-flow-block eco-flow-block--in"><span class="eco-kicker">Entraron</span><p>Se crearon por recargas.</p><strong class="eco-amount--in">▲ '.panicEcoNumber($created).'</strong><small>Eryns en estas fechas</small>'.panicEcoHelp('Eryns nuevos entregados por las recargas del rango elegido.').'</article><span class="eco-flow-arrow" aria-hidden="true">→</span>';
    echo '<article class="eco-flow-block eco-flow-block--balance"><span class="eco-kicker">En manos de jugadores</span><p>Lo que tienen guardado ahora.</p><strong>'.panicEcoNumber($circulation).'</strong><small>Eryns de jugadores reales</small>'.panicEcoHelp('Es una foto del saldo actual de cuentas reales, no la resta de las otras dos columnas. También hay misiones, baúles y otras entradas que este panel no sigue.').'</article><span class="eco-flow-arrow" aria-hidden="true">→</span>';
    echo '<article class="eco-flow-block eco-flow-block--out"><span class="eco-kicker">Salieron</span><p>Desaparecieron al gastarse.</p><strong class="'.($total<0?'eco-amount--in':'eco-amount--out').'">'.($total<0?'▲ ':'▼ ').panicEcoNumber(abs($total)).'</strong><small>'.($total<0?'Eryns devueltos de más, en total':'Eryns en estas fechas').'</small>'.panicEcoHelp('Lo que se quedó la casa, más lo gastado en tienda, VIP y comisión del mercado. Un resultado negativo significa que los juegos devolvieron más Eryns de los que se gastaron.');
    echo '</article></div><div class="eco-flow-breakdown"><h4>En qué se gastaron</h4><ul class="eco-outflows">';foreach($sources as $name=>$value) { $pct=panicEcoRatio(max(0,(float)$value),$positive);echo '<li><div><span>'.panicEcoEscape($name).'</span><b class="'.($value<0?'eco-amount--in':'eco-amount--out').'">'.($value<0?'▲ ':'▼ ').panicEcoNumber(abs($value)).'</b></div><div class="eco-bar" role="img" aria-label="'.panicEcoEscape($name.': '.panicEcoNumber($value).' Eryns').'" ><span style="width:'.number_format(min(100,max(0,$pct??0)),3,'.','').'%"></span></div><small>'.($value<0?'Volvieron de más a los jugadores':($pct===null?'Sin salidas para comparar':panicEcoNumber($pct,1).'% de las salidas')).'</small></li>'; }echo '</ul></div>';
    $delta=(float)$created-$total;
    if($total>=0) $text='Entraron '.panicEcoNumber($created).' y salieron '.panicEcoNumber($total).': quedaron '.panicEcoNumber(abs($delta)).' Eryns '.($delta>=0?'más':'menos').' por estos movimientos.';
    else $text='Entraron '.panicEcoNumber($created).' por recargas y los juegos devolvieron '.panicEcoNumber(abs($total)).' de más después de los demás gastos: quedaron '.panicEcoNumber($delta).' Eryns más por estos movimientos.';
    echo '<p class="eco-flow-takeaway">'.panicEcoEscape($text).'</p><p class="eco-note">El saldo guardado también cambia por misiones, baúles, regalos y cargas manuales. Las tres columnas no forman un cuadre completo. '.($positive!=$total?'Las barras muestran solo las salidas positivas.':'').'</p></section>';
}

function panicEcoCount($value,$singular,$plural) { return panicEcoNumber($value).' '.((float)$value===1.0?$singular:$plural); }
