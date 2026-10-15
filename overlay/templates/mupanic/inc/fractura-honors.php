<?php
if(!defined('access') || !access) die();
require_once __DIR__.'/fractura-ranks.php';
$honors=panicFracturaDistinctionsRead();
?>
<div class="fh-honors" id="distinciones-fractura">
  <div class="fh-subheading"><span class="eyebrow">EL TRIUNFO LLEVA TU NOMBRE</span><h3>Los laureados.</h3><p>Una distinción para quienes cobraron el premio del bando ganador. Dura hasta siete días después del cierre y también se ve en su ventana B. Es visual y no agrega estadísticas.</p></div>
  <?php if($honors===null) { ?><p class="fh-honor-empty">Las distinciones no están disponibles en este momento.</p>
  <?php } elseif(!$honors) { ?><p class="fh-honor-empty">Todavía no hay distinciones activas. Los primeros laureados aparecerán cuando se cierre una semana y cobren su recompensa.</p>
  <?php } else { ?><div class="fh-laureates">
    <?php foreach($honors as $honor) { $family=$honor['family'];$faction=$family===1?'Nexo':'Caos'; ?>
    <article class="fh-laureate fh-laureate-<?php echo $family; ?>"><img src="<?php echo $art.strtolower($faction); ?>-emblema-v32.svg" alt="" width="58" height="58"><div><span><?php echo $family===1?'LAUREADO DEL NEXO':'LAUREADO DE CAOS'; ?></span><h4><?php echo panicFracturaHallEscape($honor['name']); ?></h4><p><?php echo panicFracturaHallEscape($honor['rank']); ?></p><small>Hasta <?php echo panicFracturaHallDate($honor['untilUnix'],'d/m · H:i'); ?> ARG</small></div></article>
    <?php } ?></div><?php } ?>
</div>
<div class="fh-ranks" id="rangos-fractura">
  <div class="fh-subheading"><span class="eyebrow">EL JURAMENTO SE FORTALECE</span><h3>Seis rangos.<br>Tu propio camino.</h3><p>Tu rango de Fractura depende de la contribución personal que muestra B. No se reinicia con la competencia semanal. La distinción de laureado se obtiene aparte y es temporal.</p></div>
  <div class="fh-rank-table" role="region" aria-label="Rangos por contribución"><table><thead><tr><th scope="col">Desde</th><th scope="col">Nexo</th><th scope="col">Caos</th></tr></thead><tbody>
  <?php foreach(panicFracturaRanks() as $rank) { ?><tr><th scope="row"><?php echo panicFracturaNumber($rank['minimum']); ?></th><td><?php echo panicFracturaHallEscape($rank['nexo']); ?></td><td><?php echo panicFracturaHallEscape($rank['caos']); ?></td></tr><?php } ?>
  </tbody></table></div>
</div>
