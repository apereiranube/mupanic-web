<?php
if(!defined('access') || !access) die();

// Public catalogue only. Never put API keys, tokens or bank details here.
// Sales remain closed in this first delivery; no endpoint accepts purchases.
return [
    'currency' => 'WCoin C',
    'packages' => [],
    'providers' => [
        'mobbex' => ['label'=>'Mobbex', 'state'=>'pending', 'copy'=>'Estamos preparando este medio de pago.'],
        'mercadopago' => ['label'=>'MercadoPago', 'state'=>'disabled', 'copy'=>'No disponible por el momento.'],
    ],
];
