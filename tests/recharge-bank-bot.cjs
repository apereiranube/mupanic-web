'use strict';
const assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm'),crypto=require('node:crypto');
class Collection extends Map {find(f){return [...this.values()].find(f)}filter(f){return new Collection([...this].filter(([,v])=>f(v)))}map(f){return [...this.values()].map(f)}some(f){return [...this.values()].some(f)}}
class Builder {setFooter(x){this.footer=x;return this}setColor(){return this}setTitle(){return this}setDescription(){return this}addFields(){return this}addComponents(){return this}setCustomId(){return this}setLabel(){return this}setStyle(){return this}}
const D={ChannelType:{GuildText:0,GuildCategory:4},PermissionsBitField:{Flags:{ViewChannel:1024n,SendMessages:2048n,ReadMessageHistory:65536n,AttachFiles:32768n}},EmbedBuilder:Builder,ActionRowBuilder:Builder,ButtonBuilder:Builder,ButtonStyle:{Primary:1}};
const sandbox={module:{exports:{}},require:n=>n==='discord.js'?D:require(n),process:{env:{GUILD_ID:'1555229554920915057'}},Buffer,AbortSignal,fetch,console,setTimeout,setInterval};
vm.runInNewContext(fs.readFileSync('tools/discord-bank/transferencias.js','utf8'),sandbox);const mod=sandbox.module.exports;
(async()=>{
 const token='a'.repeat(64),config={token,environment:'test'};let sent;
 const fetcher=async(url,opt)=>{sent=JSON.parse(opt.body);assert.equal(crypto.createHmac('sha256',token).update(opt.headers['X-Panic-Timestamp']+'\n'+opt.body).digest('hex'),opt.headers['X-Panic-Signature']);const body=JSON.stringify({state:'ok',nonce:sent.nonce});return {ok:true,text:async()=>body,headers:new Map([['X-Panic-Signature',crypto.createHmac('sha256',token).update(sent.nonce+'\n'+body).digest('hex')]])}};
 await mod.bridge({action:'heartbeat'},fetcher,config);assert.equal(sent.environment,'test');assert.match(sent.nonce,/^[a-f0-9]{32}$/);
 await assert.rejects(()=>mod.bridge({},async()=>({ok:true,text:async()=>'{}',headers:new Map([['X-Panic-Signature','0'.repeat(64)]])}),config));
 const guildId='1555229554920915057',user='1555229554920915059',id='PANIC-'+'a'.repeat(32);let created=0,recorded=0,overwrites;
 const o={id,account:'panic',cuit:'20123456786',coins:1000,price_cents:100000,discord_id:user,discord:{guild_id:guildId},ticket_channel:null};
 const channel={id:'1555229554920915060',type:0,topic:'mu-bank:'+id+':'+user,permissionOverwrites:{set:async x=>{overwrites=x}},messages:{fetch:async()=>new Collection()},send:async()=>{},toString:()=>'<#ticket>'};
 const cache=new Collection([['category',{id:'cat',name:'🆘 SOPORTE',type:4}]]);
 const guild={id:guildId,client:{user:{id:'bot'}},channels:{cache,fetch:async target=>target?channel:cache,create:async payload=>{created++;overwrites=payload.permissionOverwrites;cache.set(channel.id,channel);return channel}},roles:{cache:new Collection([['staff',{id:'staff',name:'🆘 Soporte'}]]),fetch:async()=>{}}};
 const interaction={fields:{getTextInputValue:()=> 'c'.repeat(24)},user:{id:user},guildId,guild,editReply:async()=>{}};
 const bridge=async r=>{if(r.action==='claim')return {order:o,can_create:created===0};if(r.action==='ticket'){recorded++;return {state:'ok'}}throw Error('Unknown action')};
 await mod.open(interaction,bridge);await mod.open(interaction,bridge);assert.equal(created,1);assert.equal(recorded,2);
 assert(overwrites.some(x=>x.id===guildId && x.deny.includes(1024n)));assert(overwrites.some(x=>x.id===user && x.allow.includes(32768n)));assert(overwrites.some(x=>x.id==='staff'));
 const message={guild:{id:guildId},channel,author:{id:user,bot:false},attachments:{size:1},id:'1555229554920915061'};let received=0;
 assert.equal(await mod.receipt(message,async r=>{received++;assert.equal(r.discord_id,user);assert.equal(r.has_attachment,true)}),true);
 assert.equal(await mod.receipt({...message,author:{id:'other'}},async()=>{throw Error('Wrong buyer')}),false);assert.equal(received,1);
 assert.equal(mod.orderValid({...o,discord_id:'other'},user,guildId),false);
 const notificationOrder={...o,ticket_channel:channel.id};const sentMessages=new Collection();let notificationSends=0,noticeAcks=0,noticeDone=false;
 channel.messages.fetch=async()=>sentMessages;
 channel.send=async payload=>{notificationSends++;assert.deepEqual([...payload.allowedMentions.users],[user]);assert.match(payload.embeds[0].footer.text,/Eryns acreditados/);const m={id:'1555229554920915065',author:{id:'bot'},embeds:payload.embeds};sentMessages.set(m.id,m);return m};
 const notices=async r=>{if(r.action==='notifications')return {orders:noticeDone?[]:[notificationOrder]};if(r.action==='notified'){noticeAcks++;if(noticeAcks===1)throw Error('Simulated lost web acknowledgment');noticeDone=true;return {state:'ok'}}throw Error('Unknown notice action')};
 const notifyClient={user:{id:'bot'},guilds:{fetch:async()=>guild}};
 await mod.notifyCredits(notifyClient,notices);await mod.notifyCredits(notifyClient,notices);await mod.notifyCredits(notifyClient,notices);
 assert.equal(notificationSends,1,'Lost acknowledgment must reuse the existing Discord message');assert.equal(noticeAcks,2);
 const originalTopic=channel.topic;channel.topic='public-channel';await mod.notifyCredits(notifyClient,async()=>({orders:[notificationOrder]}));assert.equal(notificationSends,1,'Never notify in a mismatched ticket');channel.topic=originalTopic;
 // Discord login queue: adds the buyer, opens one ticket, pings once and DMs once.
 const oo={...o,id:'PANIC-'+'b'.repeat(32),access_token:'tokentoken12'};const ch2={...channel,id:'1555229554920915066',topic:'mu-bank:'+oo.id+':'+user};const msgs2=new Collection();let added=0,dms=0,tickets=0,pings=0;
 ch2.messages={fetch:async()=>msgs2};ch2.send=async p=>{if((p.content||'').startsWith('[MU-PANIC:AVISO:'))pings++;const m={id:String(msgs2.size+1),author:{id:'bot'},content:p.content,embeds:p.embeds||[]};msgs2.set(m.id,m);return m};ch2.permissionOverwrites={set:async()=>{}};
 const cache2=new Collection([['category',{id:'cat',name:'🆘 SOPORTE',type:4}]]);
 const g2={id:guildId,client:{user:{id:'bot'}},channels:{cache:cache2,fetch:async t=>t?ch2:cache2,create:async()=>{cache2.set(ch2.id,ch2);return ch2}},roles:guild.roles,members:{fetch:async()=>{throw Error('not member')},add:async(id,opt)=>{added++;assert.equal(opt.accessToken,'tokentoken12');return {send:async()=>{dms++}}}}};
 let queued=true;const q=async r=>{if(r.action==='oauth_queue')return {orders:queued?[oo]:[]};if(r.action==='ticket'){tickets++;queued=false;return {state:'ok'}}throw Error('x')};
 const c2={user:{id:'bot'},guilds:{fetch:async()=>g2}};
 await mod.oauthQueue(c2,q);await mod.oauthQueue(c2,q);queued=true;await mod.oauthQueue(c2,q);
 assert.equal(added,2);assert.equal(tickets,2);assert.equal(pings,1,'Ping only once');assert.equal(dms,1,'DM only once');
 await mod.oauthQueue(c2,async()=>({orders:[{...oo,access_token:'bad token!'}]}));assert.equal(added,2,'Invalid token ignored');
 console.log('Existing bot: signed bridge, private ACL, channel reuse and buyer-only attachment tests passed.');
})().catch(e=>{console.error(e);process.exitCode=1});
