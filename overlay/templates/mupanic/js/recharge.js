(function () {
  'use strict';
  const shop = document.querySelector('[data-recharge-shop]');
  if (!shop) return;
  const form = shop.querySelector('[data-cart-form]');
  const packages = Array.from(shop.querySelectorAll('[data-package]'));
  const format = new Intl.NumberFormat('es-AR', { maximumFractionDigits: 0 });
  const coins = value => format.format(value) + ' Eryns';
  const pesos = cents => '$ ' + format.format(cents / 100);
  const submit = form.querySelector('[data-cart-submit]');
  function updateCart() {
    let total = 0, received = 0, bonus = 0, base = 0, valid = true;
    const rows = document.createDocumentFragment();
    packages.forEach(card => {
      const input = card.querySelector('input[type=number]');
      const quantity = Number(input.value);
      const correct = /^(0|[1-9][0-9]?)$/.test(input.value) && quantity <= 99;
      input.setAttribute('aria-invalid', String(!correct));
      valid = valid && correct;
      const active = correct && quantity > 0;
      card.classList.toggle('is-selected', active);
      card.querySelector('[data-line-total]').textContent = active ? coins(Number(card.dataset.coins) * quantity) + ' · ' + pesos(Number(card.dataset.price) * quantity) : 'Elegí cuántos querés';
      if (!active) return;
      total += Number(card.dataset.price) * quantity;
      received += Number(card.dataset.coins) * quantity;
      bonus += Number(card.dataset.bonus || 0) * quantity;
      base += Number(card.dataset.baseCoins || card.dataset.coins) * quantity;
      const row = document.createElement('div');
      const label = document.createElement('span');
      const value = document.createElement('b');
      label.textContent = quantity + ' × ' + card.dataset.title;
      value.textContent = pesos(Number(card.dataset.price) * quantity);
      row.append(label, value); rows.append(row);
    });
    if (!rows.childNodes.length) {
      const empty = document.createElement('p'); empty.textContent = 'Sumá paquetes con + o escribí una cantidad.'; rows.append(empty);
    }
    shop.querySelectorAll('[data-choose-package]').forEach(button => {
      const selected = packages.find(card => card.querySelector('input').id === 'quantity-' + button.dataset.choosePackage);
      const single = selected && selected.querySelector('input').value === '1' && packages.every(card => card === selected || card.querySelector('input').value === '0');
      button.setAttribute('aria-pressed', String(Boolean(single)));
      const choice=button.querySelector('.eryns-card-choice'); if(choice) choice.textContent=single?'Elegido ✓':'Elegir pack';
    });
    shop.querySelector('[data-cart-lines]').replaceChildren(rows);
    const counter = shop.querySelector('[data-total-coins]');
    const unit = document.createElement('small'); unit.textContent = 'Eryns';
    const changed = counter.textContent !== coins(received);
    counter.replaceChildren(document.createTextNode(format.format(received) + ' '), unit);
    if (changed && counter.animate && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) counter.animate([{transform:'translateY(4px)',opacity:.5},{transform:'translateY(0)',opacity:1}],{duration:250});
    const bonusRow = shop.querySelector('[data-bonus-row]');
    if (bonusRow) { bonusRow.hidden = bonus === 0; bonusRow.querySelector('[data-bonus-rate]').textContent = '+' + new Intl.NumberFormat('es-AR',{maximumFractionDigits:1}).format(base ? bonus / base * 100 : 0) + '%'; }
    const breakdown = shop.querySelector('[data-bonus-breakdown]');
    if (breakdown) { breakdown.hidden = bonus === 0; breakdown.textContent = format.format(base) + ' + ' + format.format(bonus) + ' de regalo'; }
    const includes = shop.querySelector('[data-price-includes]');
    if (includes) includes.textContent = bonus ? 'Incluye ' + format.format(bonus) + ' Eryns de regalo.' : 'Pago único. Sin suscripción.';
    const upgrade = shop.querySelector('[data-upgrade-package]');
    if (upgrade) upgrade.hidden = !(valid && total > 0 && received < Number(upgrade.dataset.upgradeCoins) && total < Number(upgrade.dataset.upgradePrice));
    shop.querySelector('[data-total-price]').textContent = pesos(total) + ' ARS';
    const allowed = valid && total > 0 && total <= 100000000 && received <= 1000000;
    const mobile = shop.querySelector('[data-mobile-summary]');
    if (mobile) { mobile.hidden = !allowed; mobile.querySelector('[data-mobile-coins]').textContent = coins(received); mobile.querySelector('[data-mobile-price]').textContent = pesos(total) + ' ARS'; }
    submit.disabled = !allowed || submit.dataset.enabled !== '1' || form.dataset.sending === '1';
    const mobileSubmit = shop.querySelector('[data-mobile-submit]'); if (mobileSubmit) mobileSubmit.disabled = submit.disabled;
    shop.querySelector('[data-cart-hint]').textContent = !valid ? 'Usá cantidades enteras de 0 a 99.' : total > 100000000 || received > 1000000 ? 'El máximo por compra es $1.000.000.' : total === 0 ? 'Elegí al menos un paquete para continuar.' : 'Un solo pago. Todos tus Eryns.';
  }
  packages.forEach(card => {
    const input = card.querySelector('input[type=number]');
    input.addEventListener('input', updateCart);
    card.querySelectorAll('[data-quantity-change]').forEach(button => button.addEventListener('click', () => {
      input.value = Math.min(99, Math.max(0, (Number(input.value) || 0) + Number(button.dataset.quantityChange)));
      updateCart();
    }));
  });
  shop.querySelectorAll('[data-choose-package]').forEach(button => button.addEventListener('click', () => {
    packages.forEach(card => { card.querySelector('input').value = card.querySelector('input').id === 'quantity-' + button.dataset.choosePackage ? '1' : '0'; });
    updateCart();
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      button.classList.remove('is-flashing');
      requestAnimationFrame(() => requestAnimationFrame(() => button.classList.add('is-flashing')));
    }
  }));
  shop.querySelectorAll('[data-choose-package]').forEach(button => button.addEventListener('animationend', event => { if (event.animationName === 'eryns-selection-flash') button.classList.remove('is-flashing'); }));
  const vipDetails = shop.querySelector('#eryns-vip-plan-details');
  const vipCta = shop.querySelector('[data-vip-cta]');
  if (vipCta && vipDetails) {
    vipCta.addEventListener('click', () => { vipDetails.open = true; vipDetails.scrollIntoView({behavior:'auto',block:'nearest'}); });
    vipDetails.addEventListener('toggle', () => vipCta.setAttribute('aria-expanded', String(vipDetails.open)));
  }
  const vipDurations = Array.from(shop.querySelectorAll('[data-vip-duration]'));
  vipDurations.forEach(button => button.addEventListener('click', () => vipDurations.forEach(card => card.setAttribute('aria-pressed', String(card === button)))));
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');
  const surfaces = Array.from(shop.querySelectorAll('.eryns-motion-surface'));
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver(entries => entries.forEach(entry => entry.target.classList.toggle('is-visible', entry.isIntersecting)), {rootMargin:'60px'});
    surfaces.forEach(surface => observer.observe(surface));
  } else surfaces.forEach(surface => surface.classList.add('is-visible'));
  let pointerFrame = 0;
  const tilted = Array.from(shop.querySelectorAll('[data-tilt]'));
  const stage = shop.querySelector('.eryns-stage');
  function resetMotion() {
    cancelAnimationFrame(pointerFrame); pointerFrame = 0;
    tilted.forEach(card => { card.style.removeProperty('--tilt-x'); card.style.removeProperty('--tilt-y'); });
    if (stage) { stage.style.removeProperty('--hero-x'); stage.style.removeProperty('--hero-y'); }
  }
  function moveSurface(surface, event, hero) {
    if (reducedMotion.matches || !finePointer.matches) return;
    const x = event.clientX, y = event.clientY;
    cancelAnimationFrame(pointerFrame);
    pointerFrame = requestAnimationFrame(() => {
      const rect = surface.getBoundingClientRect();
      const dx = Math.max(-.5, Math.min(.5, (x - rect.left) / rect.width - .5));
      const dy = Math.max(-.5, Math.min(.5, (y - rect.top) / rect.height - .5));
      surface.style.setProperty(hero ? '--hero-x' : '--tilt-y', (dx * (hero ? 16 : 8)) + (hero ? 'px' : 'deg'));
      surface.style.setProperty(hero ? '--hero-y' : '--tilt-x', (-dy * (hero ? 12 : 6)) + (hero ? 'px' : 'deg'));
      pointerFrame = 0;
    });
  }
  tilted.forEach(card => { card.addEventListener('pointermove', event => moveSurface(card, event, false)); card.addEventListener('pointerleave', resetMotion); });
  if (stage) { stage.addEventListener('pointermove', event => moveSurface(stage, event, true)); stage.addEventListener('pointerleave', resetMotion); }
  reducedMotion.addEventListener('change', resetMotion); finePointer.addEventListener('change', resetMotion);
  const upgrade = shop.querySelector('[data-upgrade-package]');
  if (upgrade) upgrade.addEventListener('click', () => { const button = shop.querySelector('[data-choose-package="' + upgrade.dataset.upgradePackage + '"]'); if (button) button.click(); });
  const mountMessage = shop.querySelector('[data-target-message]');
  shop.querySelectorAll('[data-target-price]').forEach(button => button.addEventListener('click', () => {
    if (button.disabled) return;
    const price = Number(button.dataset.targetPrice);
    if (!Number.isSafeInteger(price) || price <= 0) return;
    const candidates = packages.map(card => ({card,quantity:1,cost:Number(card.dataset.price),units:Number(card.dataset.coins)})).filter(item => item.units >= price);
    if (!candidates.length) packages.forEach(card => { const quantity = Math.ceil(price / Number(card.dataset.coins)); if (quantity <= 99 && quantity * Number(card.dataset.coins) <= 1000000 && quantity * Number(card.dataset.price) <= 100000000) candidates.push({card,quantity,cost:quantity*Number(card.dataset.price),units:quantity*Number(card.dataset.coins)}); });
    candidates.sort((a,b) => a.cost - b.cost || a.units - b.units);
    if (!candidates.length) { mountMessage.textContent = 'Revisá las cantidades permitidas antes de recargar para este objetivo.'; return; }
    const selected = candidates[0]; packages.forEach(card => { card.querySelector('input').value = card === selected.card ? String(selected.quantity) : '0'; });
    updateCart(); mountMessage.textContent = 'Objetivo: ' + button.dataset.targetName + '. Elegimos una recarga que alcanza su precio de ' + coins(price) + '.';
    shop.querySelector('#recharge-packages').scrollIntoView({behavior:'auto',block:'start'});
  }));
  function revealUtility() { const panel = document.querySelector(window.location.hash === '#recharge-history' ? '#recharge-history' : window.location.hash === '#recharge-guide' ? '#recharge-guide' : 'body'); if (panel.tagName === 'DETAILS') panel.open = true; }
  shop.querySelectorAll('a[href^="#"]').forEach(link => link.addEventListener('click', event => {
    const hash = link.getAttribute('href'); const panel = document.getElementById(hash.slice(1));
    if (!panel || !shop.contains(panel)) return;
    event.preventDefault(); if (panel.tagName === 'DETAILS') panel.open = true;
    history.replaceState(null, '', window.location.pathname + window.location.search + hash);
    panel.scrollIntoView({behavior:'auto',block:'start'});
  }));
  window.addEventListener('hashchange', revealUtility); revealUtility();
  requestAnimationFrame(() => { const cards = shop.querySelector('.eryns-denominations'); const selected = shop.querySelector('[data-choose-package][aria-pressed="true"]'); if (cards && selected && window.matchMedia('(max-width:700px)').matches) cards.scrollLeft = selected.offsetLeft - cards.offsetLeft - (cards.clientWidth - selected.clientWidth) / 2; });
  const tabs = Array.from(shop.querySelectorAll('[data-experience-tab]'));
  function activateTab(tab) {
    tabs.forEach(button => { const active = button === tab; button.setAttribute('aria-selected', String(active)); button.tabIndex = active ? 0 : -1; });
    shop.querySelectorAll('[data-experience]').forEach(panel => { panel.hidden = panel.dataset.experience !== tab.dataset.experienceTab; });
  }
  tabs.forEach((tab, index) => {
    tab.addEventListener('click', () => activateTab(tab));
    tab.addEventListener('keydown', event => {
      let next;
      if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
      if (event.key === 'ArrowLeft') next = (index + tabs.length - 1) % tabs.length;
      if (event.key === 'Home') next = 0;
      if (event.key === 'End') next = tabs.length - 1;
      if (next === undefined) return;
      event.preventDefault(); activateTab(tabs[next]); tabs[next].focus();
    });
  });
  shop.querySelectorAll('[data-open-creature]').forEach(button => {
    const dialog = document.getElementById(button.dataset.openCreature);
    if (!dialog || typeof dialog.showModal !== 'function') return;
    let originalOverflow;
    button.addEventListener('click', () => { originalOverflow = document.body.style.overflow; dialog.showModal(); document.body.style.overflow = 'hidden'; dialog.querySelector('[data-close-creature]').focus(); });
    dialog.querySelector('[data-close-creature]').addEventListener('click', () => dialog.close());
    dialog.querySelector('[data-return-to-buy]').addEventListener('click', () => { dialog.close(); shop.querySelector('[data-choose-package]').focus({preventScroll:true}); shop.querySelector('#recharge-packages').scrollIntoView({behavior:'auto',block:'start'}); });
    dialog.addEventListener('close', () => { document.body.style.overflow = originalOverflow; button.focus({preventScroll:true}); });
  });
  form.addEventListener('submit', event => {
    if (form.dataset.sending === '1' || submit.disabled) { event.preventDefault(); return; }
    form.dataset.sending = '1'; submit.textContent = 'Preparando tu compra…';
    setTimeout(() => { submit.disabled = true; const mobileSubmit=shop.querySelector('[data-mobile-submit]'); if(mobileSubmit) mobileSubmit.disabled=true; }, 0);
  });
  window.addEventListener('pageshow', event => { if (event.persisted) { form.dataset.sending = ''; submit.textContent = submit.dataset.enabled === '1' ? 'Pagar con Ualá →' : 'Pagos próximamente'; updateCart(); } });
  updateCart();
  const orders = Array.from(shop.querySelectorAll('[data-order-id]'));
  const message = shop.querySelector('[data-history-update]');
  let checks = 0;
  function needsUpdate() { return orders.some(order => ['pending', 'approved', 'creating'].includes(order.dataset.orderState)); }
  async function checkHistory() {
    if (!needsUpdate() || checks >= 30) {
      message.textContent = needsUpdate() ? 'Podés actualizar el estado de tu compra cuando quieras.' : 'Tus compras están actualizadas.';
      return;
    }
    if (!document.hidden) {
      checks++;
      try {
        const url = new URL(window.location.href); url.searchParams.set('shop_status', '1'); url.hash = '';
        const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('Status unavailable');
        const result = await response.json();
        orders.forEach(order => {
          const state = result.orders && result.orders[order.dataset.orderId];
          if (!Array.isArray(state) || state.length !== 3 || !['pending', 'approved', 'credited', 'review', 'creating', 'rejected'].includes(state[0])) return;
          order.dataset.orderState = state[0];
          order.querySelector('[data-order-label]').textContent = state[1];
          order.querySelector('[data-order-copy]').textContent = state[2];
          const pay = order.querySelector('[data-order-pay]'); if (pay) pay.hidden = state[0] !== 'pending';
        });
        message.textContent = 'Estados actualizados. El saldo de arriba se consulta al recargar la página.';
      } catch (_) { message.textContent = 'No pudimos actualizar ahora. Vamos a intentar nuevamente.'; }
    }
    setTimeout(checkHistory, 20000);
  }
  if (needsUpdate()) setTimeout(checkHistory, 20000);
})();
