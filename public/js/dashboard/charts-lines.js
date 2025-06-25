const loginData = window.napilogin;

const labels = loginData.map(item => item.date);
const dataPoints = loginData.map(item => item.count);

const lineConfig = {
  type: 'line',
  data: {
    labels: labels,
    datasets: [
      {
        label: 'Eszköz bejelentkezések',
        backgroundColor: '#0694a2',
        borderColor: '#0694a2',
        data: dataPoints,
        fill: false,
      },
    ],
  },
  options: {
    responsive: true,
    scales: {
      x: {
        title: {
          display: true,
          text: 'Nap',
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

const lineCtx = document.getElementById('line');
window.myLine = new Chart(lineCtx, lineConfig);
