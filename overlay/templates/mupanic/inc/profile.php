<?php
/**
 * MU PANIC · Perfiles públicos. Carga el módulo nativo de WebEngine y lo presenta con inc/profile-view.php.
 * Guild: suma clase, nivel, resets y estado de cada miembro (solo datos públicos del personaje, nunca la cuenta).
 */
if(!defined('access') || !access) die();
require_once __DIR__.'/profile-view.php';

if(!function_exists('panicProfileRoster')) {
    function panicProfileRoster($names) {
        $names = array_slice(array_values(array_filter($names, function($n) { return is_string($n) && $n !== '' && strlen($n) <= 10; })), 0, 80);
        if(!$names) return [];
        sort($names);
        $cacheFile = defined('__PATH_CACHE__') ? rtrim(__PATH_CACHE__, '/\\').'/mupanic-guild-roster-v1-'.substr(hash('sha256', json_encode($names)), 0, 20).'.json' : null;
        $cached = $cacheFile && is_file($cacheFile) ? json_decode((string)@file_get_contents($cacheFile), true) : null;
        $rows = (is_array($cached) && isset($cached['updated'], $cached['rows']) && $cached['updated'] > time()-300) ? $cached['rows'] : null;
        if($rows === null) {
            try {
                $db = Connection::Database('MuOnline');
                $result = $db->query_fetch('SELECT c.[Name] AS [name], c.[Class] AS [class], c.[cLevel] AS [level], c.[ResetCount] AS [resets], c.[MasterResetCount] AS [mresets] FROM [MuOnline43].[dbo].[Character] c WHERE c.[Name] IN ('.implode(',', array_fill(0, count($names), '?')).')', $names);
                if($result === false) throw new RuntimeException('Roster query failed');
                $rows = [];
                foreach(is_array($result) ? $result : [] as $r) $rows[] = ['name'=>(string)$r['name'], 'class'=>(int)$r['class'], 'level'=>(int)$r['level'], 'resets'=>(int)$r['resets'], 'mresets'=>(int)$r['mresets']];
                if($cacheFile) {
                    $tmp = @tempnam(dirname($cacheFile), 'panic-roster-');
                    if($tmp !== false) { if(@file_put_contents($tmp, json_encode(['updated'=>time(), 'rows'=>$rows]), LOCK_EX) !== false) @rename($tmp, $cacheFile); if(is_file($tmp)) @unlink($tmp); }
                }
            } catch(Throwable $e) {
                $rows = is_array($cached) && isset($cached['rows']) && is_array($cached['rows']) ? $cached['rows'] : [];
            }
        }
        $online = function_exists('loadCache') ? loadCache('online_characters.cache') : null;
        $online = is_array($online) ? $online : [];
        $roster = [];
        foreach($rows as $r) { $r['online'] = in_array($r['name'], $online, true); $roster[$r['name']] = $r; }
        return $roster;
    }
}

$panicProfileType = ($_REQUEST['subpage'] ?? '') === 'guild' ? 'guild' : 'player';
ob_start();
$handler->loadModule($_REQUEST['page'], $_REQUEST['subpage']);
$panicProfileHtml = ob_get_clean();
$panicProfileRoster = $panicProfileType === 'guild' ? panicProfileRoster(panicProfileGuildMemberNames($panicProfileHtml)) : [];
echo panicProfileRender($panicProfileHtml, $panicProfileType, $panicProfileRoster);
