<?php
// Redirección después de enviar sugerencia o solicitud (debe ir antes de cualquier salida HTML)
if (isset($_GET['enviado']) && $_GET['enviado'] === '1') {
    if (ob_get_level()) ob_end_clean();
    header("Location: dashboard.php");
    exit;
}

// Seguridad y dependencias
include_once 'config/security.php';
$csrf_token = Security::generateCSRFToken();

if (!class_exists('DashboardControlador')) include_once 'controlador/DashboardControlador.php';
$dashboardControlador = new DashboardControlador();
$datosDashboard = $dashboardControlador->obtenerDatosAdminDashboard();

$total_solicitudes = $datosDashboard['total_solicitudes'];
$total_usuarios = $datosDashboard['total_usuarios'];
$estadisticas_categoria = $datosDashboard['estadisticas_categoria'];

if (!class_exists('Database')) include_once 'config/Database.php';
$database = new Database();
$db = $database->getConnection();

foreach (['Solicitud', 'Usuario', 'Categoria', 'Estado', 'Sugerencia'] as $clase) {
    if (!class_exists($clase)) include_once "modelo/$clase.php";
}
$solicitud = new Solicitud($db);
$usuario = new Usuario($db);
$categoria = new Categoria($db);
$estado = new Estado($db);
$sugerencia = new Sugerencia($db);

// Estadísticas generales
// Total sugerencias
$stmt = $db->prepare("SELECT COUNT(*) as total FROM sugerencias");
$stmt->execute();
$total_sugerencias = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Solicitudes por estado
$stmt = $db->prepare("SELECT e.nombre, e.color, COUNT(s.id) as total 
    FROM solicitudes s 
    JOIN estados e ON s.estado_id = e.id 
    GROUP BY s.estado_id");
$stmt->execute();
$solicitudes_por_estado = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Solicitudes recientes
$solicitudes_recientes = $solicitud->obtenerRecientes(8);

// Últimos usuarios registrados
$stmt = $db->prepare("SELECT id, nombre, apellidos, email, rol_id, fecha_registro 
    FROM usuarios ORDER BY fecha_registro DESC LIMIT 5");
$stmt->execute();
$usuarios_recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Gráfico temporal de solicitudes
$stmt = $db->prepare("SELECT DATE_FORMAT(fecha_creacion, '%Y-%m') as mes, COUNT(*) as total 
    FROM solicitudes GROUP BY DATE_FORMAT(fecha_creacion, '%Y-%m') 
    ORDER BY mes ASC LIMIT 12");
$stmt->execute();
$timeline_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tiempo promedio de resolución por categoría
$stmt = $db->prepare("SELECT c.nombre, c.color, AVG(DATEDIFF(s.fecha_resolucion, s.fecha_creacion)) as tiempo_promedio 
    FROM solicitudes s 
    JOIN categorias c ON s.categoria_id = c.id 
    WHERE s.fecha_resolucion IS NOT NULL 
    GROUP BY s.categoria_id 
    ORDER BY tiempo_promedio DESC");
$stmt->execute();
$tiempo_categoria = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top funcionarios por solicitudes resueltas
$stmt = $db->prepare("SELECT u.id, u.nombre, u.apellidos, 
    COUNT(s.id) as solicitudes_resueltas,
    AVG(DATEDIFF(s.fecha_resolucion, s.fecha_creacion)) as tiempo_promedio,
    COUNT(DISTINCT s.usuario_id) as ciudadanos_atendidos
    FROM solicitudes s 
    JOIN usuarios u ON s.funcionario_id = u.id 
    WHERE s.estado_id = (SELECT id FROM estados WHERE nombre = 'Resuelta')
    GROUP BY s.funcionario_id 
    ORDER BY solicitudes_resueltas DESC 
    LIMIT 5");
$stmt->execute();
$top_funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Actividad reciente
function fetchActividad($db, $query) {
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$actividad_solicitudes = fetchActividad($db, "SELECT 'solicitud' as tipo, s.id, s.titulo as descripcion, u.nombre as usuario_nombre, u.apellidos as usuario_apellidos, s.fecha_creacion as fecha, e.nombre as estado, e.color as color
    FROM solicitudes s 
    JOIN usuarios u ON s.usuario_id = u.id
    JOIN estados e ON s.estado_id = e.id
    ORDER BY s.fecha_creacion DESC
    LIMIT 5");
$actividad_sugerencias = fetchActividad($db, "SELECT 'sugerencia' as tipo, s.id, s.titulo as descripcion, u.nombre as usuario_nombre, u.apellidos as usuario_apellidos, s.fecha_creacion as fecha, e.nombre as estado, e.color as color
    FROM sugerencias s 
    JOIN usuarios u ON s.usuario_id = u.id
    JOIN estados e ON s.estado_id = e.id
    ORDER BY s.fecha_creacion DESC
    LIMIT 5");
$actividad_usuarios = fetchActividad($db, "SELECT 'usuario' as tipo, u.id, CONCAT('Se registró un nuevo usuario: ', u.nombre, ' ', u.apellidos) as descripcion, u.nombre as usuario_nombre, u.apellidos as usuario_apellidos, u.fecha_registro as fecha, r.nombre as estado, '#6c757d' as color
    FROM usuarios u 
    JOIN roles r ON u.rol_id = r.id
    ORDER BY u.fecha_registro DESC
    LIMIT 5");
$actividad_reciente = array_merge($actividad_solicitudes, $actividad_sugerencias, $actividad_usuarios);
usort($actividad_reciente, function($a, $b) {
    return strtotime($b['fecha']) - strtotime($a['fecha']);
});
$actividad_reciente = array_slice($actividad_reciente, 0, 10);

// Solicitudes pendientes y resueltas
$pendientes = $resueltas = 0;
foreach($solicitudes_por_estado as $estado_item) {
    if (in_array($estado_item['nombre'], ['Nueva', 'Asignada', 'En proceso'])) $pendientes += $estado_item['total'];
    if ($estado_item['nombre'] == 'Resuelta') $resueltas = $estado_item['total'];
}

// Tiempo promedio de resolución global
$stmt = $db->prepare("SELECT AVG(DATEDIFF(fecha_resolucion, fecha_creacion)) as promedio 
    FROM solicitudes WHERE fecha_resolucion IS NOT NULL");
$stmt->execute();
$tiempo_global = $stmt->fetch(PDO::FETCH_ASSOC)['promedio'];
$tiempo_global = $tiempo_global ? round($tiempo_global, 1) : 'N/A';

// Tasa de resolución
$tasa_resolucion = $total_solicitudes > 0 ? round(($resueltas / $total_solicitudes) * 100, 1) : 0;

// Distribución de usuarios por rol
$stmt = $db->prepare("SELECT r.nombre, r.id, COUNT(u.id) as total 
    FROM usuarios u 
    JOIN roles r ON u.rol_id = r.id 
    GROUP BY u.rol_id");
$stmt->execute();
$usuarios_por_rol = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Colores para los roles
$colores_roles = [
    1 => '#4e73df', // Administrador
    2 => '#1cc88a', // Funcionario
    3 => '#f6c23e'  // Ciudadano
];

// Datos para categorías
$stmt = $db->prepare("SELECT c.nombre, c.color, COUNT(s.id) as total 
    FROM categorias c 
    LEFT JOIN solicitudes s ON s.categoria_id = c.id 
    GROUP BY c.id");
$stmt->execute();
$categorias_array = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Cabecera -->
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

<!-- Tarjetas resumen -->
<div class="row g-4">
    <?php
    $cards = [
        ['icon' => 'fa-file-alt', 'color' => 'primary', 'value' => $total_solicitudes, 'label' => 'Total Solicitudes'],
        ['icon' => 'fa-users', 'color' => 'success', 'value' => $total_usuarios, 'label' => 'Total Usuarios'],
        ['icon' => 'fa-lightbulb', 'color' => 'warning', 'value' => $total_sugerencias, 'label' => 'Total Sugerencias'],
        ['icon' => 'fa-check-circle', 'color' => 'info', 'value' => $tasa_resolucion . '%', 'label' => 'Tasa de Resolución'],
    ];
    foreach ($cards as $card): ?>
    <div class="col-md-3 col-6">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center">
                <div class="mb-2"><i class="fas <?= $card['icon'] ?> fa-2x text-<?= $card['color'] ?>"></i></div>
                <h5 class="card-title mb-1"><?= $card['value'] ?></h5>
                <div class="text-muted small"><?= $card['label'] ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Gráficos -->
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
                            <?php
                            $icon = $item['tipo'] == 'solicitud' ? 'fa-file-alt' : ($item['tipo'] == 'sugerencia' ? 'fa-lightbulb' : 'fa-user-plus');
                            ?>
                            <span class="badge me-2" style="background:<?= $item['color'] ?>;"><i class="fas <?= $icon ?>"></i></span>
                            <div>
                                <div class="fw-bold"><?= htmlspecialchars($item['descripcion']) ?></div>
                                <div class="small text-muted">
                                    <?= htmlspecialchars($item['usuario_nombre'] . ' ' . $item['usuario_apellidos']) ?> &middot;
                                    <?= date('d/m/Y H:i', strtotime($item['fecha'])) ?>
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
                                    <td><?= $i+1 ?></td>
                                    <td><?= htmlspecialchars($func['nombre'] . ' ' . $func['apellidos']) ?></td>
                                    <td><?= $func['solicitudes_resueltas'] ?></td>
                                    <td><?= $func['tiempo_promedio'] ? round($func['tiempo_promedio'],1) : 'N/A' ?></td>
                                    <td><?= $func['ciudadanos_atendidos'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Botones principales
    document.getElementById('refreshDashboard').onclick = () => location.reload();
    document.getElementById('printDashboard').onclick = e => { e.preventDefault(); window.print(); };
    document.getElementById('exportPDF').onclick = function(e) {
        e.preventDefault();
        import('https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js').then(() => {
            html2canvas(document.body).then(function(canvas) {
                const imgData = canvas.toDataURL('image/png');
                const pdf = new window.jspdf.jsPDF('l', 'mm', 'a4');
                const pageWidth = pdf.internal.pageSize.getWidth();
                const imgWidth = pageWidth;
                const imgHeight = canvas.height * imgWidth / canvas.width;
                pdf.addImage(imgData, 'PNG', 0, 0, imgWidth, imgHeight);
                pdf.save('dashboard.pdf');
            });
        });
    };
    document.getElementById('exportExcel').onclick = function(e) {
        e.preventDefault();
        let wb = XLSX.utils.book_new();
        let table = document.querySelector('.table');
        if (table) {
            let ws = XLSX.utils.table_to_sheet(table);
            XLSX.utils.book_append_sheet(wb, ws, "Top Funcionarios");
        }
        XLSX.writeFile(wb, "dashboard.xlsx");
    };
    document.getElementById('verTodasActividadesBtn').onclick = e => { e.preventDefault(); alert('Funcionalidad de ver todas las actividades próximamente.'); };
    document.getElementById('exportChartBtn').onclick = function(e) {
        e.preventDefault();
        let labels = <?php echo json_encode(array_column($timeline_data, 'mes')); ?>;
        let data = <?php echo json_encode(array_column($timeline_data, 'total')); ?>;
        let ws = XLSX.utils.aoa_to_sheet([['Mes', 'Solicitudes']].concat(labels.map((l, i) => [l, data[i]])));
        let wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Evolucion");
        XLSX.writeFile(wb, "evolucion_solicitudes.xlsx");
    };
    document.getElementById('showYearView').onclick = e => { e.preventDefault(); alert('Vista anual próximamente.'); };
    document.getElementById('showMonthView').onclick = e => { e.preventDefault(); alert('Vista mensual próximamente.'); };
    document.getElementById('viewDoughnut').onclick = e => { e.preventDefault(); solicitudesPorEstadoChart.config.type = 'doughnut'; solicitudesPorEstadoChart.update(); };
    document.getElementById('viewPie').onclick = e => { e.preventDefault(); solicitudesPorEstadoChart.config.type = 'pie'; solicitudesPorEstadoChart.update(); };

    // Gráfica evolución solicitudes
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
            plugins: { legend: { display: false } },
            scales: {
                x: { title: { display: true, text: 'Mes' } },
                y: { title: { display: true, text: 'Solicitudes' }, beginAtZero: true }
            }
        }
    });

    // Gráfica solicitudes por estado
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
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // Gráfica solicitudes por categoría
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
            plugins: { legend: { position: 'bottom' } }
        }
    });
});
</script>
