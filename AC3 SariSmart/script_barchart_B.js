
fetch('script_B.php')
  .then(response => {
    if (!response.ok) throw new Error('Network response was not OK');
    return response.json();
  })
  .then(data => {
    if (Array.isArray(data)) {
      createIncomeExpenseChart(data);
    } else {
      console.error('Unexpected response format:', data);
    }
  })
  .catch(error => {
    console.error('Fetch error:', error);
  });

function createIncomeExpenseChart(chartData) {
  const ctx = document.getElementById('income_expensesChart').getContext('2d');

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: chartData.map(row => row.date),
      datasets: [
        {
          label: 'Total Income',
          data: chartData.map(row => parseFloat(row.total_income)),
          backgroundColor: 'rgba(75, 192, 192, 0.5)',
          borderColor: 'rgba(75, 192, 192, 1)',
          borderWidth: 1
        },
        {
          label: 'Total Expense',
          data: chartData.map(row => parseFloat(row.total_expense)),
          backgroundColor: 'rgba(255, 99, 132, 0.5)',
          borderColor: 'rgba(255, 99, 132, 1)',
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
          text: 'Total Income and Expenses Over Time'
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
