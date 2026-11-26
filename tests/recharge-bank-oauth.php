<?php
define('access',true);$_SESSION=['username'=>'panic'];function isLoggedIn(){return true;}function config($key,$return){return ['panic'=>true];}
require __DIR__.'/../overlay/templates/mupanic/inc/recharge-bank.php';
function okO($x,$why){if(!$x)throw new RuntimeException($why);}
function failsO(callable $f,$why){try{$f();}catch(Throwable $e){return;}throw new RuntimeException($why);}
$root=sys_get_temp_dir().'/panic-oauth-'.bin2hex(random_bytes(6));mkdir($root,0700);file_put_contents($root.'/settings.json',json_encode(['environment'=>'test']));
$b=new PanicBankTransfers($root);$guild='1555229554920915057';$support='1555229554920915058';$user='1555229554920915059';$other='1555229554920915070';
okO($b->oauthConfig()===null,'Off by default');
failsO(function()use($b){$b->saveOauth('abc','x');},'Bad client id');
failsO(function()use($b){$b->saveOauth('1555229554920915000','');},'Secret required first time');
$b->saveOauth('1555229554920915000','s3cr3t-Value_1234567890');okO($b->oauthConfig()['client_secret']==='s3cr3t-Value_1234567890','Saved');
$b->saveOauth('1555229554920915000','');okO($b->oauthConfig()['client_secret']==='s3cr3t-Value_1234567890','Empty secret keeps the saved one');
okO((fileperms($root.'/discord-oauth.json')&0777)===0600,'Private file');
$st=PanicBankTransfers::oauthState('PANIC-'.str_repeat('a',32),'k');okO(preg_match('/^PANIC-a{32}\.[a-f0-9]{32}$/',$st)===1 && $st!==PanicBankTransfers::oauthState('PANIC-'.str_repeat('a',32),'k2'),'State bound to session key');
okO(strpos(PanicBankTransfers::oauthUrl($b->oauthConfig(),'https://x.test/usercp/recharge/',$st),'scope=identify+guilds.join')!==false,'Scopes');
$b->saveConfig(['enabled'=>true,'holder'=>'T','bank'=>'B','alias'=>'mu.panic.prueba','guild_id'=>$guild,'support_channel_id'=>$support],0);
$b->bot(['action'=>'heartbeat','environment'=>'test','guild_id'=>$guild,'support_channel_id'=>$support]);
$worker=['version'=>3,'environment'=>'test','last_success'=>time()];$cat=(require __DIR__.'/../overlay/templates/mupanic/inc/recharge-config.php')['packages'];$cart=PanicRecharge::cart($cat,['wcoin-1000'=>1]);
$o=$b->begin('panic',$cart,str_repeat('a',32),'20123456786','test',$worker,PanicBankTransfers::TERMS_VERSION);
failsO(function()use($b,$o,$user){$b->linkDiscord($o['id'],'other',$user,'tokentoken12');},'Only the owner links');
$l=$b->linkDiscord($o['id'],'panic',$user,'tokentoken12');okO($l['ticket_state']==='creating' && $l['discord_id']===$user,'Linked');
failsO(function()use($b,$o,$other){$b->linkDiscord($o['id'],'panic',$other,'tokentoken12');},'Another Discord user cannot take the order');
$q=$b->bot(['action'=>'oauth_queue','environment'=>'test','guild_id'=>$guild]);okO(count($q['orders'])===1 && $q['orders'][0]['access_token']==='tokentoken12' && $q['orders'][0]['discord_id']===$user,'Queued for the bot');
$b->bot(['action'=>'ticket','environment'=>'test','id'=>$o['id'],'discord_id'=>$user,'guild_id'=>$guild,'channel_id'=>'1555229554920915060']);
$g=$b->get($o['id']);okO($g['ticket_state']==='open' && !isset($g['oauth']),'Token removed after ticket');
okO(count($b->bot(['action'=>'oauth_queue','environment'=>'test','guild_id'=>$guild])['orders'])===0,'Queue empty');
okO($b->linkDiscord($o['id'],'panic',$user,'tokentoken12')['ticket_channel']==='1555229554920915060','Relink with open ticket is harmless');
// Stale link: the code path can still open the ticket for the same user.
$o2=$b->begin('panic',$cart,str_repeat('b',32),'20123456786','test',$worker,PanicBankTransfers::TERMS_VERSION);
$b->linkDiscord($o2['id'],'panic',$user,'tokentoken12',time()-700);
okO(count($b->bot(['action'=>'oauth_queue','environment'=>'test','guild_id'=>$guild])['orders'])===0,'Expired token not queued');
$r=$b->ready($o2['id'],'panic');$c=$b->bot(['action'=>'claim','environment'=>'test','code'=>$r['code'],'discord_id'=>$user,'guild_id'=>$guild]);okO($c['can_create']===true,'Code fallback works after stale link');
echo "Discord login: owner-only link, private secret, bot queue, token cleanup and code fallback passed.\n";
