// <!-- Scripts para gráficos -->

document.addEventListener('DOMContentLoaded', function() {
    // Los datos deben ser definidos en el HTML antes de este script, por ejemplo:
    // <script>
    //   var timelineLabels = <?php echo json_encode($timeline_labels); ?>;
    //   var timelineData = <?php echo json_encode($timeline_totals); ?>;
    //   var estadoLabels = <?php echo json_encode($estado_labels); ?>;
    //   var estadoData = <?php echo json_encode($estado_totals); ?>;
    //   var estadoColors = <?php echo json_encode($estado_colors); ?>;
    //   var categoriaLabels = <?php echo json_encode($categoria_labels); ?>;
    //   var categoriaData = <?php echo json_encode($categoria_totals); ?>;
    //   var categoriaColors = <?php echo json_encode($categoria_colors); ?>;
    //   var categoriaHoverColors = <?php echo json_encode($categoria_hover_colors); ?>;
    // </script>

    // Gráfico de evolución temporal de solicitudes
    const timelineCtx = document.getElementById('solicitudesTimelineChart').getContext('2d');
    const timelineChart = new Chart(timelineCtx, {
        type: 'line',
        data: {
            labels: typeof timelineLabels !== 'undefined' ? timelineLabels : [],
            datasets: [{
                label: 'Total de Solicitudes',
                data: typeof timelineData !== 'undefined' ? timelineData : [],
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                borderColor: 'rgba(78, 115, 223, 1)',
                pointRadius: 3,
                pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                pointBorderColor: '#fff',
                pointHoverRadius: 5,
                pointHoverBackgroundColor: 'rgba(78, 115, 223, 1)',
                pointHoverBorderColor: '#fff',
                borderWidth: 3,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 10,
                    right: 25,
                    top: 25,
                    bottom: 0
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        maxTicksLimit: 7
                    }
                },
                y: {
                    grid: {
                        color: "rgb(234, 236, 244)",
                        drawBorder: false,
                        borderDash: [2],
                        zeroLineBorderDash: [2]
                    },
                    ticks: {
                        maxTicksLimit: 5,
                        padding: 10,
                        precision: 0
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyColor: "#858796",
                    titleMarginBottom: 10,
                    titleColor: '#6e707e',
                    titleFont: {
                        size: 14
                    },
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: false,
                    intersect: false,
                    mode: 'index',
                    caretPadding: 10
                }
            }
        }
    });

    // Gráfico de solicitudes por estado
    const estadoCtx = document.getElementById('solicitudesPorEstadoChart').getContext('2d');
    const estadoChart = new Chart(estadoCtx, {
        type: 'doughnut',
        data: {
            labels: typeof estadoLabels !== 'undefined' ? estadoLabels : [],
            datasets: [{
                data: typeof estadoData !== 'undefined' ? estadoData : [],
                backgroundColor: typeof estadoColors !== 'undefined' ? estadoColors : [],
                hoverBackgroundColor: typeof estadoColors !== 'undefined' ? estadoColors : [],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    display: false
                },
                tooltip: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyColor: "#858796",
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: false,
                    caretPadding: 10,
                }
            },
            cutout: '70%'
        }
    });

    // Gráfico de solicitudes por categoría
    const categoriaCtx = document.getElementById('solicitudesPorCategoriaChart').getContext('2d');
    const categoriaChart = new Chart(categoriaCtx, {
        type: 'doughnut',
        data: {
            labels: typeof categoriaLabels !== 'undefined' ? categoriaLabels : [],
            datasets: [{
                data: typeof categoriaData !== 'undefined' ? categoriaData : [],
                backgroundColor: typeof categoriaColors !== 'undefined' ? categoriaColors : [],
                hoverBackgroundColor: typeof categoriaHoverColors !== 'undefined' ? categoriaHoverColors : [],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    display: false
                },
                tooltip: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyColor: "#858796",
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: false,
                    caretPadding: 10,
                }
            },
            cutout: '70%'
        }
    });

    // Cambiar tipo de gráfico de estado
    document.getElementById('viewDoughnut').addEventListener('click', function(e) {
        e.preventDefault();
        estadoChart.config.type = 'doughnut';
        estadoChart.update();
    });

    document.getElementById('viewPie').addEventListener('click', function(e) {
        e.preventDefault();
        estadoChart.config.type = 'pie';
        estadoChart.update();
    });

    // Simular funcionalidad para los botones de exportación
    document.getElementById('refreshDashboard').addEventListener('click', function() {
        Swal.fire({
            title: 'Actualizando...',
            text: 'Obteniendo datos más recientes',
            timer: 1500,
            timerProgressBar: true,
            didOpen: () => {
                Swal.showLoading();
            }
        }).then(() => {
            window.location.reload();
        });
    });

    document.getElementById('exportPDF').addEventListener('click', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Exportando PDF',
            text: 'El informe se está generando',
            icon: 'info',
            showConfirmButton: false,
            timer: 2000
        });
    });

    document.getElementById('exportExcel').addEventListener('click', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Exportando Excel',
            text: 'Los datos se están exportando',
            icon: 'info',
            showConfirmButton: false,
            timer: 2000
        });
    });

    document.getElementById('printDashboard').addEventListener('click', function(e) {
        e.preventDefault();
        window.print();
    });

    document.getElementById('verTodasActividadesBtn').addEventListener('click', function(e) {
        e.preventDefault();
        window.location.href = 'index.php?page=admin_actividad';
    });
});