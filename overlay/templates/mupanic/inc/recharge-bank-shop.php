<?php
if(!defined('access') || !access) die();
// Controller helper: no remote calls or writes on GET.
require_once __DIR__.'/recharge-bank.php';
$bankStore=null;$bankOauthConfig=null;$bankConfig=PanicBankTransfers::defaults();$bankCanBuy=false;$bankEnvironment=null;
$bankHistory=['total'=>0,'orders'=>[]];$bankPage=max(1,min(500,(int)($_GET['transferencias'] ?? 1)));
try {
    $bankStore=new PanicBankTransfers();$bankConfig=$bankStore->config();$bankEnvironment=$bankStore->environment();
    $bankWorker=(new PanicRechargeManagement())->worker();
    $bankOauthConfig=$bankStore->oauthConfig();
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
/** Discord login return (GET ?code=&state=). Only links the user to an order of the logged-in account. */
function panicBankDiscordHttp($url,array $opts) {
    if(!function_exists('curl_init'))throw new RuntimeException('Discord no disponible.');
    $curl=curl_init($url);
    curl_setopt_array($curl,$opts+[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>15,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS]);
    $raw=curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_HTTP_CODE);curl_close($curl);
    $data=is_string($raw) && strlen($raw)<65536?json_decode($raw,true):null;
    if($status!==200 || !is_array($data))throw new RuntimeException('Discord no confirmó tu usuario.');
    return $data;
}
function panicBankDiscordCallback($bankStore,$rechargeAccount) {
    if(!$bankStore)throw new RuntimeException('Las transferencias no están disponibles.');
    $state=is_string($_GET['state'] ?? null)?$_GET['state']:'';$key=$_SESSION['bank_oauth_key'] ?? null;
    if(!is_string($key) || !preg_match('/^(PANIC-[a-f0-9]{32})\.[a-f0-9]{32}$/D',$state,$m) || !hash_equals(PanicBankTransfers::oauthState($m[1],$key),$state))throw new InvalidArgumentException('El enlace de Discord venció. Tocá otra vez «Enviar comprobante por Discord».');
    if(isset($_GET['error']))throw new InvalidArgumentException('No se autorizó Discord. Para abrir tu ticket tenés que tocar «Autorizar».');
    $code=is_string($_GET['code'] ?? null)?$_GET['code']:'';
    if(!preg_match('/^[A-Za-z0-9]{10,100}$/D',$code))throw new InvalidArgumentException('Discord no confirmó tu usuario. Probá otra vez.');
    $c=$bankStore->oauthConfig();if(!$c)throw new RuntimeException('Discord no disponible.');
    $token=panicBankDiscordHttp('https://discord.com/api/oauth2/token',[CURLOPT_POST=>true,CURLOPT_HTTPHEADER=>['Content-Type: application/x-www-form-urlencoded'],CURLOPT_POSTFIELDS=>http_build_query(['client_id'=>$c['client_id'],'client_secret'=>$c['client_secret'],'grant_type'=>'authorization_code','code'=>$code,'redirect_uri'=>__BASE_URL__.'usercp/recharge/'])]);
    $scopes=explode(' ',(string)($token['scope'] ?? ''));
    if(!is_string($token['access_token'] ?? null) || !in_array('guilds.join',$scopes,true))throw new InvalidArgumentException('Discord no dio permiso para sumarte al servidor. Probá otra vez y tocá «Autorizar».');
    $user=panicBankDiscordHttp('https://discord.com/api/users/@me',[CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$token['access_token']]]);
    return $bankStore->linkDiscord($m[1],$rechargeAccount,(string)($user['id'] ?? ''),$token['access_token']);
}
