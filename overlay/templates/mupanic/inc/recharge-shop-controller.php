<?php
if(!defined('access') || !access) die();
require_once __DIR__.'/recharge-orders.php';
$shopStore=null; $shopSettings=null; $shopCanBuy=false; $shopMessage=null; $shopCreated=null;
$shopHistory=['total'=>0,'orders'=>[]]; $shopPage=max(1,min(500,(int)($_GET['compras'] ?? 1)));
$shopAdmin=function_exists('config') && is_array(config('admins',true)) && array_key_exists($rechargeAccount,config('admins',true));
try {
    $shopStore=new PanicRechargeOrders();
    [$shopApi,$shopMerchant,$shopSettings]=panicRechargeShopGateway();
    $shopCanBuy=panicRechargeCanBuy($shopSettings,$rechargeAccount,(bool)$shopStore->token());
} catch(Throwable $ignored) { /* Closed by default; never publish configuration or secrets. */ }
if(!is_string($_SESSION['recharge_csrf'] ?? null)) $_SESSION['recharge_csrf']=bin2hex(random_bytes(32));
if(!is_string($_SESSION['recharge_nonce'] ?? null)) $_SESSION['recharge_nonce']=bin2hex(random_bytes(16));
$shopQuantities=[]; foreach($rechargePackages as $offer) $shopQuantities[$offer['id']]='0';
if(($_SERVER['REQUEST_METHOD'] ?? '')==='POST' && isset($_POST['recharge_action'])) {
    try {
        if(!is_string($_POST['recharge_csrf'] ?? null) || !hash_equals($_SESSION['recharge_csrf'],$_POST['recharge_csrf'])) throw new RuntimeException('Session expired');
        if(!$shopStore || !$shopSettings) throw new RuntimeException('Shop unavailable');
        $action=$_POST['recharge_action'];
        if($action==='create') {
            if(!$shopCanBuy) throw new RuntimeException('Shop closed');
            if(!is_array($_POST['quantity'] ?? null)) throw new InvalidArgumentException('Invalid cart');
            $cart=PanicRecharge::cart($rechargePackages,$_POST['quantity']);
            $shopQuantities=array_replace($shopQuantities,$_POST['quantity']);
            // A repeated nonce returns the same durable order, including after timeout.
            $shopCreated=$shopStore->begin($rechargeAccount,$cart,$_POST['recharge_nonce'] ?? null,$shopSettings['environment']==='production',$shopApi,__BASE_URL__,$shopSettings['recharges']['amount_unit'] ?? null);
            $_SESSION['recharge_nonce']=bin2hex(random_bytes(16));
            $shopMessage=isset($shopCreated['checkout_url'])?'Tu compra está preparada. Revisá el total y continuá a Ualá.':'Guardamos tu compra. Revisá su estado en Mis compras; no vuelvas a crearla.';
        } elseif($action==='refresh') {
            if(time()-(int)($_SESSION['recharge_check_time'] ?? 0)<15) throw new RuntimeException('Check too soon');
            $_SESSION['recharge_check_time']=time();
            $order=$shopStore->owned($_POST['order_id'] ?? '',$rechargeAccount);
            if($order['live']!==($shopSettings['environment']==='production')) throw new RuntimeException('Different payment environment');
            $shopStore->refresh($order['id'],$shopApi,$shopMerchant); $shopMessage='Estado de compra actualizado.';
        } else throw new InvalidArgumentException('Invalid action');
    } catch(InvalidArgumentException $exception) { $shopMessage='Revisá las cantidades: de 0 a 99 por paquete y hasta $1.000.000 por compra.'; }
    catch(Throwable $exception) {
        $messages=['Session expired'=>'Tu sesión venció. Recargá la página y volvé a intentar.',
            'Check too soon'=>'Esperá 15 segundos antes de volver a consultar.',
            'Too many open orders'=>'Tenés 5 compras pendientes. Completá o revisá las anteriores antes de preparar otra.',
            'Checkout unconfirmed; existing order retained'=>'Guardamos tu compra, pero falta confirmar el enlace de Ualá. Conservá su número y consultá al equipo; no vuelvas a pagar.'];
        $shopMessage=$messages[$exception->getMessage()] ?? 'No pudimos completar la operación. Revisá Mis compras antes de intentar otra vez. Si guardamos una compra, conservá su número para consultar al equipo.';
    }
}
if($shopStore) { try { $shopHistory=$shopStore->history($rechargeAccount,$shopPage); } catch(Throwable $ignored) {} }
