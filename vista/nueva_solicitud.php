<?php
// Incluir security.php para validaciones
include_once 'config/security.php';

// Generar token CSRF
$csrf_token = Security::generateCSRFToken();

// Incluir modelos necesarios
if (!class_exists('Categoria')) {
    include_once 'modelo/Categoria.php';
}
if (!class_exists('Sugerencia')) {
    include_once 'modelo/Sugerencia.php';
}

// Obtener la conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Inicializar objetos
$categoria = new Categoria($db);
$sugerencia = new Sugerencia($db);

// Obtener todas las categorías disponibles
$categorias = $categoria->obtenerTodas();

// Procesar el formulario si se envía
$mensaje = "";
$tipo_mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || !Security::verifyCSRFToken($_POST['csrf_token'])) {
        $mensaje = "Error de seguridad. Por favor, intente nuevamente.";
        $tipo_mensaje = "danger";
    } else {
        // Verificar campos requeridos
        if (
            isset($_POST['titulo']) && 
            isset($_POST['descripcion']) && 
            isset($_POST['categoria_id'])
        ) {
            // Sanitizar datos
            $titulo = Security::sanitizeInput($_POST['titulo']);
            $descripcion = Security::sanitizeInput($_POST['descripcion']);
            $categoria_id = (int) $_POST['categoria_id'];
            
            // Validar longitud mínima de los campos
            if (strlen($titulo) < 5) {
                $mensaje = "El título debe tener al menos 5 caracteres.";
                $tipo_mensaje = "warning";
            } elseif (strlen($descripcion) < 10) {
                $mensaje = "La descripción debe tener al menos 10 caracteres.";
                $tipo_mensaje = "warning";
            } else {
                // Crear la sugerencia
                $sugerencia->titulo = $titulo;
                $sugerencia->descripcion = $descripcion;
                $sugerencia->categoria_id = $categoria_id;
                $sugerencia->usuario_id = $_SESSION['usuario_id'];
                $sugerencia->estado_id = 1; // Estado "Nueva"
                
                $sugerencia_id = $sugerencia->crear();
                
                if ($sugerencia_id) {
                    // Crear notificación para administradores y funcionarios
                    include_once 'config/notification.php';
                    $notificacion = new Notification($db);
                    
                    // Obtener administradores y funcionarios
                    $query_admins = "SELECT id FROM usuarios WHERE rol_id IN (1, 2)"; // 1: admin, 2: funcionario
                    $stmt_admins = $db->prepare($query_admins);
                    $stmt_admins->execute();
                    
                    while ($admin = $stmt_admins->fetch(PDO::FETCH_ASSOC)) {
                        $notificacion->crear(
                            $admin['id'], 
                            "Nueva sugerencia", 
                            "Un ciudadano ha enviado una nueva sugerencia: {$titulo}",
                            $sugerencia_id
                        );
                    }
                    
                    $mensaje = "¡Sugerencia enviada correctamente! Gracias por contribuir a mejorar nuestra comunidad.";
                    $tipo_mensaje = "success";
                    
                    // Redirigir después de 2 segundos
                    echo "<meta http-equiv='refresh' content='2;url=index.php?page=ciudadano_dashboard'>";
                } else {
                    $mensaje = "Error al crear la sugerencia. Inténtelo de nuevo.";
                    $tipo_mensaje = "danger";
                }
            }
        } else {
            $mensaje = "Por favor, complete todos los campos obligatorios.";
            $tipo_mensaje = "warning";
        }
    }
}
?>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="d-sm-flex align-items-center justify-content-between">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-lightbulb me-2"></i>Nueva Sugerencia
            </h1>
            <a href="index.php?page=ciudadano_dashboard" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver al Dashboard
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

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-edit me-1"></i> Formulario de Sugerencia
                </h6>
            </div>
            <div class="card-body">
                <form action="index.php?page=nueva_sugerencia" method="POST" id="sugerenciaForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="mb-3">
                        <label for="titulo" class="form-label">Título de la sugerencia <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="titulo" name="titulo" required 
                               placeholder="Ej: Mejora en el sistema de iluminación del parque central" 
                               minlength="5" maxlength="200">
                        <div class="form-text">Describa brevemente su sugerencia (5-200 caracteres)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="categoria_id" class="form-label">Categoría <span class="text-danger">*</span></label>
                        <select class="form-select select2" id="categoria_id" name="categoria_id" required>
                            <option value="">Seleccione una categoría</option>
                            <?php 
                            while ($row = $categorias->fetch(PDO::FETCH_ASSOC)):
                                $categoria_color = $row['color'];
                            ?>
                                <option value="<?php echo $row['id']; ?>" style="background-color: <?php echo $categoria_color . '33'; ?>">
                                    <?php echo Security::escapeOutput($row['nombre']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <div class="form-text">Seleccione la categoría que mejor se adapte a su sugerencia</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción detallada <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="6" required
                                 placeholder="Describa detalladamente su sugerencia, incluyendo los beneficios que podría aportar a la comunidad..."
                                 minlength="10" maxlength="2000"></textarea>
                        <div class="form-text">Explique su idea con detalle (10-2000 caracteres). Sea lo más específico posible.</div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php?page=ciudadano_dashboard'">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i> Enviar Sugerencia
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-info-circle me-1"></i> Información
                </h6>
            </div>
            <div class="card-body">
                <h5>¿Qué es una sugerencia?</h5>
                <p>Una sugerencia es una propuesta o idea para mejorar algún aspecto de nuestra comunidad. Puede ser sobre servicios públicos, infraestructura, programas sociales, entre otros.</p>
                
                <h5>Proceso de revisión</h5>
                <div class="timeline timeline-xs mb-4">
                    <div class="timeline-item">
                        <div class="timeline-item-marker">
                            <div class="timeline-item-marker-indicator bg-primary">
                                <i class="fas fa-paper-plane"></i>
                            </div>
                        </div>
                        <div class="timeline-item-content pt-0">
                            <div class="timeline-item-title">Envío</div>
                            <div class="timeline-item-subtitle">Usted envía su sugerencia</div>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-item-marker">
                            <div class="timeline-item-marker-indicator bg-warning">
                                <i class="fas fa-search"></i>
                            </div>
                        </div>
                        <div class="timeline-item-content pt-0">
                            <div class="timeline-item-title">Revisión</div>
                            <div class="timeline-item-subtitle">Los funcionarios revisan su propuesta</div>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-item-marker">
                            <div class="timeline-item-marker-indicator bg-success">
                                <i class="fas fa-check"></i>
                            </div>
                        </div>
                        <div class="timeline-item-content pt-0">
                            <div class="timeline-item-title">Aprobación</div>
                            <div class="timeline-item-subtitle">Si es viable, se aprueba para implementación</div>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-item-marker">
                            <div class="timeline-item-marker-indicator bg-info">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                        </div>
                        <div class="timeline-item-content pt-0">
                            <div class="timeline-item-title">Seguimiento</div>
                            <div class="timeline-item-subtitle">Se le notificará el estado de su sugerencia</div>
                        </div>
                    </div>
                </div>
                
                <h5>Consejos para una buena sugerencia</h5>
                <ul class="mb-0">
                    <li>Sea específico y claro en su propuesta</li>
                    <li>Explique los beneficios para la comunidad</li>
                    <li>Si es posible
