"""Exercise the real HTTP entrypoint against CMS/SQL fixtures; no live credentials."""
import hashlib
import json
import os
from pathlib import Path
import shutil
import socket
import subprocess
import tempfile
import time
import urllib.request

repo = Path(__file__).resolve().parents[1]
php = os.environ.get('PHP_BINARY', 'php')
with tempfile.TemporaryDirectory() as temporary:
    root = Path(temporary)
    for folder in ['api', 'templates/mupanic', 'includes/cache', 'includes/config', 'includes/classes']:
        (root / folder).mkdir(parents=True, exist_ok=True)
    shutil.copy(repo / 'overlay/api/launcher.php', root / 'api/launcher.php')
    (root / 'templates/mupanic/inc').symlink_to(repo / 'overlay/templates/mupanic/inc', target_is_directory=True)
    # Cache shape consumed by the existing CMS template (timestamp, then pipe fields).
    (root / 'includes/cache/server_info.cache').write_text('1700000000\n100|200|3|1\n')
    (root / 'includes/cache/live-online').write_text('0')
    (root / 'includes/functions.php').write_text('''<?php
function webengineConfigs(){return json_decode(file_get_contents(__PATH_CONFIGS__.'webengine.json'),true);}
function LoadCacheData($name){$result=[];foreach(explode("\\n",file_get_contents(__PATH_CACHE__.$name)) as $i=>$line){if($line!=='')$result[$i]=explode('¦',$line);}return $result;}
''')
    (root / 'includes/classes/class.database.php').write_text('<?php // SQL fixture uses Connection below.\n')
    (root / 'includes/classes/class.connection.php').write_text('''<?php
class Connection {
 static function Database($name){
  if($name!=='Me_MuOnline')throw new Exception('Wrong database');
  file_put_contents(__PATH_CACHE__.'queries','x',FILE_APPEND);
  if(is_file(__PATH_CACHE__.'fail')){echo 'PRIVATE SQL ERROR';throw new Exception('SECRET');}
  return new self;
 }
 function query_fetch_single($query){
  if($query!=='SELECT COUNT(*) AS totalOnline FROM [MEMB_STAT] WHERE [ConnectStat] = 1')throw new Exception('Unexpected SQL');
  return ['totalOnline'=>trim(file_get_contents(__PATH_CACHE__.'live-online'))];
 }
}
''')
    config = root / 'includes/config/webengine.json'
    config.write_text('{"system_active":true}')
    cache = Path(tempfile.gettempdir()) / ('mupanic-launcher-' + hashlib.sha256(str(root).encode()).hexdigest())
    sock = socket.socket()
    sock.bind(('127.0.0.1', 0))
    port = sock.getsockname()[1]
    sock.close()
    runtime = root / 'runtime'
    runtime.mkdir()
    balance = runtime / 'public-balance.json'
    def write_rates(exp):
        balance.write_text(json.dumps({'schemaVersion':2,'generatedAt':'2099-01-01T00:00:00Z','accounts':[{'experience':exp,'master':12,'drop':27}],'maps':[],'drops':[],'crafting':{}}))
    write_rates(31)
    process = subprocess.Popen([php, '-n', '-S', f'127.0.0.1:{port}', '-t', str(root)], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, env={**os.environ, 'PANIC_ATLAS_RUNTIME_DIR':str(runtime)})
    def request(home=False):
        with urllib.request.urlopen(f'http://127.0.0.1:{port}/api/launcher.php' + ('?home=1' if home else ''), timeout=5) as response:
            assert response.status == 200
            assert response.headers['Content-Type'] == 'application/json; charset=utf-8'
            assert response.headers['Cache-Control'] == ('no-store' if home else 'max-age=30')
            assert response.headers.get('Set-Cookie') is None
            data = json.load(response)
            assert list(data) == ['online', 'servidor', 'eventos'] + (['rates'] if home else [])
            return data
    def expire():
        (cache / 'state-v2.json').unlink(missing_ok=True)
    try:
        for _ in range(50):
            try:
                with socket.create_connection(('127.0.0.1', port), timeout=.1): break
            except OSError: time.sleep(.05)
        first = request()
        assert first['online'] == 0 and first['servidor'] == 'online'
        request()
        assert (root / 'includes/cache/queries').read_text() == 'x', 'SQL was not cached'
        # A stale cron says one; live SQL says zero. After TTL, a new count wins.
        home = request(True)
        assert home['online'] == 0 and home['rates'] == {'experience':31,'master':12,'drop':27}
        write_rates(32)
        assert request(True)['rates']['experience'] == 32, 'Rates must refresh from the shared runtime export'
        (root / 'includes/cache/live-online').write_text('100')
        expire()
        assert request()['online'] == 100
        (root / 'includes/cache/live-online').write_text('0')
        expire()
        assert request()['online'] == 0
        # Missing cron cache must not stop a healthy live count.
        (root / 'includes/cache/server_info.cache').unlink()
        expire()
        assert request()['online'] == 0
        (root / 'includes/cache/fail').touch()
        expire()
        failed = request()
        assert failed['online'] == 0 and failed['servidor'] == 'offline'
        request()
        assert (root / 'includes/cache/queries').read_text() == 'xxxxx', 'failure was not cached'
        config.write_text('{"system_active":false}')
        expire()
        assert request()['servidor'] == 'mantenimiento'
        config.unlink()
        expire()
        assert request()['servidor'] == 'offline'
        print('Launcher HTTP: headers, schema, SQL cache, maintenance and clean failure passed')
    finally:
        process.terminate()
        process.wait(timeout=5)
        shutil.rmtree(cache, ignore_errors=True)

with tempfile.TemporaryDirectory() as temporary:
    htaccess = Path(temporary) / '.htaccess'
    original = 'RewriteEngine On\nRewriteRule ^private - [F]\n'
    htaccess.write_text(original)
    command = ['bash', str(repo / 'scripts/deploy-launcher.sh'), temporary]
    subprocess.run(command, check=True)
    once = htaccess.read_text()
    subprocess.run(command, check=True)
    assert htaccess.read_text() == once
    assert once.endswith(original)
    assert once.count('RewriteRule ^api/launcher\\.php$ - [END]') == 1
    print('Launcher routing: exact exemption, preservation and idempotent deployment passed')
