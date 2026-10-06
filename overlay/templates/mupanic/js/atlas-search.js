/* Public gameplay index, rebuilt from the currently published server snapshot. */
(function (root) {
  'use strict';
  function normalize(value) {
    return String(value || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
  }
  function compatible(drop, map, monster) {
    var mode = map.equipmentDrop && map.equipmentDrop.dropMode;
    return (drop.map === -1 || drop.map === map.id) &&
      (mode === '*' || mode === 'unknown' || (Number(mode) & 4) !== 0) &&
      monster.level >= drop.min && monster.level <= drop.max &&
      (drop.monster === -1 || drop.monster === monster.id);
  }
  function build(data) {
    var mobs = new Map(), objects = new Map(), entries = [];
    function object(item) {
      if (!objects.has(item.id)) objects.set(item.id, {id:item.id, type:'object', name:item.name, routes:[]});
      return objects.get(item.id);
    }
    data.maps.forEach(function (map) {
      entries.push({id:map.id, type:'map', name:map.name, aliases:map.aliases || [], maps:[map.id],
        routes:[{label:map.spots.length + ' spots · ' + map.monsters.length + ' tipos de mobs', hash:'#mapa-' + map.id}],
        levels:map.monsters.map(function (mob) { return mob.level; })});
      map.monsters.forEach(function (mob) {
        if (!mobs.has(mob.id)) mobs.set(mob.id, {id:mob.id, type:'mob', name:mob.name, levels:[], maps:[], routes:[], boss:false});
        var entry = mobs.get(mob.id);
        entry.levels.push(mob.level); entry.maps.push(map.id);
        var spots = map.spots.map(function (spot, i) { return {spot:spot, index:i}; }).filter(function (s) {
          return s.spot.monsters.some(function (m) { return m.id === mob.id; });
        });
        entry.routes.push({label:map.name + ' · Lv. ' + mob.level, maps:[map.id], level:mob.level, hash:'#mob-' + map.id + '-' + mob.id,
          spots:spots.map(function (s) { return {label:'X ' + s.spot.x + ' · Y ' + s.spot.y, hash:'#spot-' + map.id + '-' + s.index}; })});
      });
    });
    data.drops.forEach(function (drop, i) {
      var maps = data.maps.filter(function (map) { return map.monsters.some(function (mob) { return compatible(drop,map,mob); }); });
      object(drop).routes.push({kind:'drop', label:'Drop de monstruos · Lv. ' + drop.min + '–' + drop.max,
        detail:maps.length ? maps.map(function (m) { return m.name; }).join(', ') : 'Sin población fija compatible identificada',
        maps:maps.map(function (m) { return m.id; }), hash:'#drop-' + i});
    });
    (data.eventBags || []).forEach(function (bag) {
      if (bag.monster >= 0) {
        if (!mobs.has(bag.monster)) mobs.set(bag.monster, {id:bag.monster, type:'mob', name:bag.monsterName || bag.name, levels:[], maps:[], routes:[], boss:true});
        var mob = mobs.get(bag.monster); mob.boss = true;
        mob.routes.push({label:'Recompensa: ' + bag.name, hash:'#recompensa-' + bag.id});
      }
      // Unsupported lists are explicitly unverified and must not create item claims.
      if (!['standard','advanced'].includes(bag.format)) return;
      var seen = new Set();
      bag.items.forEach(function (item) {
        if (item.setName) { var obj = object(item); obj.aliases = Array.from(new Set((obj.aliases || []).concat(item.setName))); }
        if (seen.has(item.id)) return;
        seen.add(item.id);
        object(item).routes.push({kind:'reward', label:bag.name, detail:bag.monster >= 0 ? 'Recompensa de ' + (bag.monsterName || bag.name) : 'Caja o evento',
          maps:[], hash:'#recompensa-' + bag.id});
      });
    });
    entries = entries.concat(Array.from(mobs.values()), Array.from(objects.values()));
    entries.forEach(function (entry) { entry.text = normalize(entry.name + ' ' + (entry.aliases || []).join(' ')); });
    return entries;
  }
  function filter(entries, options) {
    var words = normalize(options.query).split(/\s+/).filter(Boolean);
    return entries.filter(function (entry) {
      if (options.type && entry.type !== options.type && !(options.type === 'boss' && entry.type === 'mob' && entry.boss)) return false;
      if (!words.every(function (word) { return entry.text.includes(word); })) return false;
      if (options.map !== '') {
        var map = Number(options.map);
        if (entry.type === 'object' ? !entry.routes.some(function (r) { return r.maps.includes(map); }) : !entry.maps.includes(map)) return false;
      }
      if (options.level !== '' && !entry.routes.some(function (route) { return route.level !== undefined && route.level <= Number(options.level) && (options.map === '' || (route.maps || []).includes(Number(options.map))); })) return false;
      return true;
    }).sort(function (a,b) {
      var query = normalize(options.query);
      return Number(b.text === query) - Number(a.text === query) || Number(b.text.startsWith(query)) - Number(a.text.startsWith(query)) || a.name.localeCompare(b.name);
    });
  }
  var api = {build:build, filter:filter, compatible:compatible};
  if (typeof module !== 'undefined' && module.exports) module.exports = api;
  else root.PanicAtlasSearch = api;
})(typeof window !== 'undefined' ? window : globalThis);
