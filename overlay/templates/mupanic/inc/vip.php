<?php
if(!defined('access') || !access) die();
if(!isLoggedIn()) return;
$vipAccount=(string)($_SESSION['username'] ?? '');
$vipMembership=null;
if(preg_match('/^[A-Za-z0-9_]{1,10}$/D',$vipAccount)) {
    try {
        $db=Connection::Database('MuOnline');
        $rows=$db?$db->query_fetch('SELECT CASE WHEN AccountLevel BETWEEN 1 AND 3 AND AccountExpireDate > GETDATE() THEN 1 ELSE 0 END AS active, CONVERT(varchar(19),AccountExpireDate,120) AS expiry FROM [MuOnline43].[dbo].[MEMB_INFO] WHERE memb___id = ?',[$vipAccount]):null;
        if(is_array($rows) && count($rows)===1 && isset($rows[0]['active'],$rows[0]['expiry']) && in_array($rows[0]['active'],[0,1,'0','1'],true) && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/D',(string)$rows[0]['expiry'])) {
            $vipMembership=['active'=>(bool)$rows[0]['active'],'expiry'=>$rows[0]['expiry']];
        }
    } catch(Throwable $ignored) {}
}
?>
<section class="panic-vip" aria-labelledby="vip-title">
    <header class="vip-heading"><span class="eyebrow">MU PANIC / TU CUENTA</span><h2 id="vip-title">Un solo <em>VIP.</em><br>Un impulso para tu aventura.</h2><p>Más EXP y una mejor tasa de drop. Los beneficios se aplican a todos los personajes de tu cuenta.</p></header>
    <div class="vip-layout">
        <article class="vip-offer">
            <span class="vip-emblem" aria-hidden="true"><?php echo panicAccountIcon('crown'); ?></span>
            <span class="vip-badge">VIP</span><h3>30 días para seguir creciendo.</h3>
            <p class="vip-price"><strong>25.000</strong><span>WCoin C / 30 días</span></p>
            <div class="vip-benefits"><div><span>EXP</span><strong>15 <small>→</small> 20</strong><p>5 puntos más de tasa</p></div><div><span>DROP</span><strong>25 <small>→</small> 30</strong><p>5 puntos más de tasa</p></div></div>
            <details class="vip-buy-guide"><summary>Cómo comprar en el juego <span aria-hidden="true">+</span></summary><ol><li>Ingresá con un personaje de esta cuenta.</li><li>Abrí <b>MENU → corona → Comprar VIP</b>.</li><li>Revisá que la oferta diga <b>VIP · 30 días · 25.000 WC</b> y elegí Comprar VIP.</li></ol><p>Necesitás tener las 25.000 WCoin C disponibles antes de comprar.</p></details>
            <a class="recharge-button recharge-button-secondary" href="<?php echo panicAccountEscape(__BASE_URL__.'usercp/recharge/'); ?>">Recargar WCoin C →</a>
        </article>
        <aside class="vip-account-panel" aria-labelledby="vip-account-title"><span class="eyebrow">ESTADO DE TU CUENTA</span><h3 id="vip-account-title"><?php echo panicAccountEscape($vipAccount); ?></h3>
            <?php if($vipMembership===null) { ?><p class="vip-state">No pudimos consultar tu VIP</p><p>Volvé a abrir esta página para consultar el estado.</p><?php } elseif($vipMembership['active']) { ?><span class="vip-badge"><?php echo panicAccountIcon('crown'); ?> VIP activo</span><p>Vence el <strong><?php echo panicAccountEscape(substr($vipMembership['expiry'],8,2).'/'.substr($vipMembership['expiry'],5,2).'/'.substr($vipMembership['expiry'],0,4)); ?></strong> a las <?php echo panicAccountEscape(substr($vipMembership['expiry'],11,5)); ?> (hora del servidor).</p><?php } else { ?><p class="vip-state">Cuenta normal</p><p>Tu cuenta usa EXP 15 y drop 25. Al activar VIP, pasa a EXP 20 y drop 30.</p><?php } ?>
            <table class="vip-comparison"><caption>Normal y VIP, lado a lado</caption><thead><tr><th scope="col">Beneficio</th><th scope="col">Normal</th><th scope="col">VIP</th></tr></thead><tbody><tr><th scope="row">EXP</th><td>15</td><td>20</td></tr><tr><th scope="row">Drop</th><td>25</td><td>30</td></tr><tr><th scope="row">Duración</th><td>Siempre</td><td>30 días</td></tr></tbody></table>
            <details class="vip-faq"><summary>¿Qué significan estos números?</summary><p>Son tasas configuradas del servidor. VIP suma 5 puntos a cada una; no es un aumento relativo del 5%. La EXP final también depende de las reglas del mapa y del personaje.</p><p>El drop corresponde a la tasa general. Las recompensas de bosses y eventos tienen sus propias tablas. Master EXP conserva su configuración.</p></details>
            <p class="vip-status-note">Estado consultado al abrir esta página.</p>
        </aside>
    </div>
</section>
