/**
 * JS para el dashboard de funcionario
 * Requiere Chart.js y Bootstrap Modal
 */

// Esperar a que el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // --- Datos PHP inyectados por el backend ---
    // Estos deben ser generados dinámicamente en el HTML usando JSON.encode en PHP
    // Ejemplo:
    // <script>window.dashboardData = <?php echo json_encode([...]); ?>;</script>
    // Aquí se asume que existen las siguientes variables globales:
    // window.dashboardData = {
    //   rendimientoLabels: [...],
    //   rendimientoResueltas: [...],
    //   rendimientoPromedio: [...],
    //   categoriasLabels: [...],
    //   categoriasTotales: [...],
    //   categoriasColores: [...]
    // };

    // --- Gráfico de rendimiento mensual ---
    if (window.dashboardData && window.dashboardData.rendimientoLabels) {
        const ctxRendimiento = document.getElementById('rendimientoChart').getContext('2d');
        new Chart(ctxRendimiento, {
            type: 'line',
            data: {
                labels: window.dashboardData.rendimientoLabels,
                datasets: [
                    {
                        label: 'Solicitudes Resueltas',
                        data: window.dashboardData.rendimientoResueltas,
                        backgroundColor: 'rgba(46,204,113,0.2)',
                        borderColor: 'rgba(46,204,113,1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4
                    },
                    {
                        label: 'Tiempo Promedio (días)',
                        data: window.dashboardData.rendimientoPromedio,
                        backgroundColor: 'rgba(52,152,219,0.2)',
                        borderColor: 'rgba(52,152,219,1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Solicitudes Resueltas' },
                        grid: { drawBorder: false, color: 'rgba(0,0,0,0.1)' },
                        ticks: { precision: 0 }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        title: { display: true, text: 'Tiempo Promedio (días)' },
                        grid: { display: false, drawBorder: false }
                    },
                    x: {
                        grid: { display: false, drawBorder: false }
                    }
                },
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // --- Gráfico de solicitudes por categoría ---
    if (window.dashboardData && window.dashboardData.categoriasLabels) {
        const ctxCategorias = document.getElementById('categoriasChart').getContext('2d');
        new Chart(ctxCategorias, {
            type: 'doughnut',
            data: {
                labels: window.dashboardData.categoriasLabels,
                datasets: [{
                    data: window.dashboardData.categoriasTotales,
                    backgroundColor: window.dashboardData.categoriasColores,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', display: false }
                },
                cutout: '70%'
            }
        });
    }

    // --- Modal para actualizar estado de solicitud ---
    const actualizarEstadoModal = document.getElementById('actualizarEstadoModal');
    if (actualizarEstadoModal) {
        actualizarEstadoModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (!button) return;
            const id = button.getAttribute('data-id');
            const titulo = button.getAttribute('data-titulo');
            document.getElementById('solicitud_id').value = id;
            document.getElementById('titulo_solicitud').value = titulo;
        });
    }
});