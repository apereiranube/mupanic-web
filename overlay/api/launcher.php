<?php
// Standalone public read-only endpoint. No CMS sessions or user-supplied queries.
ini_set('display_errors','0');
ini_set('display_startup_errors','0');
error_reporting(0);
$launcherBufferLevel=ob_get_level();
ob_start();
$launcherPayload=['online'=>0,'servidor'=>'offline','eventos'=>[]];
register_shutdown_function(function() use (&$launcherPayload,$launcherBufferLevel) {
    while(ob_get_level()>$launcherBufferLevel) ob_end_clean();
    header_remove('Location');header_remove('Set-Cookie');
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: max-age=30');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($launcherPayload,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
});
try {
    require_once dirname(__DIR__).'/templates/mupanic/inc/launcher-data.php';
    // Recompute dates from the shared agenda every request so cached timestamps
    // cannot advertise an event that already started. Only the SQL probe is cached.
    $launcherPayload['eventos']=panicLauncherEvents(new DateTimeImmutable('now'));
    $state=panicLauncherCachedState(dirname(__DIR__));
    $launcherPayload['online']=$state['online'];
    $launcherPayload['servidor']=$state['servidor'];
} catch(Throwable $error) { /* Shutdown emits only the safe public schema. */ }
