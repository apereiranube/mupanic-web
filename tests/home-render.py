"""Render the actual homepage with isolated CMS data; never connect to SQL."""
import hashlib, json, os, re, subprocess
from pathlib import Path
from html.parser import HTMLParser

root = Path(__file__).resolve().parents[1]
php = os.environ.get('PHP_BIN', 'php')
class Page(HTMLParser):
    def __init__(self):
        super().__init__(); self.ids=[]; self.resources=[]; self.local_links=[]
    def handle_starttag(self, tag, attrs):
        a=dict(attrs)
        if 'id' in a: self.ids.append(a['id'])
        for key in ['src', 'srcset']:
            if key in a: self.resources.append(a[key])
        if tag=='link' and a.get('rel')=='stylesheet': self.resources.append(a['href'])
        if tag=='a' and a.get('href','').startswith('#'): self.local_links.append(a['href'][1:])

for flags in [[], ['--missing-cache'], ['--logged']]:
    html = subprocess.check_output([php, '-n', str(root/'tests/fixtures/home-launch.php'), '', *flags], text=True)
    assert 'Warning:' not in html and 'Fatal error:' not in html
    assert len(re.findall(r'<h1\b', html)) == 1
    doc=Page(); doc.feed(html)
    assert len(doc.ids)==len(set(doc.ids)), 'Repeated IDs'
    assert all(anchor in doc.ids for anchor in doc.local_links)
    for url in doc.resources:
        if url.startswith('/templates/mupanic/'):
            path=root/'overlay/templates/mupanic'/url.split('/templates/mupanic/')[1].split('?')[0]
            assert path.is_file(), f'Missing resource {path}'
    for name in ['home-furia-title','home-fractura-title','home-companions-title','home-client-title','home-market-title','home-events-title','home-games-title']:
        assert doc.ids.count(name)==1
    assert html.index('home-furia-title') < html.index('home-fractura-title') < html.index('home-companions-title') < html.index('home-games-title')
    assert 'Tu próxima partida' in html and 'no necesita un spot.' in html
    assert 'data-home-version="connected-v10"' in html
    assert 'data-home-update' not in html and 'data-furia-motion' not in html
    assert 'Consultado ' not in html and 'Pausar efectos' not in html
    assert 'ph-roulette-plane' not in html and 'data-scene-fx="combat"' not in html
    assert 'ph-world-divider' not in html and 'ph-status-heading' not in html
    assert html.count('ph-flow-join ph-flow-join-') == 3
    assert 'hub-tienda' in html and 'ph-client-improvements' not in html
    assert 'data-hub-simulator' in html and 'ph-hub-menu-v12' in html
    assert 'hub-desk-v12.webp' in html and html.count('journey-world-v12.webp') == 3
    assert 'hub-real-v4.webp' not in html and 'real-v10.webp' not in html
    assert 'ph-client-name' not in html and 'ph-hub-no-capture' not in html
    assert 'join-market-v10.webp' not in html and 'join-arcade-v10.webp' not in html
    assert 'EL PULSO DEL SERVIDOR' not in html and 'Cada clase tiene su propia Furia' not in html
    assert 'ph-market-trail' not in html
    assert len(re.findall(r'<script[^>]+src="[^"]*/mu-panic-brasas\.js[^"]*"[^>]*\bdefer\b',html))==1
    assert html.rindex('mu-panic-brasas.js')<html.index('</body>')
    assert html.count('data-home-tabs') == 1
    assert 'ph-games-tour' not in html and 'elegi-minijuego' not in html
    cta=html.split('id="empeza-a-jugar"',1)[1].split('</section>',1)[0]
    assert ('usercp/' if '--logged' in flags else 'register/') in cta and 'downloads/' in cta
    assert 'hub-client-20261020' not in html and 'games-client-20261020' not in html
    expected='—' if '--missing-cache' in flags else '7'
    assert f'data-home-online>{expected}</dd>' in html
    if '--logged' in flags: assert '>MI CUENTA <' in html
    else: assert '>CREAR CUENTA <' in html

manifest=json.loads((root/'tools/home-connected-art.json').read_text())
for asset in manifest['assets']:
    file=root/'overlay/templates/mupanic'/asset['file']
    assert hashlib.sha256(file.read_bytes()).hexdigest()==asset['sha256']
assert hashlib.sha256((root/'overlay/templates/mupanic/js/mu-panic-brasas.js').read_bytes()).hexdigest()=='0f3f03ce47f9cbd981e2daa3c2f4b9855dd0789df95fc052b8d59d76ee51dbd6', 'The supplied ember script must remain byte-identical'
print('PASS homepage: guest/signed-in/missing cache, one scene per system, complete resources, working anchors and art integrity.')
