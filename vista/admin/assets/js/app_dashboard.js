/**
 * JS para Dashboard Administrativo
 * Requiere: Chart.js, SweetAlert2, Bootstrap 5
 * Los datos deben ser inyectados desde PHP como variables JS o vía AJAX.
 */

// Ejemplo de estructura de datos (rellenar dinámicamente desde PHP)
const dashboardData = window.dashboardData || {
    timeline: [],
    estados: [],
    categorias: [],
    usuariosPorRol: [],
    topFuncionarios: [],
    tiempoCategoria: [],
    actividadReciente: [],
    tasaResolucion: 0,
    tiempoGlobal: 0,
    totalSolicitudes: 0,
    totalUsuarios: 0,
    totalSugerencias: 0,
    pendientes: 0,
    resueltas: 0
};

document.addEventListener('DOMContentLoaded', function () {
    // Gráfico de evolución temporal de solicitudes
    if (dashboardData.timeline && dashboardData.timeline.length && window.Chart) {
        const timelineCtx = document.getElementById('solicitudesTimelineChart').getContext('2d');
        new Chart(timelineCtx, {
            type: 'line',
            data: {
                labels: dashboardData.timeline.map(d => d.label),
                datasets: [{
                    label: 'Total de Solicitudes',
                    data: dashboardData.timeline.map(d => d.total),
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    pointRadius: 3,
                    pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                    pointBorderColor: '#fff',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });
    }

    // Gráfico de solicitudes por estado
    if (dashboardData.estados && dashboardData.estados.length && window.Chart) {
        const estadoCtx = document.getElementById('solicitudesPorEstadoChart').getContext('2d');
        const estadoChart = new Chart(estadoCtx, {
            type: 'doughnut',
            data: {
                labels: dashboardData.estados.map(e => `${e.nombre} (${e.total})`),
                datasets: [{
                    data: dashboardData.estados.map(e => e.total),
                    backgroundColor: dashboardData.estados.map(e => e.color),
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                cutout: '70%'
            }
        });

        document.getElementById('viewDoughnut')?.addEventListener('click', e => {
            e.preventDefault();
            estadoChart.config.type = 'doughnut';
            estadoChart.update();
        });
        document.getElementById('viewPie')?.addEventListener('click', e => {
            e.preventDefault();
            estadoChart.config.type = 'pie';
            estadoChart.update();
        });
    }

    // Gráfico de solicitudes por categoría
    if (dashboardData.categorias && dashboardData.categorias.length && window.Chart) {
        const categoriaCtx = document.getElementById('solicitudesPorCategoriaChart').getContext('2d');
        let topCategorias = dashboardData.categorias.slice(0, 3);
        let otrosTotal = dashboardData.categorias.slice(3).reduce((sum, c) => sum + c.total, 0);
        let labels = topCategorias.map(c => `${c.nombre} (${c.total})`);
        let data = topCategorias.map(c => c.total);
        let colors = topCategorias.map(c => c.color);
        if (otrosTotal > 0) {
            labels.push(`Otros (${otrosTotal})`);
            data.push(otrosTotal);
            colors.push('#7c8798');
        }
        new Chart(categoriaCtx, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data,
                    backgroundColor: colors,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                cutout: '70%'
            }
        });
    }

    // Botón de refrescar dashboard
    document.getElementById('refreshDashboard')?.addEventListener('click', function () {
        if (window.Swal) {
            Swal.fire({
                title: 'Actualizando...',
                text: 'Obteniendo datos más recientes',
                timer: 1500,
                timerProgressBar: true,
                didOpen: () => Swal.showLoading()
            }).then(() => window.location.reload());
        } else {
            window.location.reload();
        }
    });

    // Exportar PDF
    document.getElementById('exportPDF')?.addEventListener('click', function (e) {
        e.preventDefault();
        if (window.Swal) {
            Swal.fire({
                title: 'Exportando PDF',
                text: 'El informe se está generando',
                icon: 'info',
                showConfirmButton: false,
                timer: 2000
            });
        }
    });

    // Exportar Excel
    document.getElementById('exportExcel')?.addEventListener('click', function (e) {
        e.preventDefault();
        if (window.Swal) {
            Swal.fire({
                title: 'Exportando Excel',
                text: 'Los datos se están exportando',
                icon: 'info',
                showConfirmButton: false,
                timer: 2000
            });
        }
    });

    // Imprimir dashboard
    document.getElementById('printDashboard')?.addEventListener('click', function (e) {
        e.preventDefault();
        window.print();
    });

    // Ver todas las actividades
    document.getElementById('verTodasActividadesBtn')?.addEventListener('click', function (e) {
        e.preventDefault();
        window.location.href = 'index.php?page=admin_actividad';
    });
});