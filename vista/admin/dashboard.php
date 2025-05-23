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
    <div class="col-md-12">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard Administrativo
            </h1>
            <div>
                <button type="button" class="btn btn-primary btn-sm" id="refreshDashboard">
                    <i class="fas fa-sync-alt me-1"></i> Actualizar
                </button>
                <div class="btn-group ms-2">
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
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card shadow h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col-auto pe-3">
                        <div class="icon-circle bg-primary text-white">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                    </div>
                    <div class="col">
                        <div class="small fw-normal text-primary text-uppercase mb-1">
                            Total de Solicitudes</div>
                        <div class="h5 mb-0 fw-normal text-dark"><?php echo $total_solicitudes; ?></div>
                        <div class="mt-2 mb-0 text-muted small">
                            <span class="text-success me-2"><i class="fas fa-check-circle"></i> <?php echo $resueltas; ?> resueltas</span>
                            <span class="text-warning"><i class="fas fa-clock"></i> <?php echo $pendientes; ?> pendientes</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card shadow h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col-auto pe-3">
                        <div class="icon-circle bg-success text-white">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="col">
                        <div class="small fw-normal text-success text-uppercase mb-1">
                            Total de Usuarios</div>
                        <div class="h5 mb-0 fw-normal text-dark"><?php echo $total_usuarios; ?></div>
                        <div class="mt-2 mb-0 text-muted small">
                            <?php foreach($usuarios_por_rol as $rol): ?>
                                <span class="me-2">
                                    <i class="fas fa-circle" style="color: <?php echo $colores_roles[$rol['id']]; ?>"></i> 
                                    <?php echo $rol['total']; ?> <?php echo strtolower($rol['nombre']); ?>(es)
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card shadow h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col-auto pe-3">
                        <div class="icon-circle bg-info text-white">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="col">
                        <div class="small fw-normal text-info text-uppercase mb-1">
                            Tasa de Resolución</div>
                        <div class="row no-gutters align-items-center">
                            <div class="col-auto">
                                <div class="h5 mb-0 me-3 fw-normal text-dark"><?php echo $tasa_resolucion; ?>%</div>
                            </div>
                            <div class="col">
                                <div class="progress progress-sm mr-2">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $tasa_resolucion; ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2 mb-0 text-muted small">
                            <span>Tiempo promedio: <?php echo $tiempo_global; ?> días</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card shadow h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col-auto pe-3">
                        <div class="icon-circle bg-warning text-white">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                    </div>
                    <div class="col">
                        <div class="small fw-normal text-warning text-uppercase mb-1">
                            Total de Sugerencias</div>
                        <div class="h5 mb-0 fw-normal text-dark"><?php echo $total_sugerencias; ?></div>
                        <div class="mt-2 mb-0 text-muted small">
                            <?php
                            // Obtener datos para sugerencias por estado
                            $query_sugerencias_estado = "SELECT e.nombre, COUNT(s.id) as total 
                                                        FROM sugerencias s 
                                                        JOIN estados e ON s.estado_id = e.id 
                                                        GROUP BY s.estado_id";
                            $stmt_sugerencias_estado = $db->prepare($query_sugerencias_estado);
                            $stmt_sugerencias_estado->execute();
                            $sugerencias_por_estado = $stmt_sugerencias_estado->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach($sugerencias_por_estado as $estado_item):
                                $icono = 'fas fa-circle';
                                $clase = 'text-muted';
                                
                                if ($estado_item['nombre'] == 'Nueva' || $estado_item['nombre'] == 'En revisión') {
                                    $icono = 'fas fa-hourglass-half';
                                    $clase = 'text-warning';
                                } elseif ($estado_item['nombre'] == 'Aprobada') {
                                    $icono = 'fas fa-check-circle';
                                    $clase = 'text-success';
                                } elseif ($estado_item['nombre'] == 'Rechazada') {
                                    $icono = 'fas fa-times-circle';
                                    $clase = 'text-danger';
                                }
                            ?>
                                <span class="me-2 <?php echo $clase; ?>">
                                    <i class="<?php echo $icono; ?>"></i> 
                                    <?php echo $estado_item['total']; ?> <?php echo strtolower($estado_item['nombre']); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos de análisis -->
<div class="row">
    <!-- Gráfico de evolución temporal de solicitudes -->
    <div class="col-xl-8 col-lg-7 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-normal text-primary">
                    <i class="fas fa-chart-area me-1"></i> Evolución de Solicitudes
                </h6>
                <div class="dropdown no-arrow">
                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                        <div class="dropdown-header">Opciones:</div>
                        <a class="dropdown-item" href="#" id="showYearView">Vista Anual</a>
                        <a class="dropdown-item" href="#" id="showMonthView">Vista Mensual</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#" id="exportChartBtn">Exportar Datos</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="solicitudesTimelineChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gráficos circulares -->
    <div class="col-xl-4 col-lg-5 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-normal text-primary">
                    <i class="fas fa-chart-pie me-1"></i> Distribución por Estado
                </h6>
                <div class="dropdown no-arrow">
                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                        <div class="dropdown-header">Vistas:</div>
                        <a class="dropdown-item" href="#" id="viewDoughnut">Vista Dona</a>
                        <a class="dropdown-item" href="#" id="viewPie">Vista Pastel</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-pie pt-4 pb-2">
                    <canvas id="solicitudesPorEstadoChart"></canvas>
                </div>
                <div class="mt-4 text-center small">
                    <?php foreach($solicitudes_por_estado as $estado_item): ?>
                        <span class="me-2">
                            <i class="fas fa-circle" style="color: <?php echo $estado_item['color']; ?>"></i> <?php echo $estado_item['nombre']; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-normal text-primary">
                    <i class="fas fa-tags me-1"></i> Solicitudes por Categoría
                </h6>
            </div>
            <div class="card-body">
                <div class="chart-pie pt-4 pb-2">
                    <canvas id="solicitudesPorCategoriaChart"></canvas>
                </div>
                <div class="mt-4 text-center small">
                    <?php foreach($categorias_array as $index => $cat): ?>
                        <?php if ($index < 3): ?>
                        <span class="me-2">
                            <i class="fas fa-circle" style="color: <?php echo $cat['color']; ?>"></i> <?php echo $cat['nombre']; ?>
                        </span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if (count($categorias_array) > 3): ?>
                        <span class="me-2">
                            <i class="fas fa-circle text-gray-500"></i> Otras
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Actividad Reciente y Top Funcionarios -->
<div class="row">
    <!-- Actividad Reciente -->
    <div class="col-lg-7 mb-4">
        <div class="card shadow">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-normal text-primary">
                    <i class="fas fa-history me-1"></i> Actividad Reciente
                </h6>
                <a href="#" class="btn btn-sm btn-primary" id="verTodasActividadesBtn">
                    Ver Todas
                </a>
            </div>
            <div class="card-body">
                <div class="timeline timeline-xs">
                    <?php foreach($actividad_reciente as $actividad): ?>
                    <div class="timeline-item">
                        <div class="timeline-item-marker">
                            <div class="timeline-item-marker-indicator" style="background-color: <?php echo $actividad['color']; ?>">
                                <?php if ($actividad['tipo'] == 'solicitud'): ?>
                                    <i class="fas fa-clipboard-list"></i>
                                <?php elseif ($actividad['tipo'] == 'sugerencia'): ?>
                                    <i class="fas fa-lightbulb"></i>
                                <?php else: ?>
                                    <i class="fas fa-user"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="timeline-item-content pt-0">
                            <div class="timeline-item-title">
                                <?php if ($actividad['tipo'] == 'solicitud'): ?>
                                    <a href="index.php?page=admin_ver_solicitud&id=<?php echo $actividad['id']; ?>">
                                        Nueva solicitud
                                    </a>
                                <?php elseif ($actividad['tipo'] == 'sugerencia'): ?>
                                    <a href="index.php?page=admin_ver_sugerencia&id=<?php echo $actividad['id']; ?>">
                                        Nueva sugerencia
                                    </a>
                                <?php else: ?>
                                    <a href="index.php?page=admin_ver_usuario&id=<?php echo $actividad['id']; ?>">
                                        Nuevo usuario
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="timeline-item-subtitle">
                                <?php echo Security::escapeOutput($actividad['descripcion']); ?>
                            </div>
                            <div class="timeline-item-content d-flex justify-content-between">
                                <div>
                                    <small class="text-muted">
                                        Por <?php echo Security::escapeOutput($actividad['usuario_nombre'] . ' ' . $actividad['usuario_apellidos']); ?>
                                    </small>
                                </div>
                                <div>
                                    <span class="badge rounded-pill" style="background-color: <?php echo $actividad['color']; ?>">
                                        <?php echo $actividad['estado']; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="timeline-item-time">
                                <?php echo date('d/m/Y H:i', strtotime($actividad['fecha'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Top Funcionarios -->
    <div class="col-lg-5 mb-4">
        <div class="card shadow">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-normal text-primary">
                    <i class="fas fa-trophy me-1"></i> Top Funcionarios
                </h6>
                <a href="index.php?page=admin_funcionarios" class="btn btn-sm btn-primary">
                    Ver Todos
                </a>
            </div>
            <div class="card-body">
                <?php if (count($top_funcionarios) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Funcionario</th>
                                    <th>Resueltas</th>
                                    <th>Tiempo</th>
                                    <th>Ciudadanos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($top_funcionarios as $funcionario): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-2">
                                                <div class="avatar-title rounded-circle bg-primary text-white">
                                                    <?php echo strtoupper(substr($funcionario['nombre'], 0, 1) . substr($funcionario['apellidos'], 0, 1)); ?>
                                                </div>
                                            </div>
                                            <div class="ms-2">
                                                <a href="index.php?page=admin_ver_usuario&id=<?php echo $funcionario['id']; ?>">
                                                    <?php echo Security::escapeOutput($funcionario['nombre'] . ' ' . $funcionario['apellidos']); ?>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success">
                                            <?php echo $funcionario['solicitudes_resueltas']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php echo round($funcionario['tiempo_promedio'], 1); ?> días
                                    </td>
                                    <td class="text-center">
                                        <?php echo $funcionario['ciudadanos_atendidos']; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-1"></i> No hay datos suficientes para mostrar.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Tiempo Promedio por Categoría -->
        <div class="card shadow mt-4">
            <div class="card-header py-3">
                <h6 class="m-0 fw-normal text-primary">
                    <i class="fas fa-hourglass-half me-1"></i> Tiempo Promedio por Categoría
                </h6>
            </div>
            <div class="card-body">
                <?php if (count($tiempo_categoria) > 0): ?>
                    <?php foreach($tiempo_categoria as $cat): ?>
                    <h4 class="small fw-normal">
                        <?php echo Security::escapeOutput($cat['nombre']); ?>
                        <span class="float-end"><?php echo round($cat['tiempo_promedio'], 1); ?> días</span>
                    </h4>
                    <div class="progress mb-4">
                        <div class="progress-bar" role="progressbar" style="width: <?php echo min(100, round($cat['tiempo_promedio'] * 10)); ?>%; background-color: <?php echo $cat['color']; ?>"></div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-1"></i> No hay datos suficientes para mostrar.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Scripts para gráficos -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de evolución temporal de solicitudes
    const timelineCtx = document.getElementById('solicitudesTimelineChart').getContext('2d');
    const timelineChart = new Chart(timelineCtx, {
        type: 'line',
        data: {
            labels: [
                <?php 
                foreach($timeline_data as $data) {
                    $fecha = date('M Y', strtotime($data['mes'] . '-01'));
                    echo "'" . $fecha . "',";
                }
                ?>
            ],
            datasets: [{
                label: 'Total de Solicitudes',
                data: [
                    <?php 
                    foreach($timeline_data as $data) {
                        echo $data['total'] . ",";
                    }
                    ?>
                ],
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
            labels: [
                <?php foreach($solicitudes_por_estado as $estado_item): ?>
                '<?php echo $estado_item['nombre']; ?> (<?php echo $estado_item['total']; ?>)',
                <?php endforeach; ?>
            ],
            datasets: [{
                data: [
                    <?php foreach($solicitudes_por_estado as $estado_item): ?>
                    <?php echo $estado_item['total']; ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: [
                    <?php foreach($solicitudes_por_estado as $estado_item): ?>
                    '<?php echo $estado_item['color']; ?>',
                    <?php endforeach; ?>
                ],
                hoverBackgroundColor: [
                    <?php foreach($solicitudes_por_estado as $estado_item): ?>
                    '<?php echo $estado_item['color']; ?>',
                    <?php endforeach; ?>
                ],
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
            labels: [
                <?php 
                $count = 0;
                $otros_total = 0;
                foreach($categorias_array as $index => $cat): 
                    if ($count < 3):
                        echo "'" . $cat['nombre'] . " (" . $cat['total'] . ")',";
                        $count++;
                    else:
                        $otros_total += $cat['total'];
                    endif;
                endforeach;
                if ($otros_total > 0):
                    echo "'Otros (" . $otros_total . ")',";
                endif;
                ?>
            ],
            datasets: [{
                data: [
                    <?php 
                    $count = 0;
                    foreach($categorias_array as $index => $cat): 
                        if ($count < 3):
                            echo $cat['total'] . ",";
                            $count++;
                        endif;
                    endforeach;
                    if ($otros_total > 0):
                        echo $otros_total . ",";
                    endif;
                    ?>
                ],
                backgroundColor: [
                    <?php 
                    $count = 0;
                    foreach($categorias_array as $index => $cat): 
                        if ($count < 3):
                            echo "'" . $cat['color'] . "',";
                            $count++;
                        endif;
                    endforeach;
                    if ($otros_total > 0):
                        echo "'#7c8798',";
                    endif;
                    ?>
                ],
                hoverBackgroundColor: [
                    <?php 
                    $count = 0;
                    foreach($categorias_array as $index => $cat): 
                        if ($count < 3):
                            echo "'" . $cat['color'] . "',";
                            $count++;
                        endif;
                    endforeach;
                    if ($otros_total > 0):
                        echo "'#5a6268',";
                    endif;
                    ?>
                ],
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
</script>

<!-- Estilos adicionales para timeline -->
<style>
.timeline {
    position: relative;
    padding-left: 1rem;
    margin: 0 0 0 1rem;
    color: #6c757d;
    font-family: Arial, sans-serif;
}
.timeline:before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 2px;
    background-color: #e9ecef;
}
.timeline-item {
    position: relative;
    padding-bottom: 1.5rem;
}
.timeline-item:last-child {
    padding-bottom: 0;
}
.timeline-item-marker {
    position: absolute;
    left: -1.5rem;
    width: 1rem;
    height: 1rem;
    margin-top: 0.25rem;
}
.timeline-item-marker-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 1.5rem;
    width: 1.5rem;
    border-radius: 100%;
    color: #fff;
    font-size: 0.675rem;
}
.timeline-item-content {
    padding-left: 0.75rem;
    padding-top: 0.25rem;
}
.timeline-item-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #343a40;
}
.timeline-item-subtitle {
    margin-top: 0.25rem;
    font-size: 0.8rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.timeline-item-time {
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: #adb5bd;
}
.avatar {
    display: inline-flex;
    height: 2rem;
    width: 2rem;
    position: relative;
}
.avatar.avatar-sm {
    height: 1.5rem;
    width: 1.5rem;
}
.avatar-title {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 600;
    font-size: 0.75rem;
}
.icon-circle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 2.5rem;
    width: 2.5rem;
    border-radius: 100%;
    font-size: 1rem;
}
</style>
|