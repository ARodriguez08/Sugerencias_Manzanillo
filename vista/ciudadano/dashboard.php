<?php
// Incluir el security.php
include_once 'config/security.php';

// Inicializar variables
$csrf_token = Security::generateCSRFToken();

// Incluir modelos necesarios
if (!class_exists('Solicitud')) {
    include_once 'modelo/Solicitud.php';
}
if (!class_exists('Sugerencia')) {
    include_once 'modelo/Sugerencia.php';
}
if (!class_exists('Notification')) {
    include_once 'config/notification.php';
}

// Obtener la conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Inicializar objetos
$solicitud = new Solicitud($db);
$sugerencia = new Sugerencia($db);
$notificacion = new Notification($db);

// Definir el ID del usuario actual
$usuario_id = $_SESSION['usuario_id'];

// Obtener estadísticas
// 1. Total de solicitudes del usuario
$query_total_solicitudes = "SELECT COUNT(*) as total FROM solicitudes WHERE usuario_id = :usuario_id";
$stmt_total_solicitudes = $db->prepare($query_total_solicitudes);
$stmt_total_solicitudes->bindParam(':usuario_id', $usuario_id);
$stmt_total_solicitudes->execute();
$total_solicitudes = $stmt_total_solicitudes->fetch(PDO::FETCH_ASSOC)['total'];

// 2. Solicitudes por estado
$query_por_estado = "SELECT e.nombre, e.color, COUNT(s.id) as total 
                     FROM solicitudes s 
                     JOIN estados e ON s.estado_id = e.id 
                     WHERE s.usuario_id = :usuario_id 
                     GROUP BY s.estado_id";
$stmt_por_estado = $db->prepare($query_por_estado);
$stmt_por_estado->bindParam(':usuario_id', $usuario_id);
$stmt_por_estado->execute();
$solicitudes_por_estado = $stmt_por_estado->fetchAll(PDO::FETCH_ASSOC);

// 3. Total de sugerencias del usuario
$query_total_sugerencias = "SELECT COUNT(*) as total FROM sugerencias WHERE usuario_id = :usuario_id";
$stmt_total_sugerencias = $db->prepare($query_total_sugerencias);
$stmt_total_sugerencias->bindParam(':usuario_id', $usuario_id);
$stmt_total_sugerencias->execute();
$total_sugerencias = $stmt_total_sugerencias->fetch(PDO::FETCH_ASSOC)['total'];

// 4. Solicitudes recientes (últimas 5)
$query_recientes = "SELECT s.id, s.titulo, s.fecha_creacion, c.nombre as categoria, c.color as categoria_color, 
                    e.nombre as estado, e.color as estado_color 
                    FROM solicitudes s 
                    JOIN categorias c ON s.categoria_id = c.id 
                    JOIN estados e ON s.estado_id = e.id 
                    WHERE s.usuario_id = :usuario_id 
                    ORDER BY s.fecha_creacion DESC LIMIT 5";
$stmt_recientes = $db->prepare($query_recientes);
$stmt_recientes->bindParam(':usuario_id', $usuario_id);
$stmt_recientes->execute();
$solicitudes_recientes = $stmt_recientes->fetchAll(PDO::FETCH_ASSOC);

// 5. Sugerencias recientes (últimas 3)
$query_sugerencias = "SELECT s.id, s.titulo, s.fecha_creacion, s.estado_id, e.nombre as estado, e.color as estado_color 
                      FROM sugerencias s 
                      JOIN estados e ON s.estado_id = e.id 
                      WHERE s.usuario_id = :usuario_id 
                      ORDER BY s.fecha_creacion DESC LIMIT 3";
$stmt_sugerencias = $db->prepare($query_sugerencias);
$stmt_sugerencias->bindParam(':usuario_id', $usuario_id);
$stmt_sugerencias->execute();
$sugerencias_recientes = $stmt_sugerencias->fetchAll(PDO::FETCH_ASSOC);

// 6. Obtener notificaciones recientes
$notificaciones = $notificacion->obtenerNoLeidas($usuario_id);
$notificaciones_array = [];
while ($row = $notificaciones->fetch(PDO::FETCH_ASSOC)) {
    $notificaciones_array[] = $row;
}

// 7. Datos para el gráfico de solicitudes por categoría
$query_categorias = "SELECT c.nombre, c.color, COUNT(s.id) as total 
                     FROM solicitudes s 
                     JOIN categorias c ON s.categoria_id = c.id 
                     WHERE s.usuario_id = :usuario_id 
                     GROUP BY s.categoria_id";
$stmt_categorias = $db->prepare($query_categorias);
$stmt_categorias->bindParam(':usuario_id', $usuario_id);
$stmt_categorias->execute();
$categorias_data = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

// 8. Datos para el gráfico de timeline de solicitudes
$query_timeline = "SELECT DATE_FORMAT(fecha_creacion, '%Y-%m') as mes, COUNT(*) as total 
                   FROM solicitudes 
                   WHERE usuario_id = :usuario_id 
                   GROUP BY DATE_FORMAT(fecha_creacion, '%Y-%m') 
                   ORDER BY mes ASC 
                   LIMIT 6";
$stmt_timeline = $db->prepare($query_timeline);
$stmt_timeline->bindParam(':usuario_id', $usuario_id);
$stmt_timeline->execute();
$timeline_data = $stmt_timeline->fetchAll(PDO::FETCH_ASSOC);

// 9. Tiempo promedio de resolución de solicitudes
$query_tiempo = "SELECT AVG(DATEDIFF(fecha_resolucion, fecha_creacion)) as promedio 
                 FROM solicitudes 
                 WHERE usuario_id = :usuario_id AND fecha_resolucion IS NOT NULL";
$stmt_tiempo = $db->prepare($query_tiempo);
$stmt_tiempo->bindParam(':usuario_id', $usuario_id);
$stmt_tiempo->execute();
$tiempo_promedio = $stmt_tiempo->fetch(PDO::FETCH_ASSOC)['promedio'];
$tiempo_promedio = $tiempo_promedio ? round($tiempo_promedio, 1) : 'N/A';
?>

<!-- Cabecera del Dashboard -->
<div class="row">
    <div class="col-md-12">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard Ciudadano
            </h1>
            <div>
                <a href="index.php?page=nueva_solicitud" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus-circle me-1"></i> Nueva Solicitud
                </a>
                <a href="index.php?page=nueva_sugerencia" class="btn btn-success btn-sm ms-2">
                    <i class="fas fa-lightbulb me-1"></i> Nueva Sugerencia
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Resumen de Solicitudes (Tarjetas) -->
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="small text-primary text-uppercase mb-1">
                            Total Solicitudes</div>
                        <div class="h5 mb-0 fw-normal text-dark"><?php echo $total_solicitudes; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                    </div>
                </div>
                <div class="progress progress-sm mt-2">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: 100%"></div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="index.php?page=ciudadano_historial" class="text-primary">Ver detalles <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="small text-success text-uppercase mb-1">
                            Sugerencias</div>
                        <div class="h5 mb-0 fw-normal text-dark"><?php echo $total_sugerencias; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-lightbulb fa-2x text-gray-300"></i>
                    </div>
                </div>
                <div class="progress progress-sm mt-2">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="index.php?page=ciudadano_sugerencias" class="text-success">Ver detalles <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="small text-info text-uppercase mb-1">
                            Tiempo Promedio Resolución</div>
                        <div class="h5 mb-0 fw-normal text-dark">
                            <?php echo $tiempo_promedio; ?> días
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                    </div>
                </div>
                <div class="progress progress-sm mt-2">
                    <div class="progress-bar bg-info" role="progressbar" style="width: 100%"></div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <span class="text-info small">Promedio de tiempo de atención</span>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="small text-warning text-uppercase mb-1">
                            Solicitudes Pendientes</div>
                        <?php
                        $pendientes = 0;
                        foreach($solicitudes_por_estado as $estado) {
                            if ($estado['nombre'] == 'Nueva' || $estado['nombre'] == 'Asignada' || $estado['nombre'] == 'En proceso') {
                                $pendientes += $estado['total'];
                            }
                        }
                        ?>
                        <div class="h5 mb-0 fw-normal text-dark"><?php echo $pendientes; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-hourglass-half fa-2x text-gray-300"></i>
                    </div>
                </div>
                <div class="progress progress-sm mt-2">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: 100%"></div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="index.php?page=ciudadano_historial?estado=pendiente" class="text-warning">Ver detalles <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos y Estadísticas -->
<div class="row">
    <!-- Gráfico de Distribución por Estado -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-normal text-primary">
                    <i class="fas fa-chart-pie me-1"></i> Estado de Solicitudes
                </h6>
            </div>
            <div class="card-body">
                <div class="chart-pie pt-4 pb-2">
                    <canvas id="solicitudesPorEstadoChart"></canvas>
                </div>
                <div class="mt-4 text-center small">
                    <?php foreach($solicitudes_por_estado as $estado): ?>
                        <span class="me-2">
                            <i class="fas fa-circle" style="color: <?php echo $estado['color']; ?>"></i> <?php echo $estado['nombre']; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico de Timeline -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-normal text-primary">
                    <i class="fas fa-chart-line me-1"></i> Historial de Solicitudes
                </h6>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="solicitudesTimelineChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Solicitudes Recientes y Notificaciones -->
<div class="row">
    <!-- Solicitudes Recientes -->
    <div class="col-lg-7 mb-4">
        <div class="card shadow">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-normal text-primary">
                    <i class="fas fa-clipboard-list me-1"></i> Solicitudes Recientes
                </h6>
                <a href="index.php?page=ciudadano_historial" class="btn btn-sm btn-primary">
                    Ver todas
                </a>
            </div>
            <div class="card-body">
                <?php if (count($solicitudes_recientes) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Título</th>
                                    <th>Categoría</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($solicitudes_recientes as $solicitud): ?>
                                <tr>
                                    <td><?php echo $solicitud['id']; ?></td>
                                    <td><?php echo Security::escapeOutput($solicitud['titulo']); ?></td>
                                    <td>
                                        <span class="badge rounded-pill" style="background-color: <?php echo $solicitud['categoria_color']; ?>">
                                            <?php echo $solicitud['categoria']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill" style="background-color: <?php echo $solicitud['estado_color']; ?>">
                                            <?php echo $solicitud['estado']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($solicitud['fecha_creacion'])); ?></td>
                                    <td>
                                        <a href="index.php?page=ver_solicitud&id=<?php echo $solicitud['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-1"></i> No tienes solicitudes recientes.
                        <a href="index.php?page=nueva_solicitud" class="alert-link">¡Crea tu primera solicitud!</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Notificaciones y Sugerencias -->
    <div class="col-lg-5 mb-4">
        <!-- Notificaciones -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-normal text-primary">
                    <i class="fas fa-bell me-1"></i> Notificaciones
                </h6>
                <a href="index.php?page=notificaciones" class="btn btn-sm btn-primary">
                    Ver todas
                </a>
            </div>
            <div class="card-body">
                <?php if (count($notificaciones_array) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach($notificaciones_array as $notif): ?>
                        <a href="index.php?page=ver_notificacion&id=<?php echo $notif['id']; ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php echo Security::escapeOutput($notif['titulo']); ?></h6>
                                <small><?php echo date('d/m H:i', strtotime($notif['fecha_creacion'])); ?></small>
                            </div>
                            <p class="mb-1 text-truncate"><?php echo Security::escapeOutput($notif['mensaje']); ?></p>
                        </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-1"></i> No tienes notificaciones nuevas.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Sugerencias Recientes -->
        <div class="card shadow">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-normal text-primary">
                    <i class="fas fa-lightbulb me-1"></i> Mis Sugerencias
                </h6>
                <a href="index.php?page=ciudadano_sugerencias" class="btn btn-sm btn-primary">
                    Ver todas
                </a>
            </div>
            <div class="card-body">
                <?php if (count($sugerencias_recientes) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach($sugerencias_recientes as $sugerencia): ?>
                        <a href="index.php?page=ver_sugerencia&id=<?php echo $sugerencia['id']; ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php echo Security::escapeOutput($sugerencia['titulo']); ?></h6>
                                <span class="badge rounded-pill" style="background-color: <?php echo $sugerencia['estado_color']; ?>">
                                    <?php echo $sugerencia['estado']; ?>
                                </span>
                            </div>
                            <small class="text-muted">Enviada el <?php echo date('d/m/Y', strtotime($sugerencia['fecha_creacion'])); ?></small>
                        </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-1"></i> No has enviado sugerencias.
                        <a href="index.php?page=nueva_sugerencia" class="alert-link">¡Comparte tus ideas!</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Accesos Rápidos -->
<div class="row">
    <div class="col-lg-12 mb-4">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 fw-normal text-primary">
                    <i class="fas fa-rocket me-1"></i> Accesos Rápidos
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3 mb-3">
                        <a href="index.php?page=nueva_solicitud" class="btn btn-light btn-icon-split btn-lg w-100 h-100 d-flex flex-column justify-content-center align-items-center p-4">
                            <span class="icon text-primary mb-3">
                                <i class="fas fa-plus-circle fa-3x"></i>
                            </span>
                            <span class="text">Nueva Solicitud</span>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="index.php?page=nueva_sugerencia" class="btn btn-light btn-icon-split btn-lg w-100 h-100 d-flex flex-column justify-content-center align-items-center p-4">
                            <span class="icon text-success mb-3">
                                <i class="fas fa-lightbulb fa-3x"></i>
                            </span>
                            <span class="text">Nueva Sugerencia</span>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="index.php?page=ciudadano_historial" class="btn btn-light btn-icon-split btn-lg w-100 h-100 d-flex flex-column justify-content-center align-items-center p-4">
                            <span class="icon text-info mb-3">
                                <i class="fas fa-history fa-3x"></i>
                            </span>
                            <span class="text">Mi Historial</span>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="index.php?page=perfil" class="btn btn-light btn-icon-split btn-lg w-100 h-100 d-flex flex-column justify-content-center align-items-center p-4">
                            <span class="icon text-secondary mb-3">
                                <i class="fas fa-user-cog fa-3x"></i>
                            </span>
                            <span class="text">Mi Perfil</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts para gráficos -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de solicitudes por estado
    const estadoCtx = document.getElementById('solicitudesPorEstadoChart').getContext('2d');
    const estadoChart = new Chart(estadoCtx, {
        type: 'doughnut',
        data: {
            labels: [
                <?php foreach($solicitudes_por_estado as $estado): ?>
                '<?php echo $estado['nombre']; ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                data: [
                    <?php foreach($solicitudes_por_estado as $estado): ?>
                    <?php echo $estado['total']; ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: [
                    <?php foreach($solicitudes_por_estado as $estado): ?>
                    '<?php echo $estado['color']; ?>',
                    <?php endforeach; ?>
                ],
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

    // Gráfico de timeline de solicitudes
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
                label: 'Solicitudes',
                data: [
                    <?php 
                    foreach($timeline_data as $data) {
                        echo $data['total'] . ",";
                    }
                    ?>
                ],
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
});
</script>
