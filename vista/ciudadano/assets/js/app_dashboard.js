/**
 * app_dashboard.js
 * JS para Dashboard Ciudadano
 * Requiere Chart.js cargado en la página
 */

// Espera a que el DOM esté listo
document.addEventListener('DOMContentLoaded', function () {
    // --- Gráfico de Solicitudes por Estado ---
    if (window.solicitudesPorEstadoData) {
        const estadoCtx = document.getElementById('solicitudesPorEstadoChart');
        if (estadoCtx) {
            new Chart(estadoCtx, {
                type: 'doughnut',
                data: {
                    labels: window.solicitudesPorEstadoData.labels,
                    datasets: [{
                        data: window.solicitudesPorEstadoData.data,
                        backgroundColor: window.solicitudesPorEstadoData.colors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            display: false
                        }
                    },
                    cutout: '70%'
                }
            });
        }
    }

    // --- Gráfico de Timeline de Solicitudes ---
    if (window.solicitudesTimelineData) {
        const timelineCtx = document.getElementById('solicitudesTimelineChart');
        if (timelineCtx) {
            new Chart(timelineCtx, {
                type: 'line',
                data: {
                    labels: window.solicitudesTimelineData.labels,
                    datasets: [{
                        label: 'Solicitudes',
                        data: window.solicitudesTimelineData.data,
                        backgroundColor: 'rgba(78, 115, 223, 0.05)',
                        borderColor: 'rgba(78, 115, 223, 1)',
                        pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                drawBorder: false,
                                color: 'rgba(0, 0, 0, 0.1)'
                            },
                            ticks: {
                                precision: 0
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    }

    // --- Marcar notificaciones como leídas al hacer click ---
    document.querySelectorAll('.list-group-item-action').forEach(function (item) {
        item.addEventListener('click', function () {
            const notifId = this.href.match(/id=(\d+)/);
            if (notifId) {
                fetch('index.php?page=marcar_notificacion_leida', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + encodeURIComponent(notifId[1])
                });
            }
        });
    });
});