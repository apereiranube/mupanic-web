"""Promoción de beta a main: prueba de archivos y restauración byte a byte. Sin SQL ni hosting."""
import hashlib, json, os, subprocess, tempfile
from pathlib import Path

repo = Path(__file__).resolve().parents[1]
php = os.environ.get('PHP_BIN', 'php')
tool = repo / 'tools/main/promover-beta.php'

def run(home, action):
    code = "define('MUPANIC_PROMOVER_TEST',true);require $argv[1];echo " + ('promApply' if action == 'apply' else 'promRestore') + "($argv[2]);"
    return subprocess.run([php, '-n', '-r', code, str(tool), str(home)], capture_output=True, text=True)

def hashes(path):
    out = {}
    for p in sorted(path.rglob('*')):
        rel = str(p.relative_to(path))
        if p.is_file(): out[rel] = hashlib.sha256(p.read_bytes()).hexdigest()
        elif p.is_dir(): out[rel + '/'] = 'dir'
    return out

GUARD = "# MU PANIC: prevent accessing this subdomain through a main folder alias\nRewriteEngine On\nRewriteCond %{HTTP_HOST} !^beta\\.mupanic\\.com\\.ar(?::[0-9]+)?$ [NC]\nRewriteRule ^ - [R=404,END]\n"
HANDLER = "# php -- BEGIN cPanel-generated handler, do not edit\n<IfModule mime_module>\n  AddHandler application/x-httpd-ea-php83 .php\n</IfModule>\n# php -- END cPanel-generated handler, do not edit\n"

def make(home, mode='website'):
    root = home / 'public_html'; beta = root / 'beta'
    for d in ['includes/config', 'templates/mupanic/inc', 'templates/mupanic/api', 'modules', 'admincp', 'api', 'img']:
        (beta / d).mkdir(parents=True, exist_ok=True)
    (beta / 'index.php').write_text('<?php echo "webengine";')
    (beta / 'includes/webengine.php').write_text('<?php // core')
    (beta / 'includes/config/webengine.json').write_text('{"SQL_PASS":"secreto"}')
    (beta / 'templates/mupanic/index.php').write_text('<?php // plantilla')
    (beta / 'templates/mupanic/inc/launch-config.php').write_text("<?php return ['productionMode'=>'%s'];" % mode)
    (beta / 'templates/mupanic/api/recharge-worker.php').write_text('<?php // worker')
    (beta / 'favicon.ico').write_bytes(b'\x00ico')
    (beta / '.well-known/acme-challenge').mkdir(parents=True)
    (beta / '.well-known/acme-challenge/tok').write_text('beta-cert')
    (beta / 'error_log').write_text('log de beta')
    (beta / '.htaccess').write_text(GUARD + "RewriteEngine On\nRewriteRule ^([^/]+)/?$ index.php?page=$1 [QSA,L]\n")
    (root / 'auditoria-web').mkdir(parents=True)
    (root / 'auditoria-web/.htaccess').write_text('AuthType Basic\nRequire valid-user\n')
    (root / 'templates/mupanic/api').mkdir(parents=True)
    (root / 'templates/mupanic/api/recharge-worker.php').write_text('<?php require "/home/mupanic/main-services/api/recharge-worker.php";')
    (root / 'index.php').write_text('<?php // landing')
    (root / 'favicon.ico').write_bytes(b'\x00landing')
    (root / 'robots.txt').write_text('User-agent: *')
    (root / '.well-known/acme-challenge').mkdir(parents=True)
    (root / '.htaccess').write_text("# MU PANIC LANDING ONLY v1\nRewriteEngine On\n\n# Previous hosting configuration retained below\n" + HANDLER)
    (home / 'main-services/api').mkdir(parents=True)
    (home / 'main-services/api/recharge-worker.php').write_text('<?php // servicio')
    (home / 'main-landing-state.json').write_text(json.dumps({'status': 'active'}))
    (home / 'payments-private').mkdir()
    (home / 'payments-private/ledger.json').write_text('{"pagos":"intactos"}')

with tempfile.TemporaryDirectory(prefix='mupanic-prom-') as t:
    home = Path(os.path.realpath(t)); make(home)
    root = home / 'public_html'
    before_root = hashes(root); before_home = {k: v for k, v in hashes(home).items() if not k.startswith('public_html')}
    pay = hashes(home / 'payments-private')
    r = run(home, 'apply'); assert r.returncode == 0, r.stderr + r.stdout
    st = json.loads((home / 'main-promoted-state.json').read_text()); assert st['status'] == 'active'
    assert (root / 'index.php').read_text() == '<?php echo "webengine";'
    assert (root / 'includes/config/webengine.json').read_text() == '{"SQL_PASS":"secreto"}'
    assert (root / 'templates/mupanic/api/recharge-worker.php').read_text() == '<?php // worker'
    assert (root / 'favicon.ico').read_bytes() == b'\x00ico'
    assert (root / 'robots.txt').exists() and (root / '.well-known').is_dir()
    assert not (root / '.well-known/acme-challenge/tok').exists() and not (root / 'error_log').exists()
    ht = (root / '.htaccess').read_text()
    assert 'R=404' not in ht and 'index.php?page=$1' in ht and 'ea-php83' in ht and 'LANDING ONLY' not in ht
    bht = (root / 'beta/.htaccess').read_text()
    assert bht.startswith('# MU PANIC BETA REDIRIGE A MAIN v1') and 'https://mupanic.com.ar/$1 [R=301,L]' in bht and GUARD in bht
    assert not (home / 'main-landing-state.json').exists() and not (home / 'main-services').exists()
    assert hashes(home / 'payments-private') == pay
    # beta sigue intacta salvo su .htaccess
    for k, v in before_root.items():
        if k.startswith('beta/') and k != 'beta/.htaccess': assert hashes(root).get(k) == v, k
    # no se puede aplicar dos veces
    r = run(home, 'apply'); assert r.returncode != 0
    r = run(home, 'restore'); assert r.returncode == 0, r.stderr + r.stdout
    assert hashes(root) == before_root, 'restauración exacta de public_html'
    after_home = {k: v for k, v in hashes(home).items() if not k.startswith('public_html') and not k.startswith('main-backups')}
    assert after_home == before_home, 'restauración exacta de main-services y estado'
    assert not (home / 'main-promoted-state.json').exists()

# Beta con "coming-soon": aborta sin tocar nada.
with tempfile.TemporaryDirectory(prefix='mupanic-prom-') as t:
    home = Path(os.path.realpath(t)); make(home, 'coming-soon')
    before = hashes(home)
    r = run(home, 'apply'); assert r.returncode != 0 and 'coming-soon' in r.stderr + r.stdout
    assert hashes(home) == before

# Sin landing activa (estado inesperado): aborta.
with tempfile.TemporaryDirectory(prefix='mupanic-prom-') as t:
    home = Path(os.path.realpath(t)); make(home)
    (home / 'main-landing-state.json').unlink(); before = hashes(home)
    r = run(home, 'apply'); assert r.returncode != 0 and hashes(home) == before

print('PASS: beta copiada a main, landing archivada, beta redirige, pagos intactos, restauración exacta y abortos seguros.')
