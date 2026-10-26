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
$homeJourney = function($part,$alt) use ($homeArt,$homeEscape) { ?>
 <div class="ph-art ph-journey-art ph-journey-part-<?php echo $part; ?>"><img src="<?php echo $homeArt; ?>journey-world-v12.webp" alt="<?php echo $homeEscape($alt); ?>" width="1024" height="1536" loading="lazy" decoding="async"><div class="ph-journey-detail" aria-hidden="true"></div></div>
<?php };
$hubFeatures = [
 'personaje'=>['Pase de batalla','Logros','Ranking','Recompensa diaria','Cuenta VIP','Buscar party','Banco de joyas','Recompensas F8'],
 'mercado'=>['Comprar','Publicaciones','Ventas','Publicar objeto','Cobrar ventas'],
 'tienda'=>['Tienda con Eryns','VIP MU PANIC'],
 'ayuda'=>['Cómo empezar','Mapas y spots','Eventos','Sistemas propios'],
];
?>
<div class="ph-lower-journey">
<section class="ph-scene ph-workshop ph-client ph-client-v12" id="hub" aria-labelledby="home-client-title">
 <?php $homeJourney(0,'Un taller entre las arcadas del bosque, que continúa hacia el mercado de Vessara'); ?>
 <div class="ph-world-layout ph-wrap">
  <div class="ph-client-intro"><p class="ph-eyebrow">UN CLIENTE CON IDENTIDAD PROPIA</p><h2 id="home-client-title">Todo MU PANIC,<br>a una tecla.</h2><p>Tu personaje, la Fractura, eventos y mucho más. Abrí F11 y tené todo a mano.</p><div class="ph-client-key"><?php $homePicture('client-crafted','La tecla F11 de acceso al Hub de MU PANIC'); ?></div></div>
  <div class="ph-client-tour ph-hub-v12" data-home-tabs data-hub-tour>
   <div class="ph-hub-stage" data-hub-stage>
    <div class="ph-hub-simulator ph-hub-desk" data-hub-simulator>
     <img class="ph-hub-desk-art" src="<?php echo $homeArt; ?>hub-desk-v12.webp" alt="Marco ilustrado del Hub, apoyado en un escritorio de piedra" width="1536" height="1024" loading="lazy" decoding="async">
     <div class="ph-hub-ui">
      <nav class="ph-hub-menu-v12" aria-label="Explorá el Hub de MU PANIC"><?php foreach($hubTabs as $tab) { ?><a href="#hub-<?php echo $tab[0]; ?>" data-world-tab="hub-<?php echo $tab[0]; ?>"><?php echo $tab[1]; ?><span aria-hidden="true">›</span></a><?php } ?></nav>
      <div class="ph-world-panels ph-hub-pages-v12">
       <?php foreach($hubTabs as $tab) { $id=$tab[0]; ?>
       <div id="hub-<?php echo $id; ?>" class="ph-hub-page" data-world-panel<?php echo $id!=='inicio'?' hidden':''; ?>>
        <p class="ph-hub-section-label"><?php echo $id==='inicio'?'MU PANIC · F11':$tab[1]; ?></p>
        <h3<?php echo $id==='eventos'?' id="home-events-title"':''; ?>><?php echo $id==='inicio'?'Tu aventura sigue acá.':$tab[2]; ?></h3>
        <?php if($id==='inicio') { ?><p>Un solo lugar para seguir jugando.</p><div class="ph-hub-actions"><button type="button" data-hub-go="hub-personaje">Tu personaje</button><button type="button" data-hub-go="hub-fractura">Nexo y Caos</button><button type="button" data-hub-go="hub-eventos">Próximos eventos</button><button type="button" data-hub-go="hub-mercado">Mercado de Vessara</button><button type="button" data-hub-go="hub-juegos">Minijuegos</button></div>
        <?php } elseif($id==='eventos') { ?>
         <ol class="ph-event-list" data-home-events><?php foreach($homeEvents as $event) { ?><li><a href="<?php echo __BASE_URL__; ?>info/#eventos"><span><?php echo $homeEscape($event['nombre']); ?></span><time datetime="<?php echo $homeEscape($event['proximo']); ?>"><?php echo $homeEscape(panicHomeEventLabel($event['proximo'])); ?></time><b aria-hidden="true">↗︎</b></a></li><?php } ?></ol>
         <?php if(!$homeEvents) { ?><p data-home-events-empty>Consultá la agenda del servidor.</p><?php } ?>
        <?php } elseif($id==='fractura') { ?><p>Nexo y Caos. Dos bandos, una guerra propia.</p><div class="ph-hub-actions ph-hub-faction-actions"><button type="button" data-hub-detail="Aldren te espera para conocer el Nexo. La guerra se disputa en Vulcanus.">Nexo</button><button type="button" data-hub-detail="Varkor te espera para conocer el Caos. La guerra se disputa en Vulcanus.">Caos</button></div><a class="ph-hub-explore" href="<?php echo __BASE_URL__; ?>fractura/">Conocé la Fractura ↗︎</a>
        <?php } elseif($id==='juegos') { ?><p>Hacé una pausa del leveo.</p><div class="ph-hub-actions ph-hub-games-actions"><?php foreach($homeGames as $game) { ?><button type="button" data-hub-detail="<?php echo $homeEscape($game[2]); ?>"><?php echo $game[1]; ?></button><?php } ?></div>
        <?php } else { ?>
         <p><?php echo $id==='personaje'?'Tu progreso y tus recompensas, a mano.':($id==='mercado'?'Comprá, publicá y cobrá en Zen o Eryns.':($id==='tienda'?'La moneda propia de MU PANIC, dentro del cliente.':'Encontrá tu próximo paso.')); ?></p>
         <div class="ph-hub-actions"><?php foreach($hubFeatures[$id] as $feature) { ?><button type="button" data-hub-detail="<?php echo $homeEscape($feature.' está disponible desde el Hub F11 dentro del juego.'); ?>"><?php echo $homeEscape($feature); ?></button><?php } ?></div>
         <?php $targets=['mercado'=>['market/','Explorá el mercado'],'tienda'=>['usercp/recharge/','Conocé la tienda'],'ayuda'=>['info/','Abrí el Atlas']];if(isset($targets[$id])) { ?><a class="ph-hub-explore" href="<?php echo __BASE_URL__.$targets[$id][0]; ?>"><?php echo $targets[$id][1]; ?> ↗︎</a><?php } ?>
        <?php } ?>
        <p class="ph-hub-detail" data-hub-detail-output aria-live="polite"></p>
       </div>
       <?php } ?>
      </div>
     </div>
    </div>
   </div>
   <div class="ph-hub-toolbar"><p>Recorré el Hub.</p><button type="button" data-hub-enlarge>Exploralo de cerca <span aria-hidden="true">↗︎</span></button></div>
  </div>
 </div>
</section>
<section class="ph-scene ph-market-world ph-journey-market" aria-labelledby="home-market-title">
 <?php $homeJourney(1,'Vessara en su puesto de equipo, bajo los toldos del mercado que continúan desde las arcadas del Hub'); ?>
 <div class="ph-scene-content ph-wrap"><div class="ph-copy"><p class="ph-eyebrow">EL MERCADO DE LOS JUGADORES</p><h2 id="home-market-title">Vessara.<br>Lo que encontrás<br>tiene valor.</h2><p>Buscá el equipo que te falta. Publicá lo que conseguís jugando. Comprá, vendé y cobrá tus ventas en <strong>Zen o Eryns</strong>.</p><p class="ph-market-access">Desde el Hub F11 o en Loren Market.</p><a class="ph-link" href="<?php echo __BASE_URL__; ?>market/">Explorá las publicaciones <span aria-hidden="true">↗︎</span></a></div></div>
</section>
<section class="ph-games" id="minijuegos" aria-labelledby="home-games-title">
 <div class="ph-scene ph-games-scene ph-journey-games">
  <?php $homeJourney(2,'El mercado da paso al salón de minijuegos, con mesas de ruleta, cartas y tragamonedas'); ?>
  <div class="ph-scene-content ph-wrap"><div class="ph-copy"><p class="ph-eyebrow">OCHO MINIJUEGOS · DENTRO DE MU PANIC</p><h2 id="home-games-title">Tu próxima partida<br>no necesita un spot.</h2><p>Hacé una pausa del leveo.<br>Hay mucho más para jugar en el Hub F11.</p><a href="#empeza-a-jugar" class="ph-link">Empezá tu aventura <span aria-hidden="true">↓</span></a></div></div>
 </div>

</section>

<section class="ph-play-cta" id="empeza-a-jugar" aria-labelledby="home-play-title">
 <div class="ph-play-art" aria-hidden="true"><img src="<?php echo $homeArt; ?>play-threshold-v14.webp" alt="" width="2172" height="724" loading="lazy" decoding="async"></div>
 <div class="ph-wrap ph-play-layout"><div class="ph-play-copy">
  <div class="ph-play-sigil" aria-hidden="true"><span></span></div>
  <p class="ph-eyebrow">MU PANIC TE ESPERA</p><h2 id="home-play-title">Tu historia<br><em>empieza acá.</em></h2><p>Elegí tu clase. Encontrá tu bando.<br>El primer paso lo das vos.</p>
  <div class="ph-actions"><a class="ph-button ph-button-artwork ph-play-primary" href="<?php echo __BASE_URL__.($isLogged ? 'usercp/' : 'register/'); ?>"><img class="ph-play-button-art" src="<?php echo $homeArt; ?>play-button-account-v16.webp" alt="" aria-hidden="true" width="2172" height="724" loading="lazy" decoding="async"><span class="ph-play-action-label"><?php echo $isLogged ? 'MI CUENTA' : 'CREAR CUENTA'; ?></span><span class="ph-play-action-icon" aria-hidden="true">↗︎</span></a><a class="ph-button ph-button-outline ph-button-artwork ph-play-download" href="<?php echo __BASE_URL__; ?>downloads/"><img class="ph-play-button-art" src="<?php echo $homeArt; ?>play-button-download-v16.webp" alt="" aria-hidden="true" width="2172" height="724" loading="lazy" decoding="async"><span class="ph-play-action-icon" aria-hidden="true">↓</span><span class="ph-play-action-label">DESCARGAR</span></a></div>
  <p class="ph-play-caption">Creá tu cuenta · Descargá el cliente · Entrá a jugar</p>
 </div></div>
</section>
</div>
<dialog class="ph-hub-lightbox" data-hub-lightbox aria-label="Explorá la vista ilustrada del Hub"><button type="button" class="ph-hub-lightbox-close" aria-label="Cerrar vista ampliada">×</button><div data-hub-lightbox-content></div></dialog>
