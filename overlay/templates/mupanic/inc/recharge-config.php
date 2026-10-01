<?php
if(!defined('access') || !access) die();

// Public catalogue only. Never put API keys, tokens or bank details here.
// Sales remain closed in this first delivery; no endpoint accepts purchases.
return [
    'currency' => 'WCoin C',
    // Confirmed base rate: ARS 1.00 (100 centavos) delivers one WCoin C.
    'exchange_rate' => ['price_cents'=>100, 'coins'=>1],
    'primary_provider' => 'uala_bis',
    'packages' => [
        ['id'=>'wcoin-1000', 'title'=>'1.000 WCoin C', 'price_cents'=>100000, 'coins'=>1000, 'bonus'=>0],
        ['id'=>'wcoin-3000', 'title'=>'3.000 WCoin C', 'price_cents'=>300000, 'coins'=>3000, 'bonus'=>0],
        ['id'=>'wcoin-5000', 'title'=>'5.000 WCoin C', 'price_cents'=>500000, 'coins'=>5000, 'bonus'=>0],
        ['id'=>'wcoin-10000', 'title'=>'10.000 WCoin C', 'price_cents'=>1000000, 'coins'=>10000, 'bonus'=>0],
        ['id'=>'wcoin-20000', 'title'=>'20.000 WCoin C', 'price_cents'=>2000000, 'coins'=>20000, 'bonus'=>0],
    ],
    'providers' => [
        'uala_bis' => ['label'=>'Ualá Bis', 'state'=>'pending', 'copy'=>'Estamos preparando este medio de pago.'],
        'mobbex' => ['label'=>'Mobbex', 'state'=>'pending', 'copy'=>'Estamos preparando este medio de pago.'],
        'mercadopago' => ['label'=>'MercadoPago', 'state'=>'disabled', 'copy'=>'No disponible por el momento.'],
    ],
];
