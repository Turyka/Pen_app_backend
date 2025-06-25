
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  // Step 1: Access login data from Laravel
  const loginData = window.napilogin;

  // Step 2: Build a full list of the last 7 days
  function getLast7Days() {
    const days = [];
    const today = new Date();
    for (let i = 6; i >= 0; i--) {
      const d = new Date(today);
      d.setDate(today.getDate() - i);
      days.push(d.toISOString().slice(0, 10)); // format: YYYY-MM-DD
    }
    return days;
  }

  const last7Days = getLast7Days();

  // Step 3: Match counts with days, fill missing with 0
  const countsByDate = {};
  loginData.forEach(entry => {
    countsByDate[entry.date] = entry.count;
  });

  const labels = [];
  const dataPoints = [];

  last7Days.forEach(date => {
    labels.push(date);
    dataPoints.push(countsByDate[date] ?? 0);
  });

  // Step 4: Build the chart config
  const lineConfig = {
    type: 'line',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Napi bejelentkezések',
          backgroundColor: '#0694a2',
          borderColor: '#0694a2',
          data: dataPoints,
          fill: false,
        },
      ],
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          display: true,
        },
        tooltip: {
          mode: 'index',
          intersect: false,
        },
      },
      interaction: {
        mode: 'nearest',
        intersect: true,
      },
      scales: {
        x: {
          title: {
            display: true,
            text: 'Dátum',
          },
        },
        y: {
          title: {
            display: true,
            text: 'Bejelentkezések száma',
          },
          beginAtZero: true,
        },
      },
    },
  };

  // Step 5: Render the chart
  const lineCtx = document.getElementById('line');
  window.myLine = new Chart(lineCtx, lineConfig);