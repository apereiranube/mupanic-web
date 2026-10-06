/* Compact event dossiers; original articles remain available without JavaScript. */
(function () {
  'use strict';
  var wiki = document.querySelector('[data-wiki]');
  var dialog = document.querySelector('[data-atlas-event-dialog]');
  if (!wiki || !dialog || typeof dialog.showModal !== 'function') return;
  var content = dialog.querySelector('[data-atlas-event-content]');
  var closeButton = dialog.querySelector('[data-atlas-event-close]');
  var trigger = null, backdropPress = false;
  wiki.classList.add('atlas-events-enhanced');
  function openEvent(id, button) {
    var source = wiki.querySelector('[data-atlas-event="' + id + '"]');
    if (!source || dialog.open) return;
    trigger = button || wiki.querySelector('[data-atlas-event-open="' + id + '"]');
    var dossier = source.cloneNode(true); dossier.removeAttribute('id'); dossier.removeAttribute('data-atlas-event'); dossier.hidden = false;
    var title = dossier.querySelector('.atlas-event-copy h3'); title.id = 'atlas-event-dialog-title';
    var intro = dossier.querySelector('.atlas-event-copy>p'); intro.id = 'atlas-event-dialog-intro';
    content.replaceChildren(dossier);
    dialog.setAttribute('aria-labelledby', title.id); dialog.setAttribute('aria-describedby', intro.id);
    document.documentElement.classList.add('atlas-event-open');
    dialog.showModal(); dialog.scrollTop = 0; closeButton.focus({preventScroll: true});
  }
  function restore() {
    document.documentElement.classList.remove('atlas-event-open');
    content.replaceChildren();
    if (trigger && document.contains(trigger)) trigger.focus({preventScroll: true});
    trigger = null;
  }
  function closeEvent() { if (dialog.open) dialog.close(); restore(); }
  closeButton.addEventListener('click', closeEvent);
  dialog.addEventListener('cancel', function (event) { event.preventDefault(); closeEvent(); });
  dialog.addEventListener('close', function () { if (!dialog.open) restore(); });
  function outside(event) {
    var bounds = dialog.getBoundingClientRect();
    return event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom;
  }
  dialog.addEventListener('pointerdown', function (event) { backdropPress = outside(event); });
  dialog.addEventListener('click', function (event) {
    if (backdropPress && outside(event)) closeEvent();
    backdropPress = false;
    if (event.target.closest('a[href]')) closeEvent();
  });
  dialog.addEventListener('keydown', function (event) {
    if (event.key !== 'Tab') return;
    var controls = Array.from(dialog.querySelectorAll('button,a[href]')).filter(function (item) { return item.getClientRects().length; });
    var first = controls[0], last = controls[controls.length-1];
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
  });
  wiki.querySelectorAll('[data-atlas-event-open]').forEach(function (button) {
    button.addEventListener('click', function (event) {
      event.preventDefault(); openEvent(button.getAttribute('data-atlas-event-open'), button);
    });
  });
  function revealEvent() {
    var match = /^#evento-([a-z-]+)$/.exec(location.hash);
    if (match) openEvent(match[1]);
    else if (dialog.open) closeEvent();
  }
  window.addEventListener('hashchange', revealEvent); revealEvent();
})();
