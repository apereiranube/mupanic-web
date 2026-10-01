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

  var revealItems=document.querySelectorAll('.reveal');
  if('IntersectionObserver' in window){
    var observer=new IntersectionObserver(function(entries){
      entries.forEach(function(entry){
        if(entry.isIntersecting){
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    },{threshold:.08,rootMargin:'0px 0px -30px 0px'});
    revealItems.forEach(function(item){observer.observe(item);});
  }else{
    revealItems.forEach(function(item){item.classList.add('is-visible');});
  }

  var particleBox=document.querySelector('[data-particles]');
  if(particleBox){
    for(var p=0;p<26;p++){
      var particle=document.createElement('span');
      particle.className='particle';
      particle.style.left=((p*37)%97)+'%';
      particle.style.top=(35+((p*23)%70))+'%';
      particle.style.setProperty('--dur',(6+((p*7)%8))+'s');
      particle.style.setProperty('--delay',(-1*((p*11)%10))+'s');
      particle.style.setProperty('--drift',((-55+((p*31)%110)))+'px');
      particleBox.appendChild(particle);
    }
  }

  var hero=document.querySelector('[data-parallax-hero]');
  var layer=document.querySelector('[data-parallax-layer]');
  if(hero&&layer&&window.matchMedia('(pointer:fine)').matches){
    hero.addEventListener('mousemove',function(e){
      var rect=hero.getBoundingClientRect();
      var x=(e.clientX-rect.left)/rect.width-.5;
      var y=(e.clientY-rect.top)/rect.height-.5;
      layer.style.transform='scale(1.055) translate3d('+(x*-10)+'px,'+(y*-8)+'px,0)';
    });
    hero.addEventListener('mouseleave',function(){
      layer.style.transform='scale(1.04) translate3d(0,0,0)';
    });
  }

  var tiltItems=document.querySelectorAll('.hover-tilt');
  if(window.matchMedia('(pointer:fine)').matches){
    tiltItems.forEach(function(card){
      card.addEventListener('mousemove',function(e){
        var r=card.getBoundingClientRect();
        var x=(e.clientX-r.left)/r.width-.5;
        var y=(e.clientY-r.top)/r.height-.5;
        card.style.transform='perspective(800px) rotateX('+(y*-3)+'deg) rotateY('+(x*4)+'deg) translateY(-2px)';
      });
      card.addEventListener('mouseleave',function(){card.style.transform='';});
    });
  }

  var counters=document.querySelectorAll('[data-count]');
  if('IntersectionObserver' in window){
    var countObserver=new IntersectionObserver(function(entries){
      entries.forEach(function(entry){
        if(!entry.isIntersecting) return;
        var el=entry.target;
        var target=parseInt(el.getAttribute('data-count'),10)||0;
        var start=performance.now();
        var duration=650;
        function tick(now){
          var progress=Math.min((now-start)/duration,1);
          var eased=1-Math.pow(1-progress,3);
          el.textContent=Math.round(target*eased).toLocaleString('es-AR');
          if(progress<1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
        countObserver.unobserve(el);
      });
    },{threshold:.5});
    counters.forEach(function(c){countObserver.observe(c);});
  }

  var journey=document.querySelector('[data-world-journey]');
  var progressEl=document.querySelector('[data-route-progress]');
  var nodes=document.querySelectorAll('.world-node');
  if(journey&&progressEl&&nodes.length){
    function updateJourney(){
      var rect=journey.getBoundingClientRect();
      var viewport=window.innerHeight;
      var progress=(viewport*0.72-rect.top)/(rect.height*0.78);
      progress=Math.max(0,Math.min(1,progress));
      progressEl.style.height=(progress*100)+'%';
      nodes.forEach(function(node,index){
        var threshold=index/(nodes.length-1);
        node.classList.toggle('is-reached',progress>=threshold-.03);
      });
    }
    updateJourney();
    window.addEventListener('scroll',updateJourney,{passive:true});
    window.addEventListener('resize',updateJourney);
  }
})();
