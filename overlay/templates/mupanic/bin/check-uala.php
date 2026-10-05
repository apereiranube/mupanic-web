<?php
// Run from cPanel Terminal only. Never expose authentication through the web.
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
define('access',true);
require __DIR__.'/../inc/recharge-uala.php';
try {
    $settings=panicUalaPrivateSettings();
    $api=new PanicUalaBisApi($settings['uala_bis'],$settings['environment']);
    $api->authenticate();
    echo 'Uala Bis: autenticacion correcta. Ambiente: '.$settings['environment'].". No se crearon cobros ni se modificaron saldos.\n";
} catch(Throwable $exception) {
    // Provider bodies, tokens and credentials must never appear in the output.
    fwrite(STDERR,"No se pudo verificar Uala Bis. Revisa el archivo privado, ambiente y credenciales, y que PHP tenga cURL. No se realizaron cobros.\n");
    exit(1);
}
