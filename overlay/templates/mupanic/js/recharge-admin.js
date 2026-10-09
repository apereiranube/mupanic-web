(function(){
 'use strict';
 const admin=document.querySelector('.shop-management--v6');if(!admin)return;
 const format=new Intl.NumberFormat('es-AR',{maximumFractionDigits:0});
 admin.querySelectorAll('.shop-package-fold').forEach(card=>{
  const base=card.querySelector('input[name$="[coins]"]'),bonus=card.querySelector('input[name$="[bonus]"]');
  if(!base||!bonus)return;
  const preview=document.createElement('div');preview.className='shop-pack-preview';preview.setAttribute('aria-live','polite');
  card.querySelector('.shop-package-editor').prepend(preview);
  function update(){const b=Number(base.value),r=Number(bonus.value);preview.textContent=Number.isSafeInteger(b)&&Number.isSafeInteger(r)&&b>=0&&r>=0?'Recibe '+format.format(b+r)+' Eryns · Paga $ '+format.format(b)+' ARS · '+format.format(r)+' de regalo si está vigente':'Revisá las cantidades enteras.';}
  base.addEventListener('input',update);bonus.addEventListener('input',update);update();
 });
 admin.querySelectorAll('a[href^="#"]').forEach(link=>link.addEventListener('click',event=>{const target=document.getElementById(link.getAttribute('href').slice(1));if(!target)return;event.preventDefault();history.replaceState(null,'',location.pathname+location.search+link.getAttribute('href'));target.scrollIntoView({behavior:'auto',block:'start'});}));
})();
