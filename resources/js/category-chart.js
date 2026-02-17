import {
    Chart,
    BarElement,
    LineElement,
    CategoryScale,
    LinearScale,
    PointElement,
    Tooltip,
    Title
} from 'chart.js';

import ChartDataLabels from 'chartjs-plugin-datalabels';

Chart.register(
    BarElement,
    LineElement,
    CategoryScale,
    LinearScale,
    PointElement,
    Tooltip,
    Title,
    ChartDataLabels
);

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll('canvas[data-categories][data-scores]').forEach(canvas => {
        const ctx = canvas.getContext("2d");
        const labels = JSON.parse(canvas.dataset.categories);
        const scores = JSON.parse(canvas.dataset.scores);
        const type = canvas.dataset.chartType || 'bar';
        const label = canvas.dataset.chartLabel || '';
        const isLine = type === 'line';

        const backgroundColors = scores.map(value => {
            if (value <= 50) return 'rgba(220, 38, 38, 0.6)'; // rojo
            if (value <= 75) return 'rgba(250, 204, 21, 0.6)'; // amarillo
            return 'rgba(22, 163, 74, 0.6)'; // verde
        });

        new Chart(ctx, {
            type,
            data: {
                labels,
                datasets: [{
                    label,
                    data: scores,
                    backgroundColor: backgroundColors,
                    fill: isLine
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Porcentaje (%)'
                        }
                    }
                },
                plugins: {
                    title: {
                        display: !!label,
                        text: label
                    },
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => {
                                const v = ctx.raw;
                                let nivel = 'Crítico';
                                if (v > 75) nivel = 'Óptimo';
                                else if (v > 50) nivel = 'Aceptable';
                                return `Nivel: ${nivel} (${v}%)`;
                            }
                        }
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'start',
                        font: {
                            weight: 'bold'
                        },
                        formatter: value => `${value}%`,
                        color: '#111'
                    }
                }
            }
        });
    });
});
