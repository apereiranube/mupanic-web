<?php
if(!defined('access') or !access) die();
// Shared by both branches: beta stays a complete website; only production home is gated.
return [
    'productionMode' => 'coming-soon',
    'productionHosts' => ['mupanic.com.ar', 'www.mupanic.com.ar'],
    'previewHosts' => ['beta.mupanic.com.ar'],
    'launchAt' => null,
    'launchDateLabel' => null,
    'hero' => 'img/launch/warfront-v4-1672.webp',
    'heroSmall' => 'img/launch/warfront-mobile-v4-720.webp',
];
