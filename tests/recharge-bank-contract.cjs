'use strict';
const assert=require('node:assert/strict'),fs=require('node:fs');
const worker=fs.readFileSync('tools/recharge_worker.ps1','utf8');
assert.equal(worker.match(/\$installSql = @'\r?\n([\s\S]*?)\r?\n'@/)[1].trim(),fs.readFileSync('tools/sql/recharges.sql','utf8').trim(),'Packaged SQL must match the SQL source');
const installer=fs.readFileSync('tools/discord-bank/Instalar_Transferencias.ps1','utf8');
assert.match(installer,/-Install -UpgradeExisting/);assert.match(worker,/if \(\$UpgradeExisting\) .*return/);
if(process.env.PANIC_BOT_SOURCE){
 let source=fs.readFileSync(process.env.PANIC_BOT_SOURCE,'utf8').replace(/^\uFEFF/,'');
 for(const [needle,insert] of [
 ['  await guild.commands.set(commands);',"  commands.push(...require('./transferencias').commands);\n"],
 ['    if (await require("./novedades").handle(interaction)) return;',"    if (await require('./transferencias').handle(interaction, client)) return;\n"],
 ['client.login(TOKEN);',"require('./transferencias').install(client);\n"]]){
  assert.equal(source.split(needle).length-1,1,'Installer anchor must occur exactly once');source=source.replace(needle,insert+needle);
 }
 new (require('node:vm').Script)(source);console.log('Actual existing bot accepts all three extension hooks.');
}
console.log('Worker SQL and upgrade contract checks passed.');
