(function(){
  var toggle=document.querySelector('.menu-toggle');
  var nav=document.querySelector('.main-nav');

  if(toggle&&nav){
    toggle.addEventListener('click',function(){
      var open=nav.classList.toggle('open');
      toggle.setAttribute('aria-expanded',open?'true':'false');
    });

    nav.addEventListener('click',function(event){
      if(event.target.tagName==='A'&&nav.classList.contains('open')){
        nav.classList.remove('open');
        toggle.setAttribute('aria-expanded','false');
      }
    });
  }

  var items=document.querySelectorAll('.reveal');
  if(!items.length) return;

  if(!('IntersectionObserver' in window)){
    for(var i=0;i<items.length;i++) items[i].classList.add('is-visible');
    return;
  }

  var observer=new IntersectionObserver(function(entries){
    entries.forEach(function(entry){
      if(entry.isIntersecting){
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    });
  },{threshold:.08,rootMargin:'0px 0px -40px 0px'});

  for(var j=0;j<items.length;j++) observer.observe(items[j]);
})();
