<?php
if(!defined('access') || !access) die();
require_once __DIR__.'/fractura-week-data.php';
$contest=panicFracturaWeekRead();
function panicFracturaHallEscape($s) { return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); }
function panicFracturaHallDate($unix,$format='d/m · H:i') { return (new DateTimeImmutable('@'.(int)$unix))->setTimezone(new DateTimeZone('America/Argentina/Buenos_Aires'))->format($format); }
function panicFracturaHallRewards($rules) {
    foreach(['W'=>'Bando ganador','L'=>'Bando perdedor','T'=>'Empate'] as $key=>$name) {
        $r=$rules[$key]; $parts=[];
        foreach(['fragments'=>'fragmentos de vínculo','bless'=>'Bless','soul'=>'Soul','chaos'=>'Chaos'] as $field=>$label) {
            if($field==='fragments' && (int)$r[$field]===1) $label='fragmento de vínculo';
            if((int)$r[$field]>0) $parts[]=panicFracturaNumber($r[$field]).' '.$label;
        }
        echo '<div class="fh-reward"><span>'.panicFracturaHallEscape($name).'</span><strong>'.panicFracturaNumber($r['contribution']).' <small>contribución personal</small></strong><p>'.panicFracturaHallEscape(implode(' · ',$parts)).'</p></div>';
    }
}
?>
<section class="fractura-hall" id="salon-de-la-fama" aria-labelledby="fh-title">
  <header class="fh-heading"><div><span class="eyebrow">LAS VICTORIAS DEJAN HUELLA</span><h2 id="fh-title">Salón de<br><em>la Fama.</em></h2></div><p>Una semana para conquistar Vulcanus.<br>Un lugar en la historia para el bando vencedor.</p></header>
  <nav class="fh-tabs" aria-label="Competencia semanal"><a href="#fh-disputa">Esta semana</a><a href="#fh-historia">Los vencedores</a><a href="#fh-premios">Premios y cobro</a></nav>
  <?php if(!$contest) { ?>
  <div class="fh-unavailable" role="status"><strong>El registro semanal no está disponible en este momento.</strong><p>Volvé a consultar más tarde. Los puntos acumulados de los bandos se muestran por separado.</p></div>
  <?php } else { $current=$contest['current'];$finished=$current['isClosed'];$waiting=!$finished && (int)$current['nowUnix']>=(int)$current['closeUnix']; ?>
  <article class="fh-dispute" id="fh-disputa">
    <header><span class="eyebrow"><?php echo $finished?'SEMANA CERRADA':($waiting?'CIERRE PENDIENTE':($current['enabled']?'LA DISPUTA ESTÁ ABIERTA':'COMPETENCIA EN PAUSA')); ?></span><span><?php echo panicFracturaHallDate($current['startUnix'],'d/m'); ?> — <?php echo panicFracturaHallDate($current['closeUnix'],'d/m/Y'); ?></span></header>
    <div class="fh-versus">
      <?php foreach([1=>'Nexo',2=>'Caos'] as $family=>$name) { $field=$family===1?'nexoPoints':'caosPoints'; ?>
      <div class="fh-faction fh-faction-<?php echo $family; ?>"><img src="<?php echo $art; ?><?php echo strtolower($name); ?>-emblema-v32.svg" alt="" width="100" height="100"><span><?php echo $name; ?></span><strong><?php echo panicFracturaNumber($current[$field]); ?></strong><small>puntos semanales</small></div>
      <?php if($family===1) { ?><span class="fh-vs" aria-hidden="true">VS</span><?php } } ?>
    </div>
    <footer><p><strong><?php echo panicFracturaNumber($current['bossPoints']); ?> puntos por victoria oficial del boss.</strong> Decide el último golpe. Las misiones y el PvP no suman a este marcador.</p><span>Cierre: <?php echo panicFracturaHallDate($current['closeUnix'],'d/m · H:i'); ?> ARG</span></footer>
    <div class="fh-updated">Registro: <?php echo panicFracturaHallDate($current['nowUnix']); ?> ARG · <a href="?actualizar=<?php echo (int)$current['nowUnix']; ?>#salon-de-la-fama">Actualizar</a></div>
  </article>
  <div class="fh-history" id="fh-historia"><div class="fh-subheading"><span class="eyebrow">EL HONOR PERMANECE</span><h3>Los vencedores.</h3><p>Resultados oficiales de las últimas 52 semanas cerradas.</p></div>
  <?php if(!$contest['history']) { ?>
    <div class="fh-empty"><span aria-hidden="true">I</span><h4>La primera conquista todavía está en juego.</h4><p>El primer bando vencedor quedará registrado después del cierre semanal. Hasta entonces, cada derrota del Señor de la Fractura puede cambiar el destino.</p></div>
  <?php } else { foreach($contest['history'] as $entry) { $winner=$entry['winnerFamily'];$name=$winner===1?'Nexo':($winner===2?'Caos':'Empate'); ?>
    <article class="fh-champion fh-champion-<?php echo $winner; ?>">
      <header><span>SEMANA DEL <?php echo panicFracturaHallEscape(substr($entry['weekStart'],8,2).'/'.substr($entry['weekStart'],5,2).'/'.substr($entry['weekStart'],0,4)); ?></span><time datetime="<?php echo gmdate('c',(int)$entry['closeUnix']); ?>">Cierre <?php echo panicFracturaHallDate($entry['closeUnix']); ?> ARG</time></header>
      <div class="fh-champion-body"><?php if($winner) { ?><img src="<?php echo $art.strtolower($name); ?>-emblema-v32.svg" alt="" width="86" height="86"><?php } ?><div><span><?php echo $winner?'BANDO VENCEDOR':'FUERZAS IGUALADAS'; ?></span><h4><?php echo $name; ?></h4><p>Nexo <b><?php echo panicFracturaNumber($entry['nexoPoints']); ?></b> <i aria-hidden="true">/</i> Caos <b><?php echo panicFracturaNumber($entry['caosPoints']); ?></b></p></div></div>
      <details><summary>Ver los premios de esta semana <span aria-hidden="true">+</span></summary><div class="fh-rewards"><?php panicFracturaHallRewards($entry['rules']); ?></div></details>
    </article>
  <?php } } ?></div>
  <div class="fh-prizes" id="fh-premios"><div class="fh-subheading"><span class="eyebrow">TU ESFUERZO TIENE RECOMPENSA</span><h3>Defendé tu bando.<br>Cobrá tu conquista.</h3><p>Completá y cobrá al menos <strong><?php echo panicFracturaNumber($current['minimumQuests']); ?> misiones diarias con el mismo personaje</strong> durante la semana, antes del cierre. La misma misión en dos días distintos también cuenta.</p></div>
    <div class="fh-rewards"><?php panicFracturaHallRewards($contest['rules']); ?></div>
    <ol class="fh-steps"><li><b>01</b><div><strong>Elegí quién cobra</strong><p>Después del cierre, hablá con Aldren o Varkor usando un personaje que cumpla las misiones. El primero que reclame fija el premio de la cuenta.</p></div></li><li><b>02</b><div><strong>Dejá descansar tu cuenta</strong><p>Desconectá todos sus personajes durante unos 3 minutos para recibir los puntos y el paquete completo.</p></div></li><li><b>03</b><div><strong>Abrí tu recompensa</strong><p>Volvé a entrar: la contribución estará acreditada. Retirá los objetos con <kbd>F8</kbd>. Tenés 20 días para sacarlos del baúl.</p></div></li></ol>
    <p class="fh-footnote">Un premio por cuenta y por semana. Sin núcleos semanales fijos. Si falta espacio en el baúl, el paquete completo queda pendiente. Las semanas cerradas conservan sus premios aunque cambien las reglas futuras.</p>
  </div>
  <?php } ?>
</section>
