<?php
/**
 * MU PANIC · Perfiles públicos (personaje y guild), estilo nuevo.
 * Lee el HTML nativo de los módulos de WebEngine (modules/profile/player.php y guild.php) y lo vuelve a armar.
 * Funciones puras (sin base de datos): tests/fixtures/profiles.php las usa con datos ficticios.
 * Privacidad: solo se muestran nombres de personaje. Nunca se lee ni se imprime el nombre de cuenta.
 * Arte existente: img/servidor/clase-<clave>-<hash>.webp (portada) e img/character-avatars/*.jpg (miniaturas).
 */
if(!defined('access') || !access) die();
if(!function_exists('panicProfileRender')) {
    function panicProfileEsc($v) { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
    function panicProfileText($html) { return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string)$html), ENT_QUOTES | ENT_HTML5, 'UTF-8'))); }
    function panicProfileBase() { return defined('__BASE_URL__') ? __BASE_URL__ : '/'; }
    function panicProfileTemplate() { return defined('__PATH_TEMPLATE__') ? __PATH_TEMPLATE__ : '/templates/mupanic/'; }

    // Clave de arte a partir del nombre de clase (texto del módulo) o del id de clase (base de datos).
    function panicProfileClassKey($name) {
        $n = strtolower((string)$name);
        foreach([
            'su'=>'/summoner|dimension master|invocador/', 'rf'=>'/rage fighter|fist master|luchador/',
            'mg'=>'/magic gladiator|duel master|gladiador/', 'dl'=>'/dark lord|lord emperor|señor oscuro/',
            'elfa'=>'/elf|muse|elfa/', 'bk'=>'/knight|blade master|caballero/', 'sm'=>'/wizard|soul master|grand master|mago/',
        ] as $key=>$pattern) if(preg_match($pattern.'u', $n)) return $key;
        return null;
    }
    function panicProfileClassKeyFromId($id) {
        $map = [0=>'sm',16=>'bk',32=>'elfa',48=>'mg',64=>'dl',80=>'su',96=>'rf'];
        return $map[(int)floor((int)$id / 16) * 16] ?? null;
    }
    function panicProfileClassArt($key) {
        static $files = null;
        if($files === null) { $dir = __DIR__.'/../img/servidor/'; $files = is_dir($dir) ? (scandir($dir) ?: []) : []; }
        if(!$key) return null;
        foreach($files as $f) if(preg_match('/^clase-'.preg_quote($key, '/').'-[0-9a-f]{12}\.webp$/D', $f)) return panicProfileTemplate().'img/servidor/'.$f;
        return null;
    }
    function panicProfileAvatar($key) {
        $file = ['sm'=>'dw','bk'=>'dk','elfa'=>'elf','mg'=>'mg','dl'=>'dl','su'=>'sum','rf'=>'rf'][$key] ?? 'avatar';
        return panicProfileTemplate().'img/character-avatars/'.$file.'.jpg';
    }
    function panicProfileNumber($text) { $digits = preg_replace('/[^0-9]/', '', (string)$text); return $digits === '' ? null : (int)$digits; }
    function panicProfileFormat($n) { return number_format((int)$n, 0, ',', '.'); }
    // Enlaces del módulo: solo href http(s) o relativos, nunca javascript:.
    function panicProfileLink($html) {
        if(!preg_match('~<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)</a>~si', (string)$html, $m)) return null;
        $href = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
        if(!preg_match('~^(https?://|/)~i', $href)) return null;
        return ['href'=>$href, 'text'=>panicProfileText($m[2])];
    }
    function panicProfileRows($html) {
        $rows = [];
        if(preg_match_all('~<tr\b[^>]*>\s*<td\b[^>]*>(.*?)</td>\s*<td\b([^>]*)>(.*?)</td>\s*</tr>~si', (string)$html, $all, PREG_SET_ORDER)) {
            foreach($all as $m) $rows[] = ['label'=>panicProfileText($m[1]), 'attrs'=>$m[2], 'html'=>$m[3], 'text'=>panicProfileText($m[3])];
        }
        return $rows;
    }
    function panicProfileLabelKey($label) {
        $l = rtrim(strtolower(panicProfileText($label)), ': ');
        $map = [
            'level'=>'level','nivel'=>'level','master level'=>'mlevel','nivel master'=>'mlevel','resets'=>'resets','reset'=>'resets',
            'grand resets'=>'mresets','master resets'=>'mresets','strength'=>'str','fuerza'=>'str','agility'=>'agi','agilidad'=>'agi','dexterity'=>'agi',
            'vitality'=>'vit','vitalidad'=>'vit','energy'=>'ene','energía'=>'ene','energia'=>'ene','command'=>'cmd','leadership'=>'cmd','liderazgo'=>'cmd','comando'=>'cmd',
            'kills'=>'kills','asesinatos'=>'kills','guild'=>'guild','status'=>'status','estado'=>'status',
            'guild master'=>'master','master'=>'master','líder'=>'master','lider'=>'master','score'=>'score','puntos'=>'score','members'=>'members','miembros'=>'members',
        ];
        return $map[$l] ?? null;
    }

    function panicProfileBack($type, $place) {
        $href = panicProfileBase().'rankings/'.($type === 'guild' ? 'guilds/' : '');
        return '<a class="pui-btn pf-back profile-back" data-place="'.$place.'" href="'.panicProfileEsc($href).'"><span aria-hidden="true">←</span> Volver al ranking</a>';
    }
    function panicProfilePresence($state, $text = null) {
        $label = $state === 'online' ? 'En línea' : ($state === 'offline' ? 'Desconectado' : ($text ?: 'Sin datos'));
        return '<span class="pf-presence profile-presence'.($state ? ' is-'.$state : '').'"><i aria-hidden="true"></i>'.panicProfileEsc($label).'</span>';
    }
    function panicProfileTile($label, $valueHtml, $extra = '', $cls = '') {
        return '<div class="pf-tile profile-value'.($cls ? ' '.$cls : '').'"><dt>'.panicProfileEsc($label).'</dt><dd>'.$valueHtml.'</dd>'.$extra.'</div>';
    }
    function panicProfileHero($o) {
        $art = $o['art'] ?? null;
        $out = '<section class="pf-hero pf-hero--'.$o['type'].'" aria-labelledby="pf-title"><div class="pf-hero-block" aria-hidden="true"></div>';
        if(!empty($o['mark'])) $out .= '<div class="pf-hero-mark"><img src="'.panicProfileEsc($o['mark']).'" alt="Escudo de la guild '.panicProfileEsc($o['name']).'" width="150" height="150" decoding="async"></div>';
        elseif($art) $out .= '<div class="pf-hero-fig"><img src="'.panicProfileEsc($art).'" alt="" width="800" height="1100" decoding="async" fetchpriority="high"></div>';
        $out .= '<div class="pui-wrap pf-hero-copy"><p class="pui-kicker">'.panicProfileEsc($o['kicker']).'</p>';
        $len = max(5, function_exists('mb_strlen') ? mb_strlen($o['name'], 'UTF-8') : strlen($o['name']));
        $out .= '<h1 class="pf-name profile-name" id="pf-title" style="--len:'.$len.'">'.panicProfileEsc($o['name']).'</h1>';
        if(!empty($o['sub'])) $out .= '<p class="pf-sub">'.$o['sub'].'</p>';
        if(!empty($o['meta'])) $out .= '<div class="pf-meta">'.$o['meta'].'</div>';
        $out .= '<div class="pf-actions">'.panicProfileBack($o['type'], 'top').($o['actions'] ?? '').'</div></div></section>';
        return $out;
    }
    function panicProfileFallback($html, $type) {
        $message = preg_match('~<div\b[^>]*class=["\'][^"\']*\balert\b[^"\']*["\'][^>]*>(.*?)</div>~si', (string)$html, $m) ? panicProfileText($m[1]) : '';
        return '<section class="pf-hero pf-hero--empty" aria-labelledby="pf-title"><div class="pui-wrap pf-hero-copy"><p class="pui-kicker">'.($type === 'guild' ? 'Perfil de guild' : 'Perfil de personaje').'</p>'.
            '<h1 class="pf-name pf-name--empty" id="pf-title">No encontramos este perfil</h1><p class="pf-sub">'.panicProfileEsc($message !== '' ? $message : 'Revisá el enlace o buscalo desde el ranking.').'</p>'.
            '<div class="pf-actions">'.panicProfileBack($type, 'top').'</div></div></section>';
    }

    /** Personaje. Devuelve null si el HTML no tiene la forma esperada. */
    function panicProfileRenderPlayer($html) {
        if(!preg_match('~<td\b[^>]*class=["\']cname["\'][^>]*>(.*?)</td>~si', $html, $nm)) return null;
        $name = panicProfileText($nm[1]);
        $className = preg_match('~<td\b[^>]*class=["\']cclass["\'][^>]*>(.*?)</td>~si', $html, $cm) ? panicProfileText($cm[1]) : '';
        $info = preg_match('~<table\b[^>]*profiles_player_table_info[^>]*>(.*?)</table>~si', $html, $tm) ? $tm[1] : '';
        $v = []; $other = []; $guild = null; $state = null; $stateText = null;
        foreach(panicProfileRows($info) as $row) {
            $key = panicProfileLabelKey($row['label']);
            if($key === 'guild') { $guild = panicProfileLink($row['html']) ?: ($row['text'] !== '' ? ['href'=>null,'text'=>$row['text']] : null); }
            elseif($key === 'status') {
                $t = strtolower($row['text']);
                $state = (stripos($row['attrs'], 'isonline') !== false || $t === 'online') ? 'online' : ((stripos($row['attrs'], 'isoffline') !== false || $t === 'offline') ? 'offline' : null);
                $stateText = $row['text'];
            }
            elseif($key !== null && !isset($v[$key])) $v[$key] = $row['text'];
            else $other[] = $row;
        }
        $key = panicProfileClassKey($className);
        $out = '<div class="pf pf--player pui" data-class="'.panicProfileEsc($key ?: 'none').'">';
        $guildChip = $guild ? '<span class="pf-chip"><small>Guild</small>'.($guild['href'] ? '<a href="'.panicProfileEsc($guild['href']).'">'.panicProfileEsc($guild['text']).'</a>' : '<b>'.panicProfileEsc($guild['text']).'</b>').'</span>' : '';
        $out .= panicProfileHero([
            'type'=>'player', 'kicker'=>'Perfil de personaje', 'name'=>$name, 'art'=>panicProfileClassArt($key),
            'sub'=>'<span class="pf-class">'.panicProfileEsc($className !== '' ? $className : 'Personaje').'</span>',
            'meta'=>panicProfilePresence($state, $stateText).$guildChip,
        ]);
        // La trayectoria
        $progress = '';
        foreach(['level'=>'Nivel','mlevel'=>'Nivel Master','resets'=>'Resets','mresets'=>'Master Resets'] as $k=>$label) if(isset($v[$k])) $progress .= panicProfileTile($label, panicProfileEsc($v[$k]));
        $out .= '<section class="pf-band pf-band--night" aria-labelledby="pf-progress-title"><div class="pui-wrap"><div class="pf-head"><p class="pui-kicker">Progreso</p><h2 class="pui-h2" id="pf-progress-title">La trayectoria</h2></div>';
        if($progress !== '') $out .= '<dl class="pf-tiles pf-tiles--progress profile-progress">'.$progress.'</dl>';
        $out .= '</div></section>';
        // Atributos y continente
        $attr = ['str'=>'Fuerza','agi'=>'Agilidad','vit'=>'Vitalidad','ene'=>'Energía','cmd'=>'Comando'];
        $max = 0; foreach(['str','agi','vit','ene'] as $k) if(isset($v[$k])) $max = max($max, (int)panicProfileNumber($v[$k]));
        $attrTiles = '';
        foreach($attr as $k=>$label) if(isset($v[$k])) {
            $pct = $max > 0 ? min(100, round(100 * (int)panicProfileNumber($v[$k]) / $max)) : 0;
            $attrTiles .= panicProfileTile($label, panicProfileEsc($v[$k]), '<span class="pf-bar" aria-hidden="true"><i style="width:'.$pct.'%"></i></span>', 'pf-tile--attr');
        }
        $detail = '';
        if(isset($v['kills'])) $detail .= panicProfileTile('Asesinatos', panicProfileEsc($v['kills']));
        $detail .= panicProfileTile('Estado', panicProfilePresence($state, $stateText), '', 'pf-tile--state');
        $detail .= panicProfileTile('Guild', $guild ? ($guild['href'] ? '<a href="'.panicProfileEsc($guild['href']).'">'.panicProfileEsc($guild['text']).'</a>' : panicProfileEsc($guild['text'])) : '<span class="pf-none">Sin guild</span>', '', 'pf-tile--guild');
        foreach($other as $row) $detail .= panicProfileTile($row['label'], panicProfileEsc($row['text']));
        $out .= '<section class="pf-band pf-band--bone" aria-label="Atributos y actividad"><div class="pui-wrap pf-split">';
        if($attrTiles !== '') $out .= '<div class="pf-col"><div class="pf-head"><p class="pui-kicker">Poder</p><h2 class="pui-h2">Atributos</h2></div><dl class="pf-tiles pf-tiles--attr profile-attributes">'.$attrTiles.'</dl></div>';
        $out .= '<div class="pf-col"><div class="pf-head"><p class="pui-kicker">Actividad</p><h2 class="pui-h2">En el continente</h2></div><dl class="pf-tiles pf-tiles--detail profile-details">'.$detail.'</dl></div>';
        $out .= '</div></section>';
        $out .= '<div class="pf-foot"><div class="pui-wrap">'.panicProfileBack('player', 'bottom').'</div></div></div>';
        return $out;
    }

    /** Guild. $roster: nombre => ['class'=>id,'level'=>n,'resets'=>n,'mresets'=>n,'online'=>bool] (opcional, solo nombres de personaje). */
    function panicProfileRenderGuild($html, $roster = []) {
        if(!preg_match('~<span\b[^>]*class=["\']guild_name["\'][^>]*>(.*?)</span>~si', $html, $nm)) return null;
        $name = panicProfileText($nm[1]);
        $mark = null;
        if(preg_match('~<span\b[^>]*class=["\']guild_logo["\'][^>]*>.*?<img\b[^>]*src=["\']([^"\']+)["\']~si', $html, $lm)) {
            $src = html_entity_decode($lm[1], ENT_QUOTES, 'UTF-8'); if(preg_match('~^(https?://|/)~i', $src)) $mark = $src;
        }
        $info = preg_match('~<table\b[^>]*class=["\']table["\'][^>]*>(.*?)</table>~si', $html, $tm) ? $tm[1] : '';
        $master = null; $score = null; $count = null;
        foreach(panicProfileRows($info) as $row) {
            $k = panicProfileLabelKey($row['label']);
            if($k === 'master') $master = panicProfileLink($row['html']) ?: ['href'=>null,'text'=>$row['text']];
            elseif($k === 'score') $score = $row['text'];
            elseif($k === 'members') $count = $row['text'];
        }
        $members = [];
        if(preg_match('~<div\b[^>]*guild_members_list[^>]*>(.*)$~si', $html, $ml) && preg_match_all('~<div\b[^>]*class=["\']col-[^"\']*["\'][^>]*>(.*?)</div>~si', $ml[1], $all)) {
            foreach($all[1] as $cell) {
                $link = panicProfileLink($cell); $text = $link ? $link['text'] : panicProfileText($cell);
                if($text !== '') $members[] = ['name'=>$text, 'href'=>$link ? $link['href'] : null];
            }
        }
        $masterName = $master ? $master['text'] : null;
        $online = 0; $known = false;
        foreach($members as $i=>$m) {
            $d = $roster[$m['name']] ?? null; $members[$i]['data'] = $d; $members[$i]['i'] = $i;
            if($d) { $known = true; if(!empty($d['online'])) $online++; }
        }
        usort($members, function($a, $b) use ($masterName) {
            $rank = function($m) use ($masterName) { $d = $m['data'] ?? []; return [$m['name'] === $masterName ? 0 : 1, empty($d['online']) ? 1 : 0, -(int)($d['mresets'] ?? 0), -(int)($d['resets'] ?? 0), -(int)($d['level'] ?? 0), $m['i']]; };
            return $rank($a) <=> $rank($b);
        });
        $masterData = $masterName !== null ? ($roster[$masterName] ?? null) : null;
        $out = '<div class="pf pf--guild pui">';
        $meta = '';
        if($master) $meta .= '<span class="pf-chip"><small>Líder</small>'.($master['href'] ? '<a href="'.panicProfileEsc($master['href']).'">'.panicProfileEsc($master['text']).'</a>' : '<b>'.panicProfileEsc($master['text']).'</b>').'</span>';
        $memberTotal = $count !== null ? $count : (string)count($members);
        $meta .= '<span class="pf-chip"><small>Miembros</small><b>'.panicProfileEsc($memberTotal).'</b></span>';
        $out .= panicProfileHero([
            'type'=>'guild', 'kicker'=>'Perfil de guild', 'name'=>$name, 'mark'=>$mark,
            'art'=>$masterData ? panicProfileClassArt(panicProfileClassKeyFromId($masterData['class'])) : null,
            'meta'=>$meta,
        ]);
        $tiles = panicProfileTile('Miembros', panicProfileEsc($memberTotal));
        if($known) $tiles .= panicProfileTile('En línea ahora', panicProfileFormat($online), '', 'pf-tile--online');
        if($score !== null) $tiles .= panicProfileTile('Puntos', panicProfileEsc($score));
        if($master) $tiles .= panicProfileTile('Líder', $master['href'] ? '<a href="'.panicProfileEsc($master['href']).'">'.panicProfileEsc($master['text']).'</a>' : panicProfileEsc($master['text']), '', 'pf-tile--guild');
        $out .= '<section class="pf-band pf-band--night" aria-labelledby="pf-guild-title"><div class="pui-wrap"><div class="pf-head"><p class="pui-kicker">La guild</p><h2 class="pui-h2" id="pf-guild-title">En números</h2></div><dl class="pf-tiles pf-tiles--progress profile-progress">'.$tiles.'</dl></div></section>';
        $out .= '<section class="pf-band pf-band--bone" aria-labelledby="pf-members-title"><div class="pui-wrap"><div class="pf-head"><p class="pui-kicker">Integrantes</p><h2 class="pui-h2" id="pf-members-title">Miembros</h2></div>';
        if(!$members) $out .= '<p class="pf-empty">Esta guild todavía no tiene miembros a la vista.</p>';
        else {
            $out .= '<ul class="pf-members">';
            foreach($members as $m) {
                $d = $m['data']; $key = $d ? panicProfileClassKeyFromId($d['class']) : null;
                $cls = $d && function_exists('getPlayerClass') ? (string)getPlayerClass((int)$d['class']) : '';
                $tag = $m['href'] ? 'a' : 'div';
                $out .= '<li><'.$tag.' class="pf-member'.(!empty($d['online']) ? ' is-online' : '').'"'.($m['href'] ? ' href="'.panicProfileEsc($m['href']).'"' : '').'>';
                $out .= '<img class="pf-member-img" src="'.panicProfileEsc(panicProfileAvatar($key)).'" alt="" width="72" height="72" loading="lazy" decoding="async">';
                $out .= '<span class="pf-member-copy"><span class="pf-member-name">'.panicProfileEsc($m['name']).'</span>';
                $line = [];
                if($d) { $line[] = 'Nivel '.panicProfileFormat($d['level']); $line[] = panicProfileFormat($d['resets']).' RR'; if(!empty($d['mresets'])) $line[] = panicProfileFormat($d['mresets']).' MR'; }
                if($cls !== '') $out .= '<span class="pf-member-meta pf-member-class">'.panicProfileEsc($cls).'</span>';
                if($line) $out .= '<span class="pf-member-meta">'.implode(' · ', $line).'</span>';
                if($d) $out .= '<span class="pf-dot'.(!empty($d['online']) ? ' is-online' : '').'"><i aria-hidden="true"></i>'.(!empty($d['online']) ? 'En línea' : 'Desconectado').'</span>';
                $out .= '</span>'.($m['name'] === $masterName ? '<span class="pf-member-side"><span class="pf-lead">Líder</span></span>' : '');
                $out .= '</'.$tag.'></li>';
            }
            $out .= '</ul>';
        }
        $out .= '</div></section><div class="pf-foot"><div class="pui-wrap">'.panicProfileBack('guild', 'bottom').'</div></div></div>';
        return $out;
    }

    function panicProfileGuildMemberNames($html) {
        $names = [];
        if(preg_match('~<div\b[^>]*guild_members_list[^>]*>(.*)$~si', (string)$html, $ml) && preg_match_all('~<div\b[^>]*class=["\']col-[^"\']*["\'][^>]*>(.*?)</div>~si', $ml[1], $all)) {
            foreach($all[1] as $cell) { $t = panicProfileText($cell); if($t !== '') $names[] = $t; }
        }
        return array_values(array_unique($names));
    }

    function panicProfileRender($html, $type, $roster = []) {
        $out = $type === 'guild' ? panicProfileRenderGuild((string)$html, is_array($roster) ? $roster : []) : panicProfileRenderPlayer((string)$html);
        return $out ?? '<div class="pf pui">'.panicProfileFallback($html, $type).'</div>';
    }
}
