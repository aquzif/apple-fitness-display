import './bootstrap';

document.addEventListener('livewire:init', async () => {
    if (window.__weightChartInitialized) {
        return;
    }



    window.__weightChartInitialized = true;

    let weightChartInstance = null;

    const destroyChart = () => {
        if (weightChartInstance) {
            weightChartInstance.destroy();
            weightChartInstance = null;
        }
    };

    const renderWeightChart = (payload) => {
        const chart = payload?.chart ?? payload;
        const canvas = document.getElementById('weightChart');

        console.log('Rendering weight chart with data:', chart);

        if (!canvas || !chart || !Array.isArray(chart.labels) || chart.labels.length === 0) {
            destroyChart();

            return;
        }

        const datasets = Array.isArray(chart.datasets) ? chart.datasets : [];

        if (datasets.length === 0) {
            destroyChart();

            return;
        }

        const context = canvas.getContext('2d');

        if (!context) {
            destroyChart();

            return;
        }

        destroyChart();

        const gradient = context.createLinearGradient(0, 0, 0, canvas.clientHeight || canvas.height || 0);
        gradient.addColorStop(0, 'rgba(132, 204, 22, 0.45)');
        gradient.addColorStop(1, 'rgba(132, 204, 22, 0.05)');

        const chartDatasets = datasets.map((dataset) => ({
            ...dataset,
            data: Array.isArray(dataset.data)
                ? dataset.data.map((value) => (typeof value === 'number' ? value : Number(value)))
                : [],
            backgroundColor: gradient,
            borderColor: dataset.borderColor ?? 'rgb(190, 242, 100)',
            pointBackgroundColor: dataset.pointBackgroundColor ?? 'rgb(190, 242, 100)',
            pointBorderColor: dataset.pointBorderColor ?? 'rgb(15, 23, 42)',
            pointHoverBackgroundColor: dataset.pointHoverBackgroundColor ?? '#ffffff',
            pointHoverBorderColor: dataset.pointHoverBorderColor ?? 'rgba(15, 23, 42, 0.6)',
            fill: true,
        }));

        weightChartInstance = new Chart(context, {
            type: 'line',
            data: {
                labels: chart.labels,
                datasets: chartDatasets,
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                layout: {
                    padding: 16,
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                        },
                        ticks: {
                            color: '#94a3b8',
                            font: {
                                family: 'Figtree, sans-serif',
                                size: 11,
                                weight: '500',
                            },
                        },
                    },
                    y: {
                        grid: {
                            color: 'rgba(148, 163, 184, 0.15)',
                            drawBorder: false,
                        },
                        ticks: {
                            color: '#94a3b8',
                            font: {
                                family: 'Figtree, sans-serif',
                                size: 11,
                                weight: '500',
                            },
                            callback(value) {
                                return `${value} kg`;
                            },
                        },
                    },
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        borderColor: 'rgba(148, 163, 184, 0.25)',
                        borderWidth: 1,
                        padding: 12,
                        titleColor: '#e2e8f0',
                        bodyColor: '#f8fafc',
                        displayColors: false,
                        callbacks: {
                            label(context) {
                                const value = context.parsed.y ?? 0;

                                return `${value.toLocaleString('pl-PL', {
                                    minimumFractionDigits: 1,
                                    maximumFractionDigits: 1,
                                })} kg`;
                            },
                        },
                    },
                },
                elements: {
                    line: {
                        tension: 0.35,
                        borderWidth: 2,
                        borderCapStyle: 'round',
                    },
                    point: {
                        radius: 4,
                        hoverRadius: 6,
                        hitRadius: 12,
                    },
                },
            },
        });
    };

    Livewire.on('weight-chart-update', (chart) => {

        renderWeightChart(chart);
    });


});
