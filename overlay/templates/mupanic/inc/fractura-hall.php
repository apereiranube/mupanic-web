<?php
if(!defined('access') || !access) die();
require_once __DIR__.'/fractura-week-data.php';
require_once __DIR__.'/fractura-ranks.php';
$contest=$contest??panicFracturaWeekRead();$honors=panicFracturaDistinctionsRead();
$history=$contest['history']??[];$latest=$history[0]??null;
function panicFracturaHallEscape($s) { return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); }
function panicFracturaHallDate($unix,$format='d/m · H:i') { return (new DateTimeImmutable('@'.(int)$unix))->setTimezone(new DateTimeZone('America/Argentina/Buenos_Aires'))->format($format); }
function panicFracturaHallCrest($family,$size=80) {
    $name=(int)$family===1?'nexo':'caos';
    return '<img src="'.htmlspecialchars(__PATH_TEMPLATE__.'img/fractura/'.$name.'-emblema-v32.svg',ENT_QUOTES,'UTF-8').'" alt="" width="'.$size.'" height="'.$size.'">';
}
$latestFamily=$latest?(int)$latest['winnerFamily']:0;
?>
<section class="fractura-hall" id="salon-de-la-fama" aria-labelledby="fh-title">
  <div class="fh-sanctum">
    <picture class="fh-sanctum-art" aria-hidden="true"><source media="(max-width:650px)" srcset="<?php echo $art; ?>salon-vencedores-mobile-v34.webp"><img src="<?php echo $art; ?>salon-vencedores-v34.webp" alt="" width="1536" height="1024" loading="lazy"></picture>
    <div class="fh-sanctum-veil" aria-hidden="true"></div>
    <header class="fh-sanctum-heading"><span class="eyebrow">LAS VICTORIAS DEJAN HUELLA</span><h2 id="fh-title">Salón de<br><em>la Fama.</em></h2></header>
    <?php foreach([1=>'Nexo',2=>'Caos'] as $family=>$name) { ?><div class="fh-standard fh-standard-<?php echo $family; ?>">
      <div class="fh-standard-sigil"><?php echo panicFracturaHallCrest($family,100); ?></div><strong><?php echo $name; ?></strong>
      <small><?php echo $family===1?'EL PACTO':'LA RUPTURA'; ?></small>
    </div><?php } ?>
    <div class="fh-inscription fh-inscription-<?php echo $latestFamily; ?>">
      <?php if($latest) { ?><span class="fh-inscription-kicker">LA ÚLTIMA CONQUISTA</span>
      <div class="fh-victor-seal"><?php if($latestFamily) echo panicFracturaHallCrest($latestFamily,90);else { echo panicFracturaHallCrest(1,65).panicFracturaHallCrest(2,65); } ?></div>
      <h3><?php echo $latestFamily===1?'Nexo':($latestFamily===2?'Caos':'Empate'); ?></h3><p><?php echo $latestFamily?'Su nombre queda en la historia.':'Ningún juramento cedió.'; ?></p>
      <span class="fh-inscription-date"><?php echo panicFracturaHallDate($latest['closeUnix'],'d.m.Y'); ?> <i aria-hidden="true">·</i> Nexo <?php echo panicFracturaNumber($latest['nexoPoints']); ?> / Caos <?php echo panicFracturaNumber($latest['caosPoints']); ?></span>
      <?php } elseif($contest) { ?><span class="fh-inscription-kicker">EL PRIMER CAPÍTULO</span><div class="fh-unclaimed-seal" aria-hidden="true"><span>Ⅰ</span></div><h3>El honor<br>aún espera.</h3><p>El primer vencedor se inscribirá después del cierre semanal.</p>
      <?php } else { ?><span class="fh-inscription-kicker">LAS CRÓNICAS DE VULCANUS</span><div class="fh-unclaimed-seal" aria-hidden="true"><span>◇</span></div><h3>Un lugar<br>en la historia.</h3><p class="fh-record-status" role="status">Las crónicas no están disponibles en este momento.</p><?php } ?>
    </div>
    <div class="fh-sanctum-foot"><span>MEMORIA DE LAS CONQUISTAS</span><a href="#marcador-semanal">Ver semana en curso <span aria-hidden="true">↑</span></a></div>
  </div>
  <?php if($history) { ?><div class="fh-chronicles" id="fh-historia"><header class="fh-chapter-heading"><span class="eyebrow">LOS JURAMENTOS QUE PREVALECIERON</span><h3>Las crónicas.</h3></header><div class="fh-monuments">
    <?php foreach($history as $index=>$entry) { $winner=(int)$entry['winnerFamily'];if($index===6) { ?></div><details class="fh-older"><summary>Abrir las crónicas anteriores <span aria-hidden="true">＋</span></summary><div class="fh-monuments"><?php } ?>
    <article class="fh-monument fh-monument-<?php echo $winner; ?>"><span class="fh-monument-date"><?php echo panicFracturaHallDate($entry['closeUnix'],'d.m.Y'); ?></span><div class="fh-monument-sigil"><?php if($winner)echo panicFracturaHallCrest($winner,72);else echo '<span class="fh-tie-mark" aria-hidden="true">◇</span>'; ?></div><span class="fh-monument-oath"><?php echo $winner?'BANDO VENCEDOR':'FUERZAS IGUALADAS'; ?></span><h4><?php echo $winner===1?'Nexo':($winner===2?'Caos':'Empate'); ?></h4><p><span>NEXO <b><?php echo panicFracturaNumber($entry['nexoPoints']); ?></b></span><i aria-hidden="true">/</i><span>CAOS <b><?php echo panicFracturaNumber($entry['caosPoints']); ?></b></span></p></article>
    <?php } ?></div><?php if(count($history)>6) { ?></details><?php } ?></div><?php } ?>
  <div class="fh-laureates-hall" id="distinciones-fractura"><header class="fh-chapter-heading"><span class="eyebrow">EL TRIUNFO LLEVA TU NOMBRE</span><h3>Los laureados.</h3></header>
    <?php if($honors) { ?><div class="fh-pedestals"><?php foreach($honors as $honor) { $family=$honor['family']; ?><article class="fh-pedestal fh-pedestal-<?php echo $family; ?>"><div class="fh-laurel" aria-hidden="true"><?php echo panicFracturaHallCrest($family,80); ?></div><span><?php echo $family===1?'LAUREADO DEL NEXO':'LAUREADO DE CAOS'; ?></span><h4><?php echo panicFracturaHallEscape($honor['name']); ?></h4><small>Hasta <?php echo panicFracturaHallDate($honor['untilUnix'],'d.m · H:i'); ?> ARG</small><div class="fh-pedestal-base" aria-hidden="true"></div></article><?php } ?></div>
    <?php } else { ?><div class="fh-silent-names"><span aria-hidden="true">✧</span><p><?php echo $honors===null?'Los nombres laureados no están disponibles en este momento.':'Los pedestales esperan nuevos nombres.'; ?></p></div><?php } ?>
  </div>
  <a class="fh-atlas-link" href="<?php echo __BASE_URL__; ?>info/#fractura">Conocé las reglas de la conquista en el Atlas <span aria-hidden="true">↗</span></a>
</section>
