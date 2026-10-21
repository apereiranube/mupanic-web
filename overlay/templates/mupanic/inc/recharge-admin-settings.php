<?php
if(!defined('access') || !access || !panicRechargeAdminAllowed()) return;
$adminMessage=null; $adminError=false; $management=null; $adminGateway=null; $adminSettings=null;
$adminCatalogue=['revision'=>0,'packages'=>[]]; $worker=[];
$adminStorefront=PanicRechargeManagement::storefrontDefaults();
try {
    $management=new PanicRechargeManagement(); $adminCatalogue=$management->catalogue(); $adminStorefront=$management->storefront(); $worker=$management->worker();
    try { [$adminGateway,$adminMerchant,$adminSettings]=panicRechargeShopGateway(); } catch(Throwable $ignored) {}
    if(($_SERVER['REQUEST_METHOD'] ?? '')==='POST' && strpos((string)($_POST['admin_action'] ?? ''),'bank_')!==0) {
        if(!is_string($_POST['admin_csrf'] ?? null) || !hash_equals($_SESSION['shop_admin_csrf'],$_POST['admin_csrf'])) throw new RuntimeException('La sesión venció. Recargá la página.');
        if(($_POST['admin_action'] ?? '')==='catalogue') {
            if(!is_array($_POST['packages'] ?? null) || count($_POST['packages'])>13) throw new InvalidArgumentException('Revisá el catálogo.');
            $rows=[];
            foreach($_POST['packages'] as $input) {
                if(!is_array($input)) throw new InvalidArgumentException('Paquete inválido.');
                $id=$input['id'] ?? ''; $coins=$input['coins'] ?? ''; $bonus=$input['bonus'] ?? '0';
                if($id==='' && ($coins==='' || $coins==='0')) continue;
                foreach([$coins,$bonus] as $value) if(!is_string($value) || !preg_match('/^(0|[1-9][0-9]{0,6})$/D',$value)) throw new InvalidArgumentException('Usá monedas y bonos enteros.');
                $row=['id'=>$id,'title'=>$input['title'] ?? '', 'coins'=>(int)$coins,'price_cents'=>(int)$coins*100,'bonus'=>(int)$bonus,
                    'enabled'=>isset($input['enabled']),'featured'=>isset($input['featured']),'starts_at'=>'','ends_at'=>''];
                foreach(['starts_at','ends_at'] as $key) {
                    $value=$input[$key] ?? ''; if(!is_string($value)) throw new InvalidArgumentException('Fecha inválida.');
                    if($value!=='') {
                        $date=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$value,new DateTimeZone('America/Argentina/Buenos_Aires'));
                        if(!$date || $date->format('Y-m-d\TH:i')!==$value) throw new InvalidArgumentException('Fecha inválida.');
                        $row[$key]=$date->format('Y-m-d\TH:iP');
                    }
                }
                $position=$input['position'] ?? ''; if(!is_string($position) || !preg_match('/^(?:[1-9]|1[0-2])$/D',$position)) throw new InvalidArgumentException('Posición inválida.');
                $row['position']=(int)$position; $rows[]=$row;
            }
            if(!is_string($_POST['revision'] ?? null) || !preg_match('/^[0-9]{1,10}$/D',$_POST['revision'])) throw new InvalidArgumentException('Revisión inválida.');
            usort($rows,function($a,$b) { return $a['position']<=>$b['position']; });
            $adminCatalogue=$management->saveCatalogue($rows,(int)$_POST['revision']);
            $adminMessage='Catálogo guardado. Las compras anteriores conservan sus importes y monedas.';
        } elseif(($_POST['admin_action'] ?? '')==='storefront') {
            if(!is_string($_POST['storefront_revision'] ?? null) || !preg_match('/^[0-9]{1,10}$/D',$_POST['storefront_revision'])) throw new InvalidArgumentException('Revisión inválida.');
            $readOptional=function($value) {
                if($value==='') return null;
                if(!is_string($value) || !preg_match('/^[1-9][0-9]{0,6}$/D',$value)) throw new InvalidArgumentException('Usá números enteros positivos o dejá el campo vacío.');
                return (int)$value;
            };
            $presentation=['products'=>[],'vip'=>[]];
            foreach(['theryon','nerathys','vaeraxes'] as $slug) {
                $input=$_POST['products'][$slug] ?? null;
                if(!is_array($input)) throw new InvalidArgumentException('Revisá las monturas.');
                $presentation['products'][]=['slug'=>$slug,'price_coins'=>$readOptional($input['price_coins'] ?? ''),'vip_price_coins'=>$readOptional($input['vip_price_coins'] ?? ''),'available'=>isset($input['available'])];
            }
            $vipInput=$_POST['vip'] ?? null;if(!is_array($vipInput)) throw new InvalidArgumentException('Revisá el plan VIP.');
            $presentation['vip']=['name'=>$vipInput['name'] ?? '', 'days'=>$readOptional($vipInput['days'] ?? ''),'price_coins'=>$readOptional($vipInput['price_coins'] ?? ''),'benefits'=>[]];
            for($benefitIndex=0;$benefitIndex<6;$benefitIndex++) {
                $input=$vipInput['benefits'][$benefitIndex] ?? null;if(!is_array($input)) throw new InvalidArgumentException('Revisá los beneficios.');
                $presentation['vip']['benefits'][]=['title'=>$input['title'] ?? '','detail'=>$input['detail'] ?? '','enabled'=>isset($input['enabled'])];
            }
            $adminStorefront=$management->saveStorefront($presentation,(int)$_POST['storefront_revision']);
            $adminMessage='Vitrina y único plan VIP guardados en la web. La configuración del juego no se modificó.';
        } else throw new InvalidArgumentException('Acción inválida.');
    }
} catch(Throwable $exception) {
    $adminError=true;
    $adminMessage=$exception instanceof InvalidArgumentException?$exception->getMessage():'No pudimos completar la operación. Recargá el panel y revisá la conexión y los archivos privados.';
    if(in_array($exception->getMessage(),['La configuración cambió en otra ventana. Recargá antes de guardar.','La sesión venció. Recargá la página.','Esperá 15 segundos entre consultas.','La compra pertenece a otro ambiente o no está disponible.'],true)) $adminMessage=$exception->getMessage();
}
$workerFresh=($worker['last_seen'] ?? 0)>=time()-180; $workerOkay=$workerFresh && in_array($worker['phase'] ?? '',['ok','polling'],true) && empty($worker['last_error']);
$workerLabel=$workerOkay?'Entrega conectada':($workerFresh?'Revisar entrega':'Sin conexión reciente');
$vipModule=defined('__PATH_MODULES_USERCP__') && is_file(__PATH_MODULES_USERCP__.'vip.php');
$vipData=null;
try { $rows=Connection::Database('MuOnline')->query_fetch('SELECT AccountLevel,AccountExpireDate FROM dbo.MEMB_INFO WHERE memb___id = ?', ['panic']); $vipData=$rows[0] ?? null; } catch(Throwable $ignored) {}
$adminUrl=__BASE_URL__.'usercp/shopadmin/';
?>
<section class="recharge-shop shop-management shop-management--v6" aria-labelledby="shop-admin-title">
<header class="recharge-hero"><span class="eyebrow">MU PANIC / ADMINISTRACIÓN</span><h2 id="shop-admin-title">Configuración de la tienda.</h2><p>Configurá la cuenta receptora, los paquetes, las monturas y el VIP. Cada formulario tiene su propio botón para guardar.</p><div class="shop-admin-badges"><span>Acceso exclusivo: panic</span><span>Pagos nuevos: transferencia bancaria</span><a href="<?php echo panicAccountEscape(__BASE_URL__.'usercp/recharge/'); ?>">Ver tienda →</a></div></header>
<?php if($adminMessage) { ?><div class="recharge-notice<?php echo $adminError?' shop-error':''; ?>" role="status"><?php echo panicAccountEscape($adminMessage); ?></div><?php } ?>

<section id="shop-catalogue" class="recharge-section"><div class="recharge-section-head"><div><span class="eyebrow">01 / PACKS Y REGALOS</span><h3>Paquetes y promociones</h3></div><span>$1 ARS = 1 Eryns base</span></div><p>Base = precio en pesos. Base + regalo = monedas acreditadas en el juego.</p><p class="recharge-subtle">Para cambiar el orden, editá la posición. Ocultar un paquete no altera compras existentes. Máximo 12 paquetes y 1.000.000 de monedas por compra.</p>
<?php if(($worker['version'] ?? 0)<2 || !$workerFresh) { ?><div class="recharge-notice">Los bonos quedan preparados, pero solo se ofrecen cuando el VPS informa la versión 2 y tiene una conexión reciente. Así se evitan compras que el servicio anterior no puede entregar.</div><?php } ?>
<form method="post" action="<?php echo panicAccountEscape($adminUrl.'?vista=configuracion#shop-catalogue'); ?>" class="shop-admin-catalogue"><input type="hidden" name="admin_csrf" value="<?php echo $_SESSION['shop_admin_csrf']; ?>"><input type="hidden" name="revision" value="<?php echo $adminCatalogue['revision']; ?>">
<?php $editRows=$adminCatalogue['packages']; if(count($editRows)<12) $editRows[]=['id'=>'','title'=>'','coins'=>0,'bonus'=>0,'enabled'=>false,'featured'=>false,'starts_at'=>'','ends_at'=>'']; foreach($editRows as $index=>$package) { ?><details class="shop-package-fold"><summary><?php echo $package['id']===''?'Agregar un paquete':number_format($package['coins']+$package['bonus'],0,',','.').' Eryns · $ '.number_format($package['coins'],0,',','.').' ARS'; ?><span><?php echo $package['enabled']?'Visible':'Oculto'; ?> · Editar</span></summary><fieldset class="shop-package-editor"><legend><?php echo $package['id']===''?'Agregar un paquete':'Paquete '.($index+1); ?></legend><div class="shop-editor-grid"><label>Código interno · conservá el existente<input name="packages[<?php echo $index; ?>][id]" value="<?php echo panicAccountEscape($package['id']); ?>" pattern="[a-z0-9_-]{1,40}" maxlength="40" placeholder="wcoin-50000"></label><label>Nombre<input name="packages[<?php echo $index; ?>][title]" value="<?php echo panicAccountEscape($package['title']); ?>" maxlength="120" placeholder="50.000 Eryns"></label><label>Eryns base · también define el precio ARS<input type="number" name="packages[<?php echo $index; ?>][coins]" value="<?php echo $package['coins']; ?>" min="0" max="1000000" step="1"></label><label>Eryns de regalo<input type="number" name="packages[<?php echo $index; ?>][bonus]" value="<?php echo $package['bonus']; ?>" min="0" max="1000000" step="1"></label><label>Inicio del bono (ARG)<input type="datetime-local" name="packages[<?php echo $index; ?>][starts_at]" value="<?php echo $package['starts_at']!==''?panicAccountEscape(panicRechargeArgDate($package['starts_at'],'Y-m-d\TH:i')):''; ?>"></label><label>Fin del bono (ARG)<input type="datetime-local" name="packages[<?php echo $index; ?>][ends_at]" value="<?php echo $package['ends_at']!==''?panicAccountEscape(panicRechargeArgDate($package['ends_at'],'Y-m-d\TH:i')):''; ?>"></label><label>Posición<input type="number" name="packages[<?php echo $index; ?>][position]" value="<?php echo $index+1; ?>" min="1" max="12" step="1"></label><div class="shop-editor-switches"><label><input type="checkbox" name="packages[<?php echo $index; ?>][enabled]" <?php if($package['enabled']) echo 'checked'; ?>> Visible en la tienda</label><label><input type="checkbox" name="packages[<?php echo $index; ?>][featured]" <?php if($package['featured']) echo 'checked'; ?>> Destacar paquete</label></div></div></fieldset></details><?php } ?><button class="recharge-button" name="admin_action" value="catalogue">Guardar catálogo y promociones</button><?php if($adminCatalogue['updated_at']) { ?><p class="recharge-subtle">Último cambio: <?php echo panicAccountEscape(panicRechargeArgDate($adminCatalogue['updated_at'])); ?> ARG</p><?php } ?></form></section>
<?php require __DIR__.'/recharge-bank-admin.php'; ?>
<?php require __DIR__.'/recharge-admin-storefront.php'; ?>
<section id="shop-delivery" class="recharge-section"><div class="recharge-section-head"><div><span class="eyebrow">05 / DE LA WEB AL JUEGO</span><h3>Entrega automática</h3></div><span class="recharge-status"><?php echo $workerLabel; ?></span></div><div class="shop-admin-metrics"><div><span>Último contacto del VPS</span><strong><?php echo isset($worker['last_seen'])?panicAccountEscape(panicRechargeArgDate('@'.$worker['last_seen'],'H:i:s')):'Sin registro'; ?></strong></div><div><span>Versión del servicio</span><strong><?php echo (int)($worker['version'] ?? 0); ?></strong></div><div><span>Esperando desconexión</span><strong><?php echo (int)($worker['waiting'] ?? 0); ?></strong></div><div><span>Consultas de pago con error</span><strong><?php echo (int)($worker['check_errors'] ?? 0); ?></strong></div></div><p>La tarea consulta cada minuto. Las cuentas conectadas esperan a desconectarse. Cada entrega queda registrada para que los reintentos no dupliquen monedas.</p><?php if(!empty($worker['last_error'])) { ?><p class="shop-error">El VPS informó un error. Revisá su registro con la opción -Status del sincronizador.</p><?php } ?><a class="recharge-text-button" href="<?php echo panicAccountEscape($adminUrl.'?vista=configuracion#shop-delivery'); ?>">Actualizar panel</a></section>

<details class="shop-system-details"><summary>Diagnóstico VIP del juego</summary><section id="shop-vip-diagnostic" class="recharge-section"><div class="recharge-section-head"><div><span class="eyebrow">04 / MEMBRESÍAS</span><h3>VIP del servidor</h3></div><span class="recharge-status">Revisión de integración</span></div><p>La base activa ya tiene nivel y vencimiento de VIP. El panel gestiona la presentación de una única membresía; la activación y renovación se conservan en el juego.</p><div class="shop-vip-grid"><article><h4>VIP de la cuenta panic</h4><?php if($vipData) { ?><p>Nivel actual: <b><?php echo (int)$vipData['AccountLevel']; ?></b><br>Vencimiento registrado: <b><?php echo panicAccountEscape($vipData['AccountExpireDate']); ?></b></p><?php } else { ?><p>No se pudo consultar el nivel de esta cuenta.</p><?php } ?><p>Consulta de lectura. No cambia la membresía.</p></article><article><h4>Compra desde el juego</h4><p>El servicio del VPS enviará una lectura de los parámetros VIP disponibles en su configuración.</p><?php if(!empty($worker['vip_config'])) { ?><details><summary>Ver configuración detectada</summary><dl><?php foreach($worker['vip_config'] as $key=>$value) { ?><dt><?php echo panicAccountEscape($key); ?></dt><dd><?php echo panicAccountEscape($value); ?></dd><?php } ?></dl></details><?php } else { ?><p class="recharge-subtle">Todavía no hay parámetros confirmados.</p><?php } ?></article><article><h4>Módulo VIP de la web</h4><p><?php echo $vipModule?'Hay un módulo VIP instalado. Se conserva su funcionamiento actual.':'No se detectó el archivo del módulo VIP estándar. Puede existir una integración adicional.'; ?></p><?php if($vipModule) { ?><a class="recharge-text-button" href="<?php echo panicAccountEscape(__BASE_URL__.'usercp/vip/'); ?>">Abrir VIP actual →</a><?php } ?><p>La venta directa de VIP no está habilitada por este panel todavía.</p></article></div></section></details></section>

<script src="<?php echo __PATH_TEMPLATE__; ?>js/recharge-admin.js?v=<?php echo substr(hash_file('sha256',__DIR__.'/../js/recharge-admin.js'),0,12); ?>"></script>
