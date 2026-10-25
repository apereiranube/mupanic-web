"""Public hunting tables, including ItemDrop-triggered reward pools.

No probability is inferred for an individual item inside a selection pool.
"""
import re
from atlas_event_bags import build_event_bags


def build_hunting(members, rows):
    monsters = {}
    for r, comment in rows('Data/Monster/Monster.txt'):
        if len(r) < 28:
            continue
        mid = int(r[0])
        monsters[mid] = dict(id=mid, name=r[2], level=int(r[3]), life=int(r[4]),
            damageMin=int(r[6]), damageMax=int(r[7]), defense=int(r[8]),
            attackRate=int(r[10]), defenseRate=int(r[11]), respawnSeconds=int(r[18]))
        if r[2].lower().endswith(' elite'):
            monsters[mid]['elite'] = True
    gates = {int(r[0]): int(r[2]) for r, c in rows('Data/Move/Gate.txt') if len(r) > 9}
    moves = {}
    if 'Data/Move/Move.txt' in members:
        for r, c in rows('Data/Move/Move.txt'):
            if len(r) >= 11 and int(r[10]) in gates:
                moves.setdefault(gates[int(r[10])], []).append(dict(name=r[1].replace('_', ' '), zen=int(r[2]), level=int(r[3])))
    names = {37:'Kanturu 1', 38:'Kanturu 2', 39:'Kanturu 3', 57:'Raklion',
             56:'Swamp of Calmness', 80:'Karutan 1', 81:'Karutan 2'}
    maps = []
    # Event subdirectories are intentionally separate from ordinary hunting maps.
    for path in sorted(members):
        match = re.fullmatch(r'Data/MonsterSetBase/(\d+) - (.+)\.txt', path, re.I)
        if not match:
            continue
        mid = int(match[1]); section = None; clusters = {}; population = {}
        for r, c in rows(path):
            if len(r) == 1:
                section = r[0]; continue
            if section not in ('1', '2') or len(r) < 6:
                continue
            mob = monsters.get(int(r[0]))
            if not mob or int(r[1]) != mid:
                continue
            population[mob['id']] = mob
            if section == '1' and len(r) >= 9:
                key = tuple(map(int, r[3:7]))
                if not (0 <= key[2]-key[0] <= 16 and 0 <= key[3]-key[1] <= 16):
                    continue
                spot = clusters.setdefault(key, dict(x=(key[0]+key[2])//2, y=(key[1]+key[3])//2, monsters=[]))
                spot['monsters'].append(dict(mob, quantity=int(r[8])))
                if mob.get('elite'):
                    spot['kind'] = 'super'
                label = re.search(r'PANIC (\d{3}-[NS]\d+) (?:NORMAL|ELITE)', c)
                if label:
                    spot['label'] = label[1]
        maps.append(dict(id=mid, name=names.get(mid,match[2]), moves=moves.get(mid,[]),
                         monsters=list(population.values()), spots=list(clusters.values())))
    configs = {int(r[0]):dict(excellent=int(r[6])/10000, ancient=int(r[7])/10000,
                 socket=r[8]=='1', dropMode=r[4]) for r, c in rows('Data/MapManager.txt') if len(r)>8}
    for m in maps:
        m['equipmentDrop'] = configs.get(m['id'],dict(excellent=0,ancient=0,socket=False,dropMode='unknown'))
    bags = build_event_bags(members, rows, monsters)
    by_bag = {b['id']:b for b in bags}
    item_names = {}; section = None
    if 'Data/Item/Item.txt' in members:
        for r, c in rows('Data/Item/Item.txt'):
            if len(r)==1:
                section=int(r[0])
            elif section is not None and len(r)>8:
                item_names[section*512+int(r[0])] = r[8]
    drops = []
    for r, c in rows('Data/Item/ItemDrop.txt'):
        if len(r)<19 or max(map(int,r[15:19]))<=0:
            continue
        item_id=int(r[0]); bag_id=int(r[1]) if item_id == -1 else None
        # -1 + Level is an immediate reward-list selection, not a dropped box.
        name = (by_bag.get(bag_id,{}).get('name') if bag_id is not None else item_names.get(item_id)) or c or 'Item '+r[0]
        if item_id >= 0 and int(r[1]) != 0: name = c or name  # variants are different named boxes
        drop = dict(id=item_id,name=name,variant=int(r[1]),
            map=-1 if r[11]=='*' else int(r[11]), monster=-1 if r[12]=='*' else int(r[12]),
            min=0 if r[13]=='*' else int(r[13]), max=1000 if r[14]=='*' else int(r[14]),
            rates=[int(v)/10000 for v in r[15:19]], source='ItemDrop')
        if drop['monster'] in monsters:
            drop['monsterName']=monsters[drop['monster']]['name']
        if bag_id is not None:
            drop['bagId']=bag_id
        drops.append(drop)
    return dict(maps=maps,drops=drops,eventBags=bags)
