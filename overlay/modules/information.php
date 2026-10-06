<?php
if(!defined('access') || !access) die();

$serverImage = function($name) {
    return __PATH_TEMPLATE__.'img/server/'.$name;
};
?>

<section class="server-identity" id="identidad" aria-labelledby="server-identity-title">
    <div class="shell server-identity-layout">
        <div class="server-identity-copy">
            <span class="eyebrow">POR QUÉ MU PANIC</span>
            <h2 id="server-identity-title">El progreso tiene<br><em>que sentirse.</em></h2>
            <p>No queremos que todos los mapas sean iguales ni que el objetivo sea únicamente sumar resets. El servidor se organiza alrededor de decisiones: dónde farmear, qué guardar, qué mejorar y qué perseguir después.</p>
        </div>
        <figure class="server-identity-art">
            <img src="<?php echo $serverImage('server-world.webp'); ?>" alt="Un grupo de aventureros observando distintas regiones del continente">
            <figcaption>El continente cambia con tu progreso.</figcaption>
        </figure>
        <div class="server-pillars">
            <article>
                <span>01</span>
                <div><h3>Mapas con propósito</h3><p>Spots, Zen, joyas y objetivos distribuidos para que avanzar también signifique elegir dónde jugar.</p></div>
            </article>
            <article>
                <span>02</span>
                <div><h3>Etapas que importan</h3><p>Reset, Master Reset y zonas de mayor exigencia forman un recorrido, no una carrera sin contexto.</p></div>
            </article>
            <article>
                <span>03</span>
                <div><h3>Más que levelear</h3><p>Logros, recompensas, membresía, ruleta y sistemas de cuenta agregan objetivos paralelos.</p></div>
            </article>
            <article>
                <span>04</span>
                <div><h3>Información real</h3><p>Atlas concentra mapas, mobs, spots, drops y referencias para que puedas planear tu siguiente paso.</p></div>
            </article>
        </div>
    </div>
</section>

<section class="server-progression" aria-labelledby="server-progression-title">
    <div class="server-progression-art">
        <img src="<?php echo $serverImage('server-progression.webp'); ?>" alt="Aventureros contemplando las distintas regiones del mundo">
    </div>
    <div class="shell server-progression-layout">
        <div class="server-progression-copy">
            <span class="eyebrow">TU RECORRIDO</span>
            <h2 id="server-progression-title">El level 400<br><em>no es el final.</em></h2>
            <p>Tu personaje atraviesa etapas. El objetivo es que siempre exista una próxima mejora razonable: mejor equipo, otro mapa, una combinación pendiente, un nuevo reset o un objetivo de cuenta.</p>
        </div>
        <div class="server-progression-steps">
            <article><b>01</b><small>ORIGEN</small><strong>Construí tu personaje</strong><p>Primer equipo, Zen, habilidades y mapas iniciales.</p></article>
            <article><b>02</b><small>ASCENSO</small><strong>Ganate el próximo mapa</strong><p>Joyas, alas, mejores spots y decisiones de equipo.</p></article>
            <article><b>03</b><small>CONQUISTA</small><strong>Hacé valer tu progreso</strong><p>Reset, Master Reset, endgame y objetivos paralelos.</p></article>
        </div>
    </div>
</section>

<section class="server-systems-new" id="sistemas" aria-labelledby="server-systems-title">
    <div class="shell">
        <header class="server-section-heading">
            <div>
                <span class="eyebrow">SISTEMAS QUE SIGUEN IMPORTANDO DESPUÉS DEL LEVEL</span>
                <h2 id="server-systems-title">Siempre hay<br><em>algo más que buscar.</em></h2>
            </div>
            <p>Progreso, recompensas, fortuna, reserva y herramientas propias del servidor. Elegí un sistema y conocelo sin salir de la página.</p>
        </header>

        <div class="server-system-cards server-system-cards-visual">
            <article class="server-system-card-visual">
                <div class="server-system-card-art"><img src="<?php echo $serverImage('systems/chronicles.webp'); ?>" alt=""></div>
                <div class="server-system-card-body">
                    <span class="server-system-icon"><?php echo mupanicGlyph('crest'); ?></span>
                    <small>PROGRESIÓN · LOGROS</small>
                    <h3>Crónicas del Conquistador</h3>
                    <p>Convertí tus objetivos en avance real. Sumá hitos, completá desafíos y hacé que tu recorrido deje marca.</p>
                    <button type="button" class="server-system-open" data-system-open="chronicles">Ver sistema <span aria-hidden="true">↗</span></button>
                </div>
            </article>

            <article class="server-system-card-visual">
                <div class="server-system-card-art"><img src="<?php echo $serverImage('systems/hero-path.webp'); ?>" alt=""></div>
                <div class="server-system-card-body">
                    <span class="server-system-icon"><?php echo mupanicGlyph('sword'); ?></span>
                    <small>RECOMPENSAS · BATTLE PASS</small>
                    <h3>Camino del Héroe</h3>
                    <p>Una ruta de progreso para que cada etapa del juego te devuelva algo concreto.</p>
                    <button type="button" class="server-system-open" data-system-open="hero-path">Ver sistema <span aria-hidden="true">↗</span></button>
                </div>
            </article>

            <article class="server-system-card-visual">
                <div class="server-system-card-art"><img src="<?php echo $serverImage('systems/daily.webp'); ?>" alt=""></div>
                <div class="server-system-card-body">
                    <span class="server-system-icon"><?php echo mupanicGlyph('party'); ?></span>
                    <small>CADA DÍA · DAILY REWARD</small>
                    <h3>Tributo Diario</h3>
                    <p>Entrá, reclamá y sostené una cadena de beneficios que acompaña tu cuenta día tras día.</p>
                    <button type="button" class="server-system-open" data-system-open="daily">Ver sistema <span aria-hidden="true">↗</span></button>
                </div>
            </article>

            <article class="server-system-card-visual">
                <div class="server-system-card-art"><img src="<?php echo $serverImage('systems/fortune.webp'); ?>" alt=""></div>
                <div class="server-system-card-body">
                    <span class="server-system-icon"><?php echo mupanicGlyph('gem'); ?></span>
                    <small>FORTUNA · RULETA</small>
                    <h3>Fortuna del Caos</h3>
                    <p>Poné a prueba tu suerte con premios definidos por el servidor y una dinámica pensada para WCoin C.</p>
                    <button type="button" class="server-system-open" data-system-open="fortune">Ver sistema <span aria-hidden="true">↗</span></button>
                </div>
            </article>

            <article class="server-system-card-visual">
                <div class="server-system-card-art"><img src="<?php echo $serverImage('systems/vault.webp'); ?>" alt=""></div>
                <div class="server-system-card-body">
                    <span class="server-system-icon"><?php echo mupanicGlyph('gem'); ?></span>
                    <small>INVENTARIO · JEWEL BANK</small>
                    <h3>Bóveda Arcana</h3>
                    <p>Guardá tus joyas a nivel de cuenta y mantené tu inventario limpio para jugar y mejorar.</p>
                    <button type="button" class="server-system-open" data-system-open="vault">Ver sistema <span aria-hidden="true">↗</span></button>
                </div>
            </article>

            <article class="server-system-card-visual">
                <div class="server-system-card-art"><img src="<?php echo $serverImage('systems/vip.webp'); ?>" alt=""></div>
                <div class="server-system-card-body">
                    <span class="server-system-icon"><?php echo mupanicGlyph('wings'); ?></span>
                    <small>MEMBRESÍA · VIP</small>
                    <h3>Sello Imperial</h3>
                    <p>Beneficios temporales, estado visible y una membresía pensada para acompañar tu progreso.</p>
                    <button type="button" class="server-system-open" data-system-open="vip">Ver sistema <span aria-hidden="true">↗</span></button>
                </div>
            </article>

            <article class="server-system-card-visual server-system-card-nexus">
                <div class="server-system-card-art"><img src="<?php echo $serverImage('systems/nexus.webp'); ?>" alt=""></div>
                <div class="server-system-card-body">
                    <span class="server-system-icon"><?php echo mupanicGlyph('crest'); ?></span>
                    <small>DENTRO DEL JUEGO · HUB F11</small>
                    <h3>Nexo PANIC</h3>
                    <p>El centro de mando del cliente. Accesos, estadísticas, funciones y utilidades reunidas en un solo lugar.</p>
                    <button type="button" class="server-system-open" data-system-open="nexus">Ver sistema <span aria-hidden="true">↗</span></button>
                </div>
            </article>
        </div>
    </div>

    <dialog class="server-system-modal" data-system-modal aria-labelledby="server-system-modal-title">
        <div class="server-system-modal-shell">
            <button type="button" class="server-system-modal-close" data-system-close aria-label="Cerrar">×</button>

            <article class="server-system-panel" data-system-panel="chronicles" hidden>
                <div class="server-system-modal-hero">
                    <img src="<?php echo $serverImage('systems/chronicles.webp'); ?>" alt="Salón ceremonial de logros y conquistas">
                    <div><small>PROGRESIÓN</small><h2 id="server-system-modal-title">Crónicas del Conquistador</h2><p>Tu progreso no se mide solo en level. También se escribe en todo lo que conquistás.</p></div>
                </div>
                <div class="server-system-modal-content">
                    <div class="server-system-modal-main">
                        <p class="server-system-lead">Este sistema convierte tu recorrido por MU PANIC en una colección de hitos reales. Cada objetivo completado suma valor a tu avance y le da más profundidad a la experiencia más allá del leveleo tradicional.</p>
                        <div class="server-system-info-grid">
                            <section><b>Qué es</b><p>El sistema de Achievements integrado al cliente.</p></section>
                            <section><b>Cómo funciona</b><p>Completás metas, desbloqueás hitos y acumulás progreso fuera del circuito clásico de level/reset.</p></section>
                            <section><b>Qué buscás</b><p>Objetivos paralelos, avance constante y una referencia visible de todo lo que ya construiste.</p></section>
                            <section><b>Dónde</b><p>Dentro del cliente, en su propia ventana de logros.</p></section>
                        </div>
                    </div>
                    <aside class="server-system-facts"><span>ACTIVO</span><dl><dt>Tipo</dt><dd>Achievements</dd><dt>Uso</dt><dd>Dentro del juego</dd><dt>Objetivo</dt><dd>Progreso extendido</dd></dl></aside>
                </div>
            </article>

            <article class="server-system-panel" data-system-panel="hero-path" hidden>
                <div class="server-system-modal-hero">
                    <img src="<?php echo $serverImage('systems/hero-path.webp'); ?>" alt="Guerrero ascendiendo hacia una recompensa">
                    <div><small>RECOMPENSAS</small><h2>Camino del Héroe</h2><p>Cada etapa que superás te acerca a una nueva recompensa.</p></div>
                </div>
                <div class="server-system-modal-content">
                    <div class="server-system-modal-main">
                        <p class="server-system-lead">El Camino del Héroe es una ruta de progreso por etapas. Jugar, completar objetivos y avanzar se traduce en recompensas visibles a lo largo del recorrido.</p>
                        <div class="server-system-info-grid">
                            <section><b>Qué es</b><p>El Battle Pass integrado a MU PANIC.</p></section>
                            <section><b>Cómo funciona</b><p>Avanzás por objetivos y desbloqueás recompensas por tramos.</p></section>
                            <section><b>Qué aporta</b><p>Dirección de progreso, continuidad y una meta estructurada dentro del juego.</p></section>
                            <section><b>Dónde</b><p>Se consulta y reclama directamente dentro del cliente.</p></section>
                        </div>
                    </div>
                    <aside class="server-system-facts"><span>ACTIVO</span><dl><dt>Tipo</dt><dd>Battle Pass</dd><dt>Progreso</dt><dd>Por etapas</dd><dt>Recompensas</dt><dd>Dentro del juego</dd></dl></aside>
                </div>
            </article>

            <article class="server-system-panel" data-system-panel="daily" hidden>
                <div class="server-system-modal-hero">
                    <img src="<?php echo $serverImage('systems/daily.webp'); ?>" alt="Cofre y calendario de recompensa diaria">
                    <div><small>CADA DÍA</small><h2>Tributo Diario</h2><p>Volvé cada día. El continente también recompensa la constancia.</p></div>
                </div>
                <div class="server-system-modal-content">
                    <div class="server-system-modal-main">
                        <p class="server-system-lead">Tributo Diario convierte la presencia diaria en un beneficio concreto. No necesita sesiones eternas: premia el regreso y mantiene vivo el circuito de tu cuenta.</p>
                        <div class="server-system-info-grid">
                            <section><b>Qué es</b><p>La recompensa diaria de cuenta.</p></section>
                            <section><b>Cómo funciona</b><p>Ingresás, reclamás el día correspondiente y sostenés continuidad.</p></section>
                            <section><b>Qué buscás</b><p>Premios por constancia y una razón útil para volver incluso en días cortos.</p></section>
                            <section><b>Dónde</b><p>Desde la interfaz de recompensas del cliente.</p></section>
                        </div>
                    </div>
                    <aside class="server-system-facts"><span>ACTIVO</span><dl><dt>Frecuencia</dt><dd>Diaria</dd><dt>Tipo</dt><dd>Cuenta</dd><dt>Acción</dt><dd>Reclamar</dd></dl></aside>
                </div>
            </article>

            <article class="server-system-panel" data-system-panel="fortune" hidden>
                <div class="server-system-modal-hero">
                    <img src="<?php echo $serverImage('systems/fortune.webp'); ?>" alt="Ruleta de premios rodeada de joyas">
                    <div><small>FORTUNA</small><h2>Fortuna del Caos</h2><p>El caos decide. Vos girás.</p></div>
                </div>
                <div class="server-system-modal-content">
                    <div class="server-system-modal-main">
                        <p class="server-system-lead">La ruleta de premios de MU PANIC usa WCoin C dentro de una mecánica simple y directa. Realizás el giro, el sistema determina el resultado y el premio queda disponible en su propia interfaz.</p>
                        <div class="server-system-info-grid">
                            <section><b>Qué es</b><p>La ruleta de premios del servidor.</p></section>
                            <section><b>Cómo funciona</b><p>Girás, obtenés un resultado y retirás el premio cuando corresponda.</p></section>
                            <section><b>Costo</b><p>300 WCoin C por giro.</p></section>
                            <section><b>Dónde</b><p>Dentro del juego, en la ventana de la ruleta.</p></section>
                        </div>
                    </div>
                    <aside class="server-system-facts"><span>OPERATIVO</span><dl><dt>Costo</dt><dd>300 WCoin C</dd><dt>Tipo</dt><dd>Azar</dd><dt>Retiro</dt><dd>Desde la interfaz</dd></dl></aside>
                </div>
            </article>

            <article class="server-system-panel" data-system-panel="vault" hidden>
                <div class="server-system-modal-hero">
                    <img src="<?php echo $serverImage('systems/vault.webp'); ?>" alt="Bóveda con cofres y joyas">
                    <div><small>INVENTARIO</small><h2>Bóveda Arcana</h2><p>Tus joyas no tienen por qué vivir apretadas en el inventario.</p></div>
                </div>
                <div class="server-system-modal-content">
                    <div class="server-system-modal-main">
                        <p class="server-system-lead">Bóveda Arcana es una mejora de calidad de vida: separa la reserva de joyas del inventario de tu personaje para que administrar recursos sea mucho más cómodo.</p>
                        <div class="server-system-info-grid">
                            <section><b>Qué es</b><p>El Jewel Bank de MU PANIC.</p></section>
                            <section><b>Cómo funciona</b><p>Depositás joyas, consultás tu reserva y las mantenés asociadas a la cuenta.</p></section>
                            <section><b>Qué ganás</b><p>Orden, espacio de inventario y una preparación más simple para mejorar equipo.</p></section>
                            <section><b>Estado</b><p>El sistema fue probado y está operativo.</p></section>
                        </div>
                    </div>
                    <aside class="server-system-facts"><span>OPERATIVO</span><dl><dt>Tipo</dt><dd>Jewel Bank</dd><dt>Alcance</dt><dd>Cuenta</dd><dt>Beneficio</dt><dd>Reserva de joyas</dd></dl></aside>
                </div>
            </article>

            <article class="server-system-panel" data-system-panel="vip" hidden>
                <div class="server-system-modal-hero">
                    <img src="<?php echo $serverImage('systems/vip.webp'); ?>" alt="Corona imperial en una sala ceremonial">
                    <div><small>MEMBRESÍA</small><h2>Sello Imperial</h2><p>Una membresía pensada para acompañar tu recorrido con beneficios definidos.</p></div>
                </div>
                <div class="server-system-modal-content">
                    <div class="server-system-modal-main">
                        <p class="server-system-lead">Sello Imperial es la capa VIP de MU PANIC: beneficios temporales de cuenta, estado visible y una experiencia integrada entre el juego y la web.</p>
                        <div class="server-system-info-grid">
                            <section><b>Qué es</b><p>La membresía VIP de MU PANIC.</p></section>
                            <section><b>Cómo funciona</b><p>Activás la membresía y obtenés los beneficios definidos para el nivel y período vigente.</p></section>
                            <section><b>Qué cambia</b><p>Bonificaciones temporales y lectura clara del estado de tu cuenta.</p></section>
                            <section><b>Dónde</b><p>En el juego y también desde Mi cuenta en la web.</p></section>
                        </div>
                    </div>
                    <aside class="server-system-facts"><span>OPERATIVO</span><dl><dt>Tipo</dt><dd>VIP</dd><dt>Duración</dt><dd>Temporal</dd><dt>Estado</dt><dd>Web + juego</dd></dl></aside>
                </div>
            </article>

            <article class="server-system-panel" data-system-panel="nexus" hidden>
                <div class="server-system-modal-hero">
                    <img src="<?php echo $serverImage('systems/nexus.webp'); ?>" alt="Panel central de mando de MU PANIC">
                    <div><small>DENTRO DEL JUEGO</small><h2>Nexo PANIC</h2><p>El punto donde el cliente se vuelve centro de mando.</p></div>
                </div>
                <div class="server-system-modal-content">
                    <div class="server-system-modal-main">
                        <p class="server-system-lead">Nexo PANIC reúne funciones, accesos y lecturas clave del cliente en una misma interfaz para que no dependas de ventanas dispersas o comandos difíciles de recordar.</p>
                        <div class="server-system-info-grid">
                            <section><b>Qué es</b><p>El Hub F11 propio de MU PANIC.</p></section>
                            <section><b>Qué reúne</b><p>Estadísticas, accesos, eventos y utilidades del cliente.</p></section>
                            <section><b>Qué aporta</b><p>Más control, mejor lectura del personaje y una identidad propia del cliente.</p></section>
                            <section><b>Acceso</b><p>Se abre directamente dentro del juego con F11.</p></section>
                        </div>
                    </div>
                    <aside class="server-system-facts"><span>OPERATIVO</span><dl><dt>Acceso</dt><dd>F11</dd><dt>Tipo</dt><dd>Hub</dd><dt>Uso</dt><dd>Centro de mando</dd></dl></aside>
                </div>
            </article>
        </div>
    </dialog>
</section>

<section class="server-atlas-showcase" aria-labelledby="server-atlas-title">
    <div class="server-atlas-background">
        <img src="<?php echo $serverImage('server-atlas.webp'); ?>" alt="Un estratega observando un mapa fantástico del continente">
    </div>
    <div class="shell server-atlas-content">
        <span class="eyebrow">CONOCÉ ANTES DE ARRIESGAR</span>
        <h2 id="server-atlas-title">Tu próximo objetivo<br><em>empieza en el Atlas.</em></h2>
        <p>Mapas, spots, mobs, drops, recompensas y rutas de progresión en una referencia construida alrededor del servidor real.</p>
        <a class="button primary" href="<?php echo __BASE_URL__; ?>info/">Abrir Atlas <span aria-hidden="true">↗</span></a>
    </div>
</section>

<section class="server-events-new" aria-labelledby="server-events-title">
    <div class="shell">
        <header class="server-section-heading">
            <div>
                <span class="eyebrow">EL CONTINENTE SE MUEVE</span>
                <h2 id="server-events-title">Eventos,<br><em>invasiones y bosses.</em></h2>
            </div>
            <p>Estamos auditando cada evento antes de publicarlo como parte definitiva del servidor. La presentación puede ser épica; la información tiene que ser cierta.</p>
        </header>

        <article class="server-event-cinematic">
            <figure>
                <img src="<?php echo $serverImage('server-events.webp'); ?>" alt="Una fortaleza sitiada durante una invasión nocturna">
            </figure>
            <div>
                <span class="server-validation-badge">EN VALIDACIÓN</span>
                <small>EVENTOS CLÁSICOS + CONTENIDO DEL SERVIDOR</small>
                <h3>Cuando suena la alarma,<br>el mapa deja de ser el mismo.</h3>
                <p>Blood Castle, Devil Square, Chaos Castle, invasiones y bosses están dentro del conjunto que estamos revisando. Horarios, recompensas y requisitos aparecerán acá cuando estén probados de punta a punta.</p>
            </div>
        </article>
    </div>
</section>

<section class="server-final-cta" aria-labelledby="server-final-title">
    <div class="server-final-art">
        <img src="<?php echo $serverImage('server-portals.webp'); ?>" alt="Aventureros ascendiendo hacia portales en una ciudadela">
    </div>
    <div class="shell server-final-content">
        <span class="eyebrow">EL CONTINENTE YA ESTÁ AHÍ</span>
        <h2 id="server-final-title">Elegí cómo<br><em>querés conquistarlo.</em></h2>
        <div class="server-final-actions">
            <a class="button primary" href="<?php echo __BASE_URL__; ?>register/">Crear cuenta <span aria-hidden="true">↗</span></a>
            <a class="button server-button-ghost" href="<?php echo __BASE_URL__; ?>downloads/">Descargar cliente <span aria-hidden="true">↓</span></a>
            <a class="text-link" href="<?php echo htmlspecialchars($discordInvite ?? 'https://discord.gg/fP4Mxcsee', ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Entrar al Discord <span aria-hidden="true">↗</span></a>
        </div>
    </div>
</section>
