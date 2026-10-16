import json,os,subprocess,sys,unittest
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1];INC=ROOT/'overlay/templates/mupanic/inc'
sys.path.insert(0,str(ROOT/'tools'))
from atlas_hunting import build_hunting
PHP=os.environ.get('PHP_BIN','php')
class Hunting(unittest.TestCase):
 def catalogue(self):
  code='define("access",true);require $argv[1];echo json_encode(panicAtlasCatalogue(json_decode(file_get_contents($argv[2]),true),json_decode(file_get_contents($argv[3]),true)));'
  return json.loads(subprocess.check_output([PHP,'-r',code,str(INC/'atlas-catalogue.php'),str(INC/'public-balance.json'),str(INC/'public-events.json')]))
 def test_confirmed_spots_and_elites(self):
  d=self.catalogue();spots=[s for m in d['maps'] for s in m['spots']]
  self.assertEqual(len(spots),208)
  self.assertEqual(sum('label' in s for s in spots),59)
  self.assertEqual(sum(s.get('kind')=='super' for s in spots),29)
  self.assertEqual(sum(m.get('quantity',0) for s in spots for m in s['monsters'] if m.get('elite')),41)
  self.assertEqual({m['id'] for s in spots for m in s['monsters'] if m.get('elite')},set(range(627,651)))
  self.assertFalse(any('label' in s for m in d['maps'] if m['id'] in [63,82] for s in m['spots']))
 def test_retired_sources_are_not_published(self):
  d=self.catalogue();self.assertFalse({7268,7289,7290}&{r['id'] for r in d['drops']})
  self.assertFalse({70,71,72,100,101,1014}&{b['id'] for b in d['eventBags']})
  self.assertFalse(any('SOUL_PRUEBA' in r['name'] for r in d['drops']))
  self.assertFalse(any(r['monster']==627 and r['id']==7182 and max(r['rates'])==100 for r in d['drops']))
 def test_direct_rewards_have_real_routes_and_complete_equipment(self):
  d=self.catalogue();bags={b['id']:b for b in d['eventBags']}
  equipment=set()
  for bid in range(211,221):
   b=bags[bid];self.assertEqual(b['kind'],'direct');self.assertTrue(b['dropRoutes']);self.assertTrue(b['items'])
   for i in b['dropRoutes']:self.assertEqual(d['drops'][i]['bagId'],bid)
   if bid<217:equipment.update(i['id'] for i in b['items'])
  wanted=set(json.loads((ROOT/'tools/atlas-hunting-source-audit.json').read_text())['equipmentIds'])
  available={i['id'] for b in bags.values() for i in b['items']}
  self.assertEqual(len(wanted),428);self.assertTrue(wanted <= available)
  self.assertEqual(max(i['excellent'] for i in bags[219]['items']),1)
  self.assertEqual(max(i['excellent'] for i in bags[220]['items']),2)
 def test_guardian_is_not_mislabelled_gemstone(self):
  d=self.catalogue()
  guardian=[r for r in d['drops'] if r['id']==7199];self.assertTrue(guardian)
  self.assertTrue(all(r['name']=='Jewel of Guardian' for r in guardian))
  self.assertTrue(all(r['id']==7209 for r in d['drops'] if r['name']=='Gemstone'))
 def test_event_refresh_uses_same_obtainable_catalogue(self):
  code='$_SERVER["SCRIPT_NAME"]="/templates/mupanic/api/atlas-events.php";include $argv[1];'
  response=json.loads(subprocess.check_output([PHP,'-r',code,str(ROOT/'overlay/templates/mupanic/api/atlas-events.php')]))
  self.assertIn('id="recompensa-12"',response['html'])
  self.assertNotIn('id="recompensa-217"',response['html'])
  self.assertNotIn('id="recompensa-70"',response['html'])
 def test_exporter_ignores_zero_rates_and_identifies_direct_pool(self):
  rows={
   'Data/Monster/Monster.txt':[(['627','1','Vepar elite','45','11200','0','180','220','88','0','288','58','3','17','4','9','400','1550','45']+['0']*9,'')],
   'Data/Move/Gate.txt':[],
   'Data/MonsterSetBase/007 - Atlans.txt':[(['1'],''),(['627','7','3','111','87','117','93','-1','1'],'PANIC 007-S1 ELITE')],
   'Data/MapManager.txt':[],
   'Data/Item/ItemDrop.txt':[(['-1','217','0']+['11']*7+['0','7','627','1','1000','1000','1000','1000','1000'],'Selection'),(['7289','0','0']+['0']*7+['0','*','*','1','1000','0','0','0','0'],'Retired')],
  }
  d=build_hunting(rows,lambda p:iter(rows[p]))
  self.assertEqual(len(d['drops']),1);self.assertEqual(d['drops'][0]['bagId'],217)
  self.assertEqual(d['maps'][0]['spots'][0]['kind'],'super')
if __name__=='__main__':unittest.main()
