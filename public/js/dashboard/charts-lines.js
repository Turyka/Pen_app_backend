<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  const loginData = window.napilogin;

  /* --- dátumlista a böngésző saját időzónájában --- */
  function last7DaysISO() {
    const days = [];
    const today = new Date();          // böngésző szerinti ma
    for (let i = 6; i >= 0; i--) {
      const d = new Date(today);
      d.setDate(today.getDate() - i);
      days.push(d.toISOString().slice(0, 10));   // YYYY-MM-DD
    }
    return days;
  }

  const last7 = last7DaysISO();

  /* --- bejelentkezések számlálása --- */
  const counts = {};
  loginData.forEach(e => { counts[e.date] = e.count; });

  const labels = [];
  const data = [];
  last7.forEach(d => {
    labels.push(
      new Date(d).toLocaleDateString('hu-HU', { month: 'short', day: 'numeric' })
    );
    data.push(counts[d] ?? 0);
  });

  /* --- Chart.js --- */
  const cfg = {
    type: 'line',
    data: { labels, datasets: [{
      label: 'Napi bejelentkezések',
      data,
      borderColor: '#0694a2',
      backgroundColor: '#0694a220',
      fill: false,
      tension: 0.3,
    }]},
    options: {
      responsive: true,
      plugins: { legend: { display: true } },
      scales: {
        y: { beginAtZero: true, title: { display: true, text: 'Darab' } },
        x: { title: { display: true, text: 'Nap' } },
      },
    },
  };

  const ctx = document.getElementById('line');
  new Chart(ctx, cfg);

  /* --- Automatikus frissítés minden nap hajnalban (helyi idő szerint) --- */
  function msUntilNextDay() {
    const now = new Date();
    const tomorrow = new Date(now);
    tomorrow.setHours(24, 0, 0, 0);   // holnap 00:00
    return tomorrow - now;
  }
  setTimeout(() => location.reload(), msUntilNextDay());
