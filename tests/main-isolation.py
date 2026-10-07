"""File migration/rollback integration tests; no SQL, provider calls or hosting writes."""
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile

repo = Path(__file__).resolve().parents[1]
php = os.environ.get('PHP_BIN', 'php')
source = Path(os.environ.get('MAIN_TEMPLATE_SOURCE', str(repo / 'overlay/templates/mupanic')))
tool = repo / 'tools/main/separar-main.php'

def run(home, action):
    code = "define('MUPANIC_MAIN_TEST',true);require $argv[1];"
    if action == 'apply':
        code += 'echo mainApply($argv[2]);'
    elif action == 'restore':
        code += 'echo mainRestore($argv[2]);'
    else:
        code += 'mainRefresh($argv[2],$argv[3]);'
    return subprocess.run([php, '-n', '-r', code, str(tool), str(home), str(source)], capture_output=True, text=True)

def hashes(path):
    return {str(p.relative_to(path)): hashlib.sha256(p.read_bytes()).hexdigest()
            for p in path.rglob('*') if p.is_file()}

with tempfile.TemporaryDirectory(prefix='mupanic-main-') as temp:
    home = Path(temp)
    root = home / 'public_html'
    (root / 'templates').mkdir(parents=True)
    shutil.copytree(source, root / 'templates/mupanic')
    for name in ['beta', 'auditoria-web', 'includes', 'modules', 'admincp', 'api', 'install', 'img']:
        (root / name).mkdir()
        (root / name / 'sentinel.txt').write_text(name)
    (root / 'beta/index.php').write_text('<?php echo "beta";')
    (root / 'index.php').write_text('<?php require "includes/secret.php";')
    (root / 'includes/secret.php').write_text('private credentials fixture')
    (root / 'index.html').write_text('old index')
    (root / '.htaccess').write_text('# php -- BEGIN hosting handler\nRewriteEngine On\n')
    (home / 'payments-private').mkdir()
    (home / 'payments-private/orders.json').write_text('{"sentinel":"untouched"}')
    (home / 'atlas-runtime').mkdir()
    before = hashes(root)
    payments = hashes(home / 'payments-private')

    result = run(home, 'apply')
    assert result.returncode == 0, result.stderr
    state = json.loads((home / 'main-landing-state.json').read_text())
    assert state['status'] == 'active'
    assert Path(state['backup']).is_dir()
    for name in ['includes', 'modules', 'admincp', 'api', 'install', 'img', 'index.html']:
        assert not (root / name).exists(), name
        assert (Path(state['backup']) / 'original' / name).exists(), name
    assert 'WebEngine' in (root / 'index.php').read_text()
    assert 'includes/' not in (root / 'index.php').read_text()
    html = subprocess.run([php, '-n', str(root / 'index.php')], capture_output=True, text=True)
    assert html.returncode == 0, html.stderr
    assert 'PRÓXIMAMENTE' in html.stdout and 'id="medusa"' in html.stdout
    api_before = {p.name: p.read_bytes() for p in (source / 'api').glob('*.php')}
    for name, original in api_before.items():
        assert (home / 'main-services/api' / name).read_bytes() == original
        assert str(home / 'main-services/api' / name) in (root / 'templates/mupanic/api' / name).read_text()
    assert hashes(home / 'payments-private') == payments
    for name in ['beta', 'auditoria-web']:
        child_hashes = hashes(root / name)
        child_hashes.pop('.htaccess', None)
        assert child_hashes == {p[len(name)+1:]: v for p, v in before.items() if p.startswith(name+'/')}
        assert '[R=404,END]' in (root / name / '.htaccess').read_text()
    result = run(home, 'refresh')
    assert result.returncode == 0, result.stderr
    assert not (root / 'includes').exists()
    assert hashes(home / 'payments-private') == payments
    result = run(home, 'restore')
    assert result.returncode == 0, result.stderr
    assert hashes(root) == before, 'Restoration must recover every original byte'
    assert not (home / 'main-landing-state.json').exists()
    assert hashes(home / 'payments-private') == payments
    # An unreviewed API must abort before permissions/routes/files are changed.
    (root / 'templates/mupanic/api/unknown.php').write_text('<?php echo "unknown";')
    before_failure = hashes(root)
    result = run(home, 'apply')
    assert result.returncode != 0 and 'Endpoints adicionales' in result.stderr + result.stdout
    assert hashes(root) == before_failure
    assert not (home / 'main-landing-state.json').exists()
print('PASS: landing real, endpoints intactos, archivos privados/beta/auditoria intactos, refresh, restauracion byte a byte y aborto seguro.')
