(function(){
  var toggle=document.querySelector('.menu-toggle');
  var nav=document.querySelector('.main-nav');
  if(toggle&&nav){
    toggle.addEventListener('click',function(){
      var open=nav.classList.toggle('open');
      toggle.setAttribute('aria-expanded',open?'true':'false');
    });
  }
})();