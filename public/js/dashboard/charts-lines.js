const loginData = window.napilogin;

const labels = loginData.map(item => {
  const date = new Date(item.date);
  return date.toLocaleDateString('hu-HU', { month: 'short', day: 'numeric' }); 
});

const counts = loginData.map(item => item.count);

const lineConfig = {
  type: 'line',
  data: {
    labels: labels,
    datasets: [{
      label: 'Napi eszközhasználat (7 nap)',
      backgroundColor: '#0694a2',
      borderColor: '#0694a2',
      data: counts,
      fill: false,
      tension: 0.3,
    }],
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
          text: 'Eszközök száma',
        },
        beginAtZero: true,
      },
    },
  },
};

const lineCtx = document.getElementById('line');
window.myLine = new Chart(lineCtx, lineConfig);