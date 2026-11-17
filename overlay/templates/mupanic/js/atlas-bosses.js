/* MU PANIC · Atlas · Bosses ahora: cuenta regresiva con la hora del servidor. Datos en inc/atlas-bosses.php. */
(function(){
var root=document.getElementById('bosses');if(!root)return;
var offset=(parseInt(root.getAttribute('data-boss-now'),10)||Math.floor(Date.now()/1000))-Math.floor(Date.now()/1000);
function fmt(s){
 if(s<60)return 'menos de 1 min';
 var d=Math.floor(s/86400),h=Math.floor(s%86400/3600),m=Math.floor(s%3600/60);
 if(d)return d+' d '+h+' h';
 if(h)return h+' h '+(m<10?'0':'')+m+' min';
 return m+' min';
}
function tick(){
 var now=Math.floor(Date.now()/1000)+offset;
 root.querySelectorAll('.bos-timer').forEach(function(t){
  var left=parseInt(t.getAttribute('data-until'),10)-now,label=t.getAttribute('data-label');
  t.textContent=left>0?(label?label+' ':'')+fmt(left):'¡Ya! Actualizá';
  t.classList.toggle('bos-timer--done',left<=0);
 });
}
root.addEventListener('click',function(e){var a=e.target.closest('[data-boss-refresh]');if(a){e.preventDefault();location.hash='bosses';location.reload();}});
tick();setInterval(tick,1000);
})();
