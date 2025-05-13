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
            isset($_POST['nombre']) && 
            isset($_POST['email']) && 
            isset($_POST['asunto']) && 
            isset($_POST['mensaje'])
        ) {
            // Sanitizar datos
            $nombre = Security::sanitizeInput($_POST['nombre']);
            $email = Security::sanitizeInput($_POST['email']);
            $asunto = Security::sanitizeInput($_POST['asunto']);
            $mensaje_texto = Security::sanitizeInput($_POST['mensaje']);
            $telefono = isset($_POST['telefono']) ? Security::sanitizeInput($_POST['telefono']) : '';
            
            // Validar email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $mensaje = "Por favor, ingrese un correo electrónico válido.";
                $tipo_mensaje = "warning";
            } 
            // Validar longitud de los campos
            elseif (strlen($nombre) < 3) {
                $mensaje = "El nombre debe tener al menos 3 caracteres.";
                $tipo_mensaje = "warning";
            }
            elseif (strlen($asunto) < 5) {
                $mensaje = "El asunto debe tener al menos 5 caracteres.";
                $tipo_mensaje = "warning";
            }
            elseif (strlen($mensaje_texto) < 10) {
                $mensaje = "El mensaje debe tener al menos 10 caracteres.";
                $tipo_mensaje = "warning";
            }
            else {
                // Obtener la conexión a la base de datos
                $database = new Database();
                $db = $database->getConnection();
                
                // Guardar mensaje de contacto en la base de datos
                $query = "INSERT INTO mensajes_contacto (nombre, email, telefono, asunto, mensaje, ip, fecha_creacion) 
                          VALUES (:nombre, :email, :telefono, :asunto, :mensaje, :ip, NOW())";
                $stmt = $db->prepare($query);
                
                // Vincular parámetros
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':telefono', $telefono);
                $stmt->bindParam(':asunto', $asunto);
                $stmt->bindParam(':mensaje', $mensaje_texto);
                
                // Obtener IP del usuario
                $ip = $_SERVER['REMOTE_ADDR'];
                $stmt->bindParam(':ip', $ip);
                
                // Ejecutar consulta
                if ($stmt->execute()) {
                    $mensaje = "¡Gracias por contactarnos! Su mensaje ha sido enviado correctamente. Nos pondremos en contacto con usted lo antes posible.";
                    $tipo_mensaje = "success";
                    
                    // Enviar notificación por correo (simulado)
                    // En un entorno real, aquí se implementaría el envío de correo
                    
                    // Limpiar formulario
                    $nombre = $email = $telefono = $asunto = $mensaje_texto = "";
                } else {
                    $mensaje = "Lo sentimos, hubo un problema al enviar su mensaje. Por favor, inténtelo de nuevo más tarde.";
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

<div class="container py-5">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="mb-4">Contacto</h1>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h5 class="m-0 font-weight-bold text-primary">Formulario de Contacto</h5>
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
                    
                    <form action="index.php?page=contacto" method="POST" id="contactForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre completo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo isset($nombre) ? $nombre : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo electrónico <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($email) ? $email : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono" value="<?php echo isset($telefono) ? $telefono : ''; ?>">
                            <div class="form-text">Opcional, pero recomendado para contacto más rápido</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="asunto" class="form-label">Asunto <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="asunto" name="asunto" value="<?php echo isset($asunto) ? $asunto : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="mensaje" class="form-label">Mensaje <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="mensaje" name="mensaje" rows="5" required><?php echo isset($mensaje_texto) ? $mensaje_texto : ''; ?></textarea>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="privacidad" required>
                            <label class="form-check-label" for="privacidad">He leído y acepto la <a href="index.php?page=privacidad" target="_blank">Política de Privacidad</a> <span class="text-danger">*</span></label>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i> Enviar Mensaje
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h5 class="m-0 font-weight-bold text-primary">Información de Contacto</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h6 class="font-weight-bold"><i class="fas fa-map-marker-alt text-primary me-2"></i> Dirección</h6>
                        <p class="mb-0">Av. Juárez #123</p>
                        <p class="mb-0">Col. Centro</p>
                        <p>Manzanillo, Colima, México</p>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="font-weight-bold"><i class="fas fa-phone text-primary me-2"></i> Teléfono</h6>
                        <p class="mb-0">(314) 123-4567</p>
                        <p>Lunes a Viernes, 9:00 AM - 5:00 PM</p>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="font-weight-bold"><i class="fas fa-envelope text-primary me-2"></i> Correo Electrónico</h6>
                        <p class="mb-0">contacto@sugerencias-manzanillo.mx</p>
                        <p>Tiempo de respuesta: 24-48 horas</p>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="font-weight-bold"><i class="fas fa-globe text-primary me-2"></i> Redes Sociales</h6>
                        <div class="d-flex mt-2">
                            <a href="#" class="btn btn-outline-primary me-2" title="Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="btn btn-outline-info me-2" title="Twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="btn btn-outline-danger me-2" title="Instagram">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" class="btn btn-outline-success" title="WhatsApp">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h5 class="m-0 font-weight-bold text-primary">Horario de Atención</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Lunes a Viernes
                            <span>9:00 AM - 5:00 PM</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Sábado
                            <span>9:00 AM - 1:00 PM</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Domingo
                            <span>Cerrado</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-lg-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h5 class="m-0 font-weight-bold text-primary">Ubicación</h5>
                </div>
                <div class="card-body p-0">
                    <div class="ratio ratio-16x9">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15102.084365222407!2d-104.31560799999999!3d19.049499999999998!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8424d43db5361d01%3A0x5d5574c02b524826!2sManzanillo%2C%20Col.!5e0!3m2!1ses-419!2smx!4v1652645321234!5m2!1ses-419!2smx" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validación del formulario
    const form = document.getElementById('contactForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            const nombre = document.getElementById('nombre').value;
            const email = document.getElementById('email').value;
            const asunto = document.getElementById('asunto').value;
            const mensaje = document.getElementById('mensaje').value;
            const privacidad = document.getElementById('privacidad').checked;
            
            let isValid = true;
            let errorMessage = '';
            
            if (nombre.length < 3) {
                isValid = false;
                errorMessage = 'El nombre debe tener al menos 3 caracteres.';
            } else if (!validateEmail(email)) {
                isValid = false;
                errorMessage = 'Por favor, ingrese un correo electrónico válido.';
            } else if (asunto.length < 5) {
                isValid = false;
                errorMessage = 'El asunto debe tener al menos 5 caracteres.';
            } else if (mensaje.length < 10) {
                isValid = false;
                errorMessage = 'El mensaje debe tener al menos 10 caracteres.';
            } else if (!privacidad) {
                isValid = false;
                errorMessage = 'Debe aceptar la política de privacidad.';
            }
            
            if (!isValid) {
                event.preventDefault();
                alert(errorMessage);
            }
        });
    }
    
    // Función para validar email
    function validateEmail(email) {
        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }
});
</script>
