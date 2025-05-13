<?php
// Incluir modelos necesarios
include_once 'config/security.php';
include_once 'modelo/Sugerencia.php';

// Obtener la conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Inicializar objetos
$sugerencia = new Sugerencia($db);

// Obtener sugerencias del usuario actual
$page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$items_per_page = 10;

// Filtros
$estado_filtro = isset($_GET['estado']) ? (int)$_GET['estado'] : null;
$fecha_desde = isset($_GET['fecha_desde']) ? Security::sanitizeInput($_GET['fecha_desde']) : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? Security::sanitizeInput($_GET['fecha_hasta']) : '';

// Construir consulta SQL
$sql_where = " WHERE s.usuario_id = :usuario_id ";
if ($estado_filtro) {
    $sql_where .= " AND s.estado_id = :estado_id ";
}
if (!empty($fecha_desde)) {
    $sql_where .= " AND s.fecha_creacion >= :fecha_desde ";
}
if (!empty($fecha_hasta)) {
    $sql_where .= " AND s.fecha_creacion <= :fecha_hasta ";
}

// Consulta para obtener sugerencias
$query = "SELECT s.*, c.nombre as categoria_nombre, c.color as categoria_color, 
                 e.nombre as estado_nombre, e.color as estado_color,
                 f.nombre as funcionario_nombre, f.apellidos as funcionario_apellidos
          FROM sugerencias s
          LEFT JOIN categorias c ON s.categoria_id = c.id
          LEFT JOIN estados e ON s.estado_id = e.id
          LEFT JOIN usuarios f ON s.funcionario_id = f.id
          $sql_where
          ORDER BY s.fecha_creacion DESC
          LIMIT :offset, :items_per_page";

$offset = ($page - 1) * $items_per_page;
$stmt = $db->prepare($query);
$stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':items_per_page', $items_per_page, PDO::PARAM_INT);

if ($estado_filtro) {
    $stmt->bindParam(':estado_id', $estado_filtro);
}
if (!empty($fecha_desde)) {
    $fecha_desde_param = $fecha_desde . ' 00:00:00';
    $stmt->bindParam(':fecha_desde', $fecha_desde_param);
}
if (!empty($fecha_hasta)) {
    $fecha_hasta_param = $fecha_hasta . ' 23:59:59';
    $stmt->bindParam(':fecha_hasta', $fecha_hasta_param);
}

$stmt->execute();
$sugerencias_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar total de sugerencias para paginación
$query_count = "SELECT COUNT(*) as total FROM sugerencias s $sql_where";
$stmt_count = $db->prepare($query_count);
$stmt_count->bindParam(':usuario_id', $_SESSION['usuario_id']);

if ($estado_filtro) {
    $stmt_count->bindParam(':estado_id', $estado_filtro);
}
if (!empty($fecha_desde)) {
    $stmt_count->bindParam(':fecha_desde', $fecha_desde_param);
}
if (!empty($fecha_hasta)) {
    $stmt_count->bindParam(':fecha_hasta', $fecha_hasta_param);
}

$stmt_count->execute();
$total_sugerencias = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_sugerencias / $items_per_page);

// Obtener estados para filtro
$query_estados = "SELECT * FROM estados ORDER BY nombre";
$stmt_estados = $db->prepare($query_estados);
$stmt_estados->execute();
$estados = $stmt_estados->fetchAll(PDO::FETCH_ASSOC);

// Contar sugerencias por estado
$query_stats = "SELECT 
                (SELECT COUNT(*) FROM sugerencias WHERE usuario_id = :usuario_id AND estado_id = 1) as nuevas,
                (SELECT COUNT(*) FROM sugerencias WHERE usuario_id = :usuario_id AND estado_id = 2) as en_revision,
                (SELECT COUNT(*) FROM sugerencias WHERE usuario_id = :usuario_id AND estado_id = 3) as aprobadas,
                (SELECT COUNT(*) FROM sugerencias WHERE usuario_id = :usuario_id AND estado_id = 4) as rechazadas";

$stmt_stats = $db->prepare($query_stats);
$stmt_stats->bindParam(':usuario_id', $_SESSION['usuario_id']);
$stmt_stats->execute();
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-lightbulb me-2"></i>Mis Sugerencias
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php?page=nueva_sugerencia" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Nueva Sugerencia
        </a>
    </div>
</div>

<!-- Estadísticas -->
<div class="row">
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total de Sugerencias</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_sugerencias; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-lightbulb fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            En Revisión</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['en_revision']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-search fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Aprobadas</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['aprobadas']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                            Rechazadas</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['rechazadas']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Filtros</h6>
    </div>
    <div class="card-body">
        <form action="index.php" method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="ciudadano_sugerencias">
            
            <div class="col-md-3">
                <label for="estado" class="form-label">Estado</label>
                <select class="form-select" id="estado" name="estado">
                    <option value="">Todos los estados</option>
                    <?php foreach($estados as $estado): ?>
                        <option value="<?php echo $estado['id']; ?>" <?php echo $estado_filtro == $estado['id'] ? 'selected' : ''; ?>>
                            <?php echo Security::escapeOutput($estado['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="fecha_desde" class="form-label">Fecha desde</label>
                <input type="date" class="form-control datepicker" id="fecha_desde" name="fecha_desde" value="<?php echo $fecha_desde; ?>">
            </div>
            
            <div class="col-md-3">
                <label for="fecha_hasta" class="form-label">Fecha hasta</label>
                <input type="date" class="form-control datepicker" id="fecha_hasta" name="fecha_hasta" value="<?php echo $fecha_hasta; ?>">
            </div>
            
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-1"></i> Filtrar
                </button>
                <a href="index.php?page=ciudadano_sugerencias" class="btn btn-secondary ms-2">
                    <i class="fas fa-sync-alt me-1"></i> Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Lista de Sugerencias -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-list me-1"></i> Mis Sugerencias
        </h6>
        <a href="index.php?page=nueva_sugerencia" class="btn btn-sm btn-primary">
            <i class="fas fa-plus me-1"></i> Nueva Sugerencia
        </a>
    </div>
    <div class="card-body">
        <?php if (count($sugerencias_list) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Categoría</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Respuesta</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($sugerencias_list as $sugerencia): ?>
                        <tr>
                            <td><?php echo $sugerencia['id']; ?></td>
                            <td><?php echo Security::escapeOutput($sugerencia['titulo']); ?></td>
                            <td>
                                <span class="badge" style="background-color: <?php echo $sugerencia['categoria_color']; ?>">
                                    <?php echo Security::escapeOutput($sugerencia['categoria_nombre']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge" style="background-color: <?php echo $sugerencia['estado_color']; ?>">
                                    <?php echo Security::escapeOutput($sugerencia['estado_nombre']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($sugerencia['fecha_creacion'])); ?></td>
                            <td>
                                <?php if ($sugerencia['estado_id'] == 3 || $sugerencia['estado_id'] == 4): ?>
                                    <?php if (!empty($sugerencia['funcionario_nombre'])): ?>
                                        <span class="badge bg-info">
                                            <i class="fas fa-user me-1"></i> <?php echo Security::escapeOutput($sugerencia['funcionario_nombre'] . ' ' . $sugerencia['funcionario_apellidos']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Sin asignar</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Pendiente</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#verSugerenciaModal" 
                                        data-id="<?php echo $sugerencia['id']; ?>"
                                        data-titulo="<?php echo Security::escapeOutput($sugerencia['titulo']); ?>"
                                        data-descripcion="<?php echo Security::escapeOutput($sugerencia['descripcion']); ?>"
                                        data-categoria="<?php echo Security::escapeOutput($sugerencia['categoria_nombre']); ?>"
                                        data-estado="<?php echo Security::escapeOutput($sugerencia['estado_nombre']); ?>"
                                        data-fecha="<?php echo date('d/m/Y H:i', strtotime($sugerencia['fecha_creacion'])); ?>"
                                        data-respuesta="<?php echo Security::escapeOutput($sugerencia['respuesta']); ?>"
                                        data-funcionario="<?php echo isset($sugerencia['funcionario_nombre']) ? Security::escapeOutput($sugerencia['funcionario_nombre'] . ' ' . $sugerencia['funcionario_apellidos']) : 'Pendiente de asignación'; ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Paginación de sugerencias">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="index.php?page=ciudadano_sugerencias&page_num=<?php echo $i; ?><?php echo $estado_filtro ? '&estado=' . $estado_filtro : ''; ?><?php echo !empty($fecha_desde) ? '&fecha_desde=' . $fecha_desde : ''; ?><?php echo !empty($fecha_hasta) ? '&fecha_hasta=' . $fecha_hasta : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="alert alert-info" role="alert">
                <h4 class="alert-heading">¡No tienes sugerencias!</h4>
                <p>Aún no has enviado ninguna sugerencia. Las sugerencias son una excelente manera de contribuir a mejorar nuestra comunidad.</p>
                <hr>
                <p class="mb-0">Haz clic en el botón "Nueva Sugerencia" para enviar tu primera propuesta.</p>
            </div>
            
            <div class="text-center mt-4">
                <a href="index.php?page=nueva_sugerencia" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Nueva Sugerencia
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Ver Sugerencia -->
<div class="modal fade" id="verSugerenciaModal" tabindex="-1" aria-labelledby="verSugerenciaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="verSugerenciaModalLabel">Detalles de la Sugerencia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>ID:</strong> <span id="ver_id"></span></p>
                        <p><strong>Título:</strong> <span id="ver_titulo"></span></p>
                        <p><strong>Categoría:</strong> <span id="ver_categoria"></span></p>
                        <p><strong>Estado:</strong> <span id="ver_estado"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Fecha de creación:</strong> <span id="ver_fecha"></span></p>
                        <p><strong>Funcionario asignado:</strong> <span id="ver_funcionario"></span></p>
                    </div>
                </div>
                <div class="mb-3">
                    <h6>Descripción:</h6>
                    <div class="p-3 bg-light rounded" id="ver_descripcion"></div>
                </div>
                <div class="mb-3" id="respuesta_container">
                    <h6>Respuesta del funcionario:</h6>
                    <div class="p-3 bg-light rounded" id="ver_respuesta"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal Ver Sugerencia
    const verSugerenciaModal = document.getElementById('verSugerenciaModal');
    if (verSugerenciaModal) {
        verSugerenciaModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            
            document.getElementById('ver_id').textContent = button.getAttribute('data-id');
            document.getElementById('ver_titulo').textContent = button.getAttribute('data-titulo');
            document.getElementById('ver_categoria').textContent = button.getAttribute('data-categoria');
            document.getElementById('ver_estado').textContent = button.getAttribute('data-estado');
            document.getElementById('ver_fecha').textContent = button.getAttribute('data-fecha');
            document.getElementById('ver_funcionario').textContent = button.getAttribute('data-funcionario');
            document.getElementById('ver_descripcion').textContent = button.getAttribute('data-descripcion');
            
            const respuesta = button.getAttribute('data-respuesta');
            const respuestaContainer = document.getElementById('respuesta_container');
            const verRespuesta = document.getElementById('ver_respuesta');
            
            if (respuesta && respuesta !== 'null') {
                respuestaContainer.style.display = 'block';
                verRespuesta.textContent = respuesta;
            } else {
                respuestaContainer.style.display = 'none';
            }
        });
    }
    
    // Inicializar datepickers
    if (document.querySelector('.datepicker')) {
        flatpickr('.datepicker', {
            locale: 'es',
            dateFormat: "Y-m-d",
            allowInput: true
        });
    }
});
</script>
