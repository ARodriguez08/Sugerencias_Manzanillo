<?php
// Incluir modelos necesarios
include_once 'config/security.php';
include_once 'modelo/Sugerencia.php';
include_once 'config/notification.php';

// Verificar que el usuario sea funcionario o administrador
if ($_SESSION['usuario_rol_id'] != 1 && $_SESSION['usuario_rol_id'] != 2) {
    header("Location: index.php");
    exit;
}

// Obtener la conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Inicializar objetos
$sugerencia = new Sugerencia($db);
$notificacion = new Notification($db);

// Generar token CSRF
$csrf_token = Security::generateCSRFToken();

// Procesar acción de actualización de estado si se envía el formulario
$mensaje = "";
$tipo_mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar_estado') {
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || !Security::verifyCSRFToken($_POST['csrf_token'])) {
        $mensaje = "Error de seguridad. Por favor, intente nuevamente.";
        $tipo_mensaje = "danger";
    } else {
        // Verificar datos requeridos
        if (isset($_POST['sugerencia_id']) && isset($_POST['estado_id']) && isset($_POST['respuesta'])) {
            $sugerencia_id = (int) $_POST['sugerencia_id'];
            $estado_id = (int) $_POST['estado_id'];
            $respuesta = Security::sanitizeInput($_POST['respuesta']);
            $funcionario_id = $_SESSION['usuario_id'];
            
            // Validar longitud de la respuesta
            if (strlen($respuesta) < 10) {
                $mensaje = "La respuesta debe tener al menos 10 caracteres.";
                $tipo_mensaje = "warning";
            } else {
                // Actualizar estado de la sugerencia
                if ($sugerencia->actualizarEstado($sugerencia_id, $estado_id, $funcionario_id, $respuesta)) {
                    // Obtener información de la sugerencia para la notificación
                    $sugerencia_info = $sugerencia->obtenerPorId($sugerencia_id);
                    
                    // Crear notificación para el ciudadano
                    $estado_texto = ($estado_id == 3) ? "aprobada" : "rechazada";
                    $notificacion->crear(
                        $sugerencia_info['usuario_id'],
                        "Sugerencia {$estado_texto}",
                        "Su sugerencia '{$sugerencia_info['titulo']}' ha sido {$estado_texto}.",
                        $sugerencia_id
                    );
                    
                    $mensaje = "Estado de la sugerencia actualizado correctamente.";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al actualizar el estado de la sugerencia.";
                    $tipo_mensaje = "danger";
                }
            }
        } else {
            $mensaje = "Faltan datos requeridos.";
            $tipo_mensaje = "warning";
        }
    }
}

// Obtener sugerencias pendientes de revisión (estado_id = 1 o 2)
$page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$items_per_page = 10;

// Filtrar por estado si se especifica
$estado_filtro = isset($_GET['estado']) ? (int)$_GET['estado'] : null;

if ($estado_filtro) {
    $sugerencias = $sugerencia->obtenerPorEstado($estado_filtro, $page, $items_per_page);
    $total_sugerencias = $sugerencia->contarPorEstado($estado_filtro);
} else {
    $sugerencias = $sugerencia->obtenerTodas($page, $items_per_page);
    $total_sugerencias = $sugerencia->contarTotal();
}

$total_pages = ceil($total_sugerencias / $items_per_page);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-lightbulb me-2"></i>Revisión de Sugerencias
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="index.php?page=funcionario_dashboard" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Volver al Dashboard
            </a>
        </div>
    </div>
</div>

<?php if (!empty($mensaje)): ?>
    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
        <?php if ($tipo_mensaje == 'success'): ?>
            <i class="fas fa-check-circle me-1"></i>
        <?php elseif ($tipo_mensaje == 'warning'): ?>
            <i class="fas fa-exclamation-triangle me-1"></i>
        <?php else: ?>
            <i class="fas fa-times-circle me-1"></i>
        <?php endif; ?>
        <?php echo $mensaje; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Filtros -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Filtros</h6>
    </div>
    <div class="card-body">
        <form action="index.php" method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="funcionario_revisar_sugerencias">
            
            <div class="col-md-4">
                <label for="estado" class="form-label">Estado</label>
                <select class="form-select" id="estado" name="estado">
                    <option value="">Todos los estados</option>
                    <?php
                    // Obtener todos los estados
                    $query_estados = "SELECT * FROM estados ORDER BY nombre";
                    $stmt_estados = $db->prepare($query_estados);
                    $stmt_estados->execute();
                    
                    while ($estado = $stmt_estados->fetch(PDO::FETCH_ASSOC)):
                        $selected = ($estado_filtro == $estado['id']) ? 'selected' : '';
                    ?>
                        <option value="<?php echo $estado['id']; ?>" <?php echo $selected; ?>>
                            <?php echo Security::escapeOutput($estado['nombre']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-1"></i> Filtrar
                </button>
                <a href="index.php?page=funcionario_revisar_sugerencias" class="btn btn-secondary">
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
            <i class="fas fa-list me-1"></i> Sugerencias
            <?php if ($estado_filtro): ?>
                <?php
                $query_estado_nombre = "SELECT nombre FROM estados WHERE id = :id";
                $stmt_estado_nombre = $db->prepare($query_estado_nombre);
                $stmt_estado_nombre->bindParam(':id', $estado_filtro);
                $stmt_estado_nombre->execute();
                $estado_nombre = $stmt_estado_nombre->fetch(PDO::FETCH_ASSOC)['nombre'];
                ?>
                <span class="badge bg-info"><?php echo Security::escapeOutput($estado_nombre); ?></span>
            <?php endif; ?>
        </h6>
        <span class="badge bg-primary"><?php echo $total_sugerencias; ?> sugerencias</span>
    </div>
    <div class="card-body">
        <?php if ($total_sugerencias > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
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
                        <?php while ($row = $sugerencias->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo Security::escapeOutput($row['titulo']); ?></td>
                            <td>
                                <span class="badge" style="background-color: <?php echo $row['categoria_color']; ?>">
                                    <?php echo Security::escapeOutput($row['categoria_nombre']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge" style="background-color: <?php echo $row['estado_color']; ?>">
                                    <?php echo Security::escapeOutput($row['estado_nombre']); ?>
                                </span>
                            </td>
                            <td><?php echo Security::escapeOutput($row['usuario_nombre'] . ' ' . $row['usuario_apellidos']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_creacion'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#verSugerenciaModal" 
                                        data-id="<?php echo $row['id']; ?>"
                                        data-titulo="<?php echo Security::escapeOutput($row['titulo']); ?>"
                                        data-descripcion="<?php echo Security::escapeOutput($row['descripcion']); ?>"
                                        data-categoria="<?php echo Security::escapeOutput($row['categoria_nombre']); ?>"
                                        data-estado="<?php echo Security::escapeOutput($row['estado_nombre']); ?>"
                                        data-usuario="<?php echo Security::escapeOutput($row['usuario_nombre'] . ' ' . $row['usuario_apellidos']); ?>"
                                        data-fecha="<?php echo date('d/m/Y H:i', strtotime($row['fecha_creacion'])); ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                                
                                <?php if ($row['estado_id'] == 1 || $row['estado_id'] == 2): // Nueva o En revisión ?>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#responderSugerenciaModal" 
                                        data-id="<?php echo $row['id']; ?>"
                                        data-titulo="<?php echo Security::escapeOutput($row['titulo']); ?>">
                                    <i class="fas fa-reply"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Paginación de sugerencias">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="index.php?page=funcionario_revisar_sugerencias&page_num=<?php echo $i; ?><?php echo $estado_filtro ? '&estado=' . $estado_filtro : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle me-1"></i> No hay sugerencias disponibles con los filtros seleccionados.
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
                        <p><strong>Ciudadano:</strong> <span id="ver_usuario"></span></p>
                        <p><strong>Fecha de creación:</strong> <span id="ver_fecha"></span></p>
                    </div>
                </div>
                <div class="mb-3">
                    <h6>Descripción:</h6>
                    <div class="p-3 bg-light rounded" id="ver_descripcion"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Responder Sugerencia -->
<div class="modal fade" id="responderSugerenciaModal" tabindex="-1" aria-labelledby="responderSugerenciaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="responderSugerenciaModalLabel">Responder Sugerencia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="responderSugerenciaForm" action="index.php?page=funcionario_revisar_sugerencias" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="accion" value="actualizar_estado">
                    <input type="hidden" id="sugerencia_id" name="sugerencia_id">
                    
                    <div class="mb-3">
                        <label for="titulo_sugerencia" class="form-label">Título de la Sugerencia</label>
                        <input type="text" class="form-control" id="titulo_sugerencia" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="estado_id" class="form-label">Decisión <span class="text-danger">*</span></label>
                        <select class="form-select" id="estado_id" name="estado_id" required>
                            <option value="">Seleccione una opción</option>
                            <option value="2">En revisión - Necesita más análisis</option>
                            <option value="3">Aprobar - La sugerencia es viable</option>
                            <option value="4">Rechazar - La sugerencia no es viable</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="respuesta" class="form-label">Respuesta <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="respuesta" name="respuesta" rows="5" required
                                 placeholder="Explique su decisión y proporcione detalles sobre los próximos pasos..."
                                 minlength="10" maxlength="2000"></textarea>
                        <div class="form-text">Proporcione una respuesta clara y detallada para el ciudadano.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="responderSugerenciaForm" class="btn btn-primary">Enviar Respuesta</button>
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
            document.getElementById('ver_usuario').textContent = button.getAttribute('data-usuario');
            document.getElementById('ver_fecha').textContent = button.getAttribute('data-fecha');
            document.getElementById('ver_descripcion').textContent = button.getAttribute('data-descripcion');
        });
    }
    
    // Modal Responder Sugerencia
    const responderSugerenciaModal = document.getElementById('responderSugerenciaModal');
    if (responderSugerenciaModal) {
        responderSugerenciaModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            
            document.getElementById('sugerencia_id').value = button.getAttribute('data-id');
            document.getElementById('titulo_sugerencia').value = button.getAttribute('data-titulo');
        });
    }
    
    // Validación del formulario
    const responderSugerenciaForm = document.getElementById('responderSugerenciaForm');
    if (responderSugerenciaForm) {
        responderSugerenciaForm.addEventListener('submit', function(event) {
            const estadoId = document.getElementById('estado_id').value;
            const respuesta = document.getElementById('respuesta').value;
            
            if (!estadoId) {
                event.preventDefault();
                alert('Por favor, seleccione una decisión.');
                return false;
            }
            
            if (respuesta.length < 10) {
                event.preventDefault();
                alert('La respuesta debe tener al menos 10 caracteres.');
                return false;
            }
            
            return true;
        });
    }
});
</script>
