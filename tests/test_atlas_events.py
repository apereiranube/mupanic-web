import copy,hashlib,hmac,json,os,socket,subprocess,sys,tempfile,time,unittest,urllib.error,urllib.request,zipfile
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1];sys.path.insert(0,str(ROOT/'tools'))
from atlas_events import build_events,schedule,sections
from sync_atlas_events import digest,sources
PHP=os.environ.get('PHP_BIN','php');BASE=ROOT/'overlay/templates/mupanic'
class Events(unittest.TestCase):
 def test_sections_do_not_confuse_scalar_with_section(self):
  self.assertEqual(sections('0\n3\nend\n1\n* * * * 19 15 0\nend')[0],[['3']])
 def test_schedules_preserve_wildcards_and_weekdays(self):
  self.assertEqual(schedule([['*','*','*','7','*','50','0']]),[[-1,-1,-1,7,-1,50,0]])
  with self.assertRaises(ValueError):schedule([['*','*','*','*','24','0','0']])
 def test_activation_schedule_removal_and_fingerprint(self):
  with tempfile.TemporaryDirectory() as folder:
   root=Path(folder);(root/'GameServer/Data').mkdir(parents=True);(root/'Data/Event').mkdir(parents=True);(root/'Data/EventItemBag').mkdir();(root/'Data/EventItemBagManager.txt').write_text('')
   flags=root/'GameServer/Data/GameServerInfo - Event.dat';flags.write_text('EventPandoraSwitch = 1\nEventPandoraMaxTime = 5')
   file=root/'Data/Event/PandoraEvent.dat';file.write_text('0\n3\nend\n1\n* * * * 19 15 0\nend')
   def build():
    with zipfile.ZipFile(root/'fixture.zip','w') as z:
     for p in sources(root):z.write(p,p.relative_to(root).as_posix())
    data=build_events(root/'fixture.zip');self.assertEqual(data['sourceHash'],digest(sources(root),root));return data,next(e for e in data['events'] if e['id']=='pandora')
   original,event=build();self.assertTrue(event['enabled']);self.assertEqual(event['name'],'Caza del Maldito');self.assertEqual(event['schedule'][0][4:6],[19,15])
   flags.write_text('EventPandoraSwitch = 0\nEventPandoraMaxTime = 5');disabled,event=build();self.assertFalse(event['enabled']);self.assertNotEqual(original['sourceHash'],disabled['sourceHash'])
   file.unlink();removed,event=build();self.assertFalse(event['schedule']);self.assertNotEqual(disabled['sourceHash'],removed['sourceHash'])
 def test_custom_activation_is_never_inferred_from_file_presence(self):
  with tempfile.TemporaryDirectory() as folder:
   path=Path(folder)/'custom.zip'
   def build(flags):
    with zipfile.ZipFile(path,'w') as z:
     z.writestr('GameServer/Data/GameServerInfo - Event.dat','EventPandoraSwitch = 0')
     if flags is not None:z.writestr('GameServer/Data/GameServerInfo - Custom.dat',flags)
     z.writestr('Data/Custom/CustomArena.txt','0\n0 * * * * * 10 0\nend\n1\n0 "Test Arena" 5 0 1 0 450 1 2 100 * 0 500 * * * * * * 1 1 1 1 1 1 1\nend\n2\n0 7179 12 0 0 0 0 0 0 0 0 255 255 255 255 255 255 0 * // Box +5\nend')
     z.writestr('Data/Custom/CustomEventDrop.txt','0\n0 * * * * 19 0 0\nend\n1\n0 "Rain" 0 145 135 5 5 3\nend\n2\n0 7181 0 1 0 // Jewel of Bless\nend')
    return {e['id']:e for e in build_events(path)['events']}
   self.assertFalse(build(None)['arena-0']['enabled'])
   on=build('CustomArenaSwitch = 1\nCustomEventDropSwitch = 1')
   self.assertTrue(on['arena-0']['enabled']);self.assertEqual(on['arena-0']['schedule'][0][4:6],[-1,10]);self.assertEqual(on['arena-0']['items'],['Box +5']);self.assertTrue(on['event-drop-0']['enabled']);self.assertEqual(on['event-drop-0']['durationMinutes'],3)
   self.assertFalse(build('CustomArenaSwitch = 0\nCustomEventDropSwitch = 0')['event-drop-0']['enabled'])
 def test_manual_arenas_keep_activation_and_stable_identity(self):
  with tempfile.TemporaryDirectory() as folder:
   archive=Path(folder)/'manual.zip'
   def build(enabled,name='Arena MG',calendar=''):
    with zipfile.ZipFile(archive,'w') as z:
     z.writestr('GameServer/Data/GameServerInfo - Event.dat','')
     z.writestr('GameServer/Data/GameServerInfo - Custom.dat','CustomArenaSwitch = '+str(enabled))
     z.writestr('Data/Custom/CustomArena.txt','0\n; old schedule is intentionally absent\n'+calendar+'end\n1\n1 "'+name+'" 10 0 5 0 451 1 4 100 * 0 400 * * * * * * 0 0 0 1 0 0 0\nend\n2\nend')
    return next(e for e in build_events(archive)['events'] if e['id']=='arena-1')
   manual=build(1);self.assertTrue(manual['enabled']);self.assertEqual(manual['mode'],'manual');self.assertEqual(manual['schedule'],[]);self.assertEqual(manual['durationMinutes'],5)
   self.assertFalse(build(0)['enabled'])
   renamed=build(1,'Arena; nueva');self.assertEqual(renamed['id'],manual['id']);self.assertEqual(renamed['name'],'Arena; nueva')
   scheduled=build(1,calendar='1 * * * * 19 0 0\n');self.assertEqual(scheduled['mode'],'scheduled')
 def test_editorial_has_no_activation_or_agenda(self):
  editorial=json.loads((BASE/'inc/atlas-event-guides.json').read_text())
  for guide in editorial['guides'].values():
   self.assertFalse({'schedule','horarios','enabled','habilitado','mode','modalidad'} & guide.keys())
  self.assertEqual(editorial['guides']['arena-0']['tiers'][0]['rewards'][0]['label'],'5 × Kundun +5')
  self.assertIn('pendientes',editorial['guides']['arena-0']['validation'])
  self.assertEqual(editorial['guides']['kundun']['tiers'][0]['bag'],32)
  self.assertEqual(editorial['guides']['erohim']['tiers'][0]['bag'],33)
 def test_signed_receiver_rejects_bad_data_preserves_last_snapshot(self):
  snapshot=json.loads((BASE/'inc/public-events.json').read_text());snapshot['generatedAt']=time.strftime('%Y-%m-%dT%H:%M:%SZ',time.gmtime())
  with tempfile.TemporaryDirectory() as folder:
   token='fixture-event-token-1234567890123456789012345';(Path(folder)/'token').write_text(token)
   with socket.socket() as sock:sock.bind(('127.0.0.1',0));port=sock.getsockname()[1]
   proc=subprocess.Popen([PHP,'-S',f'127.0.0.1:{port}','-t',str(BASE)],env={**os.environ,'PANIC_ATLAS_RUNTIME_DIR':folder},stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
   try:
    url=f'http://127.0.0.1:{port}/api/atlas-events-sync.php'
    for _ in range(50):
     try:urllib.request.urlopen(url);break
     except urllib.error.HTTPError:break
     except urllib.error.URLError:time.sleep(.1)
    def send(data,bad=False):
     body=json.dumps(data).encode();stamp=str(int(time.time()));sig=hmac.new(token.encode(),stamp.encode()+b'\n'+body,hashlib.sha256).hexdigest()
     try:return urllib.request.urlopen(urllib.request.Request(url,data=body,headers={'X-Panic-Timestamp':stamp,'X-Panic-Signature':'0'*64 if bad else sig})).status
     except urllib.error.HTTPError as e:return e.code
    self.assertEqual(send(snapshot),200);before=(Path(folder)/'public-events.json').read_bytes();self.assertEqual(send(snapshot,True),401)
    bad=copy.deepcopy(snapshot);bad['events'][0]['schedule'][0][4]=25;self.assertEqual(send(bad),422)
    bad=copy.deepcopy(snapshot);bad['events'][0]['name']='<script>';self.assertEqual(send(bad),422);self.assertEqual((Path(folder)/'public-events.json').read_bytes(),before)
    snapshot['events'][0]['enabled']=False;self.assertEqual(send(snapshot),200)
    public=json.load(urllib.request.urlopen(f'http://127.0.0.1:{port}/api/atlas-events.php'));self.assertNotIn('data-atlas-event-open="blood-castle"',public['html']);self.assertIn('Caza del Maldito',public['html'])
   finally:proc.terminate();proc.wait(timeout=5)
if __name__=='__main__':unittest.main()
