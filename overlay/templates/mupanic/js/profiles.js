(() => {
    'use strict';
    // Perfiles: el HTML ya llega armado desde inc/profile-view.php. Acá solo se ajusta "Volver al ranking"
    // para regresar a la categoría desde la que se abrió el perfil (misma web, dentro de /rankings/).
    const backs = [...document.querySelectorAll('.is-profile .profile-back')];
    if (!backs.length) return;
    const root = new URL(backs[0].getAttribute('href'), location.href);
    const rankingsPath = root.pathname.replace(/guilds\/$/, '');
    const validRanking = value => {
        try { const url = new URL(value); return url.origin === location.origin && url.pathname.startsWith(rankingsPath) ? url : null; } catch (_) { return null; }
    };
    let target = validRanking(document.referrer);
    try {
        const saved = JSON.parse(sessionStorage.getItem('mupanic:ranking-return'));
        if (saved && saved.to === location.href && Date.now() - saved.at >= 0 && Date.now() - saved.at < 86400000) target = validRanking(saved.from) || target;
    } catch (_) {}
    if (!target) return;
    backs.forEach(back => {
        back.href = target.href;
        back.addEventListener('click', event => {
            if (!event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey && document.referrer === target.href && history.length > 1) { event.preventDefault(); history.back(); }
        });
    });
})();
