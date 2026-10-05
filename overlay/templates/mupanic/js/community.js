/* Official public widget data; no bot, credentials or Discord login required. */
(() => {
    'use strict';
    const panel = document.querySelector('[data-discord-guild]');
    if (!panel || !/^\d+$/.test(panel.dataset.discordGuild)) return;
    const guild = panel.dataset.discordGuild;
    const status = panel.querySelector('.community-status');
    const content = panel.querySelector('.community-live-content');
    const membersList = panel.querySelector('[data-discord-members]');
    const channelsList = panel.querySelector('[data-discord-channels]');
    let started = false, busy = false, lastAttempt = 0;
    const make = (tag, className, text) => {
        const el = document.createElement(tag);
        if (className) el.className = className;
        if (text !== undefined) el.textContent = text;
        return el;
    };
    async function refresh() {
        if (busy || document.hidden) return;
        busy = true;
        lastAttempt = Date.now();
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 10000);
        try {
            const response = await fetch(`https://discord.com/api/guilds/${guild}/widget.json`, {
                credentials: 'omit', signal: controller.signal
            });
            if (!response.ok) throw new Error('Widget unavailable');
            const data = await response.json();
            if (data.id !== guild || !Number.isInteger(data.presence_count) || data.presence_count < 0 ||
                !Array.isArray(data.members) || !Array.isArray(data.channels)) throw new Error('Invalid widget');
            panel.querySelector('[data-discord-count]').textContent = data.presence_count;
            membersList.replaceChildren();
            data.members.slice(0, 8).forEach(member => {
                const name = typeof member.username === 'string' ? member.username : 'Miembro';
                const row = make('li');
                const avatar = make('span', 'community-member-avatar', name.slice(0, 1).toUpperCase());
                try {
                    const url = new URL(member.avatar_url);
                    if (url.protocol === 'https:' && url.hostname === 'cdn.discordapp.com') {
                        const img = make('img'); img.src = url.href; img.alt = ''; img.loading = 'lazy';
                        img.addEventListener('error', () => { avatar.textContent = name.slice(0, 1).toUpperCase(); }); avatar.replaceChildren(img);
                    }
                } catch (_) { /* Keep the initial when an avatar is absent. */ }
                row.append(avatar, make('span', 'community-member-name', name));
                membersList.append(row);
            });
            if (!data.members.length) membersList.append(make('li', '', data.presence_count ? 'Lista de miembros no disponible.' : 'Todavía no hay miembros conectados.'));
            const more = panel.querySelector('[data-discord-more]');
            const remainder = Math.max(0, data.presence_count - Math.min(8, data.members.length));
            more.hidden = !remainder;
            more.textContent = `Y ${remainder} más conectados en Discord.`;
            channelsList.replaceChildren();
            data.channels.filter(ch => /^\d+$/.test(ch.id) && typeof ch.name === 'string')
                .sort((a, b) => (a.position || 0) - (b.position || 0)).slice(0, 10).forEach(ch => {
                    const row = make('li'); const link = make('a');
                    link.href = `https://discord.com/channels/${guild}/${ch.id}`;
                    link.target = '_blank'; link.rel = 'noopener noreferrer';
                    const icon = make('span', 'community-voice-icon', '↗'); icon.setAttribute('aria-hidden', 'true');
                    link.append(icon, make('span', '', ch.name)); row.append(link); channelsList.append(row);
                });
            if (!channelsList.children.length) channelsList.append(make('li', 'community-more', 'Los canales se pueden consultar dentro de Discord.'));
            content.hidden = false; status.hidden = true;
        } catch (_) {
            status.hidden = false;
            status.textContent = content.hidden
                ? 'No pudimos consultar quién está conectado. Podés abrir la comunidad con el enlace de abajo.'
                : 'No pudimos actualizar el panel. Estos son los últimos datos consultados.';
        } finally { clearTimeout(timeout); busy = false; }
    }
    function start() {
        if (started) return;
        started = true; refresh();
        setInterval(() => { if (!document.hidden && panel.getBoundingClientRect().bottom > 0 && panel.getBoundingClientRect().top < innerHeight) refresh(); }, 300000);
    }
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(entries => {
            if (entries.some(entry => entry.isIntersecting)) { start(); observer.disconnect(); }
        }); observer.observe(panel);
    } else start();
    document.addEventListener('visibilitychange', () => {
        if (started && !document.hidden && Date.now() - lastAttempt >= 300000) refresh();
    });
})();
