import Chart from 'chart.js/auto';

const renderBarChart = (canvasId) => {
    const canvas = document.getElementById(canvasId);
    if (!canvas) {
        return;
    }

    const labels = JSON.parse(canvas.dataset.labels || '[]');
    const values = JSON.parse(canvas.dataset.values || '[]');

    if (!labels.length || !values.length) {
        return;
    }

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    data: values,
                    backgroundColor: '#334155',
                    borderRadius: 4,
                },
            ],
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false,
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                    },
                },
            },
        },
    });
};

document.addEventListener('DOMContentLoaded', () => {
    renderBarChart('booksPerRackChart');
    renderBarChart('booksPerCategoryChart');
});

