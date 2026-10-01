"""Read an administrator-provided ZIP; export only allowlisted public gameplay data.
Usage: python tools/build_public_balance.py /path/to/ZIP
Never extract or publish the ZIP or complete GameServer configuration.
"""
import json, sys, zipfile, shlex, re, hashlib
from datetime import datetime, timezone
from pathlib import Path
from atlas_event_bags import build_event_bags
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
monsters={int(r[0]):{'id':int(r[0]), 'name':r[2], 'level':int(r[3]),
 'life':int(r[4]), 'damageMin':int(r[6]), 'damageMax':int(r[7]),
 'defense':int(r[8]), 'attackRate':int(r[10]), 'defenseRate':int(r[11]),
 'respawnSeconds':int(r[18])} for r,c in rows('Data/Monster/Monster.txt') if len(r)>=28}
gates={int(r[0]):int(r[2]) for r,c in rows('Data/Move/Gate.txt') if len(r)>9}
moves={}
for r,c in rows('Data/Move/Move.txt'):
 if len(r)>=11 and int(r[10]) in gates:
  mid=gates[int(r[10])];moves.setdefault(mid,[]).append({'name':r[1].replace('_',' '),'zen':int(r[2]),'level':int(r[3])})
# Discover all configured map files, including events and custom maps.
selected={}
for file in archive_names:
 match=re.match(r'Data\\MonsterSetBase\\(\d+) - (.+)\.txt$', file, re.I)
 if match: selected[int(match[1])]=match[2]
# Preserve established public names where the file contains internal abbreviations.
selected.update({mid:name for mid,name in {37:'Kanturu 1',38:'Kanturu 2',39:'Kanturu 3',
 57:'Raklion',56:'Swamp of Calmness',80:'Karutan 1',81:'Karutan 2'}.items() if mid in selected})
maps=[]
for mid,name in sorted(selected.items()):
 files=[n for n in archive_names if n.startswith('Data\\MonsterSetBase\\'+str(mid).zfill(3)+' - ')]
 if not files: continue
 section=None;clusters={};population={}
 for r,c in rows(files[0]):
  if len(r)==1: section=r[0];continue
  if section not in ('1','2') or len(r)<6: continue
  monster=monsters.get(int(r[0]))
  if not monster or int(r[1])!=mid: continue
  population[int(r[0])]=monster
  if section=='1' and len(r)>=9 and int(r[5])-int(r[3])<=16 and int(r[6])-int(r[4])<=16:
   key=tuple(map(int,r[3:7]));spot=clusters.setdefault(key,{'x':(key[0]+key[2])//2,'y':(key[1]+key[3])//2,'monsters':[]})
   spot['monsters'].append({**monster,'quantity':int(r[8])})
 maps.append({'id':mid,'name':name,'moves':moves.get(mid,[]),'monsters':list(population.values()),'spots':list(clusters.values())})
drops=[]
for r,c in rows('Data/Item/ItemDrop.txt'):
 if len(r)<19 or max(map(int,r[15:19]))<=0: continue
 drops.append({'name':c or 'Item '+r[0],'id':int(r[0]),'variant':int(r[1]),'map':-1 if r[11]=='*' else int(r[11]),'monster':-1 if r[12]=='*' else int(r[12]),'min':int(r[13]),'max':int(r[14]),'rates':[int(v)/10000 for v in r[15:19]],'source':'ItemDrop'})
resets=[{'min':int(r[0]),'max':int(r[1]),'level':int(r[2]),'zen':int(r[6]),'points':int(r[10])} for r,c in rows('Data/Util/ResetTable.txt') if len(r)==14]
experience=[{'min':int(r[4]),'max':int(r[5]),'percent':int(r[8])} for r,c in rows('Data/Util/ExperienceTable.txt') if len(r)==9]
chaos = read('GameServer/Data/GameServerInfo - ChaosMix.dat')
rate_keys = ['ChaosItemMixRate','Wing1MixRate','Wing2MixRate','FeatherOfCondorMixRate','Wing3MixRate','PetMixRate','PieceOfHornMixRate','BrokenHornMixRate','HornOfFenrirMixRate','FruitMixRate','SocketItemCreateSeedMixRate','SocketItemCreateSeedSphereMixRate','JewelOfHarmonyItemPurityMixRate','DinorantMixRate']
rate_keys += ['DevilSquareMixRate'+str(i) for i in range(1,8)] + ['BloodCastleMixRate'+str(i) for i in range(1,9)]
rate_keys += [prefix+str(i) for prefix in ['PlusItemLevelMixRate','PlusItemExcLevelMixRate','PlusItemSetLevelMixRate','PlusItemSocketLevelMixRate','PlusItemWingLevelMixRate'] for i in range(1,7)]
crafting={'mixRates':{key:[setting(key+'_AL'+str(i),chaos) for i in range(4)] for key in rate_keys}, 'jewels':{key:[setting(key+'_AL'+str(i)) for i in range(4)] for key in ['SoulSuccessRate','LifeSuccessRate','HarmonySuccessRate','SmeltStoneSuccessRate1','SmeltStoneSuccessRate2','AddLuckSuccessRate1','AddLuckSuccessRate2']},'failLevelRemoval':setting('PlusItemFailRemoveLevelAmount',chaos)}
map_configs={int(r[0]):{'excellent':int(r[6])/10000,'ancient':int(r[7])/10000,'socket':r[8]=='1','dropMode':r[4]} for r,c in rows('Data/MapManager.txt') if len(r)>8}
for map in maps: map['equipmentDrop']=map_configs.get(map['id'], {'excellent':0,'ancient':0,'socket':False,'dropMode':'unknown'})
data={'schemaVersion':2,'generatedAt':datetime.now(timezone.utc).isoformat(),'sourceHash':hashlib.sha256(Path(sys.argv[1]).read_bytes()).hexdigest(),'revision':datetime.now(timezone.utc).date().isoformat(),'accounts':accounts,'masterMonsterMin':setting('MinMasterExperienceMonsterLevel_AL0'),'resets':resets,'experience':experience,'maps':maps,'drops':drops,'crafting':crafting}
data['eventBags']=build_event_bags(members, rows, monsters)
assert len(maps)>0 and all(account['experience']>=0 for account in accounts)
out=Path(sys.argv[2]) if len(sys.argv)>2 else Path(__file__).resolve().parents[1]/'overlay/templates/mupanic/inc/public-balance.json'
out.write_text(json.dumps(data,ensure_ascii=False,indent=2)+'\n', encoding='utf-8')
print('Exported',len(maps),'maps,',sum(len(m['spots']) for m in maps),'spots,',len(drops),'drop rules.')
print('Reward lists:',len(data['eventBags']),'; unsupported formats:',sum(b['format']!='standard' for b in data['eventBags']))
