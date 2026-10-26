/* MU PANIC · Socios: calculadora de ganancias. */
(function () {
  var box = document.querySelector('[data-soc-calc]');
  if (!box) return;
  var page = document.querySelector('.soc-page');
  var vip = parseInt(page && page.getAttribute('data-vip'), 10) || 25000;
  var inA = box.querySelector('[data-in="amigos"]');
  var inR = box.querySelector('[data-in="recarga"]');
  var out = function (k) { return box.querySelector('[data-out="' + k + '"]'); };
  var result = box.querySelector('.soc-calc-result');
  var tipo = 'socio';
  var MESES = 3;
  var fmt = function (n) { return Math.round(n).toLocaleString('es-AR'); };

  function fill(input) {
    var p = (input.value - input.min) / (input.max - input.min) * 100;
    input.style.setProperty('--p', p + '%');
  }

  function render() {
    var amigos = +inA.value, recarga = +inR.value;
    out('amigos').textContent = amigos;
    out('recarga').textContent = '$' + fmt(recarga);
    fill(inA); fill(inR);
    var base = amigos * recarga * MESES;
    if (tipo === 'socio') {
      var eryns = base * 0.10;
      var meses = eryns / vip;
      out('titulo').textContent = 'Ganás en 3 meses';
      out('total').textContent = fmt(eryns) + ' Eryns';
      out('equiv').textContent = meses >= 1
        ? 'Te alcanza para ' + (meses >= 10 ? fmt(meses) : meses.toLocaleString('es-AR', { maximumFractionDigits: 1 })) + ' meses de VIP, o para jugar, comprar en la tienda y comerciar en Vessara.'
        : 'Para usar en la tienda, en los juegos o para comerciar en Vessara. Con ' + fmt(Math.ceil(vip / (recarga * MESES * 0.10))) + ' amigos así ya te pagás el VIP.';
      result.classList.remove('is-pesos');
    } else {
      var pesos = base * 0.15;
      out('titulo').textContent = 'Cobrás en 3 meses';
      out('total').textContent = '$' + fmt(pesos);
      out('equiv').textContent = 'Por transferencia, una vez por mes: unos $' + fmt(pesos / MESES) + ' por mes con ' + amigos + (amigos === 1 ? ' jugador' : ' jugadores') + ' de tu comunidad.';
      result.classList.add('is-pesos');
    }
  }

  box.querySelectorAll('[data-tipo]').forEach(function (b) {
    b.addEventListener('click', function () {
      tipo = b.getAttribute('data-tipo');
      box.querySelectorAll('[data-tipo]').forEach(function (o) {
        var on = o === b; o.classList.toggle('is-on', on); o.setAttribute('aria-checked', on ? 'true' : 'false');
      });
      render();
    });
  });
  inA.addEventListener('input', render);
  inR.addEventListener('input', render);
  render();
})();
