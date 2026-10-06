<?php
// Fictitious test data in the native public-profile markup, never a production data source.
$mode=getenv('PROFILE_FIXTURE') ?: 'online';
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Profile fixture</title><link rel="stylesheet" href="/templates/mupanic/css/style.css"><link rel="stylesheet" href="/templates/mupanic/css/profiles.css"></head><body class="is-inner is-profile"><header class="site-header">
    <div class="shell nav-shell">
        <a class="brand" href="/">
            <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 40 48"><path d="M3 38V9l17 15L37 9v29L20 47z M3 9V2l17 15L37 2v7 M20 24v23"/></svg></span>
            <span class="brand-copy"><strong>MU PANIC</strong><small>EL CONTINENTE TE ESPERA</small></span>
        </a>

        <button class="menu-toggle" type="button" aria-label="Abrir menú" aria-expanded="false" aria-controls="main-navigation">
            <span></span><span></span>
        </button>

        <nav class="main-nav" id="main-navigation" aria-label="Navegación principal">
            <a class="nav-main-link" href="/#continente"><span>El continente</span></a>
            <a class="nav-main-link" href="/information/"><span>El servidor</span></a>
            <a class="nav-main-link" href="/info/"><span>Atlas</span></a>
            <a class="nav-main-link" href="/rankings/"><span>Rankings</span></a>
            <a class="nav-main-link" href="/downloads/"><span>Descargas</span></a>
            <a class="nav-discord" href="https://discord.com/" target="_blank" rel="noopener noreferrer"><span>Discord</span><b aria-hidden="true">↗</b></a>
            <a class="mobile-account" href="/login/">Ingresar</a>
        </nav>

        <div class="nav-actions">

                            <a class="account-link" href="/login/">Ingresar</a>
                <a class="nav-cta" href="/register/">Crear cuenta</a>
                    </div>
    </div>
</header>
<main><section class="inner-hero"><div class="shell inner-head"><div><span class="eyebrow">PERSONAJE / MU PANIC</span><h1>Perfil de personaje</h1></div><a class="profile-back" href="/rankings/">← Volver al ranking</a></div></section><section class="inner-content"><div class="shell"><div class="module-surface"><div class="profiles_player_card BM"><div class="profiles_player_content"><table class="profiles_player_table"><tr><td class="cname">FixtureHero</td></tr><tr><td class="cclass">Blade Master</td></tr></table><table class="profiles_player_table profiles_player_table_info"><tr><td>Level</td><td>400</td></tr><tr><td>Master Level</td><td>150</td></tr><tr><td>Resets</td><td>15</td></tr><?php if($mode!=='minimal') { ?><tr><td>Grand Resets</td><td>3</td></tr><?php } ?><tr><td>Strength</td><td>65,000</td></tr><tr><td>Agility</td><td>65,000</td></tr><tr><td>Vitality</td><td>65,000</td></tr><tr><td>Energy</td><td>65,000</td></tr><tr><td>Kills</td><td>0</td></tr><tr><td>Guild</td><td><a href="/profile/guild/FixtureGuild/" target="_blank">FixtureGuild</a></td></tr><?php if($mode==='unknown') { ?><tr><td>Custom metric</td><td>123</td></tr><?php } ?><tr><td>Status</td><td class="<?php echo $mode==='offline'?'isoffline':($mode==='unknown'?'':'isonline'); ?>"><?php echo $mode==='offline'?'Offline':($mode==='unknown'?'Not available':'Online'); ?></td></tr></table></div></div></div></div></section></main><script src="/templates/mupanic/js/profiles.js"></script></body></html>