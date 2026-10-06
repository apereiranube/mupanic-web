"""Read public reward lists using the column headers in the server export.

Keep pool membership and configured rates separate; do not infer per-item odds.
Advanced sections 3/4/X follow Louis UP42 documentation. Preserve selection
groups and option profiles; never turn their weights into per-kill item odds.
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
    option_rates = {}
    section = None
    if 'Data/Item/ItemOptionRate.txt' in members:
        for values, comment in rows('Data/Item/ItemOptionRate.txt'):
            if len(values) == 1:
                section = int(values[0])
            elif section is not None:
                numbers = list(map(int, values))
                option_rates[(section, numbers[0])] = [i for i, weight in enumerate(numbers[1:]) if weight > 0]
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
        source_rows = list(rows(files[index]))
        sections = {int(row[0]) for row, comment in source_rows if len(row) == 1}
        if 3 in sections or 4 in sections:
            _advanced(bag, source_rows, names, option_rates)
            bags.append(bag)
            continue
        section = None
        for row, item_comment in source_rows:
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


def _advanced(bag, source_rows, names, option_rates):
    """Only publish structurally complete, reachable advanced reward pools."""
    bag['format'] = 'advanced'
    attempts, groups, pools = [], [], {}
    section = None
    try:
        for row, comment in source_rows:
            if len(row) == 1:
                section = int(row[0])
                if section < 3: raise ValueError('Mixed or unsupported layout')
                continue
            values = list(map(int, row))
            if section == 3 and len(values) == 3:
                idx, rate, inventory = values
                if idx < 0 or not 0 <= rate <= 10000 or inventory not in (0, 1): raise ValueError('Invalid attempt')
                attempts.append({'index': idx, 'dropRate': rate, 'dropInventory': inventory})
            elif section == 4 and len(values) == 12:
                idx, pool, rate, money, flags, *classes = values
                if idx < 0 or pool < 5 or rate < 0 or money < 0 or flags < 0 or flags > 15 or any(c not in (0, 1) for c in classes): raise ValueError('Invalid group')
                groups.append({'index': idx, 'section': pool, 'sectionRate': rate, 'moneyAmount': money, 'optionValue': flags, 'classes': classes, 'label': comment})
            elif section is not None and section >= 5 and len(values) == 11:
                item_id, level, grade, *rest = values
                codes, duration = rest[:7], rest[7]
                if item_id < 0 or level < 0 or duration < 0: raise ValueError('Invalid item')
                outcomes = []
                for kind, code in enumerate(codes):
                    possible = ([level] if kind == 0 else [0]) if code == -1 else option_rates.get((kind, code))
                    if not possible: raise ValueError('Missing option profile')
                    outcomes.append(possible)
                item = {'id': item_id, 'name': names.get(item_id, 'Item '+str(item_id)),
                        'min': min(outcomes[0]), 'max': max(outcomes[0]),
                        'skill': int(any(outcomes[1])), 'luck': int(any(outcomes[2])),
                        'option': max(outcomes[3]), 'excellent': max(outcomes[4]),
                        'setOption': max(outcomes[5]), 'socketOption': max(outcomes[6]),
                        'pool': section, 'grade': grade, 'duration': duration,
                        'optionCodes': codes, 'optionValues': outcomes}
                # Set names are supplied in administrator item comments, never guessed from IDs.
                parts = [part.strip() for part in comment.split('/')]
                if item['setOption'] and len(parts) >= 2: item['setName'] = parts[0]
                pools.setdefault(section, []).append(item)
            else:
                raise ValueError('Unsupported advanced row')
        if not attempts or len({a['index'] for a in attempts}) != len(attempts): raise ValueError('Invalid attempts')
        active = {a['index'] for a in attempts if a['dropRate'] > 0}
        for idx in active:
            eligible = [g for g in groups if g['index'] == idx and g['sectionRate'] > 0 and any(g['classes'])]
            if not eligible: raise ValueError('No reachable group')
            if any(g['section'] not in pools and not g['moneyAmount'] for g in eligible): raise ValueError('Missing referenced pool')
        if any(g['index'] not in {a['index'] for a in attempts} for g in groups): raise ValueError('Unknown attempt')
        reachable = {g['section'] for g in groups if g['index'] in active and g['sectionRate'] > 0 and any(g['classes'])}
        bag['items'] = [item for section in sorted(reachable) for item in pools.get(section, [])]
        bag['selection'] = {'attempts': attempts, 'groups': groups}
    except (ValueError, TypeError, IndexError):
        bag['format'] = 'unsupported'
        bag['items'] = []
        bag['unsupportedSections'] = sorted({int(row[0]) for row, comment in source_rows if len(row) == 1})
