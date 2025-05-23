<?php
// Incluir el security.php
include_once 'config/security.php';

// Inicializar variables
$csrf_token = Security::generateCSRFToken();

// Obtener la conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Paginación
$page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Filtros
$nombre_filtro = isset($_GET['nombre']) ? Security::sanitizeInput($_GET['nombre']) : '';
$orden = isset($_GET['orden']) ? Security::sanitizeInput($_GET['orden']) : 'solicitudes_desc';

// Construir consulta SQL
$sql_where = " WHERE u.rol_id = 2 "; // Funcionarios
if (!empty($nombre_filtro)) {
    $sql_where .= " AND (u.nombre LIKE :nombre OR u.apellidos LIKE :nombre) ";
}

// Ordenamiento
$sql_order = "";
switch ($orden) {
    case 'solicitudes_desc':
        $sql_order = " ORDER BY solicitudes_resueltas DESC ";
        break;
    case 'solicitudes_asc':
        $sql_order = " ORDER BY solicitudes_resueltas ASC ";
        break;
    case 'tiempo_desc':
        $sql_order = " ORDER BY tiempo_promedio DESC ";
        break;
    case 'tiempo_asc':
        $sql_order = " ORDER BY tiempo_promedio ASC ";
        break;
    case 'nombre_asc':
        $sql_order = " ORDER BY u.nombre ASC, u.apellidos ASC ";
        break;
    case 'nombre_desc':
        $sql_order = " ORDER BY u.nombre DESC, u.apellidos DESC ";
        break;
    default:
        $sql_order = " ORDER BY solicitudes_resueltas DESC ";
}

// Consulta para obtener funcionarios
$query = "SELECT 
            u.id, u.nombre, u.apellidos, u.email, u.telefono, u.direccion, u.activo,
            COUNT(s.id) as solicitudes_resueltas,
            AVG(DATEDIFF(s.fecha_resolucion, s.fecha_creacion)) as tiempo_promedio,
            COUNT(DISTINCT s.usuario_id) as ciudadanos_atendidos,
            (SELECT COUNT(*) FROM solicitudes WHERE funcionario_id = u.id AND estado_id = 3) as en_proceso
          FROM usuarios u
          LEFT JOIN solicitudes s ON u.id = s.funcionario_id AND s.estado_id = 4 /* Resuelta */
          $sql_where
          GROUP BY u.id
          $sql_order
          LIMIT :offset, :items_per_page";

$stmt = $db->prepare($query);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':items_per_page', $items_per_page, PDO::PARAM_INT);

if (!empty($nombre_filtro)) {
    $nombre_param = "%$nombre_filtro%";
    $stmt->bindParam(':nombre', $nombre_param);
}

$stmt->execute();
$funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar total de funcionarios para paginación
$query_count = "SELECT COUNT(*) as total FROM usuarios u $sql_where";
$stmt_count = $db->prepare($query_count);

if (!empty($nombre_filtro)) {
    $stmt_count->bindParam(':nombre', $nombre_param);
}

$stmt_count->execute();
$total_funcionarios = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_funcionarios / $items_per_page);

// Estadísticas generales
$query_stats = "SELECT 
                AVG(solicitudes_por_funcionario) as promedio_solicitudes,
                AVG(tiempo_promedio) as promedio_tiempo
                FROM (
                    SELECT 
                        u.id,
                        COUNT(s.id) as solicitudes_por_funcionario,
                        AVG(DATEDIFF(s.fecha_resolucion, s.fecha_creacion)) as tiempo_promedio
                    FROM usuarios u
                    LEFT JOIN solicitudes s ON u.id = s.funcionario_id AND s.estado_id = 4
                    WHERE u.rol_id = 2
                    GROUP BY u.id
                ) as stats";
$stmt_stats = $db->prepare($query_stats);
$stmt_stats->execute();
$estadisticas = $stmt_stats->fetch(PDO::FETCH_ASSOC);
?>

<!-- Cabecera -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-user-tie me-2"></i>Gestión de Funcionarios
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="exportarPDF">
                <i class="fas fa-file-pdf me-1"></i> Exportar PDF
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="exportarExcel">
                <i class="fas fa-file-excel me-1"></i> Exportar Excel
            </button>
        </div>
        <a href="index.php?page=admin_dashboard" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver al Dashboard
        </a>
    </div>
</div>

<!-- Estadísticas Generales -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card shadow h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col-auto pe-3">
                        <div class="icon-circle bg-primary text-white">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                    </div>
                    <div class="col">
                        <div class="small fw-normal text-primary text-uppercase mb-1">
                            Promedio de Solicitudes Resueltas</div>
                        <div class="h5 mb-0 fw-normal text-dark">
                            <?php echo round($estadisticas['promedio_solicitudes'], 1); ?> solicitudes
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card shadow h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col-auto pe-3">
                        <div class="icon-circle bg-success text-white">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="col">
                        <div class="small fw-normal text-success text-uppercase mb-1">
                            Tiempo Promedio de Resolución</div>
                        <div class="h5 mb-0 fw-normal text-dark">
                            <?php echo round($estadisticas['promedio_tiempo'], 1); ?> días
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 fw-normal text-primary">Filtros</h6>
    </div>
    <div class="card-body">
        <form action="index.php" method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="admin_funcionarios">
            
            <div class="col-md-4">
                <label for="nombre" class="form-label">Nombre o Apellidos</label>
                <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo $nombre_filtro; ?>" placeholder="Buscar por nombre...">
            </div>
            
            <div class="col-md-4">
                <label for="orden" class="form-label">Ordenar por</label>
                <select class="form-select" id="orden" name="orden">
                    <option value="solicitudes_desc" <?php echo $orden == 'solicitudes_desc' ? 'selected' : ''; ?>>Más solicitudes resueltas</option>
                    <option value="solicitudes_asc" <?php echo $orden == 'solicitudes_asc' ? 'selected' : ''; ?>>Menos solicitudes resueltas</option>
                    <option value="tiempo_asc" <?php echo $orden == 'tiempo_asc' ? 'selected' : ''; ?>>Menor tiempo de resolución</option>
                    <option value="tiempo_desc" <?php echo $orden == 'tiempo_desc' ? 'selected' : ''; ?>>Mayor tiempo de resolución</option>
                    <option value="nombre_asc" <?php echo $orden == 'nombre_asc' ? 'selected' : ''; ?>>Nombre (A-Z)</option>
                    <option value="nombre_desc" <?php echo $orden == 'nombre_desc' ? 'selected' : ''; ?>>Nombre (Z-A)</option>
                </select>
            </div>
            
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-1"></i> Filtrar
                </button>
                <a href="index.php?page=admin_funcionarios" class="btn btn-secondary ms-2">
                    <i class="fas fa-sync-alt me-1"></i> Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Lista de Funcionarios -->
<div class="card shadow mb-4" id="funcionarios-table">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 fw-normal text-primary">
            <i class="fas fa-list me-1"></i> Funcionarios
        </h6>
        <span class="badge bg-primary"><?php echo $total_funcionarios; ?> funcionarios</span>
    </div>
    <div class="card-body">
        <?php if (count($funcionarios) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover" id="tabla-funcionarios">
                    <thead>
                        <tr>
                            <th>Funcionario</th>
                            <th>Contacto</th>
                            <th>Solicitudes Resueltas</th>
                            <th>Tiempo Promedio</th>
                            <th>En Proceso</th>
                            <th>Ciudadanos Atendidos</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($funcionarios as $funcionario): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-2">
                                        <div class="avatar-title rounded-circle bg-primary text-white">
                                            <?php echo strtoupper(substr($funcionario['nombre'], 0, 1) . substr($funcionario['apellidos'], 0, 1)); ?>
                                        </div>
                                    </div>
                                    <div class="ms-2">
                                        <?php echo Security::escapeOutput($funcionario['nombre'] . ' ' . $funcionario['apellidos']); ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div><i class="fas fa-envelope me-1 text-muted"></i> <?php echo Security::escapeOutput($funcionario['email']); ?></div>
                                <div><i class="fas fa-phone me-1 text-muted"></i> <?php echo Security::escapeOutput($funcionario['telefono']) ?: 'No disponible'; ?></div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success">
                                    <?php echo $funcionario['solicitudes_resueltas']; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php 
                                $tiempo = $funcionario['tiempo_promedio'] ? round($funcionario['tiempo_promedio'], 1) . ' días' : 'N/A';
                                echo $tiempo;
                                ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-warning">
                                    <?php echo $funcionario['en_proceso']; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php echo $funcionario['ciudadanos_atendidos']; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($funcionario['activo']): ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="index.php?page=admin_ver_funcionario&id=<?php echo $funcionario['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editarFuncionarioModal" 
                                        data-id="<?php echo $funcionario['id']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-<?php echo $funcionario['activo'] ? 'danger' : 'success'; ?>" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#cambiarEstadoModal" 
                                        data-id="<?php echo $funcionario['id']; ?>"
                                        data-nombre="<?php echo Security::escapeOutput($funcionario['nombre'] . ' ' . $funcionario['apellidos']); ?>"
                                        data-estado="<?php echo $funcionario['activo']; ?>">
                                    <i class="fas fa-<?php echo $funcionario['activo'] ? 'ban' : 'check'; ?>"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Paginación de funcionarios">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="index.php?page=admin_funcionarios&page_num=<?php echo $i; ?><?php echo !empty($nombre_filtro) ? '&nombre=' . urlencode($nombre_filtro) : ''; ?><?php echo !empty($orden) ? '&orden=' . urlencode($orden) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle me-1"></i> No se encontraron funcionarios con los criterios seleccionados.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Editar Funcionario -->
<div class="modal fade" id="editarFuncionarioModal" tabindex="-1" aria-labelledby="editarFuncionarioModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editarFuncionarioModalLabel">Editar Funcionario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editarFuncionarioForm" action="index.php?page=admin_editar_funcionario" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" id="edit_id" name="id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_apellidos" class="form-label">Apellidos</label>
                            <input type="text" class="form-control" id="edit_apellidos" name="apellidos" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_telefono" class="form-label">Teléfono</label>
                        <input type="tel" class="form-control" id="edit_telefono" name="telefono">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_direccion" class="form-label">Dirección</label>
                        <textarea class="form-control" id="edit_direccion" name="direccion" rows="2"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_activo" class="form-label">Estado</label>
                        <select class="form-select" id="edit_activo" name="activo" required>
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="editarFuncionarioForm" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cambiar Estado -->
<div class="modal fade" id="cambiarEstadoModal" tabindex="-1" aria-labelledby="cambiarEstadoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cambiarEstadoModalLabel">Cambiar Estado de Funcionario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea cambiar el estado del funcionario <span id="cambiar_nombre_funcionario"></span>?</p>
                <form id="cambiarEstadoForm" action="index.php?page=admin_cambiar_estado_funcionario" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" id="cambiar_id" name="id">
                    <input type="hidden" id="cambiar_estado" name="estado">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="cambiarEstadoForm" class="btn btn-primary" id="btnConfirmarCambio">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar modal de edición
    const editarFuncionarioModal = document.getElementById('editarFuncionarioModal');
    if (editarFuncionarioModal) {
        editarFuncionarioModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            
            // Cargar datos del funcionario mediante AJAX
            fetch(`index.php?page=admin_get_funcionario&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('edit_id').value = data.funcionario.id;
                        document.getElementById('edit_nombre').value = data.funcionario.nombre;
                        document.getElementById('edit_apellidos').value = data.funcionario.apellidos;
                        document.getElementById('edit_email').value = data.funcionario.email;
                        document.getElementById('edit_telefono').value = data.funcionario.telefono;
                        document.getElementById('edit_direccion').value = data.funcionario.direccion;
                        document.getElementById('edit_activo').value = data.funcionario.activo;
                    } else {
                        console.error('Error al cargar datos del funcionario');
                        alert('No se pudieron cargar los datos del funcionario');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar los datos del funcionario');
                });
        });
    }
    
    // Inicializar modal de cambio de estado
    const cambiarEstadoModal = document.getElementById('cambiarEstadoModal');
    if (cambiarEstadoModal) {
        cambiarEstadoModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const nombre = button.getAttribute('data-nombre');
            const estadoActual = button.getAttribute('data-estado');
            const nuevoEstado = estadoActual == '1' ? '0' : '1';
            const accion = estadoActual == '1' ? 'desactivar' : 'activar';
            
            document.getElementById('cambiar_nombre_funcionario').textContent = nombre;
            document.getElementById('cambiar_id').value = id;
            document.getElementById('cambiar_estado').value = nuevoEstado;
            document.getElementById('btnConfirmarCambio').textContent = estadoActual == '1' ? 'Desactivar' : 'Activar';
            document.getElementById('btnConfirmarCambio').className = estadoActual == '1' ? 'btn btn-danger' : 'btn btn-success';
        });
    }
    
    // Exportar a PDF
    document.getElementById('exportarPDF').addEventListener('click', function() {
        ExportUtils.exportToPDF('funcionarios-table', 'funcionarios');
    });
    
    // Exportar a Excel
    document.getElementById('exportarExcel').addEventListener('click', function() {
        ExportUtils.exportToExcel('tabla-funcionarios', 'funcionarios');
    });
});
</script>
