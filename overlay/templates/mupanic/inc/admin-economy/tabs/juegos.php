<?php
if(!defined('PANIC_ECO_ADMIN') || PANIC_ECO_ADMIN!==true) { http_response_code(403); exit; }
$names=['RULETA'=>'Ruleta','TRAGAMONEDAS'=>'Tragamonedas','MAYOR_MENOR'=>'Mayor o menor','DRAGON'=>'Vuelo del dragón','JOYAS'=>'Joyas','TESORO'=>'Tesoro','TORRE'=>'Torre','CARRERA'=>'Carrera'];
$game=$_GET['juego']??''; $ranking=$_GET['ranking']??'ganadores';
if(!is_string($game) || ($game!=='' && !isset($names[$game])) || !is_string($ranking) || !in_array($ranking,['ganadores','perdedores'],true)) throw new InvalidArgumentException('Filtro de juego inválido.');
$games=$ecoRepository->games(); $series=$ecoRepository->gameSeries(); $players=$ecoRepository->players($ranking==='perdedores'); $detail=$game!==''?$ecoRepository->gameDetail($game):[];
panicEcoTable('Rendimiento por juego',['Juego'=>'Juego','Jugadas'=>'Jugadas','Apostado'=>'Apostado · Eryns','Pagado'=>'Pagado · Eryns','NetoRetirado'=>'Neto retirado · Eryns','DevueltoPct'=>'Devuelto %','PremioMayor'=>'Premio mayor · Eryns'],$games,['numbers'=>['Jugadas','Apostado','Pagado','NetoRetirado','DevueltoPct','PremioMayor'],'decimals'=>['DevueltoPct'],'tone'=>function($r){return $r['DevueltoPct']===null?'':((float)$r['DevueltoPct']>=85 && (float)$r['DevueltoPct']<=97?'eco-good':'eco-warning');},'links'=>['Juego'=>function($r) use ($ecoFilters){return panicEcoUrl($ecoFilters['query'], ['juego'=>$r['Juego']]);}],'note'=>'Verde: devolución entre 85% y 97%. Amarillo: fuera del rango. Hacé clic en un juego para abrir sus movimientos.']);
$aligned=[]; foreach($series as $row) { $key=$row['Periodo']; if(!isset($aligned[$key])) $aligned[$key]=['Periodo'=>$key]+array_fill_keys(array_keys($names),0); $aligned[$key][$row['Juego']]=$row['NetoRetirado']; }
panicEcoChart('Neto retirado por juego · Eryns','line',array_values($aligned),$names);
$pie=[]; foreach($games as $row) $pie[]=['Periodo'=>$names[$row['Juego']]??$row['Juego'],'Apostado'=>$row['Apostado']];
panicEcoChart('Participación en lo apostado','doughnut',$pie,['Apostado'=>'Eryns apostados']);
?>
<nav class="eco-subnav"><a href="<?php echo panicEcoEscape(panicEcoUrl($ecoFilters['query'], ['ranking'=>'ganadores','juego'=>$game])); ?>">Top 20 · ganaron más</a><a href="<?php echo panicEcoEscape(panicEcoUrl($ecoFilters['query'], ['ranking'=>'perdedores','juego'=>$game])); ?>">Top 20 · perdieron más</a></nav>
<?php
panicEcoTable($ranking==='perdedores'?'Top 20 por menor neto del jugador':'Top 20 por mayor neto del jugador',['Cuenta'=>'Cuenta','Apostado'=>'Apostado · Eryns','Pagado'=>'Pagado · Eryns','NetoJugador'=>'Neto jugador · Eryns'],$players,['numbers'=>['Apostado','Pagado','NetoJugador'],'note'=>'Agrupado por cuenta y rango. Apuestas y premios con el mismo Tx se suman aunque estén en filas separadas.']);
if($game!=='') panicEcoTable('Últimos 200 movimientos · '.$names[$game],['FechaAR'=>'Fecha ARG','Cuenta'=>'Cuenta','Personaje'=>'Personaje','Tx'=>'Tx','Evento'=>'Evento','Gastado'=>'Apostado · Eryns','Recibido'=>'Premio · Eryns','Detalle'=>'Detalle'],$detail,['numbers'=>['Gastado','Recibido'],'dates'=>['FechaAR'],'note'=>'Son líneas del registro: una jugada puede tener apuesta y premio en filas separadas.']);
