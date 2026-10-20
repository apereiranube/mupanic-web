<?php
// A consistent tour using the real Hub, Master and game selector captures.
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
$hubShots = ['inicio'=>'hub-real-v4.webp','juegos'=>'hub-games-real-v4.webp'];
foreach (['personaje','fractura','eventos','mercado','tienda','ayuda'] as $hubId) {
 if(is_file(__DIR__.'/../img/home/hub-'.$hubId.'-real-v10.webp')) $hubShots[$hubId]='hub-'.$hubId.'-real-v10.webp';
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
 <div class="ph-client-tour ph-wrap" data-home-tabs>
  <nav class="ph-world-tabs" aria-label="Explorá las funciones del Hub">
   <?php foreach($hubTabs as $tab) { ?><a href="#hub-<?php echo $tab[0]; ?>" data-world-tab="hub-<?php echo $tab[0]; ?>"><?php echo $tab[1]; ?></a><?php } ?>
  </nav>
  <div class="ph-world-panels">
   <?php foreach($hubTabs as $tab) { $id=$tab[0]; ?>
   <div id="hub-<?php echo $id; ?>" class="ph-hub-panel<?php echo isset($hubShots[$id])?'':' ph-hub-no-capture'; ?>" data-world-panel>
    <?php if(isset($hubShots[$id])) { $shot=$hubShots[$id]; $shotSize=getimagesize(__DIR__.'/../img/home/'.$shot); ?>
    <div class="ph-hub-visual"><figure class="ph-hub-real-preview"><img class="ph-hub-capture" src="<?php echo $homeArt.$shot; ?>" alt="<?php echo $homeEscape('Ventana real de '.$tab[1].' en MU PANIC'); ?>" width="<?php echo (int)$shotSize[0]; ?>" height="<?php echo (int)$shotSize[1]; ?>" loading="lazy" decoding="async"><figcaption><?php echo $homeEscape($tab[1]); ?> · INTERFAZ REAL DEL CLIENTE</figcaption></figure></div>
    <?php } ?>
    <div class="ph-hub-description">
     <p class="ph-eyebrow">F11 · <?php echo $tab[1]; ?></p><h3<?php echo $id==='eventos'?' id="home-events-title"':''; ?>><?php echo $tab[2]; ?></h3><p><?php echo $tab[3]; ?></p>
     <?php if($tab[4]) { ?><ul><?php foreach($tab[4] as $point) { ?><li><?php echo $point; ?></li><?php } ?></ul><?php } ?>
     <?php if($id==='eventos') { ?>
      <ol class="ph-event-list" data-home-events><?php foreach($homeEvents as $event) { ?><li><a href="<?php echo __BASE_URL__; ?>info/#eventos"><span><?php echo $homeEscape($event['nombre']); ?></span><time datetime="<?php echo $homeEscape($event['proximo']); ?>"><?php echo $homeEscape(panicHomeEventLabel($event['proximo'])); ?></time><b aria-hidden="true">↗</b></a></li><?php } ?></ol>
      <?php if(!$homeEvents) { ?><p data-home-events-empty>Consultá el calendario del servidor.</p><?php } ?>
     <?php } ?>
     <?php $targets=['fractura'=>['fractura/','Conocé la Fractura'],'eventos'=>['info/#eventos','Ver calendario'],'mercado'=>['market/','Explorá el mercado'],'tienda'=>['usercp/recharge/','Conocé la tienda'],'juegos'=>['#minijuegos','Descubrí los minijuegos'],'ayuda'=>['info/','Abrí el Atlas']];if(isset($targets[$id])) { $target=$targets[$id]; ?><a class="ph-link" href="<?php echo ($target[0][0]==='#'?'':__BASE_URL__).$target[0]; ?>"><?php echo $target[1]; ?> <span aria-hidden="true">↗</span></a><?php } ?>
    </div>
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
