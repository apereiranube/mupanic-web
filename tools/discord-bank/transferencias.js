'use strict';
// Extension of the existing MU PANIC bot. No Discord token or SQL access is added.
const fs = require('node:fs'), path = require('node:path');
const { createHmac, randomBytes, timingSafeEqual } = require('node:crypto');
const D = require('discord.js');
const PRIVATE = 'C:\\MuServer43\\PaymentsPrivate';
const URL = 'https://beta.mupanic.com.ar/templates/mupanic/api/recharge-bank-bot.php';
const STAFF = ['👑 Owner', '🛡️ Administrador', '⚔️ Game Master', '🔨 Moderador', '🆘 Soporte'];
const commands = [{ name: 'recarga', description: 'Abrí el ticket privado de tu recarga por transferencia', dm_permission: false }];
const locks = new Set();
let runtime = null;
function settings() {
  const p = JSON.parse(fs.readFileSync(path.join(PRIVATE, 'recharge-worker.json'), 'utf8').replace(/^\uFEFF/, ''));
  const token = fs.readFileSync(path.join(PRIVATE, 'recharge-worker-token'), 'utf8').trim();
  if (!['test','production'].includes(p.environment) || !/^[a-f0-9]{64}$/.test(token)) throw Error('Instalá primero el servicio de recargas existente.');
  return { token, environment: p.environment };
}
async function bridge(request, fetcher = fetch, config = settings()) {
  const nonce = randomBytes(16).toString('hex');
  const body = JSON.stringify({ ...request, environment: config.environment, nonce });
  const stamp = String(Math.floor(Date.now()/1000));
  const signature = createHmac('sha256',config.token).update(stamp+'\n'+body).digest('hex');
  const res = await fetcher(URL,{method:'POST',redirect:'error',signal:AbortSignal.timeout(15000),headers:{'Content-Type':'application/json','X-Panic-Timestamp':stamp,'X-Panic-Signature':signature},body});
  if (!res.ok) throw Error('La web no pudo confirmar la operación. Conservá tu compra y no vuelvas a transferir.');
  const raw = await res.text();if (raw.length>32768) throw Error('Respuesta inválida.');
  const supplied = res.headers.get('X-Panic-Signature') || '';
  const expected = createHmac('sha256',config.token).update(nonce+'\n'+raw).digest('hex');
  if (!/^[a-f0-9]{64}$/.test(supplied) || !timingSafeEqual(Buffer.from(supplied),Buffer.from(expected))) throw Error('Respuesta no autenticada.');
  const result = JSON.parse(raw);if (result.nonce!==nonce) throw Error('Respuesta vencida.');return result;
}
function orderValid(o, user, guild) {
  return o && /^PANIC-[a-f0-9]{32}$/.test(o.id) && o.discord_id===user && o.discord?.guild_id===guild && /^[A-Za-z0-9_]{1,10}$/.test(o.account) && /^\d{11}$/.test(o.cuit) && Number.isSafeInteger(o.coins) && o.coins>0 && o.coins<=1000000 && Number.isSafeInteger(o.price_cents) && o.price_cents>0 && o.price_cents<=100000000;
}
function support(guild) { return guild.channels.cache.find(c=>c.type===D.ChannelType.GuildText && c.name.replace(/[^a-z]/g,'')==='soporte'); }
async function panel(guild) {
  await guild.channels.fetch();const channel=support(guild);if(!channel)throw Error('No se encontró el canal soporte.');
  const messages = await channel.messages.fetch({limit:100});
  const existing = messages.find(m=>m.author.id===guild.client.user.id && m.content==='[MU-PANIC:TRANSFERENCIAS]');
  const payload={content:'[MU-PANIC:TRANSFERENCIAS]',embeds:[new D.EmbedBuilder().setColor(0xc9a64b).setTitle('Recargas por transferencia').setDescription('Prepará tu compra en la web y pulsá «Ya transferí». Abrí aquí un ticket con el código para adjuntar el comprobante de forma privada. El equipo revisará el ingreso bancario antes de acreditar.')],components:[new D.ActionRowBuilder().addComponents(new D.ButtonBuilder().setCustomId('panic_bank_open').setLabel('Recarga por transferencia').setStyle(D.ButtonStyle.Primary))],allowedMentions:{parse:[]}};
  if(existing)await existing.edit(payload);else await channel.send(payload);
  return channel;
}
async function heartbeat(client) {
  const guild=await client.guilds.fetch(process.env.GUILD_ID);await guild.channels.fetch();await guild.roles.fetch();
  if(!guild.roles.cache.some(r=>STAFF.includes(r.name)) || !guild.channels.cache.some(c=>c.type===D.ChannelType.GuildCategory && c.name.includes('SOPORTE')))throw Error('Soporte no configurado.');
  const channel=support(guild);if(!channel)throw Error('No se encontró soporte.');
  await bridge({action:'heartbeat',guild_id:guild.id,support_channel_id:channel.id});
}
async function open(interaction, send=bridge) {
  const code=interaction.fields.getTextInputValue('bank_code').trim().toLowerCase();
  if(!/^[a-f0-9]{24}$/.test(code))throw Error('Copiá el código de 24 caracteres desde Mis transferencias en la web.');
  const guild=interaction.guild;await guild.channels.fetch();await guild.roles.fetch();
  const staff=guild.roles.cache.filter(r=>STAFF.includes(r.name));
  const category=guild.channels.cache.find(c=>c.type===D.ChannelType.GuildCategory && c.name.includes('SOPORTE'));
  if(!staff.size || !category)throw Error('Falta configurar el equipo o la categoría de soporte.');
  const result=await send({action:'claim',code,discord_id:interaction.user.id,guild_id:interaction.guildId});
  const o=result.order; if(!orderValid(o,interaction.user.id,interaction.guildId))throw Error('La compra no pudo verificarse.');
  if(locks.has(o.id))throw Error('El ticket se está creando. Esperá unos segundos.');
  locks.add(o.id);
  try {
    const topic='mu-bank:'+o.id+':'+interaction.user.id;
    let channel=guild.channels.cache.find(c=>c.type===D.ChannelType.GuildText && c.topic===topic);
    if(o.ticket_channel) {
      channel=await guild.channels.fetch(o.ticket_channel);
      if(!channel || channel.topic!==topic)throw Error('El ticket registrado requiere revisión del equipo. No vuelvas a transferir.');
    }
    const allow=[D.PermissionsBitField.Flags.ViewChannel,D.PermissionsBitField.Flags.SendMessages,D.PermissionsBitField.Flags.ReadMessageHistory,D.PermissionsBitField.Flags.AttachFiles];
    const overwrites=[{id:guild.id,deny:[D.PermissionsBitField.Flags.ViewChannel]},{id:interaction.user.id,allow},{id:guild.client.user.id,allow},...staff.map(r=>({id:r.id,allow}))];
    if(!channel) {
      if(!result.can_create)throw Error('El ticket quedó pendiente de revisión. Contactá al equipo con tu número de compra; no repitas el pago.');
      channel=await guild.channels.create({name:'recarga-'+o.id.slice(6,18),type:D.ChannelType.GuildText,parent:category.id,topic,permissionOverwrites:overwrites});
    } else {
      // Restore private access before showing personal/payment data in a recovered channel.
      await channel.permissionOverwrites.set(overwrites);
    }
    await send({action:'ticket',id:o.id,discord_id:interaction.user.id,guild_id:guild.id,channel_id:channel.id});
    const recent=await channel.messages.fetch({limit:100});
    if(!recent.some(m=>m.author.id===guild.client.user.id && m.content==='[MU-PANIC:RECARGA:'+o.id+']')) {
      await channel.send({content:'[MU-PANIC:RECARGA:'+o.id+']',embeds:[new D.EmbedBuilder().setColor(0xc9a64b).setTitle('Comprobante de transferencia').setDescription('Adjuntá el comprobante en este canal privado. El equipo verificará el ingreso bancario antes de aprobar la recarga. No vuelvas a transferir por esta compra.').addFields({name:'Cuenta MU',value:o.account,inline:true},{name:'CUIT del comprador',value:o.cuit,inline:true},{name:'Importe',value:'$ '+new Intl.NumberFormat('es-AR').format(o.price_cents/100)+' ARS',inline:true},{name:'Eryns',value:new Intl.NumberFormat('es-AR').format(o.coins),inline:true},{name:'Compra',value:o.id})],components:[new D.ActionRowBuilder().addComponents(new D.ButtonBuilder().setCustomId('panic_bank_receipt').setLabel('Ya adjunté el comprobante').setStyle(D.ButtonStyle.Primary))],allowedMentions:{parse:[]}});
    }
    await interaction.editReply({content:'Tu ticket privado: '+channel.toString()+'. Adjuntá allí el comprobante.'});
  } finally { locks.delete(o.id); }
}
async function receipt(message,send=bridge) {
  if(!message.guild || message.author.bot || !message.attachments?.size)return false;
  const m=/^mu-bank:(PANIC-[a-f0-9]{32}):([1-9]\d{16,19})$/.exec(message.channel.topic || '');
  if(!m || message.author.id!==m[2] || message.guild.id!==process.env.GUILD_ID)return false;
  await send({action:'receipt',id:m[1],discord_id:m[2],guild_id:message.guild.id,channel_id:message.channel.id,message_id:message.id,has_attachment:true});return true;
}
async function handle(i,client) {
  const match=(i.isChatInputCommand() && i.commandName==='recarga') || (i.isButton() && ['panic_bank_open','panic_bank_receipt'].includes(i.customId)) || (i.isModalSubmit() && i.customId==='panic_bank_code');
  if(!match)return false;
  try {
    if(!i.guild || i.guildId!==process.env.GUILD_ID)throw Error('Usá este comando dentro de MU PANIC.');
    if((i.isChatInputCommand()) || i.customId==='panic_bank_open') {
      await i.showModal(new D.ModalBuilder().setCustomId('panic_bank_code').setTitle('Tu recarga por transferencia').addComponents(new D.ActionRowBuilder().addComponents(new D.TextInputBuilder().setCustomId('bank_code').setLabel('Código de Mis transferencias (web)').setStyle(D.TextInputStyle.Short).setMinLength(24).setMaxLength(24).setRequired(true))));return true;
    }
    await i.deferReply({ephemeral:true});
    if(i.customId==='panic_bank_code')await open(i);
    else {
      const messages=await i.channel.messages.fetch({limit:100});const m=messages.find(m=>m.author.id===i.user.id && m.attachments.size);
      if(!m || !await receipt(m))throw Error('Adjuntá primero tu comprobante en tu ticket de recarga.');
      await i.editReply({content:'Comprobante registrado. El equipo debe verificar el ingreso bancario; todavía no se acreditaron Eryns.'});
    }
  } catch(e) {
    const payload={content:e.message || 'No se pudo completar el ticket. Conservá tu número de compra.',ephemeral:true};
    if(i.deferred || i.replied)await i.editReply(payload).catch(()=>{});else await i.reply(payload).catch(()=>{});
  }
  return true;
}
function install(client) {
  if(runtime)return;runtime=client;
  client.once(D.Events.ClientReady,async()=>{
    // The old bot may still be creating its support channels on first startup.
    let panelReady=false;
    const sync=async()=>{try{if(!panelReady){const g=await client.guilds.fetch(process.env.GUILD_ID);await panel(g);panelReady=true;}await heartbeat(client);}catch{console.log('[Transferencias] Esperando configuración del servicio web o soporte.');}};
    setTimeout(sync,10000).unref();
    setInterval(sync,60000).unref();
  });
  client.on(D.Events.MessageCreate,async message=>{try{await receipt(message);}catch{console.log('[Transferencias] Comprobante pendiente; el comprador puede reintentar con el botón del ticket.');}});
}
module.exports={commands,handle,install,bridge,orderValid,open,receipt};
