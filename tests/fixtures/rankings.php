<?php
// Visual fixture only: invented names and scores never enter production or SQL.
define('access',true);
$mode=getenv('RANKINGS_FIXTURE') ?: 'level';
$guild=$mode==='guilds';
$categories=['level'=>'Nivel','killers'=>'Asesinatos','guilds'=>'Guilds','master'=>'Nivel Master','resets'=>'Resets','grandresets'=>'Master Resets','bloodcastle'=>'Blood Castle','devilsquare'=>'Devil Square','duels'=>'Duelos'];
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Ranking · fixture</title><link rel="stylesheet" href="/templates/mupanic/css/style.css"><link rel="stylesheet" href="/templates/mupanic/css/rankings.css"></head>
<body class="is-inner is-rankings"><header class="site-header"><div class="shell nav-shell"><a class="brand" href="/"><span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 40 48"><path d="M3 38V9l17 15L37 9v29L20 47z M3 9V2l17 15L37 2v7 M20 24v23"/></svg></span><span class="brand-copy"><strong>MU PANIC</strong><small>EL CONTINENTE TE ESPERA</small></span></a><nav class="main-nav" aria-label="Principal"><a href="/information/">El servidor</a><a href="/info/">Atlas</a><a href="/rankings/" aria-current="page">Rankings</a></nav><div class="nav-actions"><a class="nav-cta" href="/register/">Crear cuenta</a></div></div></header>
<main><?php include __DIR__.'/../../overlay/templates/mupanic/inc/rankings-hero.php'; ?>
<section class="inner-content"><div class="shell"><div class="module-surface"><div class="rankings_menu"><?php foreach($categories as $key=>$label) { ?><a href="/rankings/<?php echo $key; ?>/"<?php echo $key===($mode==='empty'?'bloodcastle':$mode)?' class="active"':''; ?>><?php echo $label; ?></a><?php } ?></div>
<?php if($mode==='empty') { ?><p class="rankings-empty" role="status">Todavía no hay resultados en esta categoría.</p><?php } else { ?>
<?php if(!$guild) { ?><ul class="rankings-class-filter"><li><a href="#" onclick="rankingsFilterRemove()">All</a></li><li><a href="#" onclick="rankingsFilterByClass(16,17,18)">Knights</a></li><li><a href="#" onclick="rankingsFilterByClass(0,1,2)">Wizards</a></li></ul><?php } ?>
<table class="rankings-table"><tr><td></td><td><?php echo $guild?'Guild':'Class'; ?></td><td><?php echo $guild?'Guild Master':'Character'; ?></td><td><?php echo $guild?'Score':'Level'; ?></td></tr>
<?php for($i=1;$i<=45;$i++) { ?><tr data-class-id="<?php echo $i%2?18:2; ?>"><td class="rankings-table-place"><?php echo $i; ?></td><td><?php if($guild) { ?><a href="/profile/guild/Guild<?php echo $i; ?>/">Guild<?php echo $i; ?></a><?php } else { ?><span class="rankings-class-label"><?php echo $i%2?'Blade Master':'Grand Master'; ?></span><?php } ?></td><td><a href="/profile/player/Hero<?php echo $i; ?>/" target="_blank">Hero<?php echo $i; ?></a></td><td><?php echo 600-$i; ?></td></tr><?php } ?></table>
<?php } ?></div></div></section></main><script src="/templates/mupanic/js/rankings.js"></script></body></html>
