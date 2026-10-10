'use strict';
const { EmbedBuilder, ActionRowBuilder, ButtonBuilder, ButtonStyle, ChannelType, PermissionsBitField, ApplicationCommandOptionType: T, ModalBuilder, TextInputBuilder, TextInputStyle } = require('discord.js');
const { randomUUID } = require('node:crypto');
const drafts = new Map();
const TTL = 10 * 60 * 1000;
const tags = { avance: ['AVANCES DEL SERVIDOR', 0xc9a64b], mejora: ['NUEVA MEJORA', 0x7857ff], correccion: ['CORRECCIONES', 0x4f8cff], anuncio: ['NOVEDADES', 0xc9a64b] };
const fs = require('node:fs');
const path = require('node:path');
const configFile = path.join(__dirname, 'novedades-canal.json');
const forms = new Map();
const commands = [
 ...require("./avances").commands,
 { name: 'novedades-canal', description: 'Configurá una sola vez dónde publicar las novedades', default_member_permissions: '8', dm_permission: false, options: [{ name: 'canal', description: 'Canal de avances o novedades', type: T.Channel, channel_types: [ChannelType.GuildText, ChannelType.GuildAnnouncement], required: true }] },
 { name: 'novedad', description: 'Abrí el formulario para preparar una novedad', default_member_permissions: '8', dm_permission: false, options: [{ name: 'imagen', description: 'Imagen opcional para acompañar la novedad', type: T.Attachment }] }
];
function readConfig() { try { return JSON.parse(fs.readFileSync(configFile, 'utf8')); } catch (e) { if(e.code === 'ENOENT') return {}; throw new Error('No se pudo leer el canal configurado.'); } }
function owns(i) { return i.guildId === process.env.GUILD_ID && i.memberPermissions?.has(PermissionsBitField.Flags.Administrator); }
function sweep() { for (const [id, d] of drafts) if (Date.now() - d.created > TTL) drafts.delete(id); }
async function channelFor(i, id) {
 const c = await i.guild.channels.fetch(id);
 if (!c || c.guildId !== i.guildId || ![ChannelType.GuildText, ChannelType.GuildAnnouncement].includes(c.type)) throw new Error('Elegí un canal de texto de este servidor.');
 if (['estado-servidor', 'estado-server', 'server-status', 'comandos-bot'].includes(c.name) || /estado/.test(c.name)) throw new Error('Elegí un canal de novedades: los canales de estado y comandos se reservan para el bot.');
 const me = await i.guild.members.fetchMe();
 if (!c.permissionsFor(me)?.has([PermissionsBitField.Flags.ViewChannel, PermissionsBitField.Flags.SendMessages, PermissionsBitField.Flags.EmbedLinks])) throw new Error('Al bot le faltan permisos para ver, enviar mensajes o insertar enlaces en ese canal.');
 const member = await i.guild.members.fetch(i.user.id);
 if (!member.permissions.has(PermissionsBitField.Flags.Administrator)) throw new Error('Esta función es solo para administradores.');
 return c;
}
async function handle(i) {
 if (await require("./avances").handle(i)) return true;
 const command = i.isChatInputCommand() && ['novedad', 'novedades-canal'].includes(i.commandName);
 const modal = i.isModalSubmit() && i.customId.startsWith('mu_news_form:');
 const button = i.isButton() && i.customId.startsWith('mu_news:');
 if (!command && !button && !modal) return false;
 try {
  sweep();
  if (!owns(i)) { await i.reply({ content: 'Esta función es solo para administradores del servidor.', ephemeral: true }); return true; }
  for (const [id, f] of forms) if (Date.now() - f.created > TTL) forms.delete(id);
  if (command && i.commandName === 'novedades-canal') {
   await i.deferReply({ ephemeral: true });
   const c = await channelFor(i, i.options.getChannel('canal', true).id);
   const temp = configFile + '.' + randomUUID() + '.tmp';
   fs.writeFileSync(temp, JSON.stringify({ guild: i.guildId, channel: c.id }));
   try { fs.renameSync(temp, configFile); } finally { if(fs.existsSync(temp)) fs.unlinkSync(temp); }
   await i.editReply({ content: `Listo. Las novedades se publicarán en <#${c.id}>. Ahora usá /novedad para abrir el formulario.`, allowedMentions: { parse: [] } });
   return true;
  }
  if (command) {
   const conf = readConfig();
   if(conf.guild !== i.guildId || !conf.channel) { await i.reply({ content: 'Primero configurá el destino con /novedades-canal. Solo hace falta una vez.', ephemeral: true }); return true; }
   const image = i.options.getAttachment('imagen');
   if (image && (!['image/png', 'image/jpeg', 'image/webp'].includes(image.contentType) || image.size > 8 * 1024 * 1024)) throw new Error('Usá una imagen PNG, JPEG o WebP de hasta 8 MB.');
   if (forms.size >= 100) throw new Error('Hay demasiados formularios abiertos. Esperá unos minutos.');
   const id = randomUUID();
   const form = new ModalBuilder().setCustomId('mu_news_form:' + id).setTitle('Preparar novedad · MU PANIC');
   form.addComponents(
    new ActionRowBuilder().addComponents(new TextInputBuilder().setCustomId('title').setLabel('Título de la novedad').setStyle(TextInputStyle.Short).setRequired(true).setMaxLength(150).setPlaceholder('Ej: Nuevas mascotas en MU PANIC')),
    new ActionRowBuilder().addComponents(new TextInputBuilder().setCustomId('body').setLabel('¿Qué querés contar?').setStyle(TextInputStyle.Paragraph).setRequired(true).setMaxLength(3500).setPlaceholder('Escribí el avance. Después vas a ver una vista previa.'))
   );
   forms.set(id, { owner: i.user.id, guild: i.guildId, channel: conf.channel, image, created: Date.now() });
   try { await i.showModal(form); } catch(e) { forms.delete(id); throw e; }
   return true;
  }
  if (modal) {
   const formId = i.customId.slice('mu_news_form:'.length);
   const f = forms.get(formId);
   if(!f || f.owner !== i.user.id || f.guild !== i.guildId) throw new Error('El formulario venció. Abrí uno nuevo con /novedad.');
   forms.delete(formId);
   const options = { getChannel: () => ({ id: f.channel }), getAttachment: () => f.image, getString: key => ({ titulo: i.fields.getTextInputValue('title').trim(), texto: i.fields.getTextInputValue('body').trim(), tipo: 'avance' }[key]) };
   if(!options.getString('titulo') || !options.getString('texto')) throw new Error('Completá título y texto.');
   await i.deferReply({ ephemeral: true });
   if (drafts.size >= 100) throw new Error('Hay demasiadas vistas previas abiertas. Esperá unos minutos.');
   const c = await channelFor(i, options.getChannel('canal', true).id);
   const image = options.getAttachment('imagen');
   if (image && (!['image/png', 'image/jpeg', 'image/webp'].includes(image.contentType) || image.size > 8 * 1024 * 1024)) throw new Error('Usá una imagen PNG, JPEG o WebP de hasta 8 MB.');
   const [tag, color] = tags[options.getString('tipo') || 'avance'];
   const embed = new EmbedBuilder().setAuthor({ name: `MU PANIC · ${tag}` }).setTitle(options.getString('titulo', true)).setDescription(options.getString('texto', true)).setColor(color).setFooter({ text: 'MU PANIC • Comunidad oficial' });
   if (image) embed.setImage(image.url);
   const id = randomUUID();
   const row = new ActionRowBuilder().addComponents(new ButtonBuilder().setCustomId(`mu_news:publish:${id}`).setLabel('Publicar novedad').setStyle(ButtonStyle.Success), new ButtonBuilder().setCustomId(`mu_news:cancel:${id}`).setLabel('Cancelar').setStyle(ButtonStyle.Secondary));
   drafts.set(id, { owner: i.user.id, guild: i.guildId, channel: c.id, embed: embed.toJSON(), created: Date.now(), state: 'preview' });
   try { await i.editReply({ content: `**Vista previa privada** · Destino: <#${c.id}>\nRevisá el contenido. Vence en 10 minutos.`, embeds: [embed], components: [row], allowedMentions: { parse: [] } }); }
   catch (e) { drafts.delete(id); throw e; }
  } else {
   const [, action, id] = i.customId.split(':');
   const d = drafts.get(id);
   if (!d || d.owner !== i.user.id || d.guild !== i.guildId || d.state !== 'preview' || !['publish', 'cancel'].includes(action)) { await i.reply({ content: 'La vista previa venció o ya fue procesada. Prepará una nueva con /novedad.', ephemeral: true }); return true; }
   d.state = 'processing'; // Antes del primer await: evita doble publicación por doble clic.
   await i.deferUpdate();
   if (action === 'cancel') { drafts.delete(id); await i.editReply({ content: 'Novedad cancelada.', embeds: [], components: [] }); return true; }
   let sent;
   try {
    const c = await channelFor(i, d.channel);
    sent = await c.send({ embeds: [d.embed], allowedMentions: { parse: [] } });
   } catch (e) {
    drafts.delete(id);
    console.error('[Novedades] Envío no confirmado:', e.message);
    await i.editReply({ content: 'No se pudo confirmar el envío. Revisá el canal antes de crear otra novedad para evitar duplicados.', embeds: [], components: [] });
    return true;
   }
   drafts.delete(id);
   console.log(`[Novedades] Publicada ${sent.id} en ${d.channel} por ${d.owner}`);
   await i.editReply({ content: `Novedad publicada: ${sent.url}`, embeds: [], components: [] });
  }
 } catch (e) {
  console.error('[Novedades]', e.message);
  const payload = { content: e.message, embeds: [], components: [], allowedMentions: { parse: [] } };
  if (i.deferred || i.replied) await i.editReply(payload).catch(() => {});
  else await i.reply({ ...payload, ephemeral: true }).catch(() => {});
 }
 return true;
}
module.exports = { commands, handle };
