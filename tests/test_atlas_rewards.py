import sys, unittest, json
from pathlib import Path
sys.path.insert(0, str(Path(__file__).resolve().parents[1]/'tools'))
from atlas_event_bags import build_event_bags

class Rewards(unittest.TestCase):
    def fixture(self, extra=None):
        data = {
            'Data/EventItemBagManager.txt': [(['1','*','*','561','*','*','0','0','0','0','0'], 'Medusa')],
            'Data/Item/ItemOptionRate.txt': sum(([([str(i)], ''), (['11','10000','0'], '')] for i in range(7)), []),
            'Data/EventItemBag/Monster/001 - Test.txt': [(['3'], ''), (['0','10000','0'], ''), (['1','0','0'], ''), (['4'], ''), (['0','5','100','0','0','1','1','1','1','1','1','1'], ''), (['1','6','100','0','0','1','1','1','1','1','1','1'], ''), (['5'], ''), (['7181','0','0']+['11']*7+['0'], ''), (['6'], ''), (['7182','0','0']+['11']*7+['0'], '')]
        }
        if extra: extra(data)
        return build_event_bags(data, lambda n: iter(data[n]), {561:{'name':'Medusa'}})[0]
    def test_disabled_attempt_does_not_claim_items(self):
        bag=self.fixture();self.assertEqual(bag['format'],'advanced');self.assertEqual([i['id'] for i in bag['items']],[7181])
    def test_missing_pool_hides_the_entire_list(self):
        bag=self.fixture(lambda d:d['Data/EventItemBag/Monster/001 - Test.txt'].__setitem__(4, (['0','9','100','0','0']+['1']*7,'')))
        self.assertEqual(bag['format'],'unsupported');self.assertEqual(bag['items'],[])
    def test_unknown_option_profile_hides_the_entire_list(self):
        bag=self.fixture(lambda d:d.__setitem__('Data/Item/ItemOptionRate.txt',[]));self.assertEqual(bag['format'],'unsupported')
    def test_uploaded_snapshot(self):
        data=json.loads((Path(__file__).resolve().parents[1]/'overlay/templates/mupanic/inc/public-balance.json').read_text())
        bags={b['id']:b for b in data['eventBags']}
        self.assertEqual(sum(b['format']=='advanced' for b in bags.values()),25)
        self.assertEqual(len(bags[106]['items']),157)
        self.assertEqual(bags[106]['items'][0]['min'],0);self.assertEqual(bags[106]['items'][0]['max'],3)
        self.assertTrue(all(i['setOption']>0 for i in bags[106]['items']))
        self.assertNotIn(7210,[i['id'] for i in bags[106]['items']])
        self.assertEqual(bags[125]['format'],'standard');self.assertEqual(bags[200]['format'],'missing')
if __name__=='__main__': unittest.main()
