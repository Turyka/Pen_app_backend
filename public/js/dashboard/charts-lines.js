
  // Laravel data: each item has { device_id, datetime, ... }
  const loginData = window.napilogin;

  // Step 1: Get last 7 days (formatted as YYYY-MM-DD)
  function getLast7Days() {
    const days = [];
    const today = new Date();
    for (let i = 6; i >= 0; i--) {
      const d = new Date(today);
      d.setDate(today.getDate() - i);
      days.push(d.toISOString().slice(0, 10));
    }
    return days;
  }

  const last7Days = getLast7Days();

  // Step 2: Count logins by date (extracted from datetime)
  const countsByDate = {};
  loginData.forEach(entry => {
    const date = entry.datetime.slice(0, 10); // 'YYYY-MM-DD'
    countsByDate[date] = (countsByDate[date] || 0) + 1;
  });

  // Step 3: Prepare labels and datapoints
  const labels = [];
  const dataPoints = [];

  last7Days.forEach(date => {
    labels.push(date);
    dataPoints.push(countsByDate[date] ?? 0);
  });

  // Step 4: Chart config
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

  // Step 5: Render chart
  const lineCtx = document.getElementById('line');
  window.myLine = new Chart(lineCtx, lineConfig);

