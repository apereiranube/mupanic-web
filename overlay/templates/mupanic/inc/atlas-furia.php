<?php if(!defined('access') || !access) die();
$furiaArt = __PATH_TEMPLATE__.'img/home/';
// Arte nuevo (el mismo del minijuego de /servidor/): Rage Fighter en Furia y el gólem. Si falta, queda la escena anterior.
$furiaRf = panicAtlasSiteArt('servidor', 'furia-rf');
$furiaGolem = panicAtlasSiteArt('servidor', 'golem-normal');
$furiaGolemHit = panicAtlasSiteArt('servidor', 'golem-golpe');
$furiaV3 = $furiaRf && $furiaGolem;
$furiaClasses = [
    ['Dark Wizard', '+25% de daño excelente y maná infinito mientras dura.'],
    ['Dark Knight', '+100% de resistencia al stun y +20% de ignorar defensa.'],
    ['Fairy Elf', '+25% de ignorar defensa y +20% de daño crítico.'],
    ['Magic Gladiator', '+20% de doble daño y +20% de daño excelente.'],
    ['Dark Lord', '+30% de daño crítico y +10% de triple daño.'],
    ['Summoner', '+15% de ignorar SD.'],
    ['Rage Fighter', 'Cada golpe te cura el 15% del daño que hacés, hasta el 50% de tu vida máxima por Furia.'],
];
?>
<section id="furia" class="wiki-section atlas-furia" aria-labelledby="atlas-furia-title" data-wiki-search>
    <div class="atlas-furia-hero<?php echo $furiaV3 ? ' is-v3' : ''; ?>">
        <?php if($furiaV3) { ?><div class="afu-v3-stage" role="img" aria-label="Un Rage Fighter en Furia golpea a un gólem de piedra"><i class="afu-v3-slab" aria-hidden="true"></i><span class="afu-v3-word" aria-hidden="true">FURIA</span><span class="afu-v3-golem" aria-hidden="true"><img class="n" src="<?php echo panicAtlasE($furiaGolem); ?>" alt="" width="820" height="680" loading="lazy" decoding="async"><?php if($furiaGolemHit) { ?><img class="h" src="<?php echo panicAtlasE($furiaGolemHit); ?>" alt="" width="820" height="680" loading="lazy" decoding="async"><?php } ?></span><img class="afu-v3-fig" src="<?php echo panicAtlasE($furiaRf); ?>" alt="" width="831" height="1000" loading="lazy" decoding="async"></div>
        <?php } else { ?><picture><source media="(max-width:600px)" srcset="<?php echo $furiaArt; ?>furia-world-v3-mobile.webp" width="960" height="1008"><img src="<?php echo $furiaArt; ?>furia-world-v3-960.webp" alt="La energía roja de la Furia quiebra el suelo de un escenario de MU PANIC" width="960" height="400" loading="lazy" decoding="async"></picture><?php } ?>
        <div>
            <span class="eyebrow">ATLAS / SISTEMA EXCLUSIVO</span>
            <h2 id="atlas-furia-title">La <em>Furia.</em></h2>
            <p>Peleá para cargar la barra roja. Cuando está llena, apretá <kbd>Shift</kbd> y tu personaje entra en Furia: más velocidad, más daño y un poder propio de tu clase. Cada enemigo que cae te da más tiempo.</p>
        </div>
    </div>

    <div class="atlas-furia-stats" aria-label="Valores principales de la Furia">
        <div><span>DURACIÓN BASE</span><strong>15 s</strong><small>+1 s por cada muerte</small></div>
        <div><span>TOPE</span><strong>40 s</strong><small>Contados desde que la activás</small></div>
        <div><span>VELOCIDAD DE ATAQUE</span><strong>+20%</strong><small>Sobre la tuya</small></div>
        <div><span>DAÑO A MONSTRUOS</span><strong>+60%</strong><small>Daño extra en cada golpe</small></div>
    </div>

    <h3>1. Cargá la barra.</h3>
    <p>La barra de Furia se llena peleando. Cuenta el daño, no el nivel: carga igual de rápido en un mapa inicial que en uno avanzado.</p>
    <div class="wiki-table-scroll"><table><thead><tr><th>Qué hacés</th><th>Cuánto carga</th></tr></thead><tbody>
        <tr><td>Pegarle a monstruos</td><td>2% por cada monstruo completo (sacarle toda la vida)</td></tr>
        <tr><td>Pegarle a un jugador</td><td>25% por sacarle el equivalente a toda su vida + SD</td></tr>
        <tr><td>Matar a un jugador</td><td>20% extra</td></tr>
        <tr><td>Recibir daño</td><td>25% por perder el equivalente a toda tu vida + SD</td></tr>
    </tbody></table></div>
    <p class="wiki-note">Un solo golpe carga como máximo el 10%. La carga no se pierde: queda guardada si dejás de pelear, si te desconectás o si cambiás de servidor. Mientras la Furia está activa, la barra no carga.</p>

    <h3>2. Desatala.</h3>
    <p>Con la barra llena aparece <strong>FURIA LISTA</strong>. Apretá y soltá <kbd>Shift</kbd> solo, sin otra tecla. Todo el servidor ve el aviso de que entraste en Furia y tu personaje se cubre con un aura roja.</p>

    <h3>3. No pares.</h3>
    <p>La Furia dura 15 segundos. Cada monstruo que matás le suma 1 segundo, y cada golpe a un jugador también suma 1 segundo (como mucho uno por segundo). Nunca pasa de 40 segundos desde que la activaste.</p>
    <div class="atlas-furia-combos">
        <figure<?php echo $furiaV3 ? ' class="is-v3"' : ''; ?>><?php if($furiaV3) { ?><b class="afu-v3-tier">COMBO</b><?php } else { ?><img src="<?php echo $furiaArt; ?>furia-ui-combo-v3.webp" alt="COMBO" width="1024" height="256" loading="lazy" decoding="async"><?php } ?><figcaption><strong>5</strong> muertes</figcaption></figure>
        <figure<?php echo $furiaV3 ? ' class="is-v3"' : ''; ?>><?php if($furiaV3) { ?><b class="afu-v3-tier">IMPARABLE</b><?php } else { ?><img src="<?php echo $furiaArt; ?>furia-ui-imparable-v3.webp" alt="IMPARABLE" width="1024" height="256" loading="lazy" decoding="async"><?php } ?><figcaption><strong>12</strong> muertes</figcaption></figure>
        <figure<?php echo $furiaV3 ? ' class="is-v3"' : ''; ?>><?php if($furiaV3) { ?><b class="afu-v3-tier">MASACRE</b><?php } else { ?><img src="<?php echo $furiaArt; ?>furia-ui-masacre-v3.webp" alt="MASACRE" width="1024" height="256" loading="lazy" decoding="async"><?php } ?><figcaption><strong>20</strong> muertes</figcaption></figure>
    </div>
    <p>El contador de la pantalla suma los monstruos y jugadores que matás durante la Furia y el daño extra que hiciste.</p>

    <h3>El poder de cada clase.</h3>
    <p>Se suma a los valores de tu personaje mientras dura la Furia. Vale contra jugadores y contra monstruos.</p>
    <div class="wiki-table-scroll"><table><thead><tr><th>Clase</th><th>Poder durante la Furia</th></tr></thead><tbody>
        <?php foreach($furiaClasses as $furiaClass) { ?><tr><td><?php echo $furiaClass[0]; ?></td><td><?php echo $furiaClass[1]; ?></td></tr><?php } ?>
    </tbody></table></div>
    <p class="wiki-note">La Furia vale en todos los mapas, también en la arena, los duelos, Castle Siege y los eventos. Los valores pueden ajustarse con el balance del servidor.</p>
</section>
