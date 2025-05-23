<?php
// Incluir el security.php
include_once 'config/security.php';

// Inicializar variable|s
$csrf_token = Security::generateCSRFToken();

// Incluir modelos necesarios
if (!class_exists('DashboardControlador')) {
    include_once 'controlador/DashboardControlador.php';
}

// Reemplazar las consultas directas por llamadas al controlador
$dashboardControlador = new DashboardControlador();
$datosDashboard = $dashboardControlador->obtenerDatosAdminDashboard();

// Usar los datos del controlador en lugar de las consultas directas
$total_solicitudes = $datosDashboard['total_solicitudes'];
$total_usuarios = $datosDashboard['total_usuarios'];
$estadisticas_categoria = $datosDashboard['estadisticas_categoria'];

// Obtener la conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Incluir los archivos que contienen las clases necesarias
if (!class_exists('Solicitud')) {
    include_once 'modelo/Solicitud.php';
}
if (!class_exists('Usuario')) {
    include_once 'modelo/Usuario.php';
}
if (!class_exists('Categoria')) {
    include_once 'modelo/Categoria.php';
}
if (!class_exists('Estado')) {
    include_once 'modelo/Estado.php';
}
if (!class_exists('Sugerencia')) {
    include_once 'modelo/Sugerencia.php';
}

// Inicializar objetos
$solicitud = new Solicitud($db);
$usuario = new Usuario($db);
$categoria = new Categoria($db);
$estado = new Estado($db);
$sugerencia = new Sugerencia($db);

// Obtener estadísticas generales
// 3. Total de sugerencias
$query_total_sugerencias = "SELECT COUNT(*) as total FROM sugerencias";
$stmt_total_sugerencias = $db->prepare($query_total_sugerencias);
$stmt_total_sugerencias->execute();
$total_sugerencias = $stmt_total_sugerencias->fetch(PDO::FETCH_ASSOC)['total'];

// 4. Solicitudes por estado
$query_por_estado = "SELECT e.nombre, e.color, COUNT(s.id) as total 
                     FROM solicitudes s 
                     JOIN estados e ON s.estado_id = e.id 
                     GROUP BY s.estado_id";
$stmt_por_estado = $db->prepare($query_por_estado);
$stmt_por_estado->execute();
$solicitudes_por_estado = $stmt_por_estado->fetchAll(PDO::FETCH_ASSOC);

// 5. Solicitudes recientes
$solicitudes_recientes = $solicitud->obtenerRecientes(8);

// 7. Últimos usuarios registrados
$query_usuarios_recientes = "SELECT id, nombre, apellidos, email, rol_id, fecha_registro 
                            FROM usuarios 
                            ORDER BY fecha_registro DESC 
                            LIMIT 5";
$stmt_usuarios_recientes = $db->prepare($query_usuarios_recientes);
$stmt_usuarios_recientes->execute();
$usuarios_recientes = $stmt_usuarios_recientes->fetchAll(PDO::FETCH_ASSOC);

// 8. Obtener datos para gráfico temporal de solicitudes
$query_timeline = "SELECT DATE_FORMAT(fecha_creacion, '%Y-%m') as mes, COUNT(*) as total 
                  FROM solicitudes 
                  GROUP BY DATE_FORMAT(fecha_creacion, '%Y-%m') 
                  ORDER BY mes ASC 
                  LIMIT 12";
$stmt_timeline = $db->prepare($query_timeline);
$stmt_timeline->execute();
$timeline_data = $stmt_timeline->fetchAll(PDO::FETCH_ASSOC);

// 9. Tiempo promedio de resolución por categoría
$query_tiempo_categoria = "SELECT c.nombre, c.color, AVG(DATEDIFF(s.fecha_resolucion, s.fecha_creacion)) as tiempo_promedio 
                          FROM solicitudes s 
                          JOIN categorias c ON s.categoria_id = c.id 
                          WHERE s.fecha_resolucion IS NOT NULL 
                          GROUP BY s.categoria_id 
                          ORDER BY tiempo_promedio DESC";
$stmt_tiempo_categoria = $db->prepare($query_tiempo_categoria);
$stmt_tiempo_categoria->execute();
$tiempo_categoria = $stmt_tiempo_categoria->fetchAll(PDO::FETCH_ASSOC);
if (!isset($sugerenciaControlador) || !is_object($sugerenciaControlador)) {
    if (!class_exists('SugerenciaControlador')) {
        include_once 'controlador/SugerenciaController.php';
    }
    $sugerenciaControlador = new Sugerencia($db);
}
// 10. Top funcionarios por solicitudes resueltas
$query_top_funcionarios = "SELECT 
                          u.id, u.nombre, u.apellidos, 
                          COUNT(s.id) as solicitudes_resueltas,
                          AVG(DATEDIFF(s.fecha_resolucion, s.fecha_creacion)) as tiempo_promedio,
                          COUNT(DISTINCT s.usuario_id) as ciudadanos_atendidos
                          FROM solicitudes s 
                          JOIN usuarios u ON s.funcionario_id = u.id 
                          WHERE s.estado_id = (SELECT id FROM estados WHERE nombre = 'Resuelta')
                          GROUP BY s.funcionario_id 
                          ORDER BY solicitudes_resueltas DESC 
                          LIMIT 5";
$stmt_top_funcionarios = $db->prepare($query_top_funcionarios);
$stmt_top_funcionarios->execute();
$top_funcionarios = $stmt_top_funcionarios->fetchAll(PDO::FETCH_ASSOC);

// 11. Actividad reciente (combinación de solicitudes, sugerencias y usuarios nuevos)
// Solicitudes recientes
$query_actividad_solicitudes = "SELECT 
                               'solicitud' as tipo,
                               s.id,
                               s.titulo as descripcion,
                               u.nombre as usuario_nombre,
                               u.apellidos as usuario_apellidos,
                               s.fecha_creacion as fecha,
                               e.nombre as estado,
                               e.color as color
                               FROM solicitudes s 
                               JOIN usuarios u ON s.usuario_id = u.id
                               JOIN estados e ON s.estado_id = e.id
                               ORDER BY s.fecha_creacion DESC
                               LIMIT 5";
$stmt_actividad_solicitudes = $db->prepare($query_actividad_solicitudes);
$stmt_actividad_solicitudes->execute();
$actividad_solicitudes = $stmt_actividad_solicitudes->fetchAll(PDO::FETCH_ASSOC);

// Sugerencias recientes
$query_actividad_sugerencias = "SELECT 
                               'sugerencia' as tipo,
                               s.id,
                               s.titulo as descripcion,
                               u.nombre as usuario_nombre,
                               u.apellidos as usuario_apellidos,
                               s.fecha_creacion as fecha,
                               e.nombre as estado,
                               e.color as color
                               FROM sugerencias s 
                               JOIN usuarios u ON s.usuario_id = u.id
                               JOIN estados e ON s.estado_id = e.id
                               ORDER BY s.fecha_creacion DESC
                               LIMIT 5";
$stmt_actividad_sugerencias = $db->prepare($query_actividad_sugerencias);
$stmt_actividad_sugerencias->execute();
$actividad_sugerencias = $stmt_actividad_sugerencias->fetchAll(PDO::FETCH_ASSOC);

// Usuarios nuevos
$query_actividad_usuarios = "SELECT 
                            'usuario' as tipo,
                            u.id,
                            CONCAT('Se registró un nuevo usuario: ', u.nombre, ' ', u.apellidos) as descripcion,
                            u.nombre as usuario_nombre,
                            u.apellidos as usuario_apellidos,
                            u.fecha_registro as fecha,
                            r.nombre as estado,
                            '#6c757d' as color
                            FROM usuarios u 
                            JOIN roles r ON u.rol_id = r.id
                            ORDER BY u.fecha_registro DESC
                            LIMIT 5";
$stmt_actividad_usuarios = $db->prepare($query_actividad_usuarios);
$stmt_actividad_usuarios->execute();
$actividad_usuarios = $stmt_actividad_usuarios->fetchAll(PDO::FETCH_ASSOC);

// Combinar y ordenar por fecha
$actividad_reciente = array_merge($actividad_solicitudes, $actividad_sugerencias, $actividad_usuarios);
usort($actividad_reciente, function($a, $b) {
    return strtotime($b['fecha']) - strtotime($a['fecha']);
});
$actividad_reciente = array_slice($actividad_reciente, 0, 10);

// 12. Calcular solicitudes pendientes
$pendientes = 0;
foreach($solicitudes_por_estado as $estado_item) {
    if ($estado_item['nombre'] == 'Nueva' || $estado_item['nombre'] == 'Asignada' || $estado_item['nombre'] == 'En proceso') {
        $pendientes += $estado_item['total'];
    }
}

// 13. Calcular solicitudes resueltas
$resueltas = 0;
foreach($solicitudes_por_estado as $estado_item) {
    if ($estado_item['nombre'] == 'Resuelta') {
        $resueltas = $estado_item['total'];
    }
}

// 14. Tiempo promedio de resolución global
$query_tiempo_global = "SELECT AVG(DATEDIFF(fecha_resolucion, fecha_creacion)) as promedio 
                       FROM solicitudes 
                       WHERE fecha_resolucion IS NOT NULL";
$stmt_tiempo_global = $db->prepare($query_tiempo_global);
$stmt_tiempo_global->execute();
$tiempo_global = $stmt_tiempo_global->fetch(PDO::FETCH_ASSOC)['promedio'];
$tiempo_global = $tiempo_global ? round($tiempo_global, 1) : 'N/A';

// 15. Calcular tasa de resolución (solicitudes resueltas / total de solicitudes) * 100
$tasa_resolucion = $total_solicitudes > 0 ? round(($resueltas / $total_solicitudes) * 100, 1) : 0;

// 16. Calcular distribución de usuarios por rol
$query_usuarios_por_rol = "SELECT r.nombre, r.id, COUNT(u.id) as total 
                          FROM usuarios u 
                          JOIN roles r ON u.rol_id = r.id 
                          GROUP BY u.rol_id";
$stmt_usuarios_por_rol = $db->prepare($query_usuarios_por_rol);
$stmt_usuarios_por_rol->execute();
$usuarios_por_rol = $stmt_usuarios_por_rol->fetchAll(PDO::FETCH_ASSOC);

// Colores para los roles (administrador, funcionario, ciudadano)
$colores_roles = [
    1 => '#4e73df', // Azul para administrador
    2 => '#1cc88a', // Verde para funcionario
    3 => '#f6c23e'  // Amarillo para ciudadano
];

// Obtener datos para categorías
$query_categorias = "SELECT c.nombre, c.color, COUNT(s.id) as total 
                     FROM categorias c 
                     LEFT JOIN solicitudes s ON s.categoria_id = c.id 
                     GROUP BY c.id";
$stmt_categorias = $db->prepare($query_categorias);
$stmt_categorias->execute();
$categorias_array = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

// Asegurarse de que $categorias_array esté inicializado
if (!$categorias_array) {
    $categorias_array = [];
}
?>

<!-- Cabecera del Dashboard -->
<div class="row">
    <div class="col-12">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-primary">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard Administrativo
            </h1>
            <div class="d-flex align-items-center">
                <button type="button" class="btn btn-primary btn-sm me-2" id="refreshDashboard">
                    <i class="fas fa-sync-alt me-1"></i> Actualizar
                </button>
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-download me-1"></i> Exportar
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" id="exportPDF"><i class="fas fa-file-pdf me-1"></i> PDF</a></li>
                        <li><a class="dropdown-item" href="#" id="exportExcel"><i class="fas fa-file-excel me-1"></i> Excel</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" id="printDashboard"><i class="fas fa-print me-1"></i> Imprimir</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tarjetas de resumen -->
<div class="row g-4">
    <div class="col-md-3 col-6">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center">
                <div class="mb-2"><i class="fas fa-file-alt fa-2x text-primary"></i></div>
                <h5 class="card-title mb-1"><?php echo $total_solicitudes; ?></h5>
                <div class="text-muted small">Total Solicitudes</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center">
                <div class="mb-2"><i class="fas fa-users fa-2x text-success"></i></div>
                <h5 class="card-title mb-1"><?php echo $total_usuarios; ?></h5>
                <div class="text-muted small">Total Usuarios</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center">
                <div class="mb-2"><i class="fas fa-lightbulb fa-2x text-warning"></i></div>
                <h5 class="card-title mb-1"><?php echo $total_sugerencias; ?></h5>
                <div class="text-muted small">Total Sugerencias</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center">
                <div class="mb-2"><i class="fas fa-check-circle fa-2x text-info"></i></div>
                <h5 class="card-title mb-1"><?php echo $tasa_resolucion; ?>%</h5>
                <div class="text-muted small">Tasa de Resolución</div>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos de análisis -->
<div class="row g-4 mt-2">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                <span><i class="fas fa-chart-line me-2"></i>Evolución de Solicitudes (12 meses)</span>
                <div>
                    <button class="btn btn-outline-secondary btn-sm me-1" id="showYearView"><i class="fas fa-calendar-alt"></i></button>
                    <button class="btn btn-outline-secondary btn-sm me-1" id="showMonthView"><i class="fas fa-calendar"></i></button>
                    <button class="btn btn-outline-primary btn-sm" id="exportChartBtn"><i class="fas fa-file-excel"></i></button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="solicitudesTimelineChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                <span><i class="fas fa-chart-pie me-2"></i>Por Estado</span>
                <div>
                    <button class="btn btn-outline-secondary btn-sm me-1" id="viewDoughnut"><i class="fas fa-dot-circle"></i></button>
                    <button class="btn btn-outline-secondary btn-sm" id="viewPie"><i class="fas fa-chart-pie"></i></button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="solicitudesPorEstadoChart" height="180"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0">
                <i class="fas fa-layer-group me-2"></i>Por Categoría
            </div>
            <div class="card-body">
                <canvas id="solicitudesPorCategoriaChart" height="180"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Actividad Reciente y Top Funcionarios -->
<div class="row g-4 mt-2">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                <span><i class="fas fa-bolt me-2"></i>Actividad Reciente</span>
                <button class="btn btn-outline-primary btn-sm" id="verTodasActividadesBtn"><i class="fas fa-list"></i> Ver todas</button>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php foreach($actividad_reciente as $item): ?>
                        <li class="list-group-item d-flex align-items-center">
                            <?php if($item['tipo'] == 'solicitud'): ?>
                                <span class="badge me-2" style="background:<?php echo $item['color']; ?>;"><i class="fas fa-file-alt"></i></span>
                            <?php elseif($item['tipo'] == 'sugerencia'): ?>
                                <span class="badge me-2" style="background:<?php echo $item['color']; ?>;"><i class="fas fa-lightbulb"></i></span>
                            <?php else: ?>
                                <span class="badge me-2" style="background:<?php echo $item['color']; ?>;"><i class="fas fa-user-plus"></i></span>
                            <?php endif; ?>
                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($item['descripcion']); ?></div>
                                <div class="small text-muted">
                                    <?php echo htmlspecialchars($item['usuario_nombre'] . ' ' . $item['usuario_apellidos']); ?> &middot; 
                                    <?php echo date('d/m/Y H:i', strtotime($item['fecha'])); ?>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                <span><i class="fas fa-user-tie me-2"></i>Top Funcionarios</span>
                <a href="funcionarios.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-users"></i> Ver todos</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nombre</th>
                                <th>Solicitudes Resueltas</th>
                                <th>Tiempo Promedio (días)</th>
                                <th>Ciudadanos Atendidos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($top_funcionarios as $i => $func): ?>
                                <tr>
                                    <td><?php echo $i+1; ?></td>
                                    <td><?php echo htmlspecialchars($func['nombre'] . ' ' . $func['apellidos']); ?></td>
                                    <td><?php echo $func['solicitudes_resueltas']; ?></td>
                                    <td><?php echo $func['tiempo_promedio'] ? round($func['tiempo_promedio'],1) : 'N/A'; ?></td>
                                    <td><?php echo $func['ciudadanos_atendidos']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts de gráficos y botones -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Botón Actualizar ---
    document.getElementById('refreshDashboard').addEventListener('click', function() {
        location.reload();
    });

    // --- Botón Imprimir ---
    document.getElementById('printDashboard').addEventListener('click', function(e) {
        e.preventDefault();
        window.print();
    });

    // --- Botón Exportar PDF ---
    document.getElementById('exportPDF').addEventListener('click', function(e) {
        e.preventDefault();
        import('https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js').then(() => {
            html2canvas(document.body).then(function(canvas) {
                const imgData = canvas.toDataURL('image/png');
                const pdf = new window.jspdf.jsPDF('l', 'mm', 'a4');
                const pageWidth = pdf.internal.pageSize.getWidth();
                const pageHeight = pdf.internal.pageSize.getHeight();
                const imgWidth = pageWidth;
                const imgHeight = canvas.height * imgWidth / canvas.width;
                pdf.addImage(imgData, 'PNG', 0, 0, imgWidth, imgHeight);
                pdf.save('dashboard.pdf');
            });
        });
    });

    // --- Botón Exportar Excel ---
    document.getElementById('exportExcel').addEventListener('click', function(e) {
        e.preventDefault();
        // Exportar solo las tablas principales
        let wb = XLSX.utils.book_new();
        // Top Funcionarios
        let table = document.querySelector('.table');
        if (table) {
            let ws = XLSX.utils.table_to_sheet(table);
            XLSX.utils.book_append_sheet(wb, ws, "Top Funcionarios");
        }
        XLSX.writeFile(wb, "dashboard.xlsx");
    });

    // --- Botón Ver Todas Actividades ---
    document.getElementById('verTodasActividadesBtn').addEventListener('click', function(e) {
        e.preventDefault();
        alert('Funcionalidad de ver todas las actividades próximamente.');
    });

    // --- Botón Ver Todos Funcionarios ---
    // Ya es un enlace, no requiere JS

    // --- Botón Exportar Datos de Gráfica ---
    document.getElementById('exportChartBtn').addEventListener('click', function(e) {
        e.preventDefault();
        let labels = <?php echo json_encode(array_column($timeline_data, 'mes')); ?>;
        let data = <?php echo json_encode(array_column($timeline_data, 'total')); ?>;
        let ws = XLSX.utils.aoa_to_sheet([['Mes', 'Solicitudes']].concat(labels.map((l, i) => [l, data[i]])));
        let wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Evolucion");
        XLSX.writeFile(wb, "evolucion_solicitudes.xlsx");
    });

    // --- Cambiar vista de gráfica (anual/mensual) ---
    document.getElementById('showYearView').addEventListener('click', function(e) {
        e.preventDefault();
        alert('Vista anual próximamente.');
    });
    document.getElementById('showMonthView').addEventListener('click', function(e) {
        e.preventDefault();
        alert('Vista mensual próximamente.');
    });

    // --- Cambiar tipo de gráfica de estados ---
    document.getElementById('viewDoughnut').addEventListener('click', function(e) {
        e.preventDefault();
        solicitudesPorEstadoChart.config.type = 'doughnut';
        solicitudesPorEstadoChart.update();
    });
    document.getElementById('viewPie').addEventListener('click', function(e) {
        e.preventDefault();
        solicitudesPorEstadoChart.config.type = 'pie';
        solicitudesPorEstadoChart.update();
    });

    // --- Gráfica de evolución de solicitudes ---
    const timelineLabels = <?php echo json_encode(array_column($timeline_data, 'mes')); ?>;
    const timelineData = <?php echo json_encode(array_column($timeline_data, 'total')); ?>;
    const ctxTimeline = document.getElementById('solicitudesTimelineChart').getContext('2d');
    const solicitudesTimelineChart = new Chart(ctxTimeline, {
        type: 'line',
        data: {
            labels: timelineLabels,
            datasets: [{
                label: 'Solicitudes',
                data: timelineData,
                fill: true,
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                borderColor: 'rgba(78, 115, 223, 1)',
                tension: 0.4,
                pointBackgroundColor: 'rgba(78, 115, 223, 1)'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { title: { display: true, text: 'Mes' } },
                y: { title: { display: true, text: 'Solicitudes' }, beginAtZero: true }
            }
        }
    });

    // --- Gráfica de solicitudes por estado ---
    const estadoLabels = <?php echo json_encode(array_column($solicitudes_por_estado, 'nombre')); ?>;
    const estadoData = <?php echo json_encode(array_column($solicitudes_por_estado, 'total')); ?>;
    const estadoColors = <?php echo json_encode(array_column($solicitudes_por_estado, 'color')); ?>;
    const ctxEstado = document.getElementById('solicitudesPorEstadoChart').getContext('2d');
    window.solicitudesPorEstadoChart = new Chart(ctxEstado, {
        type: 'doughnut',
        data: {
            labels: estadoLabels,
            datasets: [{
                data: estadoData,
                backgroundColor: estadoColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // --- Gráfica de solicitudes por categoría ---
    const categoriaLabels = <?php echo json_encode(array_column($categorias_array, 'nombre')); ?>;
    const categoriaData = <?php echo json_encode(array_column($categorias_array, 'total')); ?>;
    const categoriaColors = <?php echo json_encode(array_column($categorias_array, 'color')); ?>;
    const ctxCategoria = document.getElementById('solicitudesPorCategoriaChart').getContext('2d');
    new Chart(ctxCategoria, {
        type: 'pie',
        data: {
            labels: categoriaLabels,
            datasets: [{
                data: categoriaData,
                backgroundColor: categoriaColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
});
</script>
