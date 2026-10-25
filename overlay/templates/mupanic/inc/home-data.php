<?php
// Reuse the launcher/Atlas projection. Rendering this page does not open SQL.
require_once __DIR__.'/launcher-data.php';

function panicHomeEvents($now = null) {
    $now = $now ?? new DateTimeImmutable('now', new DateTimeZone('America/Argentina/Buenos_Aires'));
    try { return array_slice(panicLauncherEvents($now), 0, 3); }
    catch(Throwable $error) { return []; }
}

function panicHomeEventLabel($iso) {
    try {
        $date = (new DateTimeImmutable($iso))->setTimezone(new DateTimeZone('America/Argentina/Buenos_Aires'));
        return $date->format('d/m · H:i').' hs';
    } catch(Throwable $error) { return 'Ver calendario'; }
}
