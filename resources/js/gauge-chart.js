import { Chart, ArcElement, Tooltip } from 'chart.js';

Chart.register(ArcElement, Tooltip);

document.addEventListener("DOMContentLoaded", () => {
    const canvas = document.querySelector("[data-speedometr-chart]");
    if (!canvas) return;

    const percentage = parseFloat(canvas.dataset.percentage || 0);

    const config = {
        data: {
            currentPostion: percentage,
            speedometrBarsColors: ["#dc2626", "#facc15", "#16a34a"],
            speedometrBarsWidth: [50, 25, 25],
            barLabels: ["Crítico", "Aceptable", "Óptimo"],
            mainLabel: "Nivel de dominio",
        },
        chart: {
            type: "doughnut",
            aspectRatio: 1.4,
            hoverOffset: 4,
            circumference: 180,
            rotation: 270,
            circleRadius: 8,
            needleWidth: 2,
            labelsFontSize: 12,
            needleColor: "#000",
            circleColor: "#000",
        }
    };

    let currentAngle = -Math.PI;

    const drawNeedle = (chart, targetPercentage) => {
        const ctx = chart.ctx;
        const width = chart.width;
        const height = chart.height;
        const centerX = width / 2;
        const centerY = height * 0.75;
    
        const radius = height * 0.4;
        const targetAngle = (Math.PI * targetPercentage / 100) - Math.PI;
    
        // Animación progresiva
        if (currentAngle < targetAngle) {
            currentAngle += 0.02;
            if (currentAngle > targetAngle) currentAngle = targetAngle;
        } else if (currentAngle > targetAngle) {
            currentAngle -= 0.02;
            if (currentAngle < targetAngle) currentAngle = targetAngle;
        }
    
        const x = centerX + Math.cos(currentAngle) * radius;
        const y = centerY + Math.sin(currentAngle) * radius;
    
        // Limpiar antes de dibujar (importante si animamos)
        chart.update();
    
        ctx.save();
        ctx.beginPath();
        ctx.moveTo(centerX, centerY);
        ctx.lineTo(x, y);
        ctx.lineWidth = config.chart.needleWidth;
        ctx.strokeStyle = config.chart.needleColor;
        ctx.stroke();
        ctx.restore();
    
        // Círculo central
        ctx.beginPath();
        ctx.arc(centerX, centerY, config.chart.circleRadius, 0, 2 * Math.PI);
        ctx.fillStyle = config.chart.circleColor;
        ctx.fill();
    };
    

    new Chart(canvas.getContext("2d"), {
        type: config.chart.type,
        data: {
            datasets: [{
                data: config.data.speedometrBarsWidth,
                backgroundColor: config.data.speedometrBarsColors,
                borderWidth: 0,
                circumference: config.chart.circumference,
                rotation: config.chart.rotation,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => config.data.barLabels[ctx.dataIndex] || ''
                    },
                    bodyFont: { size: config.chart.labelsFontSize },
                },
                datalabels: { display: false } // evita que aparezcan los valores
            }
        },
        plugins: [{
            id: "needle",
            afterDatasetsDraw(chart) {
              drawNeedle(chart, percentage);
            }
        }]
    });
});
