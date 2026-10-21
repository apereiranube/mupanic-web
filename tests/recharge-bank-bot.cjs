'use strict';
const assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm'),crypto=require('node:crypto');
class Collection extends Map {find(f){return [...this.values()].find(f)}filter(f){return new Collection([...this].filter(([,v])=>f(v)))}map(f){return [...this.values()].map(f)}some(f){return [...this.values()].some(f)}}
class Builder {setColor(){return this}setTitle(){return this}setDescription(){return this}addFields(){return this}addComponents(){return this}setCustomId(){return this}setLabel(){return this}setStyle(){return this}}
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
 console.log('Existing bot: signed bridge, private ACL, channel reuse and buyer-only attachment tests passed.');
})().catch(e=>{console.error(e);process.exitCode=1});
