(() => {
  'use strict';
  const button = document.querySelector('.launch-motion');
  if (!button) return;
  const preference = window.matchMedia('(prefers-reduced-motion: reduce)');
  let paused = preference.matches;
  function render() {
    document.body.classList.toggle('launch-paused', paused);
    button.setAttribute('aria-pressed', String(paused));
    button.textContent = paused ? 'Activar efectos' : 'Pausar efectos';
  }
  button.hidden = false;
  render();
  button.addEventListener('click', () => { paused = !paused; render(); });
  preference.addEventListener('change', () => { paused = preference.matches; render(); });
  document.addEventListener('visibilitychange', () => {
    document.body.classList.toggle('launch-background', document.hidden);
  });
})();
