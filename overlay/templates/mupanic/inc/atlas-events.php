<?php if(!defined('access') || !access) die();
require_once(__DIR__.'/atlas-event-runtime.php');
$eventSnapshot=panicAtlasEvents();
// Editorial copy cannot enable an event or supply its agenda.
$eventEditorial=json_decode(file_get_contents(__DIR__.'/atlas-event-guides.json'),true);
$eventGuides=$eventEditorial['guides'];
// These competitions are explicitly organized by staff, even if an older exporter
// still carries an obsolete calendar. Keep the synchronized enabled flag intact.
foreach($eventSnapshot['events'] as &$event) if(preg_match('/^arena-[0-7]$/',$event['id'])) {
 $event['mode']='manual'; $event['schedule']=[]; $event['group']='staff';
}
unset($event);
// A reader upgrade or editorial revision can change output without changing the
// server files. Refresh those changes too, while preserving the source hash.
$eventRenderVersion=hash('sha256',$eventSnapshot['sourceHash'].json_encode($eventSnapshot['events']).hash_file('sha256',__DIR__.'/atlas-event-guides.json'));
$atlasEvents=array_values(array_filter($eventSnapshot['events'],function($e){return $e['enabled'];}));
$eventGroups=['classic'=>'Clásicos','custom'=>'Competencias','boss'=>'Desafíos','invasion'=>'Invasiones','staff'=>'Manuales'];
$eventStories=[];
foreach($eventGuides as $id=>$guide) $eventStories[$id]=[$guide['tagline'],$guide['intro'],$guide['entry'],$guide['objective']];
$eventRewardIcons=json_decode(file_get_contents(__DIR__.'/atlas-drop-items.json'),true);
$eventRewardImage=function($label) use($eventRewardIcons) {
 foreach(['Chaos'=>'6159-0','Bless'=>'7181-0','Soul'=>'7182-0','Life'=>'7184-0','Harmony'=>'7194-0'] as $name=>$key)
  if(strpos($label,$name)!==false && strpos($label,' o ')===false && strpos($label,'/')===false) return $eventRewardIcons[$key]['file'] ?? null;
 return null;
};
$modeNames=['scheduled'=>'Programado','access'=>'Por acceso / condiciones','manual'=>'Manual · fecha a anunciar'];
$dayNames=['','Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
$eventHours=function($e) use($dayNames) {
 if($e['mode']==='manual') return 'Fecha a anunciar · convocatoria del staff.';
 if(!$e['schedule']) return $e['mode']==='manual' ? 'Fecha a anunciar.' : 'Sin horario fijo; depende del acceso o del ciclo del escenario.';
 $labels=[];
 foreach($e['schedule'] as $r) {
  $label=($r[4]<0?'Cada hora · ':str_pad($r[4],2,'0',STR_PAD_LEFT).':').str_pad(max(0,$r[5]),2,'0',STR_PAD_LEFT);
  if($r[3]>0) $label.=' · '.$dayNames[$r[3]];
  if($r[2]>0 || $r[1]>0 || $r[0]>0) $label.=' · fecha específica';
  $labels[$label]=true;
 }
 $all=array_keys($labels);return implode(' · ',array_slice($all,0,6)).(count($all)>6?' · +'.(count($all)-6).' horarios en la agenda':'');
};
$eventImage=function($e) use($atlasAssets,$eventGuides) {
 if(isset($eventGuides[$e['id']]['art'])) return $eventGuides[$e['id']]['art'];
 if(in_array($e['id'],['blood-castle','devil-square','pandora'],true)) return 'img/atlas/editorial/'.$e['id'].'.webp';
 foreach($e['monsters'] as $mid) if(isset($atlasAssets['monsters'][(string)$mid])) return $atlasAssets['monsters'][(string)$mid]['file'];
 if($e['id']==='raklion' && isset($atlasAssets['monsters']['459'])) return $atlasAssets['monsters']['459']['file'];
 return 'img/atlas/editorial/'.($e['group']==='classic'?'imperial-temple':($e['group']==='boss'?'devil-square':'pvp-arena')).'.webp';
};
?>
<section id="eventos" class="wiki-section" aria-labelledby="atlas-events-title" data-events-version="<?php echo $eventRenderVersion; ?>">
 <div class="atlas-section-heading"><div><span class="eyebrow">EL CONTINENTE NO SE DETIENE</span><h2 id="atlas-events-title">Respondé al desafío.</h2></div><span class="atlas-section-mark" aria-hidden="true">III</span></div>
 <p>Elegí tu próxima batalla. Conocé el objetivo, los horarios y qué podés conseguir.</p>
 <div class="atlas-agenda"><div class="atlas-agenda-head"><h3>Próximos desafíos</h3><span>Hora Argentina<br>UTC−3</span></div><div data-event-upcoming><p>Los horarios completos están disponibles debajo.</p></div><details class="atlas-full-agenda"><summary>Consultar todos los horarios</summary><?php foreach($atlasEvents as $e) { if($e['mode']!=='scheduled' || !$e['schedule']) continue; ?><div class="atlas-agenda-entry"><span><?php echo panicWikiEscape($e['name']); ?></span><div><?php foreach($e['schedule'] as $r) { ?><small><?php echo panicWikiEscape(($r[3]>0?$dayNames[$r[3]].' ':'').($r[4]<0?'Cada hora · ':str_pad($r[4],2,'0',STR_PAD_LEFT).':').str_pad(max(0,$r[5]),2,'0',STR_PAD_LEFT).($r[2]>0?' · día '.$r[2]:'').($r[1]>0?' · mes '.$r[1]:'').($r[0]>0?' · '.$r[0]:'')); ?></small><?php } ?></div></div><?php } ?></details><p class="atlas-agenda-note">Agenda prevista: confirmá la apertura con los avisos del juego. Los desafíos por acceso no tienen horario fijo. Survivor y arenas por clase: fecha a anunciar. Actualizado: <time datetime="<?php echo panicWikiEscape($eventSnapshot['generatedAt']); ?>" data-event-published></time></p></div>
 <div class="atlas-event-controls" aria-label="Filtrar eventos"><button type="button" data-event-group="all" aria-pressed="true">Todos <span><?php echo count($atlasEvents); ?></span></button><?php foreach($eventGroups as $key=>$label) { ?><button type="button" data-event-group="<?php echo $key; ?>" aria-pressed="false"><?php echo $label; ?></button><?php } ?><label><span class="sr-only">Buscar evento</span><input type="search" data-event-search placeholder="Buscar evento…"></label></div><p data-event-empty hidden>No hay eventos habilitados para esta búsqueda.</p>
 <div class="atlas-event-grid"><?php foreach($atlasEvents as $e) { $story=$eventStories[$e['id']] ?? null; ?><article class="atlas-event-card" data-event-kind="<?php echo $e['group']; ?>" data-event-name="<?php echo panicWikiEscape($e['name']); ?>"><img loading="lazy" width="640" height="360" src="<?php echo __PATH_TEMPLATE__.panicWikiEscape($eventImage($e)); ?>" alt=""><div><span class="eyebrow"><?php echo $eventGroups[$e['group']]; ?></span><h3><?php echo panicWikiEscape($e['name']); ?></h3><p><?php echo panicWikiEscape($story[0] ?? ($e['group']==='invasion'?'La amenaza llega al continente.':'Un desafío anunciado dentro del juego.')); ?></p><span class="atlas-event-status"><?php echo $modeNames[$e['mode']]; ?></span><a href="#evento-<?php echo $e['id']; ?>" data-atlas-event-open="<?php echo $e['id']; ?>">Conocer el evento →</a></div></article><?php } ?></div>
 <?php foreach($atlasEvents as $e) {
  $story=$eventStories[$e['id']] ?? (strpos($e['id'],'arena-')===0 ? ['','Una arena con reglas propias de participación. Revisá los requisitos de tu personaje y seguí la convocatoria del evento.','Consultá el acceso y los requisitos de la arena dentro del cliente.','Competir según las reglas de la arena.'] : (strpos($e['id'],'event-drop-')===0 ? ['','Una lluvia de objetos en el escenario anunciado por el juego. Los premios aparecen durante el tiempo del evento.','Acudí al mapa indicado en los anuncios del evento.','Buscar los objetos que aparecen durante la lluvia.'] : ($e['group']==='invasion' ? ['','Una invasión programada: seguí el anuncio, buscá a las criaturas en los mapas del evento y preparate para el combate.','Seguí el anuncio de invasión dentro del juego.','Buscar y derrotar a los monstruos de la invasión.'] : ['','Un evento habilitado para ser organizado dentro del juego. La convocatoria y las reglas se anuncian al iniciarlo.','Esperá la convocatoria del staff.','Seguí las reglas anunciadas durante la convocatoria.'])));
  $facts=['Cómo participar'=>$story[2],'Tu objetivo'=>$story[3],'Duración'=>$e['durationMinutes']?$e['durationMinutes'].' minutos configurados.':'Depende de las fases o condiciones del evento.','Horarios'=>$eventHours($e)];
  $guide=$eventGuides[$e['id']] ?? null;
  $tiers=$guide['tiers'] ?? [];
  if(!$tiers) $tiers=[['label'=>'Recompensas','bag'=>null,'rewards'=>[['label'=>'Condiciones dentro del juego','detail'=>'Detalle pendiente de revisión']],'delivery'=>'']];
 ?>
 <article id="evento-<?php echo $e['id']; ?>" class="atlas-event-full" data-atlas-event="<?php echo $e['id']; ?>"><div class="atlas-event-dossier"><div class="atlas-event-visual"><img loading="lazy" width="640" height="960" src="<?php echo __PATH_TEMPLATE__.panicWikiEscape($eventImage($e)); ?>" alt=""><span aria-hidden="true"><?php echo panicWikiEscape($e['name']); ?></span></div><div class="atlas-event-copy"><span class="eyebrow"><?php echo $eventGroups[$e['group']].' / '.$modeNames[$e['mode']]; ?></span><h3><?php echo panicWikiEscape($e['name']); ?></h3><p><?php echo panicWikiEscape($story[1]); ?></p><dl class="atlas-event-facts"><?php foreach($facts as $label=>$value) { ?><div><dt><?php echo panicWikiEscape($label); ?></dt><dd><?php echo panicWikiEscape($value); ?></dd></div><?php } ?></dl><div class="atlas-event-rewards">
 <div class="atlas-event-prize-heading"><strong>Premios configurados</strong><?php if(count($tiers)>1) { ?><label><span class="sr-only">Categoría de recompensa</span><select data-event-tier-select><?php foreach($tiers as $i=>$tier) { ?><option value="<?php echo $i; ?>"><?php echo panicWikiEscape($tier['label']); ?></option><?php } ?></select></label><?php } ?></div>
 <?php foreach($tiers as $i=>$tier) { ?><div class="atlas-event-prize-tier" data-event-tier="<?php echo $i; ?>" <?php if($i===0) echo 'data-tier-active'; ?>>
 <div class="atlas-event-prizes" data-prize-count="<?php echo count($tier['rewards']); ?>"><?php foreach($tier['rewards'] as $reward) { $icon=$eventRewardImage($reward['label']); ?><div class="atlas-event-prize"><?php if($icon) { ?><img loading="lazy" width="40" height="40" src="<?php echo __PATH_TEMPLATE__.panicWikiEscape($icon); ?>" alt=""><?php } else { ?><span class="atlas-prize-symbol" aria-hidden="true">◇</span><?php } ?><span><b><?php echo panicWikiEscape($reward['label']); ?></b><small><?php echo panicWikiEscape($reward['detail']); ?></small></span></div><?php } ?></div>
 <div class="atlas-event-delivery"><span><?php echo panicWikiEscape($tier['delivery'] ? 'Entrega: '.$tier['delivery'] : 'Detalle de entrega por confirmar'); ?></span><?php if($tier['bag']!==null) { ?><a href="#recompensa-<?php echo (int)$tier['bag']; ?>">Ver lista ↗</a><?php } ?></div></div><?php } ?>
 <?php if($guide) { ?><p class="atlas-event-reward-note"><?php echo panicWikiEscape($guide['rewardNote']); ?></p><small class="atlas-event-audit-date">Configuración auditada · 06/10/2026. Sujeta a revisión.</small><?php } ?>
 </div>
 <p class="atlas-event-validation"><strong>Estado de validación</strong><?php echo panicWikiEscape($guide['validation'] ?? 'Los detalles de este evento todavía requieren revisión. Seguí las reglas de la convocatoria.'); ?></p></div></div></article>
 <?php } ?>
 <div class="atlas-event-reward-lists" aria-label="Detalle de premios del evento">
 <?php $rewardBags = array_filter($wiki['eventBags'] ?? [], function($bag) { return $bag['monster'] < 0 && $bag['item'] < 0; }); include(__DIR__.'/atlas-reward-cards.php'); ?>
 </div>
 <script type="application/json" data-event-data><?php echo json_encode($eventSnapshot,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?></script>
</section>
<dialog class="atlas-event-dialog" data-atlas-event-dialog aria-label="Detalles del evento"><button class="atlas-event-close" type="button" data-atlas-event-close aria-label="Cerrar evento">×</button><div data-atlas-event-content></div></dialog>
