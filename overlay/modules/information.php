<?php
if(!defined('access') || !access) die();

$serverImage = function($name) {
    return __PATH_TEMPLATE__.'img/server/'.$name;
};
$panicSystems = [
    'chronicles' => [
        'art' => 'chronicles-d6291c69cf6b.webp',
        'category' => 'PROGRESIÓN',
        'title' => 'Crónicas del Conquistador',
        'short' => 'Cada conquista deja su marca.',
        'tagline' => 'Tu progreso no se mide solo en level. También se escribe en todo lo que conquistás.',
        'kind' => 'Achievements',
        'intro' => 'Un recorrido de hitos que continúa más allá del level.',
        'state' => 'Configurado',
        'alt' => 'Salón de logros con escudos y trofeos',
        'facts' => [['Objetivos', 'Metas e hitos'], ['Recorrido', 'Progreso extendido'], ['En paralelo', 'Nuevas conquistas'], ['Acceso', 'Dentro del cliente']],
    ],
    'hero-path' => [
        'art' => 'hero-path-5331edd1e310.webp',
        'category' => 'RECOMPENSAS',
        'title' => 'Camino del Héroe',
        'short' => 'Una nueva meta en cada etapa.',
        'tagline' => 'Cada etapa que superás te acerca a una nueva recompensa.',
        'kind' => 'Battle Pass',
        'intro' => 'Objetivos y recompensas acompañan tu avance por etapas.',
        'state' => 'Configurado',
        'alt' => 'Guerrero ascendiendo hacia un altar',
        'facts' => [['Progreso', 'Por etapas'], ['Desafío', 'Completar objetivos'], ['Destino', 'Recompensas'], ['Acceso', 'Dentro del juego']],
    ],
    'daily' => [
        'art' => 'daily-e36b93392874.webp',
        'category' => 'CONSTANCIA',
        'title' => 'Tributo Diario',
        'short' => 'El regreso también tiene su recompensa.',
        'tagline' => 'Volvé cada día. El continente también recompensa la constancia.',
        'kind' => 'Daily Reward',
        'intro' => 'Una recompensa diaria para acompañar la continuidad.',
        'state' => 'Configurado',
        'alt' => 'Calendario ceremonial y cofre',
        'facts' => [['Frecuencia', 'Cada día'], ['Tributo', 'Recompensa diaria'], ['Recorrido', 'Continuidad'], ['Acceso', 'Interfaz del cliente']],
    ],
    'fortune' => [
        'art' => 'fortune-a300dc40b62a.webp',
        'category' => 'FORTUNA',
        'title' => 'Fortuna del Caos',
        'short' => 'El caos decide. Vos girás.',
        'tagline' => 'El caos decide. Vos girás.',
        'kind' => 'Ruleta',
        'intro' => 'Girás y retirás el premio desde la propia interfaz.',
        'state' => 'Operativo',
        'alt' => 'Ruleta de bronce rodeada de energía roja',
        'facts' => [['Por giro', '300 WCoin C'], ['Premios', 'Definidos por el servidor'], ['Retiro', 'Desde la interfaz'], ['Acceso', 'Dentro del juego']],
    ],
    'vault' => [
        'art' => 'vault-47074095857a.webp',
        'category' => 'RESERVA',
        'title' => 'Bóveda Arcana',
        'short' => 'Más espacio para tu próxima conquista.',
        'tagline' => 'Tus joyas no tienen por qué vivir apretadas en el inventario.',
        'kind' => 'Jewel Bank',
        'intro' => 'Una reserva de joyas asociada a tu cuenta.',
        'state' => 'Probado · operativo',
        'alt' => 'Cámara arcana con joyas y cofres',
        'facts' => [['Reserva', 'Depósito de joyas'], ['Alcance', 'Nivel de cuenta'], ['Inventario', 'Libera espacio'], ['Prueba', 'Probado y operativo']],
    ],
    'vip' => [
        'art' => 'vip-66179e5e17f8.webp',
        'category' => 'MEMBRESÍA',
        'title' => 'Sello Imperial',
        'short' => 'Un sello para acompañar tu recorrido.',
        'tagline' => 'Una membresía pensada para acompañar tu recorrido.',
        'kind' => 'VIP',
        'intro' => 'Beneficios temporales definidos por la configuración vigente.',
        'state' => 'Disponible',
        'alt' => 'Corona y sello imperial sobre un altar',
        'facts' => [['Beneficios', 'Según configuración'], ['Vigencia', 'Temporal'], ['Estado', 'Visible en juego y web'], ['Sistema', 'Membresía VIP']],
    ],
    'nexus' => [
        'art' => 'nexus-4a6cbef57b72.webp',
        'detail_art' => 'hub-f11-03b5aefe024d.webp',
        'category' => 'CENTRO DE MANDO',
        'title' => 'Nexo PANIC',
        'short' => 'Tu mundo, reunido en un solo lugar.',
        'tagline' => 'El punto donde el cliente se vuelve centro de mando.',
        'kind' => 'Hub F11',
        'intro' => 'Abrí F11 para consultar tu progreso y entrar a los sistemas del servidor.',
        'state' => 'Operativo',
        'alt' => 'Centro de mando fantástico con portales',
        'detail_alt' => 'Hub F11: datos personales ocultos y barras de progreso ilustrativas',
        'facts' => [['Tu progreso', 'Nivel, resets y estadísticas del personaje.'], ['Accesos rápidos', 'Logros, Battle Pass y recompensa diaria.'], ['Más herramientas', 'Ruleta, VIP, rankings y eventos.'], ['En el cliente', 'Un centro de mando propio de MU PANIC.']],
    ],
];
$systemEscape = function($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); };
?>
<?php
$sv = function($name) { return __PATH_TEMPLATE__.'img/server/v2/'.$name; };
$svUrl = function($path) { return __BASE_URL__.$path; };
$svDiscord = htmlspecialchars($discordInvite ?? 'https://discord.gg/fP4Mxcsee', ENT_QUOTES, 'UTF-8');
$svChapters = [
    ['furia', 'I', 'La Furia', 'Hay un momento<br>en que todo explota.', 'Peleás y tu barra se carga. Cuando está llena, soltás Shift y tu personaje cambia: se mueve más rápido, pega más fuerte y despierta el poder propio de su clase.', ['Cada enemigo que cae te da más tiempo', 'Combos en pantalla, hasta la MASACRE', 'En PvP, cada clase tiene su propia Furia'], 'info/', 'Ver la Furia en el Atlas', 'Un Blade Knight desata la Furia entre monstruos', false],
    ['fractura', 'II', 'La Fractura', 'Elegí un bando.<br>Defendelo.', 'Nexo o Caos. Una guerra que no se termina cuando cerrás el juego: tu bando gana o pierde terreno con cada batalla en Vulcanus y con cada invasión.', ['Dos facciones, una sola historia', 'PvP con sentido: cada muerte suma para tu bando', 'Recompensas para los que más aportan'], 'fractura/', 'Conocé la Fractura', 'Los ejércitos de Nexo y Caos chocan en Vulcanus', true],
    ['vessara', 'III', 'Mercado Vessara', 'Lo que conseguís<br>jugando vale algo.', 'Publicá tus ítems, ponele precio en Eryns o en Zen y vendelos aunque estés desconectado. Del otro lado, encontrás lo que buscás sin pasar horas gritando en el chat.', ['Un mercado hecho por los jugadores', 'Tus ítems quedan en custodia hasta que se venden', 'Precios reales, que pone la comunidad'], 'market/', 'Explorá el mercado', 'Puestos del Mercado Vessara con armas, alas y joyas', false],
    ['companeros', 'IV', 'Compañeros', 'No avanzás solo.', 'Elyndra, Aurion, Vaerion y Grum te acompañan desde el primer día. Cada uno tiene su personalidad y crece con vos.', ['Cuatro compañeros únicos', 'Evolucionan a medida que avanzás', 'Diseñados para MU PANIC'], 'info/#vinculos', 'Conocé a tus compañeros', 'Elyndra, Aurion, Vaerion y Grum en un claro del bosque', true],
    ['hub', 'V', 'Hub F11', 'Cuando quieras<br>cortar con el leveo.', 'Apretás F11 y se abre otro mundo: ocho minijuegos para jugarte unos Eryns, tentar a la suerte o simplemente despejarte entre una caza y otra.', ['Ruleta, tragamonedas, mayor o menor, dragón', 'Joyas, tesoro, torre y carrera', 'Sin salir del juego'], '#sistemas', 'Ver los sistemas del juego', 'Mesa de juegos con ruleta, tragamonedas y tesoros', false],
    ['cliente', 'VI', 'El cliente', 'Se ve y se siente distinto.', 'Interfaces renovadas, barras de vida nuevas, opciones en castellano y detalles que no vas a ver en otro MU. Lo rehicimos pieza por pieza para que jugar sea más cómodo.', ['Tres interfaces para elegir', 'Barras de vida y placas de nombre propias', 'Todo en castellano'], 'downloads/', 'Descargá el cliente', 'Interfaces del cliente de MU PANIC', true],
];
$svDay = [
    ['dia-1-tributo', 'Al entrar', 'Cobrás tu Tributo', 'Una recompensa por volver cada día.', 'Cofre de recompensa diaria sobre un altar'],
    ['dia-2-caza', 'A la tarde', 'Salís a cazar', 'Cargás la Furia y encadenás combos en tu spot.', 'Personaje con la Furia activa rodeado de monstruos'],
    ['dia-3-mercado', 'Después', 'Vendés tu drop', 'Lo publicás en Vessara y se vende aunque te desconectes.', 'Una espada brillante sobre el mostrador del mercado'],
    ['dia-4-fractura', 'A la noche', 'Defendés tu bando', 'Invasión de La Fractura: Nexo contra Caos en Vulcanus.', 'Nexo y Caos cargan bajo una tormenta'],
    ['dia-5-hub', 'Para cerrar', 'Una partida en el Hub', 'Te jugás unos Eryns antes de desconectarte.', 'Ruleta de bronce con fichas y joyas'],
];
$svOnly = [
    ['Furia', 'Tu momento de poder', 'Una barra que cargás peleando y desatás con Shift.'],
    ['2', 'Bandos en guerra', 'Nexo y Caos se disputan La Fractura.'],
    ['8', 'Minijuegos', 'En el Hub F11, sin salir del juego.'],
    ['4', 'Compañeros propios', 'Elyndra, Aurion, Vaerion y Grum.'],
    ['1', 'Mercado de jugadores', 'Vessara: vendé aunque no estés conectado.'],
    ['3', 'Interfaces renovadas', 'Elegí la que más te guste.'],
    ['∞', 'Metas paralelas', 'Crónicas del Conquistador, Camino del Héroe y más.'],
    ['ES', 'Todo en castellano', 'Juego, web y soporte.'],
];
?>
<div class="sv-page">

<section class="sv-scene sv-hero" aria-labelledby="sv-hero-title">
    <img class="sv-art" src="<?php echo $sv('server-hero.webp'); ?>" alt="Un caballero observa el continente desde un acantilado" width="1920" height="1080" fetchpriority="high">
    <div class="sv-content sv-wrap">
        <div class="sv-copy">
            <p class="sv-eyebrow">El servidor · Season 6 · Argentina</p>
            <h1 id="sv-hero-title">Hicimos el MU que queríamos jugar.</h1>
            <p class="sv-lead">El MU de siempre, con sistemas que no vas a encontrar en ningún otro servidor. Pensado para durar: sin atajos, con un Zen que vale y un mundo que se mueve aunque no estés mirando.</p>
            <div class="sv-actions">
                <a class="sv-btn" href="<?php echo $svUrl('register/'); ?>">Crear cuenta <span aria-hidden="true">↗</span></a>
                <a class="sv-btn sv-btn-o" href="#capitulos">Conocé el servidor <span aria-hidden="true">↓</span></a>
            </div>
        </div>
    </div>
</section>

<div class="sv-wrap">
    <section class="sv-pillars" aria-label="Pilares de MU PANIC">
        <div><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l3 6 6 .9-4.5 4.3 1 6.3L12 16.6 6.5 19.5l1-6.3L3 8.9 9 8z"/></svg><h3>Sistemas propios</h3><p>Nada de copiar y pegar otro servidor. Cada sistema se diseñó y se probó acá.</p></div>
        <div><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="M12 7v10M9.5 9.5h4a1.8 1.8 0 010 3.5h-3a1.8 1.8 0 000 3.5h4"/></svg><h3>Una economía que importa</h3><p>El Zen y los Eryns valen. El mercado lo arman los jugadores, no una tienda.</p></div>
        <div><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><h3>Siempre hay algo para hacer</h3><p>Eventos, invasiones, minijuegos, misiones y compañeros. El día nunca es igual.</p></div>
        <div><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21V4l8 3 8-3v17l-8-3-8 3z"/></svg><h3>Hecho en Argentina</h3><p>Servidor y equipo de acá: buen ping, soporte en castellano y comunidad propia.</p></div>
    </section>
</div>

<section class="sv-intro sv-wrap" id="capitulos" aria-labelledby="sv-intro-title">
    <p class="sv-eyebrow">Lo que hace distinto a MU PANIC</p>
    <h2 id="sv-intro-title">Seis razones para quedarte.</h2>
    <p>Cada una tiene su propia página en el Atlas, con todos los detalles. Acá te contamos qué se siente.</p>
</section>

<?php foreach($svChapters as $c): ?>
<section class="sv-scene sv-chapter<?php echo $c[9] ? ' sv-right' : ''; ?>" id="sv-<?php echo $c[0]; ?>" aria-labelledby="sv-<?php echo $c[0]; ?>-title">
    <img class="sv-art" src="<?php echo $sv('server-'.$c[0].'.webp'); ?>" alt="<?php echo $c[8]; ?>" width="1920" height="820" loading="lazy">
    <div class="sv-content sv-wrap">
        <div class="sv-copy">
            <span class="sv-num"><?php echo $c[1]; ?></span>
            <p class="sv-eyebrow<?php echo $c[0] === 'furia' ? ' sv-ember' : ''; ?>"><?php echo $c[2]; ?></p>
            <h2 id="sv-<?php echo $c[0]; ?>-title"><?php echo $c[3]; ?></h2>
            <p><?php echo $c[4]; ?></p>
            <ul class="sv-feats"><?php foreach($c[5] as $f) echo '<li>'.$f.'</li>'; ?></ul>
            <a class="sv-link" href="<?php echo $c[6][0] === '#' ? $c[6] : $svUrl($c[6]); ?>"><?php echo $c[7]; ?> <span aria-hidden="true">↗</span></a>
        </div>
    </div>
</section>
<?php endforeach; ?>

<section class="sv-day sv-wrap" aria-labelledby="sv-day-title">
    <div class="sv-day-head">
        <div><p class="sv-eyebrow">Un día en MU PANIC</p><h2 id="sv-day-title">Así se juega acá.</h2></div>
        <p>No hace falta estar diez horas conectado. Cada momento del día tiene algo que vale la pena.</p>
    </div>
    <ol class="sv-timeline">
    <?php foreach($svDay as $d): ?>
        <li><img src="<?php echo $sv($d[0].'.webp'); ?>" alt="<?php echo $d[4]; ?>" width="800" height="500" loading="lazy"><span class="sv-time"><?php echo $d[1]; ?></span><h3><?php echo $d[2]; ?></h3><p><?php echo $d[3]; ?></p></li>
    <?php endforeach; ?>
    </ol>
</section>

<section class="server-features" id="sistemas" aria-labelledby="server-systems-title">
    <div class="shell">
        <header class="server-features-heading">
            <span class="eyebrow">MÁS ALLÁ DEL LEVEL</span>
            <h2 id="server-systems-title">Siempre hay<br><em>algo más que buscar.</em></h2>
            <p>Siete formas de seguir escribiendo tu historia.</p>
        </header>
        <div class="server-features-grid">
        <?php $systemNumber = 0; foreach($panicSystems as $id => $system): $systemNumber++; ?>
            <article class="server-feature-card<?php echo $id === 'nexus' ? ' server-feature-card-nexus' : ''; ?>">
                <img class="server-feature-art" src="<?php echo $serverImage('systems/'.$system['art']); ?>" alt="<?php echo $systemEscape($system['alt']); ?>" width="1536" height="1024" loading="lazy">
                <div class="server-feature-copy">
                    <span class="server-feature-category"><?php echo $system['category']; ?></span>
                    <h3><?php echo $system['title']; ?></h3>
                    <p><?php echo $system['short']; ?></p>
                    <button type="button" data-system-open="<?php echo $id; ?>" aria-haspopup="dialog" aria-controls="server-feature-dialog" aria-label="Ver sistema: <?php echo $systemEscape($system['title']); ?>">Ver sistema <span aria-hidden="true">→</span></button>
                </div>
                <span class="server-feature-number" aria-hidden="true"><?php echo sprintf('%02d', $systemNumber); ?></span>
            </article>
        <?php endforeach; ?>
        </div>
    </div>
    <dialog class="server-feature-dialog" id="server-feature-dialog" data-system-modal>
        <button type="button" class="server-feature-close" data-system-close aria-label="Cerrar sistema" autofocus>×</button>
        <?php foreach($panicSystems as $id => $system): ?>
        <article class="server-feature-panel" data-system-panel="<?php echo $id; ?>" hidden>
            <figure class="server-feature-visual">
                <img src="<?php echo $serverImage('systems/'.($system['detail_art'] ?? $system['art'])); ?>" alt="<?php echo $systemEscape($system['detail_alt'] ?? $system['alt']); ?>" width="<?php echo $id === 'nexus' ? 1522 : 1536; ?>" height="<?php echo $id === 'nexus' ? 1033 : 1024; ?>" loading="lazy">
                <figcaption>MU PANIC <span><?php echo $id === 'nexus' ? 'Datos ocultos · barras ilustrativas' : 'Arte conceptual'; ?></span></figcaption>
            </figure>
            <div class="server-feature-content">
                <span class="server-feature-category"><?php echo $system['category']; ?></span>
                <h2 id="system-title-<?php echo $id; ?>"><?php echo $system['title']; ?></h2>
                <p class="server-feature-tagline" id="system-description-<?php echo $id; ?>"><?php echo $system['tagline']; ?></p>
                <p class="server-feature-intro"><?php echo $system['intro']; ?></p>
                <dl class="server-feature-facts">
                <?php foreach($system['facts'] as $index => $fact): ?>
                    <div<?php echo $id === 'fortune' && $index === 0 ? ' class="server-feature-price"' : ''; ?>><dt><?php echo $fact[0]; ?></dt><dd><?php echo $fact[1]; ?><?php if($id === 'fortune' && $index === 0) { ?><small>por giro</small><?php } ?></dd></div>
                <?php endforeach; ?>
                </dl>
                <footer class="server-feature-status"><span><?php echo $system['kind']; ?></span><span class="server-feature-state"><?php echo $system['state']; ?></span></footer>
            </div>
        </article>
        <?php endforeach; ?>
    </dialog>
</section>

<section class="sv-only sv-wrap" aria-labelledby="sv-only-title">
    <p class="sv-eyebrow">Solo en MU PANIC</p>
    <h2 id="sv-only-title">Lo que no vas a encontrar en otro MU.</h2>
    <div class="sv-only-grid">
    <?php foreach($svOnly as $o): ?>
        <div><b><?php echo $o[0]; ?></b><h3><?php echo $o[1]; ?></h3><p><?php echo $o[2]; ?></p></div>
    <?php endforeach; ?>
    </div>
</section>

<section class="sv-community sv-wrap" aria-labelledby="sv-community-title">
    <img src="<?php echo $sv('server-comunidad.webp'); ?>" alt="Aventureros de distintas clases alrededor de una fogata" width="1200" height="800" loading="lazy">
    <div>
        <p class="sv-eyebrow">Comunidad</p>
        <h2 id="sv-community-title">Un servidor se hace con gente.</h2>
        <p>En Discord vas a encontrar party, novedades, eventos y ayuda.</p>
        <div class="sv-actions"><a class="sv-btn sv-btn-o" href="<?php echo $svDiscord; ?>" target="_blank" rel="noopener noreferrer">Entrar al Discord <span aria-hidden="true">↗</span></a></div>
    </div>
</section>

<section class="sv-scene sv-final" aria-labelledby="sv-final-title">
    <img class="sv-art" src="<?php echo $sv('server-cierre.webp'); ?>" alt="Un aventurero frente a un portal de runas" width="1920" height="820" loading="lazy">
    <div class="sv-content sv-wrap">
        <div class="sv-copy">
            <p class="sv-eyebrow">Tu lugar ya está esperando</p>
            <h2 id="sv-final-title">El continente te está llamando.</h2>
            <p class="sv-lead">Creá tu cuenta, descargá el cliente y empezá hoy.</p>
            <div class="sv-actions">
                <a class="sv-btn" href="<?php echo $svUrl('register/'); ?>">Crear cuenta <span aria-hidden="true">↗</span></a>
                <a class="sv-btn sv-btn-o" href="<?php echo $svUrl('downloads/'); ?>">Descargar <span aria-hidden="true">↓</span></a>
            </div>
        </div>
    </div>
</section>

</div>
