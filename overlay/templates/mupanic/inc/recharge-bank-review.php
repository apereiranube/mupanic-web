<?php
if(!defined('access') || !access || !panicRechargeAdminAllowed()) { http_response_code(403); return; }
$adminUrl=__BASE_URL__.'usercp/shopadmin/';
$bankReviewOnly=true;
?>
<section class="recharge-shop shop-management shop-management--v6 bank-review-page" aria-labelledby="bank-review-title">
<header class="recharge-hero"><span class="eyebrow">MU PANIC / ADMINISTRACIÓN</span><h2 id="bank-review-title">Transferencias <em>por aprobar.</em></h2><p>Revisá los comprobantes y confirmá los ingresos bancarios.</p></header>
<nav class="recharge-sections" aria-label="Administración de transferencias"><a href="<?= panicAccountEscape(__BASE_URL__.'usercp/') ?>">← Volver a Mi cuenta</a><a href="<?= panicAccountEscape($adminUrl) ?>">Configuración de tienda</a><a href="<?= panicAccountEscape($adminUrl.'#shop-bank') ?>">Cuenta receptora</a><a href="<?= panicAccountEscape($adminUrl.'?transferencias=pendientes') ?>" aria-current="page">Por aprobar</a></nav>
<?php require __DIR__.'/recharge-bank-admin.php'; ?>
</section>
