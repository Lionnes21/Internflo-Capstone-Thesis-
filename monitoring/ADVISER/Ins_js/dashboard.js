// for student BAR GRAPH
    const ctx = document.getElementById('ojtCompletionChart').getContext('2d');
    const ojtCompletionChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['Completed', 'Not Completed'],
        datasets: [{
          label: 'Number of Students',
          data: [10, 20], // Replace with dynamic data if needed
          backgroundColor: ['#4CAF50', '#FF6384'],
        }]
      },
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });

// for PIE GRAPH in total Documents
const documentData = {
    labels: ['MOA', 'Acceptance Letter', 'Medical Certificate', 'Parent Consent'],
    datasets: [{
      label: 'Documents Passed',
      data: [1, 1, 1, 1],  // Update this with actual passed data
      backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'],
      hoverOffset: 4
    }]
  };

  const config = {
    type: 'pie',
    data: documentData,
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'top',
        },
        tooltip: {
          callbacks: {
            label: function(tooltipItem) {
              return tooltipItem.label + ": " + tooltipItem.raw + "/25";
            }
          }
        }
      }
    }
  };

  const ojtDocumentsPieChart = new Chart(
    document.getElementById('ojtDocumentsPieChart'),
    config
  );
