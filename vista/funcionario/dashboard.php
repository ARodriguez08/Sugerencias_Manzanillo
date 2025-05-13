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
if (!class_exists('Categoria')) {
    include_once 'modelo/Categoria.php';
}
if (!class_exists('Estado')) {
    include_once 'modelo/Estado.php';
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
$categoria = new Categoria($db);
$estado = new Estado($db);
$notificacion = new Notification($db);

// Definir el ID del funcionario actual
$funcionario_id = $_SESSION['usuario_id'];

// Obtener estadísticas
// 1. Total de solicitudes asignadas al funcionario
$query_total_solicitudes = "SELECT COUNT(*) as total FROM solicitudes WHERE funcionario_id = :funcionario_id";
$stmt_total_solicitudes = $db->prepare($query_total_solicitudes);
$stmt_total_solicitudes->bindParam(':funcionario_id', $funcionario_id);
$stmt_total_solicitudes->execute();
$total_solicitudes = $stmt_total_solicitudes->fetch(PDO::FETCH_ASSOC)['total'];

// 2. Solicitudes por estado asignadas al funcionario
$query_por_estado = "SELECT e.nombre, e.color, COUNT(s.id) as total 
                     FROM solicitudes s 
                     JOIN estados e ON s.estado_id = e.id 
                     WHERE s.funcionario_id = :funcionario_id 
                     GROUP BY s.estado_id";
$stmt_por_estado = $db->prepare($query_por_estado);
$stmt_por_estado->bindParam(':funcionario_id', $funcionario_id);
$stmt_por_estado->execute();
$solicitudes_por_estado = $stmt_por_estado->fetchAll(PDO::FETCH_ASSOC);

// 3. Sugerencias pendientes de revisión
$query_sugerencias_pendientes = "SELECT COUNT(*) as total FROM sugerencias WHERE estado_id IN (1, 2)"; // 1: Nueva, 2: En revisión
$stmt_sugerencias_pendientes = $db->prepare($query_sugerencias_pendientes);
$stmt_sugerencias_pendientes->execute();
$sugerencias_pendientes = $stmt_sugerencias_pendientes->fetch(PDO::FETCH_ASSOC)['total'];

// 4. Solicitudes recientes asignadas al funcionario (últimas 8)
$query_recientes = "SELECT s.id, s.titulo, s.fecha_creacion, c.nombre as categoria, c.color as categoria_color, 
                    e.nombre as estado, e.color as estado_color, u.nombre as nombre_ciudadano, u.apellidos as apellidos_ciudadano
                    FROM solicitudes s 
                    JOIN categorias c ON s.categoria_id = c.id 
                    JOIN estados e ON s.estado_id = e.id 
                    JOIN usuarios u ON s.usuario_id = u.id
                    WHERE s.funcionario_id = :funcionario_id 
                    ORDER BY 
                        CASE 
                            WHEN s.estado_id = 2 THEN 1 /* Asignada */
                            WHEN s.estado_id = 3 THEN 2 /* En proceso */
                            ELSE 3
                        END,
                        s.fecha_creacion DESC 
                    LIMIT 8";
$stmt_recientes = $db->prepare($query_recientes);
$stmt_recientes->bindParam(':funcionario_id', $funcionario_id);
$stmt_recientes->execute();
$solicitudes_recientes = $stmt_recientes->fetchAll(PDO::FETCH_ASSOC);

// 5. Sugerencias pendientes de revisión (últimas 5)
$query_sugerencias = "SELECT s.id, s.titulo, s.fecha_creacion, 
                      c.nombre as categoria, c.color as categoria_color,
                      e.nombre as estado, e.color as estado_color,
                      u.nombre as nombre_ciudadano, u.apellidos as apellidos_ciudadano
                      FROM sugerencias s 
                      JOIN estados e ON s.estado_id = e.id 
                      JOIN categorias c ON s.categoria_id = c.id
                      JOIN usuarios u ON s.usuario_id = u.id
                      WHERE s.estado_id IN (1, 2) 
                      ORDER BY s.fecha_creacion DESC 
                      LIMIT 5";
$stmt_sugerencias = $db->prepare($query_sugerencias);
$stmt_sugerencias->execute();
$sugerencias_pendientes_list = $stmt_sugerencias->fetchAll(PDO::FETCH_ASSOC);

// 6. Datos para el gráfico de rendimiento mensual
$query_rendimiento = "SELECT 
                      DATE_FORMAT(fecha_resolucion, '%Y-%m') as mes, 
                      COUNT(*) as resueltas,
                      AVG(DATEDIFF(fecha_resolucion, fecha_creacion)) as tiempo_promedio
                      FROM solicitudes 
                      WHERE funcionario_id = :funcionario_id 
                      AND fecha_resolucion IS NOT NULL 
                      GROUP BY DATE_FORMAT(fecha_resolucion, '%Y-%m') 
                      ORDER BY mes ASC 
                      LIMIT 6";
$stmt_rendimiento = $db->prepare($query_rendimiento);
$stmt_rendimiento->bindParam(':funcionario_id', $funcionario_id);
$stmt_rendimiento->execute();
$rendimiento_data = $stmt_rendimiento->fetchAll(PDO::FETCH_ASSOC);

// 7. Datos para el gráfico de solicitudes por categoría
$query_categorias = "SELECT c.nombre, c.color, COUNT(s.id) as total 
                     FROM solicitudes s 
                     JOIN categorias c ON s.categoria_id = c.id 
                     WHERE s.funcionario_id = :funcionario_id 
                     GROUP BY s.categoria_id
                     ORDER BY total DESC";
$stmt_categorias = $db->prepare($query_categorias);
$stmt_categorias->bindParam(':funcionario_id', $funcionario_id);
$stmt_categorias->execute();
$categorias_data = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

// 8. Estados disponibles para actualizar solicitudes
$estados_disponibles = $estado->obtenerTodos();

// 9. Tiempo promedio de resolución de solicitudes
$query_tiempo = "SELECT AVG(DATEDIFF(fecha_resolucion, fecha_creacion)) as promedio 
                 FROM solicitudes 
                 WHERE funcionario_id = :funcionario_id AND fecha_resolucion IS NOT NULL";
$stmt_tiempo = $db->prepare($query_tiempo);
$stmt_tiempo->bindParam(':funcionario_id', $funcionario_id);
$stmt_tiempo->execute();
$tiempo_promedio = $stmt_tiempo->fetch(PDO::FETCH_ASSOC)['promedio'];
$tiempo_promedio = $tiempo_promedio ? round($tiempo_promedio, 1) : 'N/A';

// 10. Top categorías con más tiempo de resolución
$query_top_categorias = "SELECT c.nombre, c.color, AVG(DATEDIFF(s.fecha_resolucion, s.fecha_creacion)) as tiempo_promedio, COUNT(s.id) as total
                         FROM solicitudes s
                         JOIN categorias c ON s.categoria_id = c.id
                         WHERE s.funcionario_id = :funcionario_id AND s.fecha_resolucion IS NOT NULL
                         GROUP BY s.categoria_id
                         ORDER BY tiempo_promedio DESC
                         LIMIT 3";
$stmt_top_categorias = $db->prepare($query_top_categorias);
$stmt_top_categorias->bindParam(':funcionario_id', $funcionario_id);
$stmt_top_categorias->execute();
$top_categorias = $stmt_top_categorias->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Cabecera del Dashboard -->
<div class="row">
    <div class="col-md-12">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-briefcase me-2"></i>Dashboard de Funcionario
            </h1>
            <div>
                <a href="index.php?page=funcionario_solicitudes" class="btn btn-primary btn-sm">
                    <i class="fas fa-clipboard-list me-1"></i> Gestionar Solicitudes
                </a>
                <a href="index.php?page=funcionario_sugerencias" class="btn btn-success btn-sm ms-2">
                    <i class="fas fa-lightbulb me-1"></i> Revisar Sugerencias
                </a>
                <a href="index.php?page=funcionario_reportes" class="btn btn-info btn-sm ms-2">
                    <i class="fas fa-chart-bar me-1"></i> Reportes
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
                        <div class="text-xs fw-normal text-primary text-uppercase mb-1">
                            Solicitudes Asignadas</div>
                        <div class="h5 mb-0 fw-normal text-gray-800"><?php echo $total_solicitudes; ?></div>
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
                <a href="index.php?page=funcionario_solicitudes" class="text-primary">Ver todas <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-normal text-success text-uppercase mb-1">
                            Solicitudes Resueltas</div>
                        <?php
                        $resueltas = 0;
                        foreach($solicitudes_por_estado as $estado) {
                            if ($estado['nombre'] == 'Resuelta') {
                                $resueltas = $estado['total'];
                                break;
                            }
                        }
                        ?>
                        <div class="h5 mb-0 fw-normal text-gray-800"><?php echo $resueltas; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
                <div class="progress progress-sm mt-2">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="index.php?page=funcionario_solicitudes?estado=resuelta" class="text-success">Ver detalles <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-normal text-info text-uppercase mb-1">
                            Tiempo Promedio Resolución</div>
                        <div class="h5 mb-0 fw-normal text-gray-800">
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
                <span class="text-info small">Promedio de tiempo de resolución</span>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-normal text-warning text-uppercase mb-1">
                            Sugerencias Pendientes</div>
                        <div class="h5 mb-0 fw-normal text-gray-800"><?php echo $sugerencias_pendientes; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-lightbulb fa-2x text-gray-300"></i>
                    </div>
                </div>
                <div class="progress progress-sm mt-2">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: 100%"></div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="index.php?page=funcionario_sugerencias" class="text-warning">Revisar <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos y Estadísticas -->
<div class="row">
    <!-- Gráfico de Rendimiento Mensual -->
    <div class="col-lg-8 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-normal text-primary">
                    <i class="fas fa-chart-line me-1"></i> Rendimiento Mensual
                </h6>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="rendimientoChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico de Distribución por Categoría -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-normal text-primary">
                    <i class="fas fa-chart-pie me-1"></i> Solicitudes por Categoría
                </h6>
            </div>
            <div class="card-body">
                <div class="chart-pie pt-4 pb-2">
                    <canvas id="categoriasChart"></canvas>
                </div>
                <div class="mt-4 text-center small">
                    <?php foreach($categorias_data as $cat): ?>
                        <span class="me-2">
                            <i class="fas fa-circle" style="color: <?php echo $cat['color']; ?>"></i> <?php echo $cat['nombre']; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Solicitudes Pendientes y Sugerencias Pendientes -->
<div class="row">
    <!-- Solicitudes Pendientes -->
    <div class="col-lg-8 mb-4">
        <div class="card shadow">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-normal text-primary">
                    <i class="fas fa-clipboard-list me-1"></i> Solicitudes Pendientes
                </h6>
                <a href="index.php?page=funcionario_solicitudes" class="btn btn-sm btn-primary">
                    Ver todas
                </a>
            </div>
            <div class="card-body">
                <?php if (count($solicitudes_recientes) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0 datatable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Título</th>
                                    <th>Categoría</th>
                                    <th>Estado</th>
                                    <th>Ciudadano</th>
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
                                    <td><?php echo Security::escapeOutput($solicitud['nombre_ciudadano'] . ' ' . $solicitud['apellidos_ciudadano']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($solicitud['fecha_creacion'])); ?></td>
                                    <td>
                                        <a href="index.php?page=funcionario_ver_solicitud&id=<?php echo $solicitud['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#actualizarEstadoModal" 
                                                data-id="<?php echo $solicitud['id']; ?>"
                                                data-titulo="<?php echo htmlspecialchars($solicitud['titulo'], ENT_QUOTES); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-1"></i> No tienes solicitudes pendientes asignadas.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sugerencias Pendientes y Categorías más Lentas -->
    <div class="col-lg-4 mb-4">
        <!-- Sugerencias Pendientes -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-normal text-primary">
                    <i class="fas fa-lightbulb me-1"></i> Sugerencias Pendientes
                </h6>
                <a href="index.php?page=funcionario_sugerencias" class="btn btn-sm btn-primary">
                    Ver todas
                </a>
            </div>
            <div class="card-body">
                <?php if (count($sugerencias_pendientes_list) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach($sugerencias_pendientes_list as $sugerencia): ?>
                        <a href="index.php?page=funcionario_ver_sugerencia&id=<?php echo $sugerencia['id']; ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php echo Security::escapeOutput($sugerencia['titulo']); ?></h6>
                                <span class="badge rounded-pill" style="background-color: <?php echo $sugerencia['estado_color']; ?>">
                                    <?php echo $sugerencia['estado']; ?>
                                </span>
                            </div>
                            <div class="d-flex w-100 justify-content-between">
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i> <?php echo Security::escapeOutput($sugerencia['nombre_ciudadano'] . ' ' . $sugerencia['apellidos_ciudadano']); ?>
                                </small>
                                <small class="text-muted"><?php echo date('d/m/Y', strtotime($sugerencia['fecha_creacion'])); ?></small>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-1"></i> No hay sugerencias pendientes de revisión.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Categorías con más tiempo de resolución -->
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 fw-normal text-primary">
                    <i class="fas fa-hourglass-half me-1"></i> Categorías Más Lentas
                </h6>
            </div>
            <div class="card-body">
                <?php if (count($top_categorias) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach($top_categorias as $index => $cat): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <h6 class="mb-1">
                                    <span class="badge rounded-pill me-2" style="background-color: <?php echo $cat['color']; ?>">
                                        <?php echo ($index + 1); ?>
                                    </span>
                                    <?php echo $cat['nombre']; ?>
                                </h6>
                                <span class="text-muted small"><?php echo round($cat['tiempo_promedio'], 1); ?> días</span>
                            </div>
                            <div class="progress mt-2" style="height: 5px;">
                                <div class="progress-bar" role="progressbar" style="width: <?php echo min(100, round($cat['tiempo_promedio'] * 10)); ?>%; background-color: <?php echo $cat['color']; ?>"></div>
                            </div>
                            <small class="text-muted"><?php echo $cat['total']; ?> solicitudes</small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-1"></i> No hay datos suficientes para este análisis.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal para actualizar estado de solicitud -->
<div class="modal fade" id="actualizarEstadoModal" tabindex="-1" aria-labelledby="actualizarEstadoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="actualizarEstadoModalLabel">Actualizar Estado de Solicitud</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="actualizarEstadoForm" action="index.php?page=funcionario_actualizar_estado" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" id="solicitud_id" name="solicitud_id">
                    
                    <div class="mb-3">
                        <label for="titulo_solicitud" class="form-label">Solicitud</label>
                        <input type="text" class="form-control" id="titulo_solicitud" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="estado_id" class="form-label">Estado</label>
                        <select class="form-select" id="estado_id" name="estado_id" required>
                            <?php 
                            $estados_disponibles->execute();
                            while ($estado = $estados_disponibles->fetch(PDO::FETCH_ASSOC)):
                            ?>
                                <option value="<?php echo $estado['id']; ?>"><?php echo $estado['nombre']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="comentario" class="form-label">Comentario</label>
                        <textarea class="form-control" id="comentario" name="comentario" rows="3" placeholder="Añade un comentario sobre la actualización..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="actualizarEstadoForm" class="btn btn-primary">Actualizar</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts para gráficos -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de rendimiento mensual
    const rendimientoCtx = document.getElementById('rendimientoChart').getContext('2d');
    const rendimientoChart = new Chart(rendimientoCtx, {
        type: 'line',
        data: {
            labels: [
                <?php 
                foreach($rendimiento_data as $data) {
                    $fecha = date('M Y', strtotime($data['mes'] . '-01'));
                    echo "'" . $fecha . "',";
                }
                ?>
            ],
            datasets: [
                {
                    label: 'Solicitudes Resueltas',
                    data: [
                        <?php 
                        foreach($rendimiento_data as $data) {
                            echo $data['resueltas'] . ",";
                        }
                        ?>
                    ],
                    backgroundColor: 'rgba(46, 204, 113, 0.2)',
                    borderColor: 'rgba(46, 204, 113, 1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4
                },
                {
                    label: 'Tiempo Promedio (días)',
                    data: [
                        <?php 
                        foreach($rendimiento_data as $data) {
                            echo round($data['tiempo_promedio'], 1) . ",";
                        }
                        ?>
                    ],
                    backgroundColor: 'rgba(52, 152, 219, 0.2)',
                    borderColor: 'rgba(52, 152, 219, 1)',
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
                    title: {
                        display: true,
                        text: 'Solicitudes Resueltas'
                    },
                    grid: {
                        drawBorder: false,
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        precision: 0
                    }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Tiempo Promedio (días)'
                    },
                    grid: {
                        display: false,
                        drawBorder: false
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
                    position: 'bottom'
                }
            }
        }
    });

    // Gráfico de solicitudes por categoría
    const categoriasCtx = document.getElementById('categoriasChart').getContext('2d');
    const categoriasChart = new Chart(categoriasCtx, {
        type: 'doughnut',
        data: {
            labels: [
                <?php foreach($categorias_data as $cat): ?>
                '<?php echo $cat['nombre']; ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                data: [
                    <?php foreach($categorias_data as $cat): ?>
                    <?php echo $cat['total']; ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: [
                    <?php foreach($categorias_data as $cat): ?>
                    '<?php echo $cat['color']; ?>',
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

    // Inicializar modal de actualización de estado
    const actualizarEstadoModal = document.getElementById('actualizarEstadoModal');
    if (actualizarEstadoModal) {
        actualizarEstadoModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const titulo = button.getAttribute('data-titulo');
            
            document.getElementById('solicitud_id').value = id;
            document.getElementById('titulo_solicitud').value = titulo;
        });
    }
});
</script>
