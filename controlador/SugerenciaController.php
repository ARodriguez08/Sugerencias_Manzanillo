<?php
// Incluir archivos necesarios
include_once 'config/database.php';
include_once 'config/security.php';
include_once 'modelo/Sugerencia.php';
include_once 'modelo/Categoria.php';
include_once 'config/notification.php';

class SugerenciaController {
    private $db;
    private $sugerencia;
    private $categoria;
    private $notificacion;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->sugerencia = new Sugerencia($this->db);
        $this->categoria = new Categoria($this->db);
        $this->notificacion = new Notification($this->db);
    }

    public function obtenerEstados() {
        // Return an array of states or fetch them from the database
        return ['Pendiente', 'En Proceso', 'Resuelto'];
    }

    public function obtenerSugerencias($page_num, $filtro, $estado) {
        // Implement logic to fetch suggestions based on page number, filter, and state
        // Example return value (replace with actual implementation):
        return [
            'sugerencias' => [], // Array of suggestions
            'total_pages' => 1   // Total number of pages
        ];
    }

    // Procesar creación de nueva sugerencia
    public function crearSugerencia() {
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
                        $this->sugerencia->titulo = $titulo;
                        $this->sugerencia->descripcion = $descripcion;
                        $this->sugerencia->categoria_id = $categoria_id;
                        $this->sugerencia->usuario_id = $_SESSION['usuario_id'];
                        $this->sugerencia->estado_id = 1; // Estado "Nueva"
                        
                        $sugerencia_id = $this->sugerencia->crear();
                        
                        if ($sugerencia_id) {
                            // Crear notificación para administradores y funcionarios
                            $query_admins = "SELECT id FROM usuarios WHERE rol_id IN (1, 2)"; // 1: admin, 2: funcionario
                            $stmt_admins = $this->db->prepare($query_admins);
                            $stmt_admins->execute();
                            
                            while ($admin = $stmt_admins->fetch(PDO::FETCH_ASSOC)) {
                                $this->notificacion->crear(
                                    $admin['id'], 
                                    "Nueva sugerencia", 
                                    "Un ciudadano ha enviado una nueva sugerencia: {$titulo}",
                                    $sugerencia_id
                                );
                            }
                            
                            $mensaje = "¡Sugerencia enviada correctamente! Gracias por contribuir a mejorar nuestra comunidad.";
                            $tipo_mensaje = "success";
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
        
        // Obtener categorías para el formulario
        $categorias = $this->categoria->obtenerTodas();
        
        return [
            'mensaje' => $mensaje,
            'tipo_mensaje' => $tipo_mensaje,
            'categorias' => $categorias,
            'csrf_token' => Security::generateCSRFToken()
        ];
    }

    // Procesar actualización de estado de sugerencia por funcionario
    public function actualizarEstadoSugerencia() {
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
                        if ($this->sugerencia->actualizarEstado($sugerencia_id, $estado_id, $funcionario_id, $respuesta)) {
                            // Obtener información de la sugerencia para la notificación
                            $sugerencia_info = $this->sugerencia->obtenerPorId($sugerencia_id);
                            
                            // Crear notificación para el ciudadano
                            $estado_texto = ($estado_id == 3) ? "aprobada" : (($estado_id == 4) ? "rechazada" : "en revisión");
                            $this->notificacion->crear(
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
        
        return [
            'mensaje' => $mensaje,
            'tipo_mensaje' => $tipo_mensaje
        ];
    }

    // Obtener sugerencias para el dashboard de funcionario
    public function obtenerSugerenciasFuncionario($estado_filtro = null, $page = 1) {
        $items_per_page = 10;
        
        if ($estado_filtro) {
            $sugerencias = $this->sugerencia->obtenerPorEstado($estado_filtro, $page, $items_per_page);
            $total_sugerencias = $this->sugerencia->contarPorEstado($estado_filtro);
        } else {
            $sugerencias = $this->sugerencia->obtenerTodas($page, $items_per_page);
            $total_sugerencias = $this->sugerencia->contarTotal();
        }
        
        $total_pages = ceil($total_sugerencias / $items_per_page);
        
        return [
            'sugerencias' => $sugerencias,
            'total_sugerencias' => $total_sugerencias,
            'total_pages' => $total_pages,
            'current_page' => $page,
            'estado_filtro' => $estado_filtro,
            'csrf_token' => Security::generateCSRFToken()
        ];
    }

    // Obtener sugerencias de un ciudadano
    public function obtenerSugerenciasCiudadano($usuario_id, $page = 1) {
        $items_per_page = 10;
        
        $sugerencias = $this->sugerencia->obtenerPorUsuario($usuario_id, $page, $items_per_page);
        
        // Contar total de sugerencias del usuario
        $query_total = "SELECT COUNT(*) as total FROM sugerencias WHERE usuario_id = :usuario_id";
        $stmt_total = $this->db->prepare($query_total);
        $stmt_total->bindParam(':usuario_id', $usuario_id);
        $stmt_total->execute();
        $total_sugerencias = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];
        
        $total_pages = ceil($total_sugerencias / $items_per_page);
        
        // Contar sugerencias por estado
        $query_stats = "SELECT 
                        (SELECT COUNT(*) FROM sugerencias WHERE usuario_id = :usuario_id AND estado_id = 1) as nuevas,
                        (SELECT COUNT(*) FROM sugerencias WHERE usuario_id = :usuario_id AND estado_id = 2) as en_revision,
                        (SELECT COUNT(*) FROM sugerencias WHERE usuario_id = :usuario_id AND estado_id = 3) as aprobadas,
                        (SELECT COUNT(*) FROM sugerencias WHERE usuario_id = :usuario_id AND estado_id = 4) as rechazadas";
        
        $stmt_stats = $this->db->prepare($query_stats);
        $stmt_stats->bindParam(':usuario_id', $usuario_id);
        $stmt_stats->execute();
        $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
        
        return [
            'sugerencias' => $sugerencias,
            'total_sugerencias' => $total_sugerencias,
            'total_pages' => $total_pages,
            'current_page' => $page,
            'stats' => $stats
        ];
    }

    // Obtener detalles de una sugerencia específica
    public function obtenerDetalleSugerencia($id) {
        return $this->sugerencia->obtenerPorId($id);
    }
}
?>
