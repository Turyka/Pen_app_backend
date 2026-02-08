const deviceChartData = window.deviceChartData || [];


const brandColorMap = {
  samsung: '#0694a2',
  iPhone: '#7e3af2',
  redmi: '#1c64f2',
  huawei: '#10b981',
  OnePlus: '#f59e0b',
  HONOR: '#f43f5e',
  google: '#9e17c0',
  Other: '#6366f1', // fallback color
};

const deviceLabels = deviceChartData.map(item => item.brand);
const deviceValues = deviceChartData.map(item => item.count);
const deviceColors = deviceLabels.map(brand => brandColorMap[brand] || brandColorMap['Other']);

const pieConfig = {
  type: 'doughnut',
  data: {
    labels: deviceLabels,
    datasets: [
      {
        data: deviceValues,
        backgroundColor: deviceColors,
        label: 'Device Brands',
      },
    ],
  },
  options: {
    responsive: true,
    cutoutPercentage: 80,
    legend: {
      display: false,
    },
  },
};

const pieCtx = document.getElementById('pie');
window.myPie = new Chart(pieCtx, pieConfig);