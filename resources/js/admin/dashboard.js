// resources/js/admin/dashboard.js
// KPI + Bar chart "Tren 7 Hari" per source

(async () => {
  const $ = (s) => document.querySelector(s);

  const setText = (sel, val) => { const el = $(sel); if (el) el.textContent = val; };
  const pct = (x) => `${Math.round((Number(x) || 0) * 100)}%`;

  async function getJSON(url) {
    const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
    if (!r.ok) throw new Error(`${r.status} ${r.statusText}`);
    return r.json();
  }

  try {
    // KPI harian
    const m = await getJSON('/api/admin/metrics/today');
    setText('#kpi-total', m.today_total ?? 0);
    setText('#kpi-auto',  pct(m.auto_answer_rate ?? 0));    // 0..1 → %
    setText('#kpi-avg',   pct(m.avg_similarity ?? 0));      // 0..1 → %

    // Tren 7 Hari (bar chart per source)
    if (window.Chart) {
      const d = await getJSON('/api/admin/metrics/trend-sources');
      const ctx = document.getElementById('trendChart');
      if (ctx) {
        new Chart(ctx.getContext('2d'), {
          type: 'bar',
          data: {
            labels: d.labels || ['product','service','office_hours','contact','faq','gemini'],
            datasets: [{
              label: '7 Hari Terakhir',
              data: d.data || [0,0,0,0,0,0],
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
    }
  } catch (e) {
    setText('#kpi-total', 'Err');
    setText('#kpi-auto',  'Err');
    setText('#kpi-avg',   'Err');
    // console.error(e);
  }
})();