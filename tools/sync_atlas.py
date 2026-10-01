r"""Publish an allowlisted gameplay snapshot from the Windows VPS.

python tools/sync_atlas.py --server-root C:\MuServer43 --url https://beta.mupanic.com.ar/templates/mupanic/api/atlas-sync.php --token-file C:\MuServer43\AtlasPrivate\token
Run once through Windows Task Scheduler; no game processes or SQL are accessed.
"""
import argparse
import hashlib
import hmac
import json
import subprocess
import sys
import tempfile
import time
import urllib.error
import urllib.request
import zipfile
from pathlib import Path

def sources(root):
    paths = []
    for directory in ('Data/Monster','Data/MonsterSetBase','Data/Move','Data/Item'):
        paths.extend((root/directory).rglob('*.txt'))
    for relative in ('Data/MapManager.txt','Data/Util/ExperienceTable.txt','Data/Util/ResetTable.txt',
                     'GameServer/Data/GameServerInfo - Common.dat',
                     'GameServer/Data/GameServerInfo - ChaosMix.dat'):
        paths.append(root/relative)
    missing = [str(p) for p in paths if not p.is_file()]
    if missing: raise ValueError('Missing gameplay sources: '+', '.join(missing))
    return sorted(set(paths))

def digest(paths, root):
    value = hashlib.sha256()
    for path in paths:
        value.update(path.relative_to(root).as_posix().encode())
        value.update(b'\0')
        value.update(path.read_bytes())
    return value.hexdigest()

def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--server-root', type=Path, required=True)
    parser.add_argument('--url', required=True)
    parser.add_argument('--token-file', type=Path, required=True)
    parser.add_argument('--force', action='store_true')
    args = parser.parse_args()
    if not args.url.startswith('https://'): raise ValueError('HTTPS is required')
    token = args.token_file.read_text(encoding='utf-8').strip()
    if len(token) < 32: raise ValueError('The private token must contain at least 32 characters')
    state = args.token_file.parent/'last-success.json'
    paths = sources(args.server_root)
    fingerprint = digest(paths, args.server_root)
    old = json.loads(state.read_text(encoding='utf-8')) if state.exists() else {}
    if old.get('sourceHash') == fingerprint and not args.force:
        print('Unchanged. Last successfully published snapshot remains active.')
        return
    with tempfile.TemporaryDirectory(prefix='panic-atlas-') as temporary:
        archive = Path(temporary)/'sources.zip'
        output = Path(temporary)/'snapshot.json'
        with zipfile.ZipFile(archive, 'w', zipfile.ZIP_DEFLATED) as z:
            for path in paths: z.write(path, path.relative_to(args.server_root).as_posix())
        subprocess.run([sys.executable,str(Path(__file__).with_name('build_public_balance.py')),str(archive),str(output)],check=True)
        if fingerprint != digest(paths,args.server_root): raise ValueError('Configuration changed during export; retry after saving all changes')
        snapshot = json.loads(output.read_text(encoding='utf-8'))
        snapshot['sourceHash'] = fingerprint
        body = json.dumps(snapshot,ensure_ascii=False).encode('utf-8')
        stamp = str(int(time.time()))
        signature = hmac.new(token.encode(), stamp.encode()+b'\n'+body, hashlib.sha256).hexdigest()
        request = urllib.request.Request(args.url,data=body,method='POST',headers={
            'Content-Type':'application/json','X-Panic-Timestamp':stamp,'X-Panic-Signature':signature})
        with urllib.request.urlopen(request,timeout=30) as response:
            if response.status != 200: raise ValueError('Publication was not confirmed')
            acknowledgement = json.load(response)
            if acknowledgement.get('message') != 'Snapshot updated': raise ValueError('Unexpected publication response')
    temporary_state = state.with_suffix('.tmp')
    temporary_state.write_text(json.dumps({'sourceHash':fingerprint,'publishedAt':stamp}), encoding='utf-8')
    temporary_state.replace(state)
    print('Public snapshot updated. Original configuration and game processes were not changed.')

if __name__ == '__main__':
    try: main()
    except Exception as error:
        print('Atlas sync failed; the previous published snapshot is retained: '+str(error),file=sys.stderr)
        sys.exit(1)
