import json,os,shutil,subprocess,unittest
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
INC=ROOT/'overlay/templates/mupanic/inc'
class DropCatalogueTest(unittest.TestCase):
 def setUp(self):
  self.data=json.loads((INC/'public-balance.json').read_text())
  self.art=json.loads((INC/'atlas-drop-items.json').read_text())
 def groups(self):
  php=os.environ.get('PHP_BIN') or shutil.which('php')
  if not php:self.skipTest('PHP is required to test the actual grouping model')
  code="define('access',true);require $argv[1];$d=json_decode(file_get_contents($argv[2]),true);echo json_encode(panicAtlasDropGroups($d['drops']));"
  return json.loads(subprocess.check_output([php,'-r',code,str(INC/'atlas-drop-model.php'),str(INC/'public-balance.json')],text=True))
 def test_every_original_rule_and_rate_survives_grouping(self):
  groups=self.groups();rows={int(i):r for g in groups.values() for i,r in (g['rules'].items() if isinstance(g['rules'],dict) else enumerate(g['rules']))}
  self.assertEqual(len(groups),30);self.assertEqual(rows,dict(enumerate(self.data['drops'])))
 def test_distinct_variants_are_not_merged(self):
  groups=self.groups()
  for key in ['6670-0','6670-1','6687-0','6687-1']:self.assertIn(key,groups)
  self.assertNotEqual(groups['6670-0']['name'],groups['6670-1']['name'])
 def test_numbered_materials_keep_each_level_and_rule(self):
  groups=self.groups()
  for key,count in [('6672',8),('6673',8),('6705',6),('6706',6),('7185',7),('7186',7),('7197',7)]:
   self.assertTrue(groups[key]['numbered']);self.assertEqual(len(groups[key]['rules']),count)
 def test_all_catalogue_images_are_local_real_webp(self):
  self.assertEqual(set(self.groups()),set(self.art))
  for a in self.art.values():
   p=ROOT/'overlay/templates/mupanic'/a['file'];self.assertTrue(p.is_file());raw=p.read_bytes();self.assertEqual(raw[:4],b'RIFF');self.assertEqual(raw[8:12],b'WEBP');self.assertGreater(a['width'],0);self.assertGreater(a['height'],0)
if __name__=='__main__':unittest.main()
