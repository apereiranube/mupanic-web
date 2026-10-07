<?php
if(!defined('access') or !access) die();
function panicLaunchVisible($baseUrl, $page, $preview, array $config) {
    if($page !== '') return false;
    $host = strtolower((string) parse_url($baseUrl, PHP_URL_HOST));
    if(in_array($host, $config['previewHosts'], true)) return $preview === 'launch';
    return $config['productionMode'] === 'coming-soon' && in_array($host, $config['productionHosts'], true);
}
