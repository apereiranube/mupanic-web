"""Publish an allowlisted event catalogue; keep the balance sync and game untouched."""
import argparse, hashlib, hmac, json, sys, tempfile, time, urllib.request, zipfile
from pathlib import Path
from atlas_events import build_events

def sources(root):
 paths=list((root/'Data/Event').rglob('*.dat'))+list((root/'Data/EventItemBag').rglob('*.txt'))
 paths.extend(root/p for p in ('GameServer/Data/GameServerInfo - Event.dat','Data/EventItemBagManager.txt'))
 if any(not p.is_file() for p in paths):raise ValueError('Missing public event sources')
 return sorted(set(paths))

def digest(paths,root):
 h=hashlib.sha256()
 for p in paths:h.update(p.relative_to(root).as_posix().encode());h.update(b'\0');h.update(p.read_bytes())
 return h.hexdigest()

def main():
 p=argparse.ArgumentParser(description=__doc__);p.add_argument('--server-root',type=Path,required=True);p.add_argument('--url',required=True);p.add_argument('--token-file',type=Path,required=True);p.add_argument('--force',action='store_true');args=p.parse_args()
 if not args.url.startswith('https://'):raise ValueError('HTTPS is required')
 token=args.token_file.read_text(encoding='utf-8').strip()
 if len(token)<32:raise ValueError('Private token is not configured')
 state=args.token_file.parent/'last-events-success.json';paths=sources(args.server_root);fingerprint=digest(paths,args.server_root)
 old=json.loads(state.read_text(encoding='utf-8')) if state.exists() else {}
 if old.get('sourceHash')==fingerprint and old.get('exporterVersion')==1 and not args.force:print('Events unchanged. Last published catalogue remains active.');return
 with tempfile.TemporaryDirectory(prefix='panic-events-') as folder:
  archive=Path(folder)/'sources.zip'
  with zipfile.ZipFile(archive,'w',zipfile.ZIP_DEFLATED) as z:
   for file in paths:z.write(file,file.relative_to(args.server_root).as_posix())
  catalogue=build_events(archive)
  if fingerprint!=digest(sources(args.server_root),args.server_root):raise ValueError('Event configuration changed during export; retry after saving')
  if catalogue['sourceHash']!=fingerprint:raise ValueError('Unexpected source fingerprint')
  stamp=str(int(time.time()));body=json.dumps(catalogue,ensure_ascii=False).encode('utf-8');signature=hmac.new(token.encode(),stamp.encode()+b'\n'+body,hashlib.sha256).hexdigest()
  request=urllib.request.Request(args.url,data=body,method='POST',headers={'Content-Type':'application/json','X-Panic-Timestamp':stamp,'X-Panic-Signature':signature})
  with urllib.request.urlopen(request,timeout=30) as response:
   if response.status!=200 or json.load(response).get('message')!='Snapshot updated':raise ValueError('Publication was not confirmed')
 tmp=state.with_suffix('.tmp');tmp.write_text(json.dumps({'sourceHash':fingerprint,'exporterVersion':1,'publishedAt':stamp}),encoding='utf-8');tmp.replace(state)
 print('Events published:',sum(e['enabled'] for e in catalogue['events']),'enabled. Game configuration and processes were not changed.')

if __name__=='__main__':
 try:main()
 except Exception as error:print('Event sync failed; the last published catalogue is retained: '+str(error),file=sys.stderr);sys.exit(1)
