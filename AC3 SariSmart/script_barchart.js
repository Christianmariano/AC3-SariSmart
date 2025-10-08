
fetch('script.php')
  .then(response => {
    if (!response.ok) throw new Error('Network response was not OK');
    return response.json();
  })
  .then(data => {
    if (Array.isArray(data)) {
      createDailyIncomeChart(data);
    } else {
      console.error('Unexpected response format:', data);
    }
  })
  .catch(error => {
    console.error('Fetch error:', error);
  });

function createDailyIncomeChart(chartData) {
  const ctx = document.getElementById('myChart').getContext('2d');

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: chartData.map(row => row.date),
      datasets: [
        {
          label: 'Income (Store)',
          data: chartData.map(row => parseFloat(row.income_store)),
          backgroundColor: 'rgba(54, 162, 235, 0.5)',
          borderColor: 'rgba(54, 162, 235, 1)',
          borderWidth: 1
        },
        {
          label: 'Income (School Service)',
          data: chartData.map(row => parseFloat(row.income_school_service)),
          backgroundColor: 'rgba(255, 206, 86, 0.5)',
          borderColor: 'rgba(255, 206, 86, 1)',
          borderWidth: 1
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top'
        },
        title: {
          display: true,
          text: 'Daily Income Breakdown'
        }
      },
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });
}
