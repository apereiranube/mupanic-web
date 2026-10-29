<?php
if(!defined('access') || !access) die();
require_once __DIR__.'/recharge-wallet.php';
require_once __DIR__.'/recharge-vip-status.php';
require_once __DIR__.'/recharge-management.php';
require_once __DIR__.'/referidos-cola.php';
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
    require_once __DIR__.'/tienda-art.php';
    $fondo=panicTiendaArt('tienda-fondo'); $emblem=panicTiendaEmblemaVip(); $packArt=panicTiendaArt('tienda-pack-3');
    $quick=[]; foreach(['reset','myaccount','addstats','mypassword'] as $k) if(isset($links[$k])) $quick[]=$links[$k];
    $beasts=[]; foreach(['theryon','nerathys','vaeraxes'] as $slug) { $art=panicTiendaArt('tienda-'.$slug); if($art) $beasts[$slug]=$art; }
    ?>
    <section class="account-dashboard cta-home" aria-labelledby="account-home-title">
      <header class="cta-hero<?php echo $fondo?' has-fondo':''; ?>">
        <div class="cta-hero-block" aria-hidden="true"><?php if($fondo) { ?><i class="cta-hero-fondo" style="background-image:url('<?php echo panicAccountEscape($fondo); ?>')"></i><?php } ?></div>
        <div class="cta-hero-copy">
          <p class="cta-kicker">Tu centro de mando</p>
          <h2 class="cta-hero-title" id="account-home-title">Hola, <span><?php echo panicAccountEscape($name); ?></span></h2>
          <p class="cta-hero-lead">Tu cuenta, tus personajes y todo lo que viene.</p>
        </div>
        <div class="cta-hero-stats">
          <a class="cta-stat cta-stat--eryns" href="<?php echo panicAccountEscape(__BASE_URL__.'usercp/recharge/'); ?>"><small>Tus Eryns</small><strong><?php echo panicTiendaGemaImg(40); ?><span<?php if($balance!==null) { ?> data-count="<?php echo (int)$balance; ?>"<?php } ?>><?php echo $balance===null?'No disponible':number_format($balance,0,',','.'); ?></span></strong><em>Recargar Eryns →</em></a>
          <a class="cta-stat cta-stat--vip<?php echo $vipActive?' is-on':''; ?>" href="<?php echo panicAccountEscape(__BASE_URL__.'usercp/vip/'); ?>"><?php if($emblem) { ?><img class="cta-stat-emblem" src="<?php echo panicAccountEscape($emblem); ?>" alt="" width="900" height="872" loading="lazy" decoding="async"><?php } ?><small>Tu membresía</small><strong><?php echo $vip===null?'No disponible':($vipActive?'VIP activo':'Cuenta normal'); ?></strong><em><?php echo $vipActive?$vip['days'].' días restantes':'Conocé el VIP →'; ?></em></a>
        </div>
      </header>
      <?php if(panicReferidosHayPorConfirmar($name)) { ?><a class="account-ref-callout cta-callout" href="<?php echo panicAccountEscape(__BASE_URL__.'usercp/referidos/'); ?>"><span><strong>Te invitó un amigo</strong><small>Confirmalo y arrancá con 3 días de VIP.</small></span><b>Confirmar →</b></a><?php } ?>

      <section class="cta-quick" aria-label="Accesos rápidos">
        <a class="cta-tile cta-tile--shop<?php echo $packArt?' has-art':''; ?>" href="<?php echo panicAccountEscape(__BASE_URL__.'usercp/recharge/'); ?>"><?php if($packArt) { ?><img class="cta-tile-art" src="<?php echo panicAccountEscape($packArt); ?>" alt="" width="600" height="600" loading="lazy" decoding="async"><?php } ?><small>Tienda de Eryns</small><strong>Recargá y elegí tu pack</strong><span>Packs, bestias y VIP →</span></a>
        <a class="cta-tile cta-tile--vip" href="<?php echo panicAccountEscape($vipHref); ?>"><small>VIP PANIC</small><strong><?php echo $vipConfigured?($vipActive?'Extendé tu VIP':'Activá tu VIP'):'Conocé el VIP'; ?></strong><span><?php echo $vipActive?'Seguís con todos los beneficios →':'Más experiencia, más drop →'; ?></span></a>
        <a class="cta-tile cta-tile--ref" href="<?php echo panicAccountEscape(__BASE_URL__.'usercp/referidos/'); ?>"><small>Referidos</small><strong>Invitá y ganá VIP</strong><span>Tu link de invitación →</span></a>
        <?php foreach($quick as $item) { ?><a class="cta-tile cta-tile--tool" href="<?php echo panicAccountEscape($item['href']); ?>"<?php if($item['newtab']) echo ' target="_blank" rel="noopener"'; ?>><?php echo panicAccountIcon($item['icon'],$item['title']); ?><strong><?php echo panicAccountEscape($item['title']); ?></strong></a><?php } ?>
      </section>

      <section id="account-home-characters" class="account-home-section cta-sec"><div class="cta-sec-head"><div><p class="cta-kicker">Tu historia en MU PANIC</p><h3 class="cta-h">Tus personajes</h3></div><?php if(isset($links['myaccount'])) { ?><a class="cta-link" href="<?php echo panicAccountEscape($links['myaccount']['href']); ?>">Ver mi cuenta →</a><?php } ?></div>
        <?php if($characters===null) { ?><p class="account-empty cta-empty">No pudimos consultar tus personajes. Volvé a abrir el panel para actualizar.</p><?php } elseif(!$characters) { ?><div class="account-empty cta-empty"><strong>Tu historia empieza en Lorencia.</strong><p>Creá tu primer personaje dentro del juego.</p><a class="cta-link" href="<?php echo panicAccountEscape(__BASE_URL__.'downloads/'); ?>">Descargar el cliente →</a></div><?php } else { ?><div class="account-roster cta-roster"><?php foreach($characters as $character) { $class=$classes[(int)floor((int)$character['class']/16)*16] ?? ['avatar','Personaje']; ?><article class="account-roster-card cta-char"><img src="<?php echo __PATH_TEMPLATE__.'img/character-avatars/'.$class[0].'.jpg'; ?>" alt="<?php echo panicAccountEscape($class[1]); ?>" width="120" height="120" loading="lazy"><div><small><?php echo panicAccountEscape($class[1]); ?></small><h4><?php echo panicAccountEscape($character['name']); ?></h4><dl><div><dt>Nivel</dt><dd><?php echo (int)$character['level']; ?></dd></div><div><dt>Resets</dt><dd><?php echo number_format((int)$character['resets'],0,',','.'); ?></dd></div></dl><a href="<?php echo panicAccountEscape(__BASE_URL__.'profile/player/'.rawurlencode($character['name']).'/'); ?>">Ver personaje →</a></div></article><?php } ?></div><?php } ?>
      </section>

      <?php if(count($beasts)===3) { ?><a class="cta-beasts" href="<?php echo panicAccountEscape(__BASE_URL__.'usercp/recharge/#eryns-bestias'); ?>"><span class="cta-beasts-copy"><small>La próxima leyenda puede ser tuya</small><strong>Las bestias primordiales</strong><em>Ver en la Tienda →</em></span><span class="cta-beasts-art" aria-hidden="true"><?php foreach($beasts as $slug=>$art) { ?><img class="cta-beast--<?php echo $slug; ?>" src="<?php echo panicAccountEscape($art); ?>" alt="" width="1200" height="1200" loading="lazy" decoding="async"><?php } ?></span></a><?php } ?>

      <section class="cta-sec cta-tools" aria-labelledby="cta-tools-title"><div class="cta-sec-head"><div><p class="cta-kicker">Todo en un solo lugar</p><h3 class="cta-h" id="cta-tools-title">Herramientas de tu cuenta</h3></div></div>
        <div class="cta-tools-grid"><?php foreach(['Personajes','Eryns y VIP','Seguridad','Más opciones','Administración'] as $group) { $groupItems=array_filter($items,function($item)use($group){return $item['group']===$group;});if(!$groupItems)continue; ?><div class="account-tool-section cta-tools-col"><h4><?php echo panicAccountEscape($group); ?></h4><?php foreach($groupItems as $item) { ?><a class="account-tool cta-tool-row" href="<?php echo panicAccountEscape($item['href']); ?>"<?php if($item['newtab'])echo ' target="_blank" rel="noopener"'; ?>><?php echo panicAccountIcon($item['icon'],$item['title']); ?><span><strong><?php echo panicAccountEscape($item['title']); ?></strong><small><?php echo panicAccountEscape($item['copy']); ?></small></span></a><?php } ?></div><?php } ?></div>
      </section>
      <p class="account-data-note cta-note">Saldo, VIP y personajes consultados al abrir el panel.</p>
    </section>
    <?php
}
