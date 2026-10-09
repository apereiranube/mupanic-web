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
    let total = 0, received = 0, valid = true;
    const rows = document.createDocumentFragment();
    packages.forEach(card => {
      const input = card.querySelector('input[type=number]');
      const quantity = Number(input.value);
      const correct = /^(0|[1-9][0-9]?)$/.test(input.value) && quantity <= 99;
      input.setAttribute('aria-invalid', String(!correct));
      valid = valid && correct;
      const active = correct && quantity > 0;
      card.classList.toggle('is-selected', active);
      const select = card.querySelector('[data-select-package]');
      if (select) select.textContent = active ? 'Agregado · sumar otro +' : 'Agregar este paquete +';
      card.querySelector('[data-line-total]').textContent = active ? coins(Number(card.dataset.coins) * quantity) + ' · ' + pesos(Number(card.dataset.price) * quantity) : 'Elegí cuántos querés';
      if (!active) return;
      total += Number(card.dataset.price) * quantity;
      received += Number(card.dataset.coins) * quantity;
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
    shop.querySelector('[data-cart-lines]').replaceChildren(rows);
    shop.querySelector('[data-total-coins]').textContent = coins(received);
    shop.querySelector('[data-total-price]').textContent = pesos(total) + ' ARS';
    const allowed = valid && total > 0 && total <= 100000000 && received <= 1000000;
    const mobile = shop.querySelector('[data-mobile-summary]');
    if (mobile) { mobile.hidden = !allowed; mobile.querySelector('[data-mobile-coins]').textContent = coins(received); mobile.querySelector('[data-mobile-price]').textContent = pesos(total) + ' ARS'; }
    submit.disabled = !allowed || submit.dataset.enabled !== '1' || form.dataset.sending === '1';
    shop.querySelector('[data-cart-hint]').textContent = !valid ? 'Usá cantidades enteras de 0 a 99.' : total > 100000000 || received > 1000000 ? 'El máximo por compra es $1.000.000.' : total === 0 ? 'Elegí al menos un paquete para continuar.' : 'Un solo pago. Todos tus Eryns.';
  }
  packages.forEach(card => {
    const input = card.querySelector('input[type=number]');
    input.addEventListener('input', updateCart);
    const select = card.querySelector('[data-select-package]');
    if (select) select.addEventListener('click', () => {
      input.value = Math.min(99, Math.max(0, Math.floor(Number(input.value) || 0)) + 1);
      updateCart();
    });
    card.querySelectorAll('[data-quantity-change]').forEach(button => button.addEventListener('click', () => {
      input.value = Math.min(99, Math.max(0, (Number(input.value) || 0) + Number(button.dataset.quantityChange)));
      updateCart();
    }));
  });
  form.addEventListener('submit', event => {
    if (form.dataset.sending === '1' || submit.disabled) { event.preventDefault(); return; }
    form.dataset.sending = '1'; submit.textContent = 'Preparando tu compra…';
    setTimeout(() => { submit.disabled = true; }, 0);
  });
  window.addEventListener('pageshow', event => { if (event.persisted) { form.dataset.sending = ''; submit.textContent = submit.dataset.enabled === '1' ? 'Preparar pago con Ualá' : 'Pagos próximamente'; updateCart(); } });
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
