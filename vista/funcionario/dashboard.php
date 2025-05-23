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
$query_total_solicitudes = "SELECT COUNT(*) as total FROM solicitudes WHERE funcionario_id = :funcionario_id";
$stmt_total_solicitudes = $db->prepare($query_total_solicitudes);
$stmt_total_solicitudes->bindParam(':funcionario_id', $funcionario_id);
$stmt_total_solicitudes->execute();
$total_solicitudes = $stmt_total_solicitudes->fetch(PDO::FETCH_ASSOC)['total'];

$query_por_estado = "SELECT e.nombre, e.color, COUNT(s.id) as total 
                     FROM solicitudes s 
                     JOIN estados e ON s.estado_id = e.id 
                     WHERE s.funcionario_id = :funcionario_id 
                     GROUP BY s.estado_id";
$stmt_por_estado = $db->prepare($query_por_estado);
$stmt_por_estado->bindParam(':funcionario_id', $funcionario_id);
$stmt_por_estado->execute();
$solicitudes_por_estado = $stmt_por_estado->fetchAll(PDO::FETCH_ASSOC);

$query_sugerencias_pendientes = "SELECT COUNT(*) as total FROM sugerencias WHERE estado_id IN (1, 2)";
$stmt_sugerencias_pendientes = $db->prepare($query_sugerencias_pendientes);
$stmt_sugerencias_pendientes->execute();
$sugerencias_pendientes = $stmt_sugerencias_pendientes->fetch(PDO::FETCH_ASSOC)['total'];

$query_recientes = "SELECT s.id, s.titulo, s.fecha_creacion, c.nombre as categoria, c.color as categoria_color, 
                    e.nombre as estado, e.color as estado_color, u.nombre as nombre_ciudadano, u.apellidos as apellidos_ciudadano
                    FROM solicitudes s 
                    JOIN categorias c ON s.categoria_id = c.id 
                    JOIN estados e ON s.estado_id = e.id 
                    JOIN usuarios u ON s.usuario_id = u.id
                    WHERE s.funcionario_id = :funcionario_id 
                    ORDER BY 
                        CASE 
                            WHEN s.estado_id = 2 THEN 1
                            WHEN s.estado_id = 3 THEN 2
                            ELSE 3
                        END,
                        s.fecha_creacion DESC 
                    LIMIT 8";
$stmt_recientes = $db->prepare($query_recientes);
$stmt_recientes->bindParam(':funcionario_id', $funcionario_id);
$stmt_recientes->execute();
$solicitudes_recientes = $stmt_recientes->fetchAll(PDO::FETCH_ASSOC);

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

$estados_disponibles = $estado->obtenerTodos();

$query_tiempo = "SELECT AVG(DATEDIFF(fecha_resolucion, fecha_creacion)) as promedio 
                 FROM solicitudes 
                 WHERE funcionario_id = :funcionario_id AND fecha_resolucion IS NOT NULL";
$stmt_tiempo = $db->prepare($query_tiempo);
$stmt_tiempo->bindParam(':funcionario_id', $funcionario_id);
$stmt_tiempo->execute();
$tiempo_promedio = $stmt_tiempo->fetch(PDO::FETCH_ASSOC)['promedio'];
$tiempo_promedio = $tiempo_promedio ? round($tiempo_promedio, 1) : 'N/A';

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

<!-- Incluir CSS del dashboard -->
<link rel="stylesheet" href="../funcionario/assets/css/styles_dashboard.css">
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">

<!-- Cabecera del Dashboard -->
<div class="container my-4">
    <h1 class="mb-4">Dashboard de Funcionario</h1>
    <div class="mb-4">
        <!-- Botones que abren modales -->
        <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#modalSolicitudes">Gestionar Solicitudes</button>
        <button class="btn btn-secondary me-2" data-bs-toggle="modal" data-bs-target="#modalSugerencias">Revisar Sugerencias</button>
        <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalReportes">Reportes</button>
    </div>

    <!-- Resumen de Solicitudes -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="card-title">Solicitudes Asignadas</div>
                    <div class="display-6"><?php echo $total_solicitudes; ?></div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-link" data-bs-toggle="modal" data-bs-target="#modalSolicitudes">Ver todas</button>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="card-title">Solicitudes Resueltas</div>
                    <?php
                    $resueltas = 0;
                    foreach($solicitudes_por_estado as $estado) {
                        if ($estado['nombre'] == 'Resuelta') {
                            $resueltas = $estado['total'];
                            break;
                        }
                    }
                    ?>
                    <div class="display-6"><?php echo $resueltas; ?></div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-link" data-bs-toggle="modal" data-bs-target="#modalSolicitudesResueltas">Ver detalles</button>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="card-title">Tiempo Promedio Resolución</div>
                    <div class="display-6">
                        <?php echo $tiempo_promedio; ?> días
                    </div>
                </div>
                <div class="card-footer">
                    <span>Promedio de tiempo de resolución</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="card-title">Sugerencias Pendientes</div>
                    <div class="display-6"><?php echo $sugerencias_pendientes; ?></div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-link" data-bs-toggle="modal" data-bs-target="#modalSugerencias">Revisar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Solicitudes Pendientes y Sugerencias Pendientes -->
    <div class="row">
        <!-- Solicitudes Pendientes -->
        <div class="col-lg-7 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Solicitudes Pendientes</h6>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalSolicitudes">Ver todas</button>
                </div>
                <div class="card-body">
                    <?php if (count($solicitudes_recientes) > 0): ?>
                        <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="table-light">
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
                                        <span class="badge" style="background:<?php echo htmlspecialchars($solicitud['categoria_color']); ?>">
                                            <?php echo $solicitud['categoria']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge" style="background:<?php echo htmlspecialchars($solicitud['estado_color']); ?>">
                                            <?php echo $solicitud['estado']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo Security::escapeOutput($solicitud['nombre_ciudadano'] . ' ' . $solicitud['apellidos_ciudadano']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($solicitud['fecha_creacion'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalVerSolicitud"
                                            data-id="<?php echo $solicitud['id']; ?>"
                                            data-titulo="<?php echo htmlspecialchars($solicitud['titulo'], ENT_QUOTES); ?>">
                                            Ver
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#actualizarEstadoModal" 
                                                data-id="<?php echo $solicitud['id']; ?>"
                                                data-titulo="<?php echo htmlspecialchars($solicitud['titulo'], ENT_QUOTES); ?>">
                                            Editar
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">
                            No tienes solicitudes pendientes asignadas.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sugerencias Pendientes y Categorías más Lentas -->
        <div class="col-lg-5">
            <!-- Sugerencias Pendientes -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Sugerencias Pendientes</h6>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalSugerencias">Ver todas</button>
                </div>
                <div class="card-body">
                    <?php if (count($sugerencias_pendientes_list) > 0): ?>
                        <?php foreach($sugerencias_pendientes_list as $sugerencia): ?>
                        <div class="mb-2 border-bottom pb-2">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold"><?php echo Security::escapeOutput($sugerencia['titulo']); ?></span>
                                <span class="badge" style="background:<?php echo htmlspecialchars($sugerencia['estado_color']); ?>">
                                    <?php echo $sugerencia['estado']; ?>
                                </span>
                            </div>
                            <div class="text-muted small">
                                <?php echo Security::escapeOutput($sugerencia['nombre_ciudadano'] . ' ' . $sugerencia['apellidos_ciudadano']); ?>
                                &middot; <?php echo date('d/m/Y', strtotime($sugerencia['fecha_creacion'])); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">
                            No hay sugerencias pendientes de revisión.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Categorías con más tiempo de resolución -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Categorías Más Lentas</h6>
                </div>
                <div class="card-body">
                    <?php if (count($top_categorias) > 0): ?>
                        <?php foreach($top_categorias as $index => $cat): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <span class="badge bg-secondary me-2"><?php echo ($index + 1); ?></span>
                                <span class="fw-bold"><?php echo $cat['nombre']; ?></span>
                                <span class="badge ms-2" style="background:<?php echo htmlspecialchars($cat['color']); ?>">&nbsp;</span>
                            </div>
                            <div>
                                <span class="me-2"><?php echo round($cat['tiempo_promedio'], 1); ?> días</span>
                                <span class="text-muted small"><?php echo $cat['total']; ?> solicitudes</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">
                            No hay datos suficientes para este análisis.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Gestionar Solicitudes -->
<div class="modal fade" id="modalSolicitudes" tabindex="-1" aria-labelledby="modalSolicitudesLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalSolicitudesLabel">Solicitudes Asignadas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <?php if (count($solicitudes_recientes) > 0): ?>
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle">
            <thead class="table-light">
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
                  <span class="badge" style="background:<?php echo htmlspecialchars($solicitud['categoria_color']); ?>">
                    <?php echo $solicitud['categoria']; ?>
                  </span>
                </td>
                <td>
                  <span class="badge" style="background:<?php echo htmlspecialchars($solicitud['estado_color']); ?>">
                    <?php echo $solicitud['estado']; ?>
                  </span>
                </td>
                <td><?php echo Security::escapeOutput($solicitud['nombre_ciudadano'] . ' ' . $solicitud['apellidos_ciudadano']); ?></td>
                <td><?php echo date('d/m/Y', strtotime($solicitud['fecha_creacion'])); ?></td>
                <td>
                  <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalVerSolicitud"
                    data-id="<?php echo $solicitud['id']; ?>"
                    data-titulo="<?php echo htmlspecialchars($solicitud['titulo'], ENT_QUOTES); ?>">
                    Ver
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#actualizarEstadoModal" 
                    data-id="<?php echo $solicitud['id']; ?>"
                    data-titulo="<?php echo htmlspecialchars($solicitud['titulo'], ENT_QUOTES); ?>">
                    Editar
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <div class="alert alert-info mb-0">
          No tienes solicitudes pendientes asignadas.
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Solicitudes Resueltas -->
<div class="modal fade" id="modalSolicitudesResueltas" tabindex="-1" aria-labelledby="modalSolicitudesResueltasLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalSolicitudesResueltasLabel">Solicitudes Resueltas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <?php
        $query_resueltas = "SELECT s.id, s.titulo, s.fecha_creacion, c.nombre as categoria, e.nombre as estado
                            FROM solicitudes s
                            JOIN categorias c ON s.categoria_id = c.id
                            JOIN estados e ON s.estado_id = e.id
                            WHERE s.funcionario_id = :funcionario_id AND s.estado_id = 4
                            ORDER BY s.fecha_creacion DESC";
        $stmt_resueltas = $db->prepare($query_resueltas);
        $stmt_resueltas->bindParam(':funcionario_id', $funcionario_id);
        $stmt_resueltas->execute();
        $solicitudes_resueltas = $stmt_resueltas->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <?php if (count($solicitudes_resueltas) > 0): ?>
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Título</th>
                <th>Categoría</th>
                <th>Estado</th>
                <th>Fecha</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($solicitudes_resueltas as $solicitud): ?>
              <tr>
                <td><?php echo $solicitud['id']; ?></td>
                <td><?php echo Security::escapeOutput($solicitud['titulo']); ?></td>
                <td><?php echo $solicitud['categoria']; ?></td>
                <td><?php echo $solicitud['estado']; ?></td>
                <td><?php echo date('d/m/Y', strtotime($solicitud['fecha_creacion'])); ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <div class="alert alert-info mb-0">
          No hay solicitudes resueltas.
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Sugerencias Pendientes -->
<div class="modal fade" id="modalSugerencias" tabindex="-1" aria-labelledby="modalSugerenciasLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalSugerenciasLabel">Sugerencias Pendientes</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <?php if (count($sugerencias_pendientes_list) > 0): ?>
        <div class="list-group">
          <?php foreach($sugerencias_pendientes_list as $sugerencia): ?>
          <div class="list-group-item">
            <div class="d-flex justify-content-between">
              <span class="fw-bold"><?php echo Security::escapeOutput($sugerencia['titulo']); ?></span>
              <span class="badge" style="background:<?php echo htmlspecialchars($sugerencia['estado_color']); ?>">
                <?php echo $sugerencia['estado']; ?>
              </span>
            </div>
            <div class="text-muted small">
              <?php echo Security::escapeOutput($sugerencia['nombre_ciudadano'] . ' ' . $sugerencia['apellidos_ciudadano']); ?>
              &middot; <?php echo date('d/m/Y', strtotime($sugerencia['fecha_creacion'])); ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="alert alert-info mb-0">
          No hay sugerencias pendientes de revisión.
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Reportes -->
<div class="modal fade" id="modalReportes" tabindex="-1" aria-labelledby="modalReportesLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalReportesLabel">Reportes</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <!-- Aquí puedes agregar contenido de reportes, gráficos, etc. -->
        <div class="alert alert-info mb-0">
          Próximamente reportes detallados.
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Ver Solicitud (solo ejemplo, puedes personalizar) -->
<div class="modal fade" id="modalVerSolicitud" tabindex="-1" aria-labelledby="modalVerSolicitudLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalVerSolicitudLabel">Detalle de Solicitud</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div id="detalleSolicitudContent">
          <!-- El contenido se puede cargar dinámicamente con JS si lo deseas -->
          <div class="alert alert-info">Selecciona una solicitud para ver el detalle.</div>
        </div>
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
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="actualizarEstadoForm" action="index.php?page=funcionario_actualizar_estado" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" id="solicitud_id" name="solicitud_id">
                    
                    <div class="mb-3">
                        <label for="titulo_solicitud" class="form-label">Solicitud</label>
                        <input type="text" id="titulo_solicitud" class="form-control" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="estado_id" class="form-label">Estado</label>
                        <select id="estado_id" name="estado_id" class="form-select" required>
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
                        <textarea id="comentario" name="comentario" class="form-control" rows="3" placeholder="Añade un comentario sobre la actualización..."></textarea>
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

<!-- Bootstrap JS Bundle (with Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<!-- Incluir JS del dashboard -->
<script src="../funcionario/assets/js/app_dashboard.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal para Editar Estado
    var actualizarEstadoModal = document.getElementById('actualizarEstadoModal');
    actualizarEstadoModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var solicitudId = button.getAttribute('data-id');
        var titulo = button.getAttribute('data-titulo');
        document.getElementById('solicitud_id').value = solicitudId;
        document.getElementById('titulo_solicitud').value = titulo;
    });

    // Modal para Ver Solicitud (puedes cargar detalles por AJAX si lo deseas)
    var modalVerSolicitud = document.getElementById('modalVerSolicitud');
    modalVerSolicitud.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var solicitudId = button.getAttribute('data-id');
        var titulo = button.getAttribute('data-titulo');
        var content = document.getElementById('detalleSolicitudContent');
        content.innerHTML = '<div class="mb-2"><strong>ID:</strong> ' + solicitudId + '</div>' +
                            '<div class="mb-2"><strong>Título:</strong> ' + titulo + '</div>' +
                            '<div class="alert alert-info">Puedes cargar más detalles por AJAX aquí.</div>';
    });
});
</script>
