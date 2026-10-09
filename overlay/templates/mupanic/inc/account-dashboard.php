<?php
if(!defined('access') || !access) die();
require_once __DIR__.'/recharge-wallet.php';
require_once __DIR__.'/recharge-vip-status.php';
require_once __DIR__.'/recharge-management.php';
function panicAccountCharacters($account) {
    if(!preg_match('/^[A-Za-z0-9_]{1,10}$/D',(string)$account)) return null;
    try {
        $db=Connection::Database('MuOnline');
        $rows=$db?$db->query_fetch('SELECT TOP 10 [Name] AS [name], [Class] AS [class], [cLevel] AS [level], [ResetCount] AS [resets] FROM dbo.Character WHERE [AccountID] = ? ORDER BY [Name]',[$account]):null;
        if(!is_array($rows)) return null;
        foreach($rows as $row) {
            if(!isset($row['name'],$row['class'],$row['level'],$row['resets']) || !is_string($row['name']) || !preg_match('/^[A-Za-z0-9_]{1,10}$/D',$row['name'])) return null;
            foreach(['class','level','resets'] as $key) if(!preg_match('/^[0-9]{1,10}$/D',(string)$row[$key])) return null;
        }
        return $rows;
    } catch(Throwable $ignored) { return null; }
}
function panicAccountDashboard() {
    if(!isLoggedIn()) return;
    $name=(string)($_SESSION['username'] ?? '');$items=panicAccountMenuItems();$links=[];foreach($items as $item)$links[$item['key']]=$item;
    $balance=panicRechargeWalletBalance($name);$vip=panicRechargeVipStatus($name);$characters=panicAccountCharacters($name);
    $offer=PanicRechargeManagement::storefrontDefaults()['vip'];try{$offer=(new PanicRechargeManagement())->storefront()['vip'];}catch(Throwable $ignored){}
    $vipActive=$vip!==null && $vip['active'];$vipConfigured=is_int($offer['price_coins']) && $offer['price_coins']>0;
    $vipHref=__BASE_URL__.'usercp/'.($vipConfigured?'recharge/?recharge_goal=vip#recharge-cart':'vip/');
    $classes=[0=>['dw','Dark Wizard'],16=>['dk','Dark Knight'],32=>['elf','Elfa'],48=>['mg','Magic Gladiator'],64=>['dl','Dark Lord'],80=>['sum','Summoner'],96=>['rf','Rage Fighter']];
    ?>
    <section class="account-dashboard" aria-labelledby="account-home-title">
      <header class="account-command-hero"><span class="eyebrow">MU PANIC / TU CENTRO DE MANDO</span><h2 id="account-home-title">Tu próxima<br><em>gran aventura.</em></h2><div class="account-player-line"><?php echo panicAccountIcon('shield'); ?><span>Hola, <strong><?php echo panicAccountEscape($name); ?></strong></span></div><p>Tu cuenta, tus personajes y todo lo que viene.</p></header>
      <div class="account-live-metrics"><a href="<?php echo panicAccountEscape(__BASE_URL__.'usercp/recharge/'); ?>"><?php echo panicAccountIcon('gem'); ?><span><small>TUS ERYNS</small><strong><?php echo $balance===null?'No disponible':number_format($balance,0,',','.'); ?></strong></span><b aria-hidden="true">＋</b></a><a href="<?php echo panicAccountEscape(__BASE_URL__.'usercp/vip/'); ?>"><?php echo panicAccountIcon('crown'); ?><span><small>TU MEMBRESÍA</small><strong><?php echo $vip===null?'No disponible':($vipActive?'VIP activo':'Cuenta normal'); ?></strong><em><?php echo $vipActive?$vip['days'].' días restantes':'Conocé el VIP'; ?></em></span></a><a href="#account-home-characters"><?php echo panicAccountIcon('sword'); ?><span><small>TUS PERSONAJES</small><strong><?php echo $characters===null?'No disponible':count($characters); ?></strong></span><b aria-hidden="true">↓</b></a></div>
      <section class="account-vip-campaign" aria-labelledby="account-vip-promo"><img src="<?php echo __PATH_TEMPLATE__; ?>img/recharge/vip-champion-scene-v5.webp" alt="Caballero de MU con aura dorada" width="810" height="1080" loading="lazy"><div class="account-vip-campaign-copy"><span class="eyebrow"><?php echo $vipActive?'TU MEMBRESÍA / VIP PANIC':'DESCUBRÍ VIP PANIC'; ?></span><h3 id="account-vip-promo"><?php echo $vipActive?'Tu VIP sigue<br>con vos.':'Tu próxima compra en X.<br><em>10% de descuento.</em>'; ?></h3><p><?php echo $vipActive?$vip['days'].' días restantes. Prepará tu próxima renovación.':'Con VIP activo, el descuento te acompaña en la tienda X.'; ?></p><a class="account-gold-button" href="<?php echo panicAccountEscape($vipHref); ?>"><?php echo $vipConfigured?($vipActive?'Extender VIP':'Activá tu VIP'):'Conocé el VIP'; ?> <span aria-hidden="true">→</span></a><a class="account-subtle-link" href="<?php echo panicAccountEscape(__BASE_URL__.'usercp/vip/'); ?>">Ver todos los beneficios</a></div></section>
      <section id="account-home-characters" class="account-home-section"><div class="account-section-heading"><div><span class="eyebrow">TU HISTORIA EN MU PANIC</span><h3>Tus personajes</h3></div><?php if(isset($links['myaccount'])) { ?><a href="<?php echo panicAccountEscape($links['myaccount']['href']); ?>">Ver mi cuenta →</a><?php } ?></div>
        <?php if($characters===null) { ?><p class="account-empty">No pudimos consultar tus personajes. Volvé a abrir el panel para actualizar.</p><?php } elseif(!$characters) { ?><div class="account-empty"><strong>Tu historia empieza en Lorencia.</strong><p>Creá tu primer personaje dentro del juego.</p><a href="<?php echo panicAccountEscape(__BASE_URL__.'downloads/'); ?>">Descargar el cliente →</a></div><?php } else { ?><div class="account-roster"><?php foreach($characters as $character) { $class=$classes[(int)floor((int)$character['class']/16)*16] ?? ['avatar','Personaje']; ?><article class="account-roster-card"><img src="<?php echo __PATH_TEMPLATE__.'img/character-avatars/'.$class[0].'.jpg'; ?>" alt="<?php echo panicAccountEscape($class[1]); ?>" width="120" height="120" loading="lazy"><div><small><?php echo panicAccountEscape($class[1]); ?></small><h4><?php echo panicAccountEscape($character['name']); ?></h4><dl><div><dt>Nivel</dt><dd><?php echo (int)$character['level']; ?></dd></div><div><dt>Resets</dt><dd><?php echo number_format((int)$character['resets'],0,',','.'); ?></dd></div></dl><a href="<?php echo panicAccountEscape(__BASE_URL__.'profile/player/'.rawurlencode($character['name']).'/'); ?>">Ver personaje →</a></div></article><?php } ?></div><?php } ?>
      </section>
      <?php foreach(['Personajes','Eryns y VIP','Seguridad','Más opciones','Administración'] as $group) { $groupItems=array_filter($items,function($item)use($group){return $item['group']===$group;});if(!$groupItems)continue; ?><section class="account-tool-section"><div class="account-section-heading"><div><span class="eyebrow"><?php echo $group==='Personajes'?'PREPARÁ TU PRÓXIMO PASO':($group==='Seguridad'?'TU CUENTA, BAJO CONTROL':'TODO EN UN SOLO LUGAR'); ?></span><h3><?php echo panicAccountEscape($group); ?></h3></div></div><div class="account-tools-grid"><?php foreach($groupItems as $item) { ?><a class="account-tool" href="<?php echo panicAccountEscape($item['href']); ?>"<?php if($item['newtab'])echo ' target="_blank" rel="noopener"'; ?>><span class="account-tool-icon"><?php echo panicAccountIcon($item['icon']); ?></span><span><strong><?php echo panicAccountEscape($item['title']); ?></strong><small><?php echo panicAccountEscape($item['copy']); ?></small></span><b aria-hidden="true">→</b></a><?php } ?></div></section><?php } ?>
      <section class="account-legend-teaser"><div><span class="eyebrow">LA PRÓXIMA LEYENDA PUEDE SER TUYA</span><h3>Elegí lo que viene.</h3><a href="<?php echo panicAccountEscape(__BASE_URL__.'usercp/recharge/'); ?>">Explorá la tienda de Eryns →</a></div><div class="account-legend-scenes" aria-hidden="true"><?php foreach(['theryon','nerathys','vaeraxes'] as $slug) { ?><img src="<?php echo __PATH_TEMPLATE__.'img/recharge/'.$slug.'-scene-v5.webp'; ?>" alt="" width="810" height="1080" loading="lazy"><?php } ?></div></section>
      <p class="account-data-note">Saldo, VIP y personajes consultados al abrir el panel.</p>
    </section>
    <?php
}
