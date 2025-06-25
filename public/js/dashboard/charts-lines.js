 // Step 1: Receive Laravel data from Blade
 const loginData = window.napilogin; // Format: [{ date: '2025-06-25', count: 1 }, ...]

 // Step 2: Generate last 7 days in local timezone as YYYY-MM-DD
 function getLast7Days() {
   const days = [];
   const today = new Date();
   for (let i = 6; i >= 0; i--) {
     const d = new Date(today);
     d.setDate(today.getDate() - i);
     days.push(d.toISOString().slice(0, 10)); // Format: YYYY-MM-DD
   }
   return days;
 }

 const last7Days = getLast7Days();

 // Step 3: Map loginData by date
 const countsByDate = {};
 loginData.forEach(entry => {
   countsByDate[entry.date] = entry.count;
 });

 // Step 4: Prepare chart labels (localized) and data points
 const labels = [];
 const dataPoints = [];

 last7Days.forEach(date => {
   // Convert date to localized format like "jún. 26."
   const formatted = new Date(date).toLocaleDateString('hu-HU', {
     month: 'short',
     day: 'numeric',
   });
   labels.push(formatted);
   dataPoints.push(countsByDate[date] ?? 0);
 });

 // Step 5: Configure Chart.js line chart
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
         tension: 0.3,
       },
     ],
   },
   options: {
     responsive: true,
     plugins: {
       legend: { display: true },
       tooltip: { mode: 'index', intersect: false },
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
         ticks: {
           precision: 0,
           stepSize: 1,
         },
       },
     },
   },
 };

 // Step 6: Render the chart
 const lineCtx = document.getElementById('line');
 if (lineCtx) {
   new Chart(lineCtx, lineConfig);
 }

 // Step 7: Auto-refresh at local midnight
 function msUntilNextMidnight() {
   const now = new Date();
   const midnight = new Date(now);
   midnight.setHours(24, 0, 0, 0); // next day at 00:00
   return midnight.getTime() - now.getTime();
 }

 setTimeout(() => location.reload(), msUntilNextMidnight());