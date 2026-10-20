<?php
// Matching real client windows for every F11 tab; Master is a separate A-key interface.
$hubTabs = [
 ['inicio','Inicio','Tu centro de control.','El próximo evento, el pulso de la Fractura y los accesos a los sistemas propios. Abrís F11 y encontrás lo que necesitás.', ['Personaje y recompensas','Agenda e invasiones','Mercado, tienda y juegos']],
 ['personaje','Personaje','Todo lo que hacés te hace avanzar.','Tu progreso y tus recompensas tienen su lugar en el Hub.', ['Información de tu personaje','Progreso y recompensas','Panel de tu personaje']],
 ['fractura','La Fractura','Elegí tu bando. Hacé historia.','Nexo y Caos se disputan el continente. Seguí el estado de la guerra y encontrá el acceso a la Fractura desde el Hub.', ['Nexo contra Caos','Guerra de bandos','Tu lugar en la historia']],
 ['eventos','Eventos','Siempre hay una próxima batalla.','Consultá la agenda y las invasiones desde el cliente. Estos son los próximos eventos del servidor.', []],
 ['mercado','Mercado','Comprá. Publicá. Cobrá.','Vessara conecta a los jugadores. Buscá equipo, publicá lo que conseguís y cobrá tus ventas en Zen o Eryns.', ['Comprar y buscar objetos','Gestionar tus publicaciones','Cobrar las ventas']],
 ['tienda','Tienda','Eryns. La moneda de MU PANIC.','El Hub también tiene una tienda. Entrá desde F11 para conocer las propuestas del servidor y comprar con Eryns.', ['Tienda dentro del cliente','Eryns como moneda propia','VIP MU PANIC con Eryns']],
 ['juegos','Juegos','Otra partida dentro de tu partida.','Ocho minijuegos dentro del cliente. Cambiá de ritmo, elegí cómo jugar y volvé a la aventura cuando quieras.', ['Ruleta, Tragamonedas y cartas','Vuelo del Dragón y Torre del Caos','Lluvia de Joyas, Kalima y Carrera']],
 ['ayuda','Ayuda','Encontrá tu próximo paso.','Los sistemas propios también necesitan una guía. Usá Ayuda en el Hub y profundizá en el Atlas de la web.', ['Cómo empezar y usar el servidor','Mapas, eventos y sistemas','Guías para seguir avanzando']],
];
$hubShots = ['inicio'=>'hub-real-v4.webp'];
foreach (['personaje','fractura','eventos','mercado','tienda','juegos','ayuda'] as $hubId) {
 $hubShots[$hubId]='hub-'.$hubId.'-real-v10.webp';
}
$homeGames = [
 ['ruleta','Ruleta','Elegí tu jugada y seguí la rueda. Jugás con Eryns desde el cliente.'],
 ['tragamonedas','Tragamonedas','Hacé girar los tres rodillos y buscá una nueva combinación.'],
 ['mayor-menor','Mayor o Menor','La próxima carta cambia la partida. ¿Arriesgás una más?'],
 ['dragon','Vuelo del Dragón','La tensión sube con el vuelo. Decidí cuándo cobrar antes de que caiga.'],
 ['joyas','Lluvia de Joyas','Seguí la caída de las joyas y descubrí dónde terminan.'],
 ['kalima','Tesoro de Kalima','El tesoro te tienta. Decidí hasta dónde llegar antes de cobrar.'],
 ['torre','Torre del Caos','Cada piso es una decisión. ¿Subís uno más o cobrás?'],
 ['carrera','Carrera','Elegí tu monstruo y seguí la carrera hasta la llegada.'],
];
?>
<section class="ph-workshop ph-client" id="hub" aria-labelledby="home-client-title">
 <div class="ph-client-heading ph-wrap">
  <div><p class="ph-eyebrow">UN CLIENTE CON IDENTIDAD PROPIA</p><h2 id="home-client-title">Todo MU PANIC,<br>a una tecla.</h2><p>Un Hub propio para tu personaje, la Fractura, eventos, mercado, tienda y juegos. Abrilo con F11 y tené todo a mano.</p></div>
  <div class="ph-client-key"><?php $homePicture('client-crafted', 'La tecla F11 de acceso al Hub de MU PANIC'); ?></div>
 </div>
 <div class="ph-client-tour ph-wrap ph-interactive-tour" data-home-tabs data-hub-tour>
  <div class="ph-hub-toolbar"><p>Tocá los botones del Hub y explorá sus pantallas reales.</p><button type="button" data-hub-enlarge>Ampliar Hub <span aria-hidden="true">↗</span></button></div>
  <div class="ph-hub-stage" data-hub-stage>
   <div class="ph-hub-simulator" data-hub-simulator>
    <div class="ph-client-window ph-hub-shell" aria-hidden="true">
     <img class="ph-hub-capture" src="<?php echo $homeArt.$hubShots['personaje']; ?>" alt="" width="1997" height="1248" loading="eager">
     <span class="ph-client-name">Panic</span>
    </div>
    <nav class="ph-hub-native-menu" aria-label="Botones del Hub de MU PANIC">
     <?php foreach($hubTabs as $i=>$tab) { $id=$tab[0]; $idle=$id==='ayuda'?'personaje':'ayuda'; $active=$id==='mercado'?$idle:$id;
      $menuTop=-(286+89*$i)/71*100;
     ?>
     <a href="#hub-<?php echo $id; ?>" data-world-tab="hub-<?php echo $id; ?>" class="ph-hub-native-button<?php echo $id==='mercado'?' ph-hub-market-button':''; ?>" style="--menu-y:<?php echo number_format(22.1596+$i*8.3568,4,'.',''); ?>%;--menu-image-y:<?php echo number_format($menuTop,4,'.',''); ?>%">
      <img class="ph-hub-menu-idle" src="<?php echo $homeArt.$hubShots[$idle]; ?>" alt="" aria-hidden="true" width="1997" height="1248" loading="eager">
      <img class="ph-hub-menu-active<?php echo $id==='inicio'?' ph-hub-menu-inicio':''; ?>" src="<?php echo $homeArt.$hubShots[$active]; ?>" alt="" aria-hidden="true" width="<?php echo $id==='inicio'?1440:1997; ?>" height="<?php echo $id==='inicio'?877:1248; ?>" loading="eager">
      <span class="ph-sr-only"><?php echo $tab[1]; ?></span>
     </a>
     <?php } ?>
    </nav>
    <div class="ph-world-panels ph-hub-screen-stack">
     <?php foreach($hubTabs as $tab) { $id=$tab[0]; ?>
     <div id="hub-<?php echo $id; ?>" class="ph-hub-panel" data-world-panel<?php echo $id!=='inicio'?' hidden':''; ?>>
      <div class="ph-hub-content-viewport ph-hub-content-<?php echo $id; ?>">
       <?php if($id==='mercado') { ?>
        <div class="ph-client-window ph-client-window-mercado"><img class="ph-hub-capture" src="<?php echo $homeArt.$hubShots[$id]; ?>" alt="Mercado de Vessara: ventana real del cliente" width="1997" height="1248" loading="eager"></div>
       <?php } else { ?>
        <div class="ph-hub-content-crop<?php echo $id==='inicio'?' ph-hub-content-home':''; ?>">
         <img class="ph-hub-capture" src="<?php echo $homeArt.$hubShots[$id]; ?>" alt="<?php echo $homeEscape('Pantalla real de '.$tab[1].' del cliente MU PANIC'); ?>" width="<?php echo $id==='inicio'?1440:1997; ?>" height="<?php echo $id==='inicio'?877:1248; ?>" loading="eager" decoding="async">
         <?php if($id==='personaje') { ?><span class="ph-hub-character-name" aria-hidden="true">Panic</span><?php } ?>
        </div>
       <?php } ?>
      </div>
     </div>
     <?php } ?>
    </div>
   </div>
  </div>
  <div class="ph-hub-caption-stack" aria-live="polite">
   <?php foreach($hubTabs as $tab) { $id=$tab[0]; ?>
   <div class="ph-hub-description" data-hub-caption="hub-<?php echo $id; ?>"<?php echo $id!=='inicio'?' hidden':''; ?>>
    <p class="ph-eyebrow">F11 · <?php echo $tab[1]; ?></p><h3<?php echo $id==='eventos'?' id="home-events-title"':''; ?>><?php echo $tab[2]; ?></h3><p><?php echo $tab[3]; ?></p>
    <?php if($id==='eventos') { ?>
     <details class="ph-hub-calendar"><summary>Próximos eventos del servidor</summary><ol class="ph-event-list" data-home-events><?php foreach($homeEvents as $event) { ?><li><a href="<?php echo __BASE_URL__; ?>info/#eventos"><span><?php echo $homeEscape($event['nombre']); ?></span><time datetime="<?php echo $homeEscape($event['proximo']); ?>"><?php echo $homeEscape(panicHomeEventLabel($event['proximo'])); ?></time><b aria-hidden="true">↗</b></a></li><?php } ?></ol></details>
     <?php if(!$homeEvents) { ?><p data-home-events-empty>Consultá el calendario del servidor.</p><?php } ?>
    <?php } ?>
    <?php $targets=['fractura'=>['fractura/','Conocé la Fractura'],'eventos'=>['info/#eventos','Ver calendario'],'mercado'=>['market/','Explorá el mercado'],'tienda'=>['usercp/recharge/','Conocé la tienda'],'juegos'=>['#minijuegos','Descubrí los minijuegos'],'ayuda'=>['info/','Abrí el Atlas']];if(isset($targets[$id])) { $target=$targets[$id]; ?><a class="ph-link" href="<?php echo ($target[0][0]==='#'?'':__BASE_URL__).$target[0]; ?>"><?php echo $target[1]; ?> <span aria-hidden="true">↗</span></a><?php } ?>
   </div>
   <?php } ?>
  </div>
 </div>

</section>
<?php $homeJoin('market'); ?>
<section class="ph-scene ph-market-world" data-scene-fx="market" aria-labelledby="home-market-title">
 <?php $homePicture('market-world', 'Vessara y los jugadores comerciando equipo en el mercado de Loren Market', false, 'v4'); ?>
 <div class="ph-scene-content ph-wrap"><div class="ph-copy"><p class="ph-eyebrow">EL MERCADO DE LOS JUGADORES</p><h2 id="home-market-title">Vessara.<br>Lo que encontrás<br>tiene valor.</h2><p>Buscá el equipo que te falta. Publicá lo que conseguís jugando. Comprá, vendé y cobrá tus ventas en <strong>Zen o Eryns</strong>.</p><p class="ph-market-access">Entrá desde el Hub F11 o visitá el mercado de Loren Market.</p><a class="ph-link" href="<?php echo __BASE_URL__; ?>market/">Explorá las publicaciones <span aria-hidden="true">↗</span></a></div></div>
</section>
<?php $homeJoin('arcade'); ?>
<section class="ph-games" id="minijuegos" aria-labelledby="home-games-title">
 <div class="ph-scene ph-games-scene" data-scene-fx="casino">
  <?php $homePicture('games-hall', 'La sala de minijuegos de MU PANIC con la ruleta integrada, tragamonedas y cartas', false, 'v5'); ?>
  <div class="ph-scene-content ph-wrap"><div class="ph-copy"><p class="ph-eyebrow">OCHO MINIJUEGOS · DENTRO DE MU PANIC</p><h2 id="home-games-title">Tu próxima partida<br>no necesita un spot.</h2><p>Hacé una pausa del leveo.<br>Hay mucho más para jugar en el Hub F11.</p><a href="#elegi-minijuego" class="ph-link">Encontrá tu próxima partida <span aria-hidden="true">↓</span></a></div></div>
 </div>
 <div class="ph-games-tour ph-wrap" id="elegi-minijuego" data-home-tabs><p class="ph-eyebrow">ELEGÍ CÓMO SEGUIR</p><nav class="ph-world-tabs" aria-label="Los ocho minijuegos"><?php foreach($homeGames as $game) { ?><a href="#juego-<?php echo $game[0]; ?>" data-world-tab="juego-<?php echo $game[0]; ?>"><?php echo $game[1]; ?></a><?php } ?></nav><div class="ph-world-panels"><?php foreach($homeGames as $game) { ?><div id="juego-<?php echo $game[0]; ?>" data-world-panel><h3><?php echo $game[1]; ?></h3><p><?php echo $game[2]; ?></p></div><?php } ?></div><p class="ph-games-access"><kbd>F11</kbd><span>Juegos</span><b aria-hidden="true">↗</b>Abrí el Hub dentro del juego.</p></div>
</section>

<dialog class="ph-hub-lightbox" data-hub-lightbox aria-label="Vista ampliada de la interfaz del cliente"><button type="button" class="ph-hub-lightbox-close" aria-label="Cerrar vista ampliada">×</button><div data-hub-lightbox-content></div></dialog>
