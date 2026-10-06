"""Allowlisted event catalogue. Configuration availability is not live process state."""
import hashlib, json, re, shlex, sys, zipfile
from datetime import datetime, timezone
from pathlib import Path

EVENTS = [
 ('blood-castle','Blood Castle','BloodCastleEvent','BloodCastle',1,'classic',list(range(12,20))),
 ('devil-square','Devil Square','DevilSquareEvent','DevilSquare',1,'classic',list(range(145,152))),
 ('chaos-castle','Chaos Castle','ChaosCastleEvent','ChaosCastle',1,'classic',list(range(20,27))),
 ('illusion-temple','Illusion Temple','IllusionTempleEvent','IllusionTemple',1,'classic',list(range(56,62))),
 ('double-goer','Double Goer','DoubleGoerEvent','DoubleGoer',None,'classic',[]),
 ('imperial-guardian','Imperial Guardian','ImperialGuardianEvent','ImperialGuardian',None,'classic',[]),
 ('kanturu','Kanturu','KanturuEvent','Kanturu',None,'boss',[]),
 ('raklion','Selupan · LaCleon','RaklionEvent','Raklion',None,'boss',[67]),
 ('pandora','Caza del Maldito','EventPandoraSwitch','PandoraEvent',1,'custom',[163]),
 ('tvt','Team vs Team','EventTvtSwitch','TvTEvent',1,'custom',[]),
 ('gvg','Guild vs Guild','EventGvGSwitch','GvGEvent',1,'custom',[]),
 ('battle-royale','Battle Royale','EventBattleRoyaleSwitch','BattleRoyale',1,'custom',[]),
 ('demon-guardian','Demon Guardian','DemonGuardianSwitch','DemonGuardian',1,'custom',[]),
 ('king-of-mu','King of MU','ReiDoMUEvent','ReiDoMU',1,'custom',[]),
 ('marathon','Marathon','EventMarathonSwitch','MarathonEvent',0,'custom',[]),
 ('pvp-championship','PvP Championship','EventPvpChampionshipSwitch','PvpChampionship',0,'custom',[]),
 ('moss','Moss Merchant','MossMerchantEvent','MossMerchant',0,'classic',[]),
 ('castle-deep','Castle Deep','CastleDeepEvent','CastleDeepEvent',0,'boss',[]),
 ('crywolf','Crywolf','CrywolfEvent','Crywolf',None,'classic',[]),
 ('castle-siege','Castle Siege','CastleSiegeEvent','MuCastleData',None,'classic',[]),
 ('auction','Auction','EventAuctionSwitch','AuctionEvent',None,'custom',[]),
]
MANUAL=[('quickly','Quickly','QuicklyEvent'),('hide-and-seek','Hide and Seek','EventHideAndSeekSwitch'),('run-and-catch','Run and Catch','EventRunAndCatchSwitch'),('russian-roulette','Russian Roulette','EventRussianRouletteSwitch'),('pvp','PvP','EventPvPSwitch'),('kill-all','Kill All','EventKillAllSwitch')]

def sections(text):
 result={}; section=None
 for line in text.splitlines():
  code=re.sub(r'("[^"]*")|//.*|;.*',lambda m:m[1] or '',line).strip()
  if not code:continue
  if code.lower()=='end':section=None;continue
  row=shlex.split(code)
  if section is None:
   if len(row)!=1 or not row[0].isdigit():raise ValueError('Unknown event section')
   section=int(row[0]);result.setdefault(section,[])
  else:result[section].append(row)
 return result

def schedule(rows,indexed=False):
 result=[]
 limits=[(2000,2100),(1,12),(1,31),(1,7),(0,23),(0,59),(0,59)]
 for row in rows:
  values=row[1:8] if indexed else row[:7]
  if len(values)!=7:raise ValueError('Unsupported schedule columns')
  entry=[]
  for value,(lo,hi) in zip(values,limits):
   if value=='*':entry.append(-1)
   elif lo<=int(value)<=hi:entry.append(int(value))
   else:raise ValueError('Invalid schedule value')
  if entry not in result:result.append(entry)
 return result

def build_events(archive):
 z=zipfile.ZipFile(archive);members={n.replace('\\','/'):n for n in z.namelist() if not n.endswith(('/','\\'))}
 def read(p):return z.read(members[p]).decode('utf-8-sig',errors='replace')
 key='GameServer/Data/GameServerInfo - Event.dat'
 if key not in members:raise ValueError('Missing event activation configuration')
 settings={k:int(v) for k,v in re.findall(r'^\s*(\w+)\s*=\s*(-?\d+)',read(key),re.M)}
 custom_key='GameServer/Data/GameServerInfo - Custom.dat'
 if custom_key in members:
  for k,v in re.findall(r'^\s*(CustomArenaSwitch|CustomEventDropSwitch)\s*=\s*(-?\d+)',read(custom_key),re.M):settings[k]=int(v)
 def data(file):
  p='Data/Event/'+file+'.dat'
  return sections(read(p)) if p in members else {}
 # Item summaries retain configured pools; no invented odds or currency names.
 bag_files={int(m[1]):p for p in members if (m:=re.match(r'Data/EventItemBag/(\d+) - .+\.txt$',p))}
 bag_names={};bag_monsters={}
 if 'Data/EventItemBagManager.txt' in members:
  for row in sections('0\n'+read('Data/EventItemBagManager.txt')+'\nend').get(0,[]):
   if len(row)>=11 and row[3]!='*':bag_monsters.setdefault(int(row[3]),[]).append(int(row[0]))
 for bid,p in bag_files.items():
  names=[]; labels={}
  for line in read(p).splitlines():
   code,_,comment=line.partition('//')
   if len(code.split())>=8 and comment.strip() and len(comment.strip())<100:
    label=comment.strip()
    if label not in names:names.append(label)
    if code.split()[0].isdigit():labels[int(code.split()[0])]=label
  # Only quantify single-item, single-group attempts valid for every class.
  advanced=sections(read(p)); attempts=advanced.get(3,[]);groups=advanced.get(4,[])
  guaranteed={}; optional=[];complete=bool(attempts and groups)
  for attempt in attempts:
   associated=[g for g in groups if g[0]==attempt[0]]
   if len(attempt)!=3 or len(associated)!=1:complete=False;break
   g=associated[0];pool=advanced.get(int(g[1]),[])
   if len(g)!=12 or g[2]!='10000' or any(v!='1' for v in g[5:]) or len(pool)!=1 or len(pool[0])!=11:complete=False;break
   label=labels.get(int(pool[0][0]));rate=int(attempt[1])/100
   if not label:complete=False;break
   if rate==100:guaranteed[label]=guaranteed.get(label,0)+1
   elif rate>0:optional.append(label+' · '+str(rate).rstrip('0').rstrip('.')+'% de posibilidad')
  if complete:names=[str(count)+' × '+name for name,count in guaranteed.items()]+optional
  bag_names[bid]=names
 def item_summary(ids):
  names=list(dict.fromkeys(n for bid in ids for n in bag_names.get(bid,[])))
  return names[:6],len(names)
 events=[]
 for eid,name,flag,file,sec,group,bags in EVENTS:
  d=data(file);enabled=settings.get(flag)==1 and bool(d)
  indexed=file in ('MarathonEvent','PvpChampionship')
  times=schedule(d.get(sec,[]),indexed) if sec is not None else []
  duration=0
  if d.get(0) and file not in ('MarathonEvent','PvpChampionship','MossMerchant','Kanturu','Raklion','Crywolf','MuCastleData','AuctionEvent','CastleDeepEvent'):
   row=d[0][0];duration=int(row[1] if file in ('DoubleGoer','ImperialGuardian','ReiDoMU') else row[2]) if len(row)>=3 else 0
  if eid=='pandora':duration=settings.get('EventPandoraMaxTime',0)
  if eid=='moss':duration=settings.get('MossMerchantEventTime',0)
  if eid=='castle-deep':duration=settings.get('CastleDeepEventTime',0)
  coins=[]
  coinsec={'pandora':3,'tvt':3,'gvg':3,'battle-royale':3,'demon-guardian':3,'king-of-mu':3,'illusion-temple':3,'blood-castle':5,'devil-square':4,'chaos-castle':3}.get(eid)
  if coinsec is not None:
   coins=list(dict.fromkeys(tuple(map(int,r[-3:])) for r in d.get(coinsec,[]) if len(r)>=3))
  if eid=='pvp-championship':coins=[tuple(settings.get('EventPvpChampionship'+stage+'Reward'+str(i),0) for i in (1,2,3)) for stage in ('Round','Winner')]
  if eid=='king-of-mu' and d.get(2):coins.extend(tuple(map(int,r[-3:])) for r in d[2])
  names,total=item_summary(bags)
  events.append(dict(id=eid,name=name,enabled=enabled,group=group,mode='scheduled' if times else 'access',schedule=times,durationMinutes=duration,coins=[list(c) for c in coins if any(c)],bags=bags,items=names,itemCount=total,maps=[],monsters=[]))
 for eid,name,flag in MANUAL:
  prefix={'quickly':'QuicklyEvent','hide-and-seek':'EventHideAndSeek','run-and-catch':'EventRunAndCatch','russian-roulette':'EventRussianRoulette','pvp':'EventPvP','kill-all':'EventKillAll'}[eid]
  coins=[settings.get(prefix+'AutoReward'+str(i),0) for i in (1,2,3)]
  rewards=[coins] if any(coins) else []
  if eid=='kill-all':rewards=[[settings.get(prefix+'AutoReward'+str(i)+'Rank'+str(rank),0) for i in (1,2,3)] for rank in (1,2,3)]
  events.append(dict(id=eid,name=name,enabled=settings.get(flag)==1,group='staff',mode='manual',schedule=[],durationMinutes=settings.get(prefix+'MaxTime',0),coins=[c for c in rewards if any(c)],bags=[],items=[],itemCount=0,maps=[],monsters=[]))
 # Optional custom activation file is read by the VPS publisher, not guessed from filenames.
 for file,flag,prefix in [('CustomArena','CustomArenaSwitch','arena-'),('CustomEventDrop','CustomEventDropSwitch','event-drop-')]:
  path='Data/Custom/'+file+'.txt'
  if path not in members:continue
  d=sections(read(path))
  for row in d.get(1,[]):
   if len(row)!=(26 if file=='CustomArena' else 8):raise ValueError('Unsupported custom event layout')
   idx=int(row[0]);times=schedule([r for r in d.get(0,[]) if int(r[0])==idx],True)
   item_names=[]
   for line in read(path).splitlines():
    code,_,comment=line.partition('//');values=code.split()
    if len(values)==(19 if file=='CustomArena' else 5) and values[0]==str(idx) and comment.strip() and len(comment.strip())<100:
     if comment.strip() not in item_names:item_names.append(comment.strip())
   events.append(dict(id=prefix+str(idx),name=row[1],enabled=settings.get(flag)==1 and (file=='CustomArena' or bool(times)),group='staff' if file=='CustomArena' and not times else 'custom',mode='manual' if file=='CustomArena' and not times else 'scheduled',schedule=times,durationMinutes=int(row[4] if file=='CustomArena' else row[7]),coins=[],bags=[],items=item_names[:6],itemCount=len(item_names),maps=[] if file=='CustomArena' else [int(row[2])],monsters=[]))
 inv=data('InvasionManager')
 for row in inv.get(1,[]):
  if len(row)!=8:raise ValueError('Unsupported invasion layout')
  idx=int(row[0]);times=schedule([r for r in inv.get(0,[]) if int(r[0])==idx],True)
  maps=sorted({int(r[2]) for r in inv.get(2,[]) if int(r[0])==idx})
  mobs=sorted({int(r[2]) for r in inv.get(3,[]) if int(r[0])==idx})
  bags=sorted({b for m in mobs for b in bag_monsters.get(m,[])})
  names,total=item_summary(bags)
  events.append(dict(id='invasion-'+str(idx),name=row[1],enabled=settings.get('InvasionManagerSwitch')==1 and bool(times and maps and mobs),group='invasion',mode='scheduled' if times else 'access',schedule=times,durationMinutes=int(row[6])/60,coins=[],bags=bags,items=names,itemCount=total,maps=maps,monsters=mobs))
 sources=sorted(p for p in members if p in (key,custom_key,'Data/Custom/CustomArena.txt','Data/Custom/CustomEventDrop.txt','Data/EventItemBagManager.txt') or p.startswith(('Data/Event/','Data/EventItemBag/')))
 h=hashlib.sha256()
 for p in sources:h.update(p.encode());h.update(b'\0');h.update(z.read(members[p]))
 return dict(schemaVersion=1,generatedAt=datetime.now(timezone.utc).isoformat(),sourceHash=h.hexdigest(),timezone='America/Argentina/Buenos_Aires',events=events)

if __name__=='__main__':
 snapshot=build_events(sys.argv[1]);Path(sys.argv[2]).write_text(json.dumps(snapshot,ensure_ascii=False,indent=2)+'\n',encoding='utf-8');print('Event catalogue:',len(snapshot['events']),'configured;',sum(e['enabled'] for e in snapshot['events']),'enabled.')
