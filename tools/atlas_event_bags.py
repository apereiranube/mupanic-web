"""Read public reward lists using the column headers in the server export.

Keep pool membership and configured rates separate; do not infer per-item odds.
Advanced bag formats remain explicitly unsupported until their selection rules
are verified against the server implementation.
"""
import re


def build_event_bags(members, rows, monsters):
    if 'Data/EventItemBagManager.txt' not in members:
        return []
    names = {}
    section = None
    if 'Data/Item/Item.txt' in members:
        for values, comment in rows('Data/Item/Item.txt'):
            if len(values) == 1:
                section = int(values[0])
            elif section is not None and len(values) > 8:
                names[section * 512 + int(values[0])] = values[8]
    files = {}
    for path in members:
        match = re.match(r'Data/EventItemBag/(?:.*/)?(\d+) - .+\.txt$', path, re.I)
        if match:
            index = int(match[1])
            if index in files:
                raise ValueError('Duplicate event bag index ' + str(index))
            files[index] = path
    bags = []
    def optional(value):
        return -1 if value == '*' else int(value)
    for values, comment in rows('Data/EventItemBagManager.txt'):
        if len(values) < 11:
            continue
        index = int(values[0])
        monster = optional(values[3])
        bag = {'id': index, 'name': comment or 'Recompensa ' + str(index),
               'monster': monster, 'monsterName': monsters.get(monster, {}).get('name', ''),
               'item': optional(values[1]), 'variant': optional(values[2]),
               'topHit': optional(values[4]), 'special': optional(values[5]),
               'coins': [int(v) for v in values[8:11]], 'format': 'standard',
               'settings': {}, 'items': [], 'unsupportedSections': []}
        if index not in files:
            bag['format'] = 'missing'
            bags.append(bag)
            continue
        section = None
        for row, item_comment in rows(files[index]):
            if len(row) == 1:
                section = int(row[0])
                continue
            if section == 0 and len(row) == 8:
                bag['name'] = row[0]
                bag['settings'] = dict(zip(
                    ('dropZen', 'itemDropRate', 'itemDropCount', 'setItemDropRate',
                     'itemDropType', 'fireworks', 'dropInventory'), map(int, row[1:])))
            elif section in (1, 2) and len(row) == (8 if section == 1 else 10):
                item_id = int(row[0]) * 512 + int(row[1])
                bag['items'].append({'id': item_id, 'name': names.get(item_id, item_comment or 'Item ' + str(item_id)),
                    'min': int(row[2]), 'max': int(row[3]), 'skill': int(row[4]),
                    'luck': int(row[5]), 'option': int(row[6]), 'excellent': int(row[7]),
                    'setOption': int(row[8]) if section == 2 else 0,
                    'socketOption': int(row[9]) if section == 2 else 0, 'pool': section})
            else:
                bag['format'] = 'unsupported'
                if section not in bag['unsupportedSections']:
                    bag['unsupportedSections'].append(section)
        if not bag['settings']:
            bag['format'] = 'unsupported'
        bags.append(bag)
    return bags
