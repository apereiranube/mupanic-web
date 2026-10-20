"""Validate the complete template, actual assets and public data without SQL."""
import os, re, json, hashlib, subprocess
from pathlib import Path
from lxml import html
root=Path(__file__).resolve().parents[1]
php=os.environ.get('PHP_BIN','php')
def render(page='',*flags):
    value=subprocess.check_output([php,'-n',str(root/'tests/fixtures/experience.php'),page,*flags],text=True)
    assert '<!DOCTYPE html>' in value and 'Warning:' not in value
    doc=html.fromstring(value)
    assert len(doc.xpath('//h1'))==1
    ids=doc.xpath('//@id'); assert len(ids)==len(set(ids)), 'Duplicate IDs'
    for url in doc.xpath('//img/@src|//source/@srcset|//script[@src]/@src|//link[@rel="stylesheet"]/@href'):
        if url.startswith('/templates/mupanic/'):
            path=root/'overlay/templates/mupanic'/url.split('/templates/mupanic/',1)[1].split('?')[0]
            assert path.is_file(), f'Missing asset {path}'
    for href in doc.xpath('//a/@href'):
        if href.startswith('#'): assert href[1:] in ids, f'Broken local anchor {href}'
    return value,doc
home,doc=render()
assert len(doc.xpath('//dl[@class="px-facts"]/div'))==6
assert doc.xpath('//*[@data-home-online]')[0].text_content()=='7'
assert 'hero-rf-bk-v1.webp' in home and 'fractura-nexo-caos-v1.webp' in home
assert home.index('home-fractura-title')<home.index('home-companions-title')<home.index('home-games-title')
assert len(doc.xpath('//ol[@data-home-events]/li'))<=3
_,missing=render('','--missing-cache')
assert missing.xpath('//*[@data-home-online]')[0].text_content()=='—'
_,logged=render('','--logged')
assert 'MI CUENTA' in logged.xpath('//section[@class="px-hero"]')[0].text_content()
server,doc=render('server')
for fragment in ['progresion','fractura','mascotas','cliente','mercado','eventos','minijuegos']:
    assert doc.xpath(f'//*[@id="{fragment}"]'),fragment
assert len(doc.xpath('//details[@class="px-detail"]'))==4
pets=json.loads((root/'overlay/templates/mupanic/inc/atlas-vinculos.json').read_text())['entries']
for name in ['Elyndra','Aurion','Vaerion','Grum']:
    assert name in server
    pet=next(p for p in pets if p['name']==name)
    for stat in pet['stats']: assert stat in server
assert len(doc.xpath('//ol[@class="px-games-index"]/li'))==8
for old in ['Battle Pass','Tributo Diario','300 WCoin','Camino del Héroe']:
    assert old not in server,old
manifest=json.loads((root/'tools/home-art-20261020.json').read_text())
for asset in manifest['assets']:
    file=root/'overlay/templates/mupanic'/asset['file']
    assert hashlib.sha256(file.read_bytes()).hexdigest()==asset['sha256']
print('PASS: guest, signed-in and missing-cache homepage; complete server page; links, images, four pets, eight games, artwork integrity.')
