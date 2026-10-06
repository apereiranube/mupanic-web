<?php if(!defined('access') or !access) die(); ?>
<section id="recompensas" class="wiki-section">
    <span class="eyebrow">BOSSES, CAJAS Y OBJETOS</span><h2>Recompensas posibles.</h2>
    <p>Buscá un boss, una caja o un objeto para consultar su lista de recompensas. La lista incluye posibilidades, no objetos garantizados. Los grupos de selección y las condiciones de cada recompensa afectan el resultado.</p>
    <label class="wiki-control">Buscar recompensa<input type="search" data-reward-filter placeholder="Selupan, Kundun, Harmony…"></label>
    <div class="wiki-quick" aria-label="Tipo de recompensa"><button type="button" data-reward-kind="" aria-pressed="true">Todo</button><button type="button" data-reward-kind="boss" aria-pressed="false">Bosses y criaturas</button><button type="button" data-reward-kind="box" aria-pressed="false">Cajas y objetos</button></div>
    <p data-reward-status role="status" aria-live="polite"></p>
    <div class="wiki-map-list">
    <?php $rewardBags = array_filter($wiki['eventBags'], function($bag) { return $bag['monster'] >= 0 || $bag['item'] >= 0; }); include(__DIR__.'/atlas-reward-cards.php'); ?>
    </div>
    <p class="atlas-portrait-credit">Imágenes del juego: <a href="https://www.muonline.net/guides/" target="_blank" rel="noopener noreferrer">MuOnline.Net</a> y <a href="https://blackrock.games/index.php?id=guide" target="_blank" rel="noopener noreferrer">Blackrock</a>, <a href="https://bless.gs/en/index.php?page=dropboxes" target="_blank" rel="noopener noreferrer">Bless</a>, <a href="https://wiki.munext.online/db/" target="_blank" rel="noopener noreferrer">MuNext</a>, <a href="https://mureforge.com/about-server/event-drops" target="_blank" rel="noopener noreferrer">MU Reforge</a> y <a href="https://mu.lv/guides/" target="_blank" rel="noopener noreferrer">MU.LV</a>. Assets de MU Online: Webzen. Arte ilustrativo: MU PANIC.</p>
</section>
