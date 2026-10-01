<?php
if(!defined('access') || !access) die();

// Counters and identifiers verified against the administrator's MuOnline43 audit.
// The MuOnline connection name is a CMS alias, not the physical database name.
function panicRankingCategories() {
    return [
        'resets' => ['title'=>'Resets', 'copy'=>'Los personajes con más resets. En caso de empate, decide el nivel.', 'fields'=>['Resets'=>'ResetCount','Nivel'=>'cLevel'], 'order'=>'c.[ResetCount] DESC, c.[cLevel] DESC'],
        'grandresets' => ['title'=>'Master Resets', 'copy'=>'Los personajes con más Master Resets. En caso de empate, deciden los resets.', 'fields'=>['Master Resets'=>'MasterResetCount','Resets'=>'ResetCount'], 'order'=>'c.[MasterResetCount] DESC, c.[ResetCount] DESC'],
        'bloodcastle' => ['title'=>'Blood Castle', 'copy'=>'Puntos totales registrados por el servidor en Blood Castle.', 'table'=>'RankingBloodCastle', 'fields'=>['Puntos'=>'Score'], 'order'=>'r.[Score] DESC'],
        'devilsquare' => ['title'=>'Devil Square', 'copy'=>'Puntos totales registrados por el servidor en Devil Square.', 'table'=>'RankingDevilSquare', 'fields'=>['Puntos'=>'Score'], 'order'=>'r.[Score] DESC'],
        'duels' => ['title'=>'Duelos', 'copy'=>'Duelos ganados. También podés ver las derrotas; este ranking es independiente del estado PK.', 'table'=>'RankingDuel', 'fields'=>['Victorias'=>'WinScore','Derrotas'=>'LoseScore'], 'order'=>'r.[WinScore] DESC, r.[LoseScore] ASC'],
    ];
}
function panicRankingEscape($value) { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function panicRankingExtendMenu($markup, $categories, $current) {
    return preg_replace_callback('~(<div\b[^>]*class=["\'][^"\']*\brankings_menu\b[^"\']*["\'][^>]*>)(.*?)(</div>)~s', function($match) use ($categories, $current) {
        $links = preg_replace_callback('~<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>.*?</a>~s', function($anchor) use ($categories) {
            $key = basename(rtrim(parse_url(html_entity_decode($anchor[1]), PHP_URL_PATH) ?? '', '/'));
            return isset($categories[$key]) ? '' : $anchor[0];
        }, $match[2]);
        foreach($categories as $key=>$category) {
            $links .= '<a href="'.panicRankingEscape(__BASE_URL__.'rankings/'.$key.'/').'"'.($key===$current ? ' class="active"' : '').'>'.panicRankingEscape($category['title']).'</a>';
        }
        return $match[1].$links.$match[3];
    }, $markup, 1);
}
function panicRankingRead($category, $key) {
    $limit = max(1, min(100, (int)(mconfig('rankings_results') ?: 25)));
    $excluded = array_values(array_filter(array_map('trim', explode(',', (string)mconfig('rankings_excluded_characters'))), function($name) { return $name !== ''; }));
    $source = isset($category['table']) ? 'r' : 'c';
    $columns = [];
    foreach(array_values($category['fields']) as $i=>$field) $columns[] = $source.'.['.$field.'] AS [metric'.$i.']';
    $from = '[MuOnline43].[dbo].[Character] c';
    if(isset($category['table'])) $from .= ' INNER JOIN [MuOnline43].[dbo].['.$category['table'].'] r ON r.[Name]=c.[Name]';
    $metric = array_values($category['fields'])[0];
    $sql = 'SELECT TOP '.$limit.' c.[Name] AS [name], c.[Class] AS [class], '.implode(', ', $columns).' FROM '.$from.' WHERE '.$source.'.['.$metric.'] > 0';
    if($excluded) $sql .= ' AND c.[Name] NOT IN ('.implode(',', array_fill(0, count($excluded), '?')).')';
    $sql .= ' ORDER BY '.$category['order'].', c.[Name] ASC';
    // Cache only public ranking fields. Include policy in the key so exclusions
    // and result limits take effect immediately. Never overwrite native caches.
    $cacheFile = defined('__PATH_CACHE__') ? rtrim(__PATH_CACHE__, '/\\').'/mupanic-ranking-v1-'.$key.'-'.substr(hash('sha256', $sql.json_encode($excluded)), 0, 16).'.json' : null;
    $cached = $cacheFile && is_file($cacheFile) ? json_decode((string)@file_get_contents($cacheFile), true) : null;
    $valid = is_array($cached) && isset($cached['updated'], $cached['rows']) && is_array($cached['rows']);
    if($valid && $cached['updated'] > time()-300) return $cached;
    try {
        $db = Connection::Database('MuOnline');
        $rows = $db->query_fetch($sql, $excluded);
        // WebEngine returns NULL for a successful query with no rows, FALSE on error.
        if($rows === false) throw new RuntimeException('Ranking query failed');
        $snapshot = ['updated'=>time(), 'rows'=>is_array($rows) ? $rows : []];
        if($cacheFile) {
            $temporary = @tempnam(dirname($cacheFile), 'panic-rank-');
            if($temporary !== false) {
                if(@file_put_contents($temporary, json_encode($snapshot), LOCK_EX) !== false) @rename($temporary, $cacheFile);
                if(is_file($temporary)) @unlink($temporary);
            }
        }
        return $snapshot;
    } catch(Throwable $error) {
        if($valid && $cached['updated'] > time()-86400) { $cached['stale'] = true; return $cached; }
        return null;
    }
}

$panicCategories = panicRankingCategories();
$panicRankingKey = (string)($_REQUEST['subpage'] ?? '');
loadModuleConfigs('rankings');
if(!mconfig('active')) {
    $handler->loadModule($_REQUEST['page'], $_REQUEST['subpage']);
} elseif(!isset($panicCategories[$panicRankingKey])) {
    ob_start();
    $handler->loadModule($_REQUEST['page'], $_REQUEST['subpage']);
    echo panicRankingExtendMenu(ob_get_clean(), $panicCategories, $panicRankingKey);
} else {
    $panicCategory = $panicCategories[$panicRankingKey];
    $panicRankings = new Rankings();
    ob_start(); $panicRankings->rankingsMenu();
    echo panicRankingExtendMenu(ob_get_clean(), $panicCategories, $panicRankingKey);
    echo '<div class="rankings-category-copy"><h2>'.panicRankingEscape($panicCategory['title']).'</h2><p>'.panicRankingEscape($panicCategory['copy']).'</p></div>';
    $panicSnapshot = panicRankingRead($panicCategory, $panicRankingKey);
    if($panicSnapshot === null) {
        echo '<p class="rankings-empty" role="status">No pudimos consultar este ranking. Volvé a intentarlo en unos minutos.</p>';
    } elseif(!$panicSnapshot['rows']) {
        echo '<p class="rankings-empty" role="status">Todavía no hay resultados en esta categoría. Los primeros registros aparecerán cuando el servidor los guarde.</p>';
    } else {
        if(mconfig('rankings_class_filter')) $panicRankings->rankingsFilterMenu();
        echo '<table class="rankings-table"><tr><th>Puesto</th><th>Clase</th><th>Personaje</th>';
        foreach($panicCategory['fields'] as $label=>$field) echo '<th>'.panicRankingEscape($label).'</th>';
        echo '</tr>';
        foreach($panicSnapshot['rows'] as $i=>$row) {
            $name = (string)$row['name']; $class = (int)$row['class'];
            $profile = config('player_profiles', true) ? '<a href="'.panicRankingEscape(playerProfile($name, true)).'">'.panicRankingEscape($name).'</a>' : panicRankingEscape($name);
            echo '<tr data-class-id="'.$class.'"><td class="rankings-table-place">'.($i+1).'</td><td><span class="rankings-class-label">'.panicRankingEscape(getPlayerClass($class)).'</span></td><td>'.$profile.'</td>';
            foreach(array_values($panicCategory['fields']) as $j=>$field) echo '<td>'.number_format((int)$row['metric'.$j], 0, ',', '.').'</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    if($panicSnapshot !== null) {
        echo '<p class="rankings-update-time">'.(!empty($panicSnapshot['stale']) ? 'Mostrando la última consulta disponible. ' : '').'Última consulta: '.gmdate('d/m/Y H:i', $panicSnapshot['updated']-3*3600).' (Argentina). Se actualiza al consultar, cada 5 minutos.</p>';
    }
}
