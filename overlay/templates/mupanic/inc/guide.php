<?php
if(!defined('access') or !access) die();
$wiki = $publicBalance;
// The deployed single VIP plan uses AccountLevel 1. Preserve its rate index.
$wiki['accounts'] = array_slice($wiki['accounts'], 0, 2);
$wiki['accounts'][1]['name'] = 'VIP';
// Match the administrator-confirmed name shown by this server's client.
foreach($wiki['maps'] as &$atlasMap) {
    if($atlasMap['id'] === 57) {
        $atlasMap['aliases'] = array_values(array_unique([$atlasMap['name'], 'Raklion']));
        $atlasMap['name'] = 'LaCleon';
        foreach($atlasMap['moves'] as &$atlasMove) $atlasMove['name'] = str_ireplace('Raklion', 'LaCleon', $atlasMove['name']);
        unset($atlasMove);
    }
}
unset($atlasMap);

// Comments in ItemDrop may include administrator release labels.
foreach($wiki['drops'] as &$drop) {
    $drop['name'] = preg_replace('/^(?:(?:REGIONAL|GLOBAL RARO)\s+V[\d.]+\s+INTEGRAL|PILAR\s*\d+\s+.+?\s+MIX)\s+/iu', '', $drop['name']);
    $drop['name'] = preg_replace('/\s+-\s+Kanturu\s+1\s+V[\d.]+$/iu', '', $drop['name']);
}
unset($drop);

$atlasAssets = json_decode(file_get_contents(__DIR__.'/atlas-assets.json'), true);
$recipes = json_decode(file_get_contents(__DIR__.'/crafting-recipes.json'), true);
$wiki['recipes'] = $recipes;
$wiki['vinculos'] = json_decode(file_get_contents(__DIR__.'/atlas-vinculos.json'), true);
// Public names stay stable while live ItemDrop conditions and rates remain untouched.
foreach($wiki['drops'] as &$drop) {
    if($drop['id'] === 7368) $drop['name'] = 'Fragmentos de Vínculo';
    if($drop['id'] === 7369) $drop['name'] = 'Núcleos de Evolución';
}
unset($drop);
$wiki['portraits'] = $atlasAssets['monsters'] ?? [];
function panicWikiEscape($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function panicWikiRate($value) { return rtrim(rtrim(number_format($value, 6, ',', ''), '0'), ',').'%'; }
$mapNames = array(); foreach($wiki['maps'] as $map) $mapNames[$map['id']] = $map['name'];
// Event arenas have no fixed population in the normal-map explorer.
foreach([9=>'Devil Square',32=>'Devil Square',52=>'Blood Castle 8',53=>'Chaos Castle 7'] as $id=>$name) $mapNames[$id] = $name;
for($i=11;$i<=17;$i++) $mapNames[$i] = 'Blood Castle '.($i-10);
for($i=18;$i<=23;$i++) $mapNames[$i] = 'Chaos Castle '.($i-17);
?>
<div class="panic-wiki" data-wiki>
    <script>
    (function(script){
        var root=script.parentElement, hash=location.hash.slice(1), section='inicio';
        if(/^(mapa-|spot-|mob-|mob-list-)/.test(hash)) section='progresion';
        else if(/^recompensa-/.test(hash)) section='recompensas';
        else if(/^evento-/.test(hash)) section='eventos';
        else if(/^crear-/.test(hash)) section='taller';
        else if(/^equipo-/.test(hash)) section='equipo';
        else if(/^drop-/.test(hash)) section='drops';
        else if(/^vinculo-/.test(hash)) section='vinculos';
        else if(['vinculos','inicio','progresion','buscar','recompensas','eventos','primeros-pasos','drops','rates','sistemas','taller','mejoras','equipo'].indexOf(hash)>=0) section=hash;
        root.dataset.atlasInitial=section;root.classList.add('wiki-enhanced','atlas-boot');
        window.setTimeout(function(){if(root.classList.contains('atlas-boot')) root.classList.remove('atlas-boot','wiki-enhanced');},5000);
    })(document.currentScript);
    </script>
    <aside class="wiki-nav">
        <span class="eyebrow">EXPLORÁ MU PANIC</span>
        <label for="wiki-search">¿Qué estás buscando?</label><input id="wiki-search" type="search" placeholder="Chaos, Lorencia, reset…" autocomplete="off">
        <div class="wiki-search-results" hidden></div><p class="wiki-search-status" role="status" aria-live="polite"></p>
        <nav aria-label="Guía de MU PANIC">
            <a href="#inicio"><span>Inicio del Atlas</span> →</a><a href="#progresion"><span>Mapas y spots</span> →</a><a href="#buscar"><span>Buscar objetos y mobs</span> →</a><a href="#recompensas"><span>Bosses y recompensas</span> →</a><a href="#eventos"><span>Eventos y agenda</span> →</a><a href="#vinculos"><span>Vínculos de PANIC</span> →</a>
            <details class="atlas-learn-nav"><summary>Aprender a jugar</summary><a href="#primeros-pasos"><span>Primeros pasos</span> →</a><a href="#sistemas"><span>Reset y mejoras</span> →</a><a href="#taller"><span>Crear objetos y alas</span> →</a><a href="#mejoras"><span>Mejorar mi equipo</span> →</a><a href="#equipo"><span>Tipos de equipo</span> →</a><a href="#rates"><span>Experiencia y tasas</span> →</a><a href="#drops"><span>Objetos y drops</span> →</a></details>
        </nav>
        <div class="wiki-saved" hidden><span class="eyebrow">TUS MAPAS GUARDADOS</span><div data-saved-maps></div></div><p class="wiki-date">Datos del servidor<br>Revisión: <?php echo panicWikiEscape($wiki['revision']); ?></p>
    </aside>
    <div class="wiki-content">
        <?php include(__DIR__.'/atlas-home.php'); ?>
        <?php include(__DIR__.'/atlas-search.php'); ?>
        <?php include(__DIR__.'/atlas-events.php'); ?>
        <?php include(__DIR__.'/atlas-vinculos.php'); ?>
        <section id="primeros-pasos" class="wiki-section" data-wiki-search>
            <span class="eyebrow">01 / EMPEZÁ ACÁ</span><h2>Entrá al continente.</h2>
            <ol class="wiki-route"><li><strong>Prepará tu cuenta y el cliente.</strong><p>Registrate y descargá el cliente de MU PANIC desde Descargas. Conservá tus datos de acceso.</p><a href="<?php echo __BASE_URL__; ?><?php echo $isLogged ? 'usercp/' : 'register/'; ?>"><?php echo $isLogged ? 'Mi cuenta' : 'Crear cuenta'; ?> →</a> <a href="<?php echo __BASE_URL__; ?>downloads/">Descargas →</a></li><li><strong>Conocé tu personaje.</strong><p>Empezá en Lorencia, Noria o Elbeland. Equipá las habilidades de tu clase, llevá pociones y probá un spot inicial.</p></li><li><strong>Buscá un spot que puedas sostener.</strong><p>Un spot es una zona con monstruos que reaparecen. Si gastás más pociones de las que podés reponer o tardás demasiado en matar, volvé a una zona más tranquila. El nivel de traslado no mide la dificultad del mapa.</p><a href="#progresion">Elegir mapa y coordenadas →</a></li><li><strong>Prepará el siguiente salto.</strong><p>Guardá Zen para moverte y resetear, y separá las joyas de los objetos que vas a vender. No confirmes una mezcla sin revisar sus ingredientes y su probabilidad dentro del juego.</p><a href="#sistemas">Entender el reset →</a></li></ol>
        </section>
        <?php include(__DIR__.'/atlas-maps.php'); ?>
        <?php include(__DIR__.'/atlas-drops.php'); ?>
        <?php if(!empty($wiki['eventBags'])) { include(__DIR__.'/reward-lists.php'); } ?>
        <section id="rates" class="wiki-section" data-wiki-search><span class="eyebrow">04 / EL RITMO DE PANIC</span><h2>Experiencia por etapas.</h2><div class="wiki-table-scroll"><table><thead><tr><th>Cuenta</th><th>EXP base</th><th>Master base</th><th>Drop general</th></tr></thead><tbody><?php foreach($wiki['accounts'] as $account) { ?><tr><td><?php echo $account['name']; ?></td><td><?php echo $account['experience']; ?>x</td><td><?php echo $account['master']; ?>x</td><td><?php echo $account['drop']; ?>%</td></tr><?php } ?></tbody></table></div><p>La EXP base cambia con el tramo de reset. Party, eventos y otros bonos pueden alterar la experiencia recibida. Para EXP Master se requieren monstruos de nivel <?php echo $wiki['masterMonsterMin']; ?> o superior, además de los requisitos de progresión Master del personaje.</p><div class="wiki-table-scroll"><table><thead><tr><th>Resets actuales</th><th>Factor del tramo</th><th>EXP Free sin otros bonos</th></tr></thead><tbody><?php foreach($wiki['experience'] as $step) { ?><tr><td><?php echo $step['min'].'–'.$step['max']; ?></td><td><?php echo $step['percent']; ?>%</td><td><?php echo rtrim(rtrim(number_format($wiki['accounts'][0]['experience']*$step['percent']/100,2,',',''),'0'),','); ?>x</td></tr><?php } ?></tbody></table></div></section>
        <section id="sistemas" class="wiki-section" data-wiki-search><span class="eyebrow">05 / PREPARÁ EL REGRESO</span><h2>Reset, Zen y mejoras.</h2><p>El comando <code>/reset</code> vuelve a nivel 1 y reinicia los puntos distribuidos. El límite configurado es de 20 resets. Conservás habilidades y quests; la tabla de etapas define el nivel, Zen y puntos de cada tramo.</p><div class="wiki-table-scroll"><table><thead><tr><th>Resets actuales</th><th>Nivel</th><th>Zen del tramo</th><th>Puntos del tramo</th></tr></thead><tbody><?php foreach($wiki['resets'] as $step) { ?><tr><td><?php echo $step['min'].'–'.$step['max']; ?></td><td><?php echo $step['level']; ?></td><td><?php echo number_format($step['zen'],0,',','.'); ?></td><td><?php echo $step['points']; ?></td></tr><?php } ?></tbody></table></div><p class="wiki-note">Antes de ejecutar el reset, revisá los requisitos de objetos dentro del juego. Los puntos de tramo no representan por sí solos el total final de estadísticas. El Master Reset tiene reglas propias: confirmá sus requisitos y efectos en el juego antes de utilizarlo.</p><h3>Que cada recurso tenga un destino.</h3><div class="atlas-resource-grid"><div><h4>Zen</h4><p>Reservá el costo del próximo reset y tus traslados antes de mejorar equipo.</p></div><div><h4>Joyas</h4><p>Usá la tabla de drops para elegir monstruos elegibles.</p></div><div><h4>Alas y combinaciones</h4><p>La Chaos Machine muestra ingredientes y probabilidad; la tasa puede depender de lo que agregás. Revisala antes de confirmar.</p></div></div><h3>Hablar el idioma de MU.</h3><dl class="wiki-glossary"><dt>Spot</dt><dd>Zona de aparición de monstruos para entrenar.</dd><dt>Party</dt><dd>Grupo de jugadores que coordina combate y progresión.</dd><dt>Drop</dt><dd>Objeto que deja un monstruo al morir.</dd><dt>Reset</dt><dd>Reinicio del nivel bajo las condiciones del servidor.</dd><dt>Excellent / Ancient / Socket</dt><dd>Tipos de equipo con opciones adicionales. Su obtención tiene reglas específicas; no se deduce del porcentaje general de drop.</dd></dl></section>
        <?php include(__DIR__.'/workshop.php'); ?>
        <p class="wiki-empty" hidden>No encontramos coincidencias. Probá con el nombre de un mapa, una joya o «reset».</p>
    </div>
</div>

<script type="application/json" id="wiki-data"><?php echo json_encode($wiki, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
