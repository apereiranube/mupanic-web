<?php
if(!defined('access') || !access) die();
// Controller helper: no remote calls or writes on GET.
require_once __DIR__.'/recharge-bank.php';
$bankStore=null;$bankConfig=PanicBankTransfers::defaults();$bankCanBuy=false;$bankEnvironment=null;
$bankHistory=['total'=>0,'orders'=>[]];$bankPage=max(1,min(500,(int)($_GET['transferencias'] ?? 1)));
try {
    $bankStore=new PanicBankTransfers();$bankConfig=$bankStore->config();$bankEnvironment=$bankStore->environment();
    $bankWorker=(new PanicRechargeManagement())->worker();
    $bankCanBuy=$bankConfig['enabled'] && PanicBankTransfers::configured($bankConfig) && PanicBankTransfers::workerReady($bankWorker,$bankEnvironment) && $bankStore->botReady($bankEnvironment);
} catch(Throwable $ignored) {}
$bankSelected=($_POST['payment_method'] ?? '')==='bank_transfer';
$bankCuit=is_string($_POST['buyer_cuit'] ?? null)?substr($_POST['buyer_cuit'],0,13):'';
function panicBankShopAction($action,$bankStore,$bankCanBuy,$bankEnvironment,array $bankWorker,$rechargeAccount,array $rechargePackages,array &$shopQuantities,&$shopMessage) {
    if(!$bankStore)throw new RuntimeException('Las transferencias no están disponibles.');
    if($action==='create') {
        if(!$bankCanBuy)throw new RuntimeException('Las transferencias todavía no están habilitadas.');
        if(!is_array($_POST['quantity'] ?? null))throw new InvalidArgumentException('Carrito inválido.');
        $cart=PanicRecharge::cart($rechargePackages,$_POST['quantity']);$shopQuantities=array_replace($shopQuantities,$_POST['quantity']);
        $o=$bankStore->begin($rechargeAccount,$cart,$_POST['recharge_nonce'] ?? null,$_POST['buyer_cuit'] ?? '',$bankEnvironment,$bankWorker,($_POST['accept_terms'] ?? null)==='1'?($_POST['terms_version'] ?? null):null);
        $_SESSION['recharge_nonce']=bin2hex(random_bytes(16));$shopMessage='Transferencia preparada. Los datos y el importe están en Mis transferencias. Adjuntá el comprobante en Discord después de pagar.';
        return $o;
    }
    if($action==='bank_ready') {
        $r=$bankStore->ready(is_string($_POST['order_id'] ?? null)?$_POST['order_id']:'',$rechargeAccount);
        $_SESSION['bank_claim_code']=['id'=>$r['order']['id'],'code'=>$r['code'],'expires'=>time()+1800];
        $shopMessage='Ahora abrí Discord y usá el código de tu compra para adjuntar el comprobante en un ticket privado.';return $r['order'];
    }
    throw new RuntimeException('Acción inválida.');
}
