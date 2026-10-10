'use strict';
const https=require('node:https'),fs=require('node:fs'),path=require('node:path'),{randomUUID}=require('node:crypto');
const REF='https://api.github.com/repos/apereiranube/mupanic-web/git/ref/heads/beta';
const RAW='https://raw.githubusercontent.com/apereiranube/mupanic-web/';
const file=path.join(__dirname,'avances','_remote-feed.json');
let running=null;
function validate(rows){if(!Array.isArray(rows)||rows.length>100)throw Error('Formato de avances inválido.');const seen=new Set();for(const n of rows){if(!n||!/^[a-z0-9_-]{1,80}$/.test(n.id)||seen.has(n.id)||typeof n.title!=='string'||!n.title.trim()||n.title.length>150||typeof n.text!=='string'||!n.text.trim()||n.text.length>3500)throw Error('Avance inválido.');seen.add(n.id)}return rows.map(({id,title,text})=>({id,title,text}));}
function getJson(url){return new Promise((resolve,reject)=>{const req=https.get(url,{headers:{'User-Agent':'MU-PANIC-Avances/5','Cache-Control':'no-cache','Accept':'application/json'}},res=>{if(res.statusCode!==200){res.resume();reject(Error('La fuente de avances respondió '+res.statusCode));return}let size=0;const chunks=[];res.on('data',chunk=>{size+=chunk.length;if(size>1048576){req.destroy(Error('La fuente de avances es demasiado grande.'));return}chunks.push(chunk)});res.on('error',reject);res.on('end',()=>{try{resolve(JSON.parse(Buffer.concat(chunks).toString('utf8')))}catch(e){reject(e)}})});req.setTimeout(8000,()=>req.destroy(Error('La fuente de avances tardó demasiado.')));req.on('error',reject)})}
async function download(){
 const ref=await getJson(REF+'?sync='+Date.now()+'-'+randomUUID());
 const sha=ref&&ref.object&&ref.object.sha;
 if(!/^[a-f0-9]{40}$/.test(sha)||ref.ref!=='refs/heads/beta'||ref.object.type!=='commit')throw Error('No se pudo comprobar la versión de los avances.');
 // Lee el archivo de un commit inmutable, nunca el alias beta en el servidor raw.
 return validate(await getJson(RAW+sha+'/community/discord-avances.json'));
}
async function refresh(){if(running)return running;running=(async()=>{const rows=await download();fs.mkdirSync(path.dirname(file),{recursive:true});const temp=file+'.'+randomUUID()+'.tmp';fs.writeFileSync(temp,JSON.stringify(rows,null,2));try{fs.renameSync(temp,file)}finally{if(fs.existsSync(temp))fs.unlinkSync(temp)}return rows.length})();try{return await running}finally{running=null}}
function start(){const sync=()=>refresh().then(n=>console.log('[Avances] Fuente actualizada: '+n+' anuncio(s).')).catch(e=>console.error('[Avances] Se conserva la copia local:',e.message));sync();const timer=setInterval(sync,300000);timer.unref();}
module.exports={refresh,start,validate};
