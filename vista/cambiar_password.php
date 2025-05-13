<?php
// Incluir security.php para validaciones
include_once 'config/security.php';

// Generar token CSRF
$csrf_token = Security::generateCSRFToken();

// Inicializar variables
$mensaje = "";
$tipo_mensaje = "";

// Procesar formulario si se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || !Security::verifyCSRFToken($_POST['csrf_token'])) {
        $mensaje = "Error de seguridad. Por favor, intente nuevamente.";
        $tipo_mensaje = "danger";
    } else {
        // Verificar campos requeridos
        if (
            isset($_POST['password_actual']) && 
            isset($_POST['password_nuevo']) && 
            isset($_POST['confirmar_password'])
        ) {
            // Sanitizar datos
            $password_actual = Security::sanitizeInput($_POST['password_actual']);
            $password_nuevo = Security::sanitizeInput($_POST['password_nuevo']);
            $confirmar_password = Security::sanitizeInput($_POST['confirmar_password']);
            
            // Validar que las contraseñas nuevas coincidan
            if ($password_nuevo !== $confirmar_password) {
                $mensaje = "Las contraseñas nuevas no coinciden.";
                $tipo_mensaje = "warning";
            } 
            // Validar longitud y complejidad de la contraseña
            elseif (strlen($password_nuevo) < 8) {
                $mensaje = "La contraseña debe tener al menos 8 caracteres.";
                $tipo_mensaje = "warning";
            }
            elseif (!preg_match('/[A-Za-z]/', $password_nuevo) || !preg_match('/[0-9]/', $password_nuevo)) {
                $mensaje = "La contraseña debe contener al menos una letra y un número.";
                $tipo_mensaje = "warning";
            }
            else {
                // Obtener la conexión a la base de datos
                $database = new Database();
                $db = $database->getConnection();
                
                // Verificar contraseña actual
                $query = "SELECT password FROM usuarios WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $_SESSION['usuario_id']);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $password_hash = $row['password'];
                    
                    if (password_verify($password_actual, $password_hash)) {
                        // Actualizar contraseña
                        $nuevo_hash = password_hash($password_nuevo, PASSWORD_BCRYPT);
                        
                        $query_update = "UPDATE usuarios SET password = :password WHERE id = :id";
                        $stmt_update = $db->prepare($query_update);
                        $stmt_update->bindParam(':password', $nuevo_hash);
                        $stmt_update->bindParam(':id', $_SESSION['usuario_id']);
                        
                        if ($stmt_update->execute()) {
                            $mensaje = "Contraseña actualizada correctamente.";
                            $tipo_mensaje = "success";
                            
                            // Registrar actividad
                            $query_log = "INSERT INTO actividad_usuarios (usuario_id, accion, detalles, ip) 
                                         VALUES (:usuario_id, 'cambio_password', 'Cambio de contraseña exitoso', :ip)";
                            $stmt_log = $db->prepare($query_log);
                            $usuario_id = $_SESSION['usuario_id'];
                            $ip = $_SERVER['REMOTE_ADDR'];
                            $stmt_log->bindParam(':usuario_id', $usuario_id);
                            $stmt_log->bindParam(':ip', $ip);
                            $stmt_log->execute();
                        } else {
                            $mensaje = "Error al actualizar la contraseña. Inténtelo de nuevo.";
                            $tipo_mensaje = "danger";
                        }
                    } else {
                        $mensaje = "La contraseña actual es incorrecta.";
                        $tipo_mensaje = "danger";
                        
                        // Registrar intento fallido
                        $query_log = "INSERT INTO actividad_usuarios (usuario_id, accion, detalles, ip) 
                                     VALUES (:usuario_id, 'cambio_password_fallido', 'Contraseña actual incorrecta', :ip)";
                        $stmt_log = $db->prepare($query_log);
                        $usuario_id = $_SESSION['usuario_id'];
                        $ip = $_SERVER['REMOTE_ADDR'];
                        $stmt_log->bindParam(':usuario_id', $usuario_id);
                        $stmt_log->bindParam(':ip', $ip);
                        $stmt_log->execute();
                    }
                } else {
                    $mensaje = "Error al verificar la contraseña actual.";
                    $tipo_mensaje = "danger";
                }
            }
        } else {
            $mensaje = "Por favor, complete todos los campos.";
            $tipo_mensaje = "warning";
        }
    }
}
?>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="d-sm-flex align-items-center justify-content-between">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-key me-2"></i>Cambiar Contraseña
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Cambiar Contraseña</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-lock me-1"></i> Formulario de Cambio de Contraseña
                </h6>
            </div>
            <div class="card-body">
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
                
                <form action="index.php?page=cambiar_password" method="POST" id="cambiarPasswordForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="mb-3">
                        <label for="password_actual" class="form-label">Contraseña Actual <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password_actual" name="password_actual" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password_actual">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Ingrese su contraseña actual para verificar su identidad</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password_nuevo" class="form-label">Nueva Contraseña <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password_nuevo" name="password_nuevo" required minlength="8">
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password_nuevo">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">La contraseña debe tener al menos 8 caracteres, incluyendo letras y números</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirmar_password" class="form-label">Confirmar Nueva Contraseña <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirmar_password" name="confirmar_password" required minlength="8">
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirmar_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Repita la nueva contraseña para confirmar</div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Requisitos de seguridad</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item" id="length-check">
                                        <i class="fas fa-times-circle text-danger me-2"></i> Mínimo 8 caracteres
                                    </li>
                                    <li class="list-group-item" id="letter-check">
                                        <i class="fas fa-times-circle text-danger me-2"></i> Al menos una letra
                                    </li>
                                    <li class="list-group-item" id="number-check">
                                        <i class="fas fa-times-circle text-danger me-2"></i> Al menos un número
                                    </li>
                                    <li class="list-group-item" id="match-check">
                                        <i class="fas fa-times-circle text-danger me-2"></i> Las contraseñas coinciden
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php?page=perfil" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Cambiar Contraseña
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar/ocultar contraseña
    const toggleButtons = document.querySelectorAll('.toggle-password');
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
    
    // Validación en tiempo real
    const passwordNuevo = document.getElementById('password_nuevo');
    const confirmarPassword = document.getElementById('confirmar_password');
    const lengthCheck = document.getElementById('length-check');
    const letterCheck = document.getElementById('letter-check');
    const numberCheck = document.getElementById('number-check');
    const matchCheck = document.getElementById('match-check');
    
    function updateChecks() {
        // Verificar longitud
        if (passwordNuevo.value.length >= 8) {
            lengthCheck.innerHTML = '<i class="fas fa-check-circle text-success me-2"></i> Mínimo 8 caracteres';
        } else {
            lengthCheck.innerHTML = '<i class="fas fa-times-circle text-danger me-2"></i> Mínimo 8 caracteres';
        }
        
        // Verificar letra
        if (/[A-Za-z]/.test(passwordNuevo.value)) {
            letterCheck.innerHTML = '<i class="fas fa-check-circle text-success me-2"></i> Al menos una letra';
        } else {
            letterCheck.innerHTML = '<i class="fas fa-times-circle text-danger me-2"></i> Al menos una letra';
        }
        
        // Verificar número
        if (/[0-9]/.test(passwordNuevo.value)) {
            numberCheck.innerHTML = '<i class="fas fa-check-circle text-success me-2"></i> Al menos un número';
        } else {
            numberCheck.innerHTML = '<i class="fas fa-times-circle text-danger me-2"></i> Al menos un número';
        }
        
        // Verificar coincidencia
        if (passwordNuevo.value && confirmarPassword.value && passwordNuevo.value === confirmarPassword.value) {
            matchCheck.innerHTML = '<i class="fas fa-check-circle text-success me-2"></i> Las contraseñas coinciden';
        } else {
            matchCheck.innerHTML = '<i class="fas fa-times-circle text-danger me-2"></i> Las contraseñas coinciden';
        }
    }
    
    passwordNuevo.addEventListener('keyup', updateChecks);
    confirmarPassword.addEventListener('keyup', updateChecks);
    
    // Validación del formulario
    const form = document.getElementById('cambiarPasswordForm');
    form.addEventListener('submit', function(event) {
        if (passwordNuevo.value !== confirmarPassword.value) {
            event.preventDefault();
            alert('Las contraseñas nuevas no coinciden.');
            return false;
        }
        
        if (passwordNuevo.value.length < 8) {
            event.preventDefault();
            alert('La contraseña debe tener al menos 8 caracteres.');
            return false;
        }
        
        if (!(/[A-Za-z]/.test(passwordNuevo.value) && /[0-9]/.test(passwordNuevo.value))) {
            event.preventDefault();
            alert('La contraseña debe contener al menos una letra y un número.');
            return false;
        }
        
        return true;
    });
});
</script>
