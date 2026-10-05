'use strict';
const assert = require('node:assert/strict');
const {build,filter} = require('../overlay/templates/mupanic/js/atlas-search.js');
const mob = {id:3,name:'Spider',level:2};
const data = {
  maps:[{id:0,name:'LaCleon',aliases:['Raklion'],equipmentDrop:{dropMode:4},monsters:[mob],spots:[{x:10,y:20,monsters:[mob]}]},
    {id:1,name:'Disabled drop',equipmentDrop:{dropMode:0},monsters:[mob],spots:[]}],
  drops:[{id:6159,name:'Jewel of Chaos',map:-1,monster:-1,min:1,max:10},
    {id:6159,name:'Jewel of Chaos',map:0,monster:9,min:1,max:10}],
  eventBags:[{id:7,name:'Boss chest',monster:459,monsterName:'Selupan',format:'standard',items:[{id:6159,name:'Jewel of Chaos'},{id:6159,name:'Jewel of Chaos'}]},
    {id:8,name:'Unverified',monster:-1,format:'unsupported',items:[{id:900,name:'Do not claim this drop'}]}],
};
const entries = build(data);
const find = (query, extra={}) => filter(entries,{query,type:'',map:'',level:'',...extra});
assert.equal(find('chaos jewel').length,1,'Words can be typed in either order');
assert.equal(find('Chaos')[0].routes.length,3,'Rules preserved; duplicate reward pool rows collapsed');
assert.deepEqual(find('Chaos')[0].routes[0].maps,[0],'Disabled map must not be offered as a farming location');
assert.deepEqual(find('Chaos')[0].routes[1].maps,[],'Exclusive monster rule must not match a different monster');
assert.equal(find('Chaos',{map:'1'}).length,0,'Map filter respects actual compatible population');
assert.equal(find('Raklion',{type:'map'})[0].name,'LaCleon','Client map aliases remain searchable');
assert.equal(find('spider',{type:'mob',level:'1'}).length,0,'Mob level limit enforced');
assert.equal(find('Selupan',{type:'boss'}).length,1,'Event-only boss can be found without invented spawn map');
assert.equal(find('Do not claim').length,0,'Unsupported rewards must not assert item availability');
assert.equal(find('Selupan')[0].maps.length,0,'Unknown boss map stays unknown');
console.log('Atlas search: 10 behavior checks passed.');
