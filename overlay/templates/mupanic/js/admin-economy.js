(function () {
  'use strict';
  function init() {
    const root = document.getElementById('economia');
    if (!root) return;
    const form = root.querySelector('.eco-filters');
    if (form) {
      const range = form.querySelector('[name="rango"]');
      form.querySelectorAll('[data-eco-date]').forEach(input => input.addEventListener('change', () => { range.value = 'personalizado'; }));
    }
    const fmt = new Intl.NumberFormat('es-AR', { maximumFractionDigits: 2 });
    const colors = ['#ac7b40', '#ad5461', '#567e9c', '#6d927b', '#8f70a8', '#c19e55', '#6a9c9b', '#c58357'];
    function drawChart(node) {
      if (node.closest('details:not([open])')) return;
      if (node.dataset.ecoReady) { const chart = node.closest('.eco-panel').chart; if (chart) chart.resize(); return; }
      node.dataset.ecoReady = 'true';
      const panel = node.closest('.eco-panel');
      if (!window.Chart) { panel.querySelector('details').open = true; return; }
      try {
        const data = JSON.parse(node.textContent);
        const circular = ['pie', 'doughnut'].includes(data.type);
        if (circular && !data.rows.some(r => Number(r[Object.keys(data.series)[0]]) > 0)) {
          panel.querySelector('.eco-chart').innerHTML = '<p class="eco-empty">Sin datos en este período</p>';
          return;
        }
        const chart = new window.Chart(panel.querySelector('canvas'), {
          type: data.type,
          data: {
            labels: data.rows.map(r => r.Periodo),
            datasets: Object.entries(data.series).map(([key, label], i) => ({
              label,
              data: data.rows.map(r => r[key] == null ? null : Number(r[key])),
              backgroundColor: circular ? colors : colors[i % colors.length] + (data.type === 'line' ? '22' : 'df'),
              borderColor: circular ? '#fffdf9' : colors[i % colors.length],
              borderWidth: circular ? 2 : 1.8, tension: 0.12, pointRadius: data.rows.length > 60 ? 0 : 2,
              fill: false, borderRadius: data.type === 'bar' ? 3 : 0
            }))
          },
          options: {
            responsive: true, maintainAspectRatio: false, animation: false,
            interaction: { mode: circular ? 'nearest' : 'index', intersect: false },
            plugins: {
              legend: { position: 'bottom', labels: { color: '#626775', boxWidth: 12, padding: 14, font: { size: 10 } } },
              tooltip: { callbacks: { label: ctx => (ctx.dataset.label || ctx.label) + ': ' + fmt.format(circular ? ctx.raw : ctx.parsed.y) } }
            },
            scales: circular ? {} : {
              x: { stacked: !!data.options.stacked, grid: { display: false }, ticks: { maxTicksLimit: 10, maxRotation: 35, color: '#7c8088', font: { size: 10 } } },
              y: { stacked: !!data.options.stacked, beginAtZero: true, ticks: { color: '#7c8088', font: { size: 10 }, callback: v => fmt.format(v) }, grid: { color: '#e9e4db' } }
            }
          }
        });
        panel.chart = chart;
      } catch (err) {
        panel.querySelector('details').open = true;
        panel.querySelector('.eco-chart').innerHTML = '<p class="eco-empty">No se pudo dibujar el gráfico. Consultá los valores de abajo.</p>';
      }
    }
    function prepareTable(table) {
      if (table.closest('details:not([open])')) return;
      if (table.dataset.ecoReady) { if (window.jQuery && window.jQuery.fn.DataTable) window.jQuery(table).DataTable().columns.adjust(); return; }
      if (window.jQuery && window.jQuery.fn.DataTable) {
        table.dataset.ecoReady = 'true';
        window.jQuery(table).DataTable({
          order: [], pageLength: 20, lengthMenu: [10, 20, 50, 100], autoWidth: false,
          language: {
            search: 'Buscar:', lengthMenu: 'Mostrar _MENU_', info: '_START_–_END_ de _TOTAL_',
            infoEmpty: 'Todavía no hay movimientos para mostrar', infoFiltered: '(filtrado de _MAX_)',
            zeroRecords: 'No encontramos movimientos con esa búsqueda', emptyTable: 'Todavía no hay movimientos para mostrar',
            paginate: { first: 'Primera', last: 'Última', next: '→', previous: '←' }
          }
        });
      }
    }
    function refresh(scope) {
      scope.querySelectorAll('.eco-chart-data').forEach(drawChart);
      scope.querySelectorAll('[data-eco-table]').forEach(prepareTable);
    }
    refresh(root);
    root.addEventListener('toggle', event => { if (event.target.open) requestAnimationFrame(() => refresh(event.target)); }, true);
    function placeHelp(help) {
      const rect = help.querySelector('button').getBoundingClientRect();
      const tip = help.querySelector('.eco-tooltip');
      const width = Math.min(265, innerWidth - 32);
      tip.style.width = width + 'px';
      tip.style.left = Math.max(16, Math.min(innerWidth - width - 16, rect.right - width)) + 'px';
      const height = tip.offsetHeight;
      tip.style.top = (rect.top > height + 18 ? rect.top - height - 10 : Math.min(innerHeight - height - 12, rect.bottom + 10)) + 'px';
    }
    root.addEventListener('pointerover', event => { const help = event.target.closest('.eco-help'); if (help) placeHelp(help); });
    root.addEventListener('focusin', event => { const help = event.target.closest('.eco-help'); if (help) placeHelp(help); });
    function closeHelp(except) {
      root.querySelectorAll('.eco-help.is-open').forEach(help => {
        if (help !== except) { help.classList.remove('is-open'); help.querySelector('button').setAttribute('aria-expanded', 'false'); }
      });
    }
    document.addEventListener('click', event => {
      const button = event.target.closest('.eco-help-button');
      if (!button || !root.contains(button)) { closeHelp(); return; }
      event.preventDefault();
      const help = button.closest('.eco-help');
      const open = !help.classList.contains('is-open');
      closeHelp(help); help.classList.toggle('is-open', open); button.setAttribute('aria-expanded', String(open)); placeHelp(help);
    });
    window.addEventListener('scroll', () => root.querySelectorAll('.eco-help.is-open').forEach(placeHelp), { passive: true });
    root.addEventListener('keydown', event => {
      if (event.key === 'Escape') { closeHelp(); if (document.activeElement.matches('.eco-help-button')) document.activeElement.blur(); }
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
