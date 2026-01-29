// public/assets/landing/js/admin/dashboard.js
(() => {
  const $ = (s) => document.querySelector(s);

  const setText = (sel, val) => {
    const el = $(sel);
    if (el) el.textContent = val;
  };

  const pct = (x) => `${Math.round((Number(x) || 0) * 100)}%`;

  async function getJSON(url) {
    const r = await fetch(url, { headers: { Accept: 'application/json' } });
    if (!r.ok) throw new Error(`${r.status} ${r.statusText}`);
    return r.json();
  }

  async function loadKpi() {
    const m = await getJSON('/api/admin/metrics/today');
    setText('#kpi-total', m.today_total ?? 0);
    setText('#kpi-auto', pct(m.auto_answer_rate ?? 0));
    setText('#kpi-avg', pct(m.avg_similarity ?? 0));
  }

  async function loadChart() {
    if (!window.Chart) return;

    const d = await getJSON('/api/admin/metrics/trend-sources');
    const canvas = document.getElementById('trendChart');
    if (!canvas) return;

    const labels = d.labels || ['product', 'service', 'office_hours', 'contact', 'faq', 'gemini'];
    const data = d.data || [0, 0, 0, 0, 0, 0];

    new Chart(canvas.getContext('2d'), {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: '7 Hari Terakhir',
          data
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
          x: { title: { display: true, text: 'Source' } },
          y: { beginAtZero: true, ticks: { precision: 0 }, title: { display: true, text: 'Jumlah' } }
        }
      }
    });
  }

  document.addEventListener('DOMContentLoaded', async () => {
    try {
      await loadKpi();
    } catch (e) {
      setText('#kpi-total', 'Err');
      setText('#kpi-auto', 'Err');
      setText('#kpi-avg', 'Err');
    }

    try {
      await loadChart();
    } catch (e) {
      // chart error, biarkan KPI tetap angka
      // kalau mau, kamu bisa tampilkan notif di sini
    }
  });
})();
