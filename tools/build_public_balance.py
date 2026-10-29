"""Read an administrator-provided ZIP; export only allowlisted public gameplay data.
Usage: python tools/build_public_balance.py /path/to/ZIP
Never extract or publish the ZIP or complete GameServer configuration.
"""
import json, sys, zipfile, shlex, re, hashlib
from datetime import datetime, timezone
from pathlib import Path
from atlas_hunting import build_hunting
z=zipfile.ZipFile(sys.argv[1])
members={name.replace('\\','/'):name for name in z.namelist()}
archive_names=[name.replace('/','\\') for name in members]
def read(path): return z.read(members[path.replace('\\','/')]).decode('utf-8-sig', errors='replace')
def rows(path):
 for line in read(path).splitlines():
  code, _, comment=line.partition('//'); code=code.strip()
  if not code or code=='end': continue
  try: yield shlex.split(code), comment.strip()
  except ValueError: raise ValueError('Invalid public data line in '+path)
common=read('GameServer/Data/GameServerInfo - Common.dat')
def setting(key, source=common):
 m=re.search(r'^\s*'+re.escape(key)+r'\s*=\s*(-?\d+)',source,re.M)
 if not m: raise ValueError('Missing setting '+key)
 return int(m[1])
accounts=[{'name':n,'experience':setting('AddExperienceRate_AL'+str(i)), 'master':setting('AddMasterExperienceRate_AL'+str(i)), 'drop':setting('ItemDropRate_AL'+str(i))} for i,n in enumerate(['Free','VIP 1','VIP 2','VIP 3'])]
hunting=build_hunting(members, rows)
maps=hunting['maps'];drops=hunting['drops']
resets=[{'min':int(r[0]),'max':int(r[1]),'level':int(r[2]),'zen':int(r[6]),'points':int(r[10])} for r,c in rows('Data/Util/ResetTable.txt') if len(r)==14]
experience=[{'min':int(r[4]),'max':int(r[5]),'percent':int(r[8])} for r,c in rows('Data/Util/ExperienceTable.txt') if len(r)==9]
chaos = read('GameServer/Data/GameServerInfo - ChaosMix.dat')
rate_keys = ['ChaosItemMixRate','Wing1MixRate','Wing2MixRate','FeatherOfCondorMixRate','Wing3MixRate','PetMixRate','PieceOfHornMixRate','BrokenHornMixRate','HornOfFenrirMixRate','FruitMixRate','SocketItemCreateSeedMixRate','SocketItemCreateSeedSphereMixRate','JewelOfHarmonyItemPurityMixRate','DinorantMixRate']
rate_keys += ['DevilSquareMixRate'+str(i) for i in range(1,8)] + ['BloodCastleMixRate'+str(i) for i in range(1,9)]
rate_keys += [prefix+str(i) for prefix in ['PlusItemLevelMixRate','PlusItemExcLevelMixRate','PlusItemSetLevelMixRate','PlusItemSocketLevelMixRate','PlusItemWingLevelMixRate'] for i in range(1,7)]
# Every other rate the server reads from ChaosMix.dat (MuEmu S6), so the Taller follows rate changes automatically.
rate_keys += ['HornOfFenrirUpgradeMixRate','JewelOfHarmonyItemRestoreMixRate','SocketItemDestroySeedMixRate']
rate_keys += ['ShieldPotionMixRate'+str(i) for i in range(1,4)] + ['JewelOfHarmonyItemSmeltMixRate1','JewelOfHarmonyItemSmeltMixRate2','Item380MixRate1','Item380MixRate2','LuckyItemRefineMixRate1','LuckyItemRefineMixRate2']
rate_keys += ['IllusionTempleMixRate'+str(i) for i in range(1,7)]
crafting={'mixRates':{key:[setting(key+'_AL'+str(i),chaos) for i in range(4)] for key in rate_keys}, 'jewels':{key:[setting(key+'_AL'+str(i)) for i in range(4)] for key in ['SoulSuccessRate','LifeSuccessRate','HarmonySuccessRate','SmeltStoneSuccessRate1','SmeltStoneSuccessRate2','AddLuckSuccessRate1','AddLuckSuccessRate2']},'failLevelRemoval':setting('PlusItemFailRemoveLevelAmount',chaos)}
# Use the same content fingerprint as sync_atlas.py, independent of ZIP timestamps.
prefixes = ('Data/Monster/', 'Data/MonsterSetBase/', 'Data/Move/', 'Data/Item/', 'Data/EventItemBag/')
specific = ('Data/MapManager.txt', 'Data/EventItemBagManager.txt', 'Data/Util/ExperienceTable.txt', 'Data/Util/ResetTable.txt', 'GameServer/Data/GameServerInfo - Common.dat', 'GameServer/Data/GameServerInfo - ChaosMix.dat')
fingerprint = hashlib.sha256()
for member in sorted(n for n in members if n in specific or (n.endswith('.txt') and n.startswith(prefixes))):
 fingerprint.update(member.encode()); fingerprint.update(b'\0'); fingerprint.update(z.read(members[member]))
data={'schemaVersion' :2,'generatedAt':datetime.now(timezone.utc).isoformat(),'sourceHash':fingerprint.hexdigest(),'revision':datetime.now(timezone.utc).date().isoformat(),'accounts':accounts,'masterMonsterMin':setting('MinMasterExperienceMonsterLevel_AL0'),'resets':resets,'experience':experience,'maps':maps,'drops':drops,'crafting':crafting}
data['eventBags']=hunting['eventBags']
assert len(maps)>0 and all(account['experience']>=0 for account in accounts)
out=Path(sys.argv[2]) if len(sys.argv)>2 else Path(__file__).resolve().parents[1]/'overlay/templates/mupanic/inc/public-balance.json'
out.write_text(json.dumps(data,ensure_ascii=False,indent=2)+'\n', encoding='utf-8')
print('Exported',len(maps),'maps,',sum(len(m['spots']) for m in maps),'spots,',len(drops),'drop rules.')
print('Reward lists:',len(data['eventBags']),'; unsupported formats:',sum(b['format'] not in ('standard','advanced') for b in data['eventBags']))
