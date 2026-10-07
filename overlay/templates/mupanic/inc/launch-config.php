<?php
if(!defined('access') or !access) die();
// Shared by both branches: beta stays a complete website; only production home is gated.
return [
    'productionMode' => 'coming-soon',
    'productionHosts' => ['mupanic.com.ar', 'www.mupanic.com.ar'],
    'previewHosts' => ['beta.mupanic.com.ar'],
    'launchAt' => '2026-10-31T20:00:00-03:00',
    'launchDateLabel' => '31 OCTUBRE · 20:00 ARG',
    'hero' => 'img/launch/portal-1672.webp',
    'heroSmall' => 'img/launch/portal-960.webp',
];
