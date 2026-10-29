<?php
// Fictitious test data in the native WebEngine public-profile markup, never a production data source.
// PROFILE_FIXTURE: online | offline | unknown | minimal | dl | rf | guild | guild-nomark | error
define('access', true);
define('__BASE_URL__', '/');
define('__PATH_TEMPLATE__', '/templates/mupanic/');
function getPlayerClass($id) { return [0=>'Dark Wizard',1=>'Soul Master',16=>'Dark Knight',17=>'Blade Knight',33=>'Muse Elf',48=>'Magic Gladiator',64=>'Dark Lord',80=>'Summoner',96=>'Rage Fighter'][$id] ?? 'Personaje'; }
require __DIR__.'/../../overlay/templates/mupanic/inc/profile-view.php';
$mode = getenv('PROFILE_FIXTURE') ?: 'online';
$roster = [];
$player = function($name, $code, $class, $rows, $status) {
    $html = '<div class="page-title"><span>Player Profile</span></div><div class="profiles_player_card '.$code.'"><div class="profiles_player_content"><table class="profiles_player_table"><tr><td class="cname">'.$name.'</td></tr><tr><td class="cclass">'.$class.'</td></tr></table><table class="profiles_player_table profiles_player_table_info">';
    foreach($rows as $label=>$value) $html .= '<tr><td>'.$label.'</td><td>'.$value.'</td></tr>';
    $html .= '<tr><td>Status</td><td class="'.($status==='Offline'?'isoffline':($status==='Online'?'isonline':'')).'">'.$status.'</td></tr></table></div></div>';
    return $html;
};
$guildLink = '<a href="/profile/guild/FixtureGuild/" target="_blank">FixtureGuild</a>';
if($mode === 'guild' || $mode === 'guild-nomark') {
    $type = 'guild';
    $members = ['FixtureHero','Lordia','Puños','Brujo','Novata','SinDatos'];
    $html = '<div class="page-title"><span>Guild Profile</span></div><div class="profiles_guild_card"><div class="row"><div class="col-xs-6 text-center"><span class="guild_logo">'.($mode==='guild' ? '<img src="/templates/mupanic/img/character-avatars/avatar.jpg" width="150" height="150">' : '').'</span></div><div class="col-xs-6 text-center"><span class="guild_name">FixtureGuild</span><table class="table"><tr><td class="text-right">Guild Master:</td><td class="text-left"><a href="/profile/player/Lordia/" target="_blank">Lordia</a></td></tr><tr><td class="text-right">Score:</td><td class="text-left">1,250</td></tr><tr><td class="text-right">Members:</td><td class="text-left">'.count($members).'</td></tr></table></div></div><hr><div class="row"><div class="col-xs-8 col-xs-offset-2 text-center"><span class="guild_members">Members</span><div class="row guild_members_list">';
    foreach($members as $m) $html .= '<div class="col-xs-3"><a href="/profile/player/'.rawurlencode($m).'/" target="_blank">'.$m.'</a></div>';
    $html .= '</div></div></div></div>';
    $roster = [
        'FixtureHero'=>['class'=>17,'level'=>400,'resets'=>15,'mresets'=>3,'online'=>true],
        'Lordia'=>['class'=>64,'level'=>380,'resets'=>12,'mresets'=>0,'online'=>false],
        'Puños'=>['class'=>96,'level'=>350,'resets'=>20,'mresets'=>1,'online'=>true],
        'Brujo'=>['class'=>1,'level'=>220,'resets'=>2,'mresets'=>0,'online'=>false],
        'Novata'=>['class'=>33,'level'=>40,'resets'=>0,'mresets'=>0,'online'=>false],
    ];
} elseif($mode === 'error') {
    $type = 'player';
    $html = '<div class="page-title"><span>Player Profile</span></div><div class="alert alert-danger" role="alert">Your request could not be completed, please try again.</div>';
} elseif($mode === 'dl') {
    $type = 'player';
    $html = $player('Lordia','LE','Lord Emperor',['Level'=>'380','Master Level'=>'90','Resets'=>'12','Grand Resets'=>'0','Strength'=>'32,000','Agility'=>'18,500','Vitality'=>'9,000','Energy'=>'12,000','Command'=>'40,000','Kills'=>'27','Guild'=>$guildLink],'Offline');
} elseif($mode === 'rf') {
    $type = 'player';
    $html = $player('Puños','FM','Fist Master',['Level'=>'350','Master Level'=>'0','Resets'=>'20','Grand Resets'=>'1','Strength'=>'25,000','Agility'=>'12,000','Vitality'=>'30,000','Energy'=>'4,000','Kills'=>'3'],'Online');
} else {
    $type = 'player';
    $rows = ['Level'=>'400','Master Level'=>'150','Resets'=>'15'];
    if($mode !== 'minimal') $rows['Grand Resets'] = '3';
    $rows += ['Strength'=>'65,000','Agility'=>'65,000','Vitality'=>'65,000','Energy'=>'65,000','Kills'=>'0','Guild'=>$guildLink];
    if($mode === 'unknown') $rows['Custom metric'] = '123';
    $html = $player('FixtureHero','BM','Blade Master',$rows,$mode==='offline'?'Offline':($mode==='unknown'?'Not available':'Online'));
}
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Profile fixture</title><link rel="stylesheet" href="/templates/mupanic/css/style.css"><link rel="stylesheet" href="/templates/mupanic/css/atlas.css"><link rel="stylesheet" href="/templates/mupanic/css/panic-ui.css"><link rel="stylesheet" href="/templates/mupanic/css/profiles.css"></head><body class="is-inner is-profile"><header class="site-header">
    <div class="shell nav-shell">
        <a class="brand" href="/">
            <span class="brand-copy"><strong>MU PANIC</strong><small>EL CONTINENTE TE ESPERA</small></span>
        </a>
        <nav class="main-nav" id="main-navigation" aria-label="Navegación principal">
            <a class="nav-main-link" href="/information/"><span>El servidor</span></a>
            <a class="nav-main-link" href="/rankings/"><span>Rankings</span></a>
            <a class="nav-main-link" href="/downloads/"><span>Descargas</span></a>
        </nav>
        <div class="nav-actions"><a class="account-link" href="/login/">Ingresar</a><a class="nav-cta" href="/register/">Crear cuenta</a></div>
    </div>
</header>
<main id="main-content"><section class="inner-content"><div class="shell"><div class="module-surface"><?php echo panicProfileRender($html, $type, $roster); ?></div></div></section></main><footer class="footer-modern" style="height:120px;background:#0b0c0e"></footer><script src="/templates/mupanic/js/profiles.js"></script></body></html>
