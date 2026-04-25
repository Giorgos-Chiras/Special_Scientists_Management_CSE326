
function createDoughnutChart(canvasId, labels, values) {
    const canvas = document.getElementById(canvasId);

    if (!canvas) return;

    new Chart(canvas, {
        type: 'doughnut',
        data: {
        labels: labels,
            datasets: [{
            data: values,
                borderWidth: 2
            }]
        },
        options: {
        responsive: true,
            plugins: {
            legend: {
                position: 'bottom'
                }
        }
        }
    });
}

function createBarChart(canvasId, labels, values) {
    const canvas = document.getElementById(canvasId);

    if (!canvas) return;

    new Chart(canvas, {
        type: 'bar',
        data: {
        labels: labels,
            datasets: [{
            label: 'Applications',
                data: values,
                borderWidth: 1,
                borderRadius: 8
            }]
        },
        options: {
        responsive: true,
            plugins: {
            legend: {
                display: false
                }
        },
            scales: {
            y: {
                beginAtZero: true,
                    ticks: {
                    precision: 0
                    }
                }
        }
        }
    });
}