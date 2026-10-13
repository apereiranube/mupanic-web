// Behavioral tests of public score updates; synthetic data, never deployed.
const vm = require('node:vm');
const fs = require('node:fs');
const assert = require('node:assert/strict');
function element(text = '') {
    const classes = new Set();
    return { textContent: text, hidden: true, disabled: false, style: {}, events: {}, classList: { toggle(k, v) { if (v) classes.add(k); else classes.delete(k); }, add(k) { classes.add(k); }, contains(k) { return classes.has(k); } }, setAttribute() {}, addEventListener(k, v) { this.events[k] = v; }, animate() {} };
}
const elements = new Map();
function node(selector) { if (!elements.has(selector)) elements.set(selector, element()); return elements.get(selector); }
const points = [1, 2].map(id => node('[data-fractura-points="' + id + '"]'));
points[0].textContent = '230'; points[1].textContent = '100';
const root = element(); root.dataset = { fracturaUrl: '/beta/fractura/?fractura_data=1' };
root.querySelector = s => s === '[data-fractura-map-dialog]' ? null : node(s);
root.querySelectorAll = s => s === '[data-fractura-points]' ? points : [];
const document = { hidden: false, querySelector: () => root, addEventListener() {} };
let mode = 'normal', families = { 1: { points: '9007199254740993', members: '1' }, 2: { points: '12000', members: '3' } };
const context = { document, window: { matchMedia: () => ({ matches: false }) }, localStorage: { getItem: () => null, setItem() {} }, Intl, AbortController, setTimeout, clearTimeout, setInterval: () => 1, clearInterval() {}, fetch: async url => { assert.equal(url, root.dataset.fracturaUrl); return { ok: mode !== 'failure', json: async () => ({ schema: 1, updated: Math.floor(Date.now() / 1000), stale: mode === 'stale', factions: families }) }; } };
vm.runInNewContext(fs.readFileSync(__dirname + '/../overlay/templates/mupanic/js/fractura.js', 'utf8'), context);
const refresh = () => node('[data-fractura-refresh]').events.click();
(async () => {
    await refresh();
    assert.equal(points[0].textContent, '9.007.199.254.740.993');
    assert.equal(points[1].textContent, '12.000');
    assert.equal(node('[data-fractura-lead]').textContent, 'Nexo lleva la delantera');
    assert.equal(node('[data-fractura-member-label="1"]').textContent, 'personaje afiliado');
    assert.ok(points[0].classList.contains('is-long'));
    mode = 'failure'; await refresh();
    assert.equal(points[0].textContent, '9.007.199.254.740.993');
    assert.ok(node('[data-fractura-dot]').classList.contains('is-offline'));
    mode = 'normal'; families = { 1: { points: '10', members: '1' }, 2: { points: '30', members: '2' } }; await refresh();
    assert.equal(node('[data-fractura-lead]').textContent, 'Caos lleva la delantera');
    assert.equal(node('[data-fractura-share="2"]').textContent, '75%');
    assert.equal(node('[data-fractura-share="1"]').textContent, '25%');
    families[1].points = '30'; await refresh();
    assert.equal(node('[data-fractura-lead]').textContent, 'Caos y Nexo están igualados');
    mode = 'stale'; await refresh();
    assert.match(node('[data-fractura-status]').textContent, /Última lectura disponible/);
    node('[data-fractura-effects]').events.click();
    assert.ok(root.classList.contains('effects-paused'));
    console.log('PASS: native family identity, exact large points, lead, percentage, stale/error retention and effects pause.');
})().catch(e => { console.error(e); process.exitCode = 1; });
