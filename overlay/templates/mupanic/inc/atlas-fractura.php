<?php
if(!defined('access') || !access) die();
require_once __DIR__.'/fractura-ranks.php';
require_once __DIR__.'/fractura-week-data.php';
$fracturaReference=json_decode(file_get_contents(__DIR__.'/fractura-guide.json'),true);
$fracturaRewards=$fracturaReference['rewards'];$fracturaRulesLive=false;
try {
    $db=Connection::Database('MuOnline');
    if($db) {
        $rows=$db->query_fetch("SELECT Result AS result,Contribution AS contribution,Fragments AS fragments,Bless AS bless,Soul AS soul,Chaos AS chaos FROM [MuOnline43].[dbo].[MUPanicFracturaWeekRulesV34]");
        $fracturaRewards=panicFracturaWeekRules($rows);$fracturaRulesLive=true;
    }
} catch(Throwable $e) { /* Approved, dated reference is used if live configuration cannot be read. */ }
function panicFracturaGuideNumber($n) {return number_format((int)$n,0,',','.');}
// Arte nuevo de /fractura/ (img/fractura-v2/); si falta, queda la imagen anterior.
$afAldren=panicAtlasSiteArt('fractura-v2','fractura-aldren');$afVarkor=panicAtlasSiteArt('fractura-v2','fractura-varkor');$afGrieta=panicAtlasSiteArt('fractura-v2','fractura-grieta');
$afNexo=panicAtlasPick('fractura-v2','fractura-emblema-nexo','img/fractura/nexo-emblema-v32.svg');$afCaos=panicAtlasPick('fractura-v2','fractura-emblema-caos','img/fractura/caos-emblema-v32.svg');
$afSplit=$afAldren && $afVarkor;
$afBoss=panicAtlasSiteArt('fractura-v2','fractura-boss');$afInsNexo=panicAtlasSiteArt('fractura-v2','fractura-insignia-nexo');$afInsCaos=panicAtlasSiteArt('fractura-v2','fractura-insignia-caos');
?>
<link rel="stylesheet" href="<?php echo __PATH_TEMPLATE__; ?>css/atlas-fractura.css?v=<?php echo substr(hash_file('sha256',__DIR__.'/../css/atlas-fractura.css'),0,12); ?>">
<section id="fractura" class="wiki-section atlas-fractura-guide" aria-labelledby="atlas-fractura-title" data-wiki-search>
  <header class="af-guide-hero<?php echo $afSplit?' is-v3':''; ?>"><?php if($afSplit) { ?><div class="af-v3-stage" role="img" aria-label="Aldren, Guardián de Nexo, frente a Varkor, Heraldo de Caos"><i class="af-v3-nexo" aria-hidden="true"></i><i class="af-v3-caos" aria-hidden="true"></i><span class="af-v3-word af-v3-word--nexo" aria-hidden="true">NEXO</span><span class="af-v3-word af-v3-word--caos" aria-hidden="true">CAOS</span><?php if($afGrieta) { ?><img class="af-v3-grieta" src="<?php echo panicAtlasE($afGrieta); ?>" alt="" width="266" height="1300" loading="lazy" decoding="async"><?php } ?><img class="af-v3-fig af-v3-fig--aldren" src="<?php echo panicAtlasE($afAldren); ?>" alt="" width="1021" height="1300" loading="lazy" decoding="async"><img class="af-v3-fig af-v3-fig--varkor" src="<?php echo panicAtlasE($afVarkor); ?>" alt="" width="990" height="1300" loading="lazy" decoding="async"></div><?php } else { ?><img src="<?php echo __PATH_TEMPLATE__; ?>img/fractura/salon-vencedores-v34.webp" alt="Salón ceremonial de Nexo y Caos" width="1536" height="1024" loading="lazy"><?php } ?><div><span class="eyebrow">ATLAS / SISTEMAS DEL CONTINENTE</span><h2 id="atlas-fractura-title">La Fractura.</h2><p>Cómo elegir un bando, sumar contribución, disputar Vulcanus y cobrar la recompensa semanal.</p></div></header>
  <nav class="af-guide-index" aria-label="Guía de Fractura"><a href="#fractura-bandos">Bandos</a><a href="#fractura-boss">La conquista</a><a href="#fractura-premios">Premios</a><a href="#fractura-cobro">Cómo cobrar</a><a href="#fractura-rangos">Rangos</a><a href="#fractura-distincion">Distinción</a></nav>
  <div class="afx-band afx-band--bone" id="fractura-bandos">
    <header class="afx-head"><span class="afx-num" aria-hidden="true">01</span><div><span class="eyebrow">Tu juramento</span><h3>Nexo o Caos.</h3></div></header>
    <div class="afx-oath">
      <div class="afx-tile afx-tile--ink"><strong>NIVEL 50</strong><span>Desde ahí podés elegir tu bando hablando con su representante.</span></div>
      <article class="afx-side afx-side--nexo"><img src="<?php echo panicAtlasE($afNexo); ?>" alt="" width="96" height="96" loading="lazy"><div><h4>Nexo</h4><b>Aldren</b><p>«Mantener unido el mundo».</p></div></article>
      <article class="afx-side afx-side--caos"><img src="<?php echo panicAtlasE($afCaos); ?>" alt="" width="96" height="96" loading="lazy"><div><h4>Caos</h4><b>Varkor</b><p>«Romper los límites del poder».</p></div></article>
    </div>
    <p class="afx-note"><b class="afx-bang" aria-hidden="true">!</b><span><strong>Sin bando no hay Vulcanus.</strong> Para ingresar a Vulcanus necesitás pertenecer a uno.</span></p>
  </div>
  <div class="afx-band" id="fractura-boss">
    <header class="afx-head"><span class="afx-num" aria-hidden="true">02</span><div><span class="eyebrow">Dos marcadores diferentes</span><h3>La conquista de Vulcanus.</h3></div></header>
    <div class="afx-boss<?php echo $afBoss?' has-art':''; ?>">
      <div class="afx-boss-copy"><p class="afx-lead">Así funciona la invasión del <strong>Señor de la Fractura</strong>.</p>
        <div class="afx-stats afx-stats--3"><div class="afx-tile afx-tile--red"><strong>22:30</strong><span>Aparece, hora Argentina</span></div><div class="afx-tile"><strong>30 MIN</strong><span>Dura la invasión, como máximo</span></div><div class="afx-tile"><strong>NO REGENERA</strong><span>El boss no regenera durante esa aparición</span></div></div>
      </div>
      <?php if($afBoss) { ?><div class="afx-boss-art" aria-hidden="true"><i></i><img src="<?php echo panicAtlasE($afBoss); ?>" alt="" width="1139" height="1300" loading="lazy" decoding="async"></div><?php } ?>
    </div>
    <h4 class="afx-sub">Lo que da el último golpe</h4>
    <div class="afx-stats afx-stats--2">
      <article class="afx-tile afx-tile--big afx-tile--lime"><span>Marcador semanal del bando</span><strong>1000</strong><p>Por cada derrota oficial del boss. Los recibe el bando del personaje que da el <b>último golpe</b>.</p></article>
      <article class="afx-tile afx-tile--big"><span>Contribución personal del vencedor</span><strong>500</strong><p>Para el personaje que da ese último golpe. <b>Reconectá</b> para cargar el crédito individual del boss.</p></article>
    </div>
    <h4 class="afx-sub">¿Qué suma al marcador de tu bando?</h4>
    <ul class="afx-counts">
      <li class="is-yes"><b>Suma</b><div><strong>Boss oficial</strong><p>1000 puntos por cada derrota oficial.</p></div></li>
      <li class="is-yes"><b>Suma</b><div><strong>PvP válido en Vulcanus</strong><p>Al derrotar a un personaje del bando contrario, tu bando recibe la misma contribución que otorgue esa baja: si ganás 15 puntos personales, suma 15 a tu bando. No se entrega un segundo premio personal ni se restan puntos semanales al bando de la víctima.</p></div></li>
      <li class="is-no"><b>No suma</b><div><strong>Misiones diarias</strong><p>Sólo aportan contribución personal y sirven como requisito para cobrar.</p></div></li>
      <li class="is-no"><b>No suma</b><div><strong>Pruebas manuales del boss</strong><p>No cuentan como victorias oficiales.</p></div></li>
    </ul>
    <div class="afx-warn"><strong class="afx-warn-num">2</strong><div><span class="afx-warn-tag">Ojo · misma IP</span><p>Entre personajes de la <strong>misma IP</strong> cuentan como máximo las <strong>primeras dos bajas premiadas por día Argentina</strong>.</p><ul><li>El cupo es compartido entre cuentas, personajes y ambos sentidos de la pelea.</li><li>Las bajas bloqueadas no suman al personaje ni al bando.</li><li>Las IP distintas conservan la ganancia nativa y no consumen ese cupo.</li><li>El registro semanal de PvP cuenta desde su activación; las bajas anteriores no se reconstruyen.</li></ul></div></div>
    <h4 class="afx-sub">El cierre de la semana</h4>
    <div class="afx-stats afx-stats--3">
      <div class="afx-tile afx-tile--bone"><strong>DOM 23:15</strong><span>Cierre, hora Argentina</span></div>
      <div class="afx-tile"><strong>LUN 00:00</strong><span>Comienza la nueva semana</span></div>
      <div class="afx-tile"><strong>2 MIN</strong><span>Lo máximo que puede esperar el cierre a los guardados recientes antes de publicar el resultado</span></div>
    </div>
    <p class="afx-text">Gana quien tenga <strong>más puntos de boss oficial y PvP válido</strong>; si tienen los mismos, hay empate. Consultá el marcador semanal con Aldren o Varkor y en las pantallas de Fractura.</p>
    <h4 class="afx-sub">Lo que deja el boss</h4>
    <div class="afx-stats afx-stats--3">
      <div class="afx-tile afx-tile--loot"><strong>2</strong><span>Fragmentos de vínculo</span></div>
      <div class="afx-tile afx-tile--loot"><strong>1</strong><span>Joya aleatoria entre Bless, Soul, Chaos y Life</span></div>
      <div class="afx-tile afx-tile--loot afx-tile--red"><strong>25%</strong><span>Probabilidad de dejar además un núcleo de evolución</span></div>
    </div>
  </div>
  <div class="afx-band afx-band--bone" id="fractura-premios">
    <header class="afx-head"><span class="afx-num" aria-hidden="true">03</span><div><span class="eyebrow">Requisitos y recompensas</span><h3>Prepará tu cobro semanal.</h3></div></header>
    <div class="afx-stats afx-stats--2">
      <div class="afx-tile afx-tile--ink afx-tile--req"><strong>2 MISIONES</strong><p>El personaje que vaya a cobrar debe haber <b>completado y cobrado al menos dos misiones diarias</b> durante esa semana, antes del cierre. La misma misión cobrada en dos fechas distintas también puede cumplir el requisito.</p></div>
      <div class="afx-tile afx-tile--ink afx-tile--req"><strong>1 PREMIO</strong><p>Se entrega <b>un premio por cuenta y por semana</b>.</p></div>
    </div>
    <p class="afx-note"><b class="afx-bang" aria-hidden="true">!</b><span><strong>Ojo:</strong> no alcanza con pertenecer al bando vencedor. El personaje elegido también debe cumplir sus misiones.</span></p>
    <div class="afx-rewards"><?php foreach(['W'=>'Bando ganador','T'=>'Empate','L'=>'Bando perdedor'] as $key=>$name) { $r=$fracturaRewards[$key]; ?><article class="afx-reward afx-reward--<?php echo strtolower($key); ?>"><h4><?php echo $name; ?></h4><strong><?php echo panicFracturaGuideNumber($r['contribution']); ?></strong><small>contribución personal</small><ul><?php foreach(['fragments'=>'fragmentos de vínculo','bless'=>'Bless','soul'=>'Soul','chaos'=>'Chaos'] as $field=>$label) { if((int)$r[$field]===0)continue;if($field==='fragments' && (int)$r[$field]===1)$label='fragmento de vínculo'; ?><li><b><?php echo panicFracturaGuideNumber($r[$field]); ?></b> <?php echo htmlspecialchars($label,ENT_QUOTES,'UTF-8'); ?></li><?php } ?></ul></article><?php } ?></div>
    <p class="afx-small"><?php echo $fracturaRulesLive?'Paquetes configurados para los próximos cierres.':'Premios de referencia · revisión 14/10/2026.'; ?> Cada semana cerrada conserva el paquete que tenía al momento del cierre. No hay núcleos semanales fijos.</p>
  </div>
  <div class="afx-band" id="fractura-cobro">
    <header class="afx-head"><span class="afx-num" aria-hidden="true">04</span><div><span class="eyebrow">Del NPC al baúl</span><h3>Cómo recibir tu premio.</h3></div></header>
    <ol class="afx-steps">
      <li><span class="afx-step-n" aria-hidden="true">1</span><span class="afx-chip">Aldren o Varkor</span><strong>Hablá con Aldren o Varkor después del cierre.</strong><p>Usá el personaje que cumplió las misiones. El primero elegible que reclame fija la recompensa de la cuenta; otro personaje no puede cambiar esa elección.</p></li>
      <li><span class="afx-step-n" aria-hidden="true">2</span><span class="afx-chip">3 min afuera</span><strong>Dejá toda la cuenta desconectada unos 3 minutos.</strong><p>El sistema entrega los puntos y el paquete completo cuando la cuenta está desconectada.</p></li>
      <li><span class="afx-step-n" aria-hidden="true">3</span><span class="afx-chip">F8 · 20 días</span><strong>Volvé a entrar y abrí F8.</strong><p>La contribución estará acreditada. Los objetos se retiran del baúl de recompensas con <kbd>F8</kbd>, fuera del chat. Tenés 20 días para retirarlos del baúl.</p></li>
    </ol>
    <p class="afx-note afx-note--dark"><b class="afx-bang" aria-hidden="true">!</b><span><strong>¿Te falta espacio?</strong> Queda pendiente el paquete completo. Retirá objetos con F8 y dejá la cuenta desconectada nuevamente. Los premios anteriores no se borran para hacer lugar.</span></p>
  </div>
  <div class="afx-band afx-band--bone" id="fractura-rangos">
    <header class="afx-head"><span class="afx-num" aria-hidden="true">05</span><div><span class="eyebrow">Tu contribución personal</span><h3>Los seis rangos de Fractura.</h3></div></header>
    <ul class="afx-facts"><li>Consultá tu rango y el próximo umbral en la ventana <kbd>B</kbd>.</li><li>Se calculan con tu <strong>contribución personal actual</strong>.</li><li><strong>No se reinician</strong> con el cierre semanal.</li></ul>
    <div class="afx-ranks"><table><thead><tr><th scope="col">Desde</th><th scope="col" class="afx-th-nexo">Nexo</th><th scope="col" class="afx-th-caos">Caos</th></tr></thead><tbody><?php foreach(panicFracturaRanks() as $rank) { ?><tr><th scope="row"><?php echo panicFracturaGuideNumber($rank['minimum']); ?></th><td><?php echo htmlspecialchars($rank['nexo'],ENT_QUOTES,'UTF-8'); ?></td><td><?php echo htmlspecialchars($rank['caos'],ENT_QUOTES,'UTF-8'); ?></td></tr><?php } ?></tbody></table></div>
  </div>
  <div class="afx-band" id="fractura-distincion">
    <header class="afx-head"><span class="afx-num" aria-hidden="true">06</span><div><span class="eyebrow">El honor de la semana</span><h3>Laureado del Nexo / Laureado de Caos.</h3></div></header>
    <div class="afx-laurels">
      <div class="afx-laurel afx-laurel--nexo"><?php if($afInsNexo) { ?><img src="<?php echo panicAtlasE($afInsNexo); ?>" alt="" width="128" height="128" loading="lazy"><?php } ?><strong>Laureado del Nexo</strong></div>
      <div class="afx-laurel afx-laurel--caos"><?php if($afInsCaos) { ?><img src="<?php echo panicAtlasE($afInsCaos); ?>" alt="" width="128" height="128" loading="lazy"><?php } ?><strong>Laureado de Caos</strong></div>
      <div class="afx-tile afx-tile--bone afx-laurel-time"><strong>7 DÍAS</strong><span>Vence <b>siete días después del cierre</b>; cobrar más tarde no reinicia ese plazo.</span></div>
    </div>
    <ul class="afx-facts afx-facts--dark"><li>Aparece al <strong>cobrar un premio del bando ganador</strong>.</li><li>Se muestra debajo del rango en <kbd>B</kbd> y entre los laureados del Salón de la Fama.</li><li>Es una <strong>distinción visual</strong>: no agrega estadísticas ni bonos de combate.</li></ul>
    <p class="afx-text">Las pantallas principales muestran la competencia semanal en curso. El Salón conserva los ganadores y resultados de las semanas cerradas, junto con sus laureados vigentes.</p>
  </div>
  <a class="af-return" href="<?php echo __BASE_URL__; ?>fractura/#salon-de-la-fama">Entrar al Salón de la Fama <span aria-hidden="true">↗︎</span></a>
</section>
