<?php
// Incluir archivos necesarios
include_once 'config/database.php';
include_once 'modelo/Solicitud.php';
include_once 'modelo/Usuario.php';
include_once 'modelo/Categoria.php';
include_once 'modelo/Estado.php';

class DashboardControlador {
    private $db;
    private $solicitud;
    private $usuario;
    private $categoria;
    private $estado;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->solicitud = new Solicitud($this->db);
        $this->usuario = new Usuario($this->db);
        $this->categoria = new Categoria($this->db);
        $this->estado = new Estado($this->db);
    }

    // Obtener datos para el dashboard de administrador
    public function obtenerDatosAdminDashboard() {
        // Contar total de solicitudes
        $total_solicitudes = $this->solicitud->contarTotal();
        
        // Contar total de usuarios
        $total_usuarios = $this->usuario->contarTotal();
        
        // Obtener estadísticas por categoría
        $estadisticas_categoria = $this->solicitud->estadisticasPorCategoria();
        
        // Obtener estadísticas por estado
        $estadisticas_estado = $this->solicitud->estadisticasPorEstado();
        
        // Obtener solicitudes recientes
        $solicitudes_recientes = $this->solicitud->obtenerRecientes(5);
        
        return [
            'total_solicitudes' => $total_solicitudes,
            'total_usuarios' => $total_usuarios,
            'estadisticas_categoria' => $estadisticas_categoria,
            'estadisticas_estado' => $estadisticas_estado,
            'solicitudes_recientes' => $solicitudes_recientes
        ];
    }

    // DashboardController.php - Funciones agregadas
    public function obtenerSolicitudesPorUsuario($usuario_id) {
        // return $this->solicitud->obtenerPorUsuario($usuario_id);
    }

    public function obtenerSolicitudesPorEstado($estado_id) {
        return $this->solicitud->obtenerPorEstado($estado_id);
    }

    public function obtenerSolicitudesPorCategoria($categoria_id) {
        return $this->solicitud->obtenerPorCategoria($categoria_id);
    }

    public function obtenerSolicitudesRecientesPorUsuario($usuario_id, $limit = 5) {
        return $this->solicitud->obtenerRecientesPorUsuario($usuario_id, $limit);
    }

    public function obtenerEstadisticasUsuario($usuario_id) {
        return [
            'total_solicitudes' => $this->solicitud->contarPorUsuario($usuario_id),
            'solicitudes_por_estado' => $this->solicitud->estadisticasPorEstadoUsuario($usuario_id),
            'solicitudes_por_categoria' => $this->solicitud->estadisticasPorCategoriaUsuario($usuario_id)
        ];
    }
    
    

    // Obtener datos para dashboard de funcionario
    public function obtenerDatosFuncionarioDashboard($funcionario_id) {
        return [
            'total_solicitudes' => $this->solicitud->contarPorFuncionario($funcionario_id),
            'solicitudes_por_estado' => $this->solicitud->estadisticasPorEstadoFuncionario($funcionario_id),
            'solicitudes_recientes' => $this->solicitud->obtenerPorFuncionario($funcionario_id, 8),
            'rendimiento_mensual' => $this->solicitud->rendimientoMensualFuncionario($funcionario_id),
            'top_categorias' => $this->solicitud->topCategoriasLentasFuncionario($funcionario_id)
        ];
    }

    // Obtener datos para dashboard de ciudadano
    public function obtenerDatosCiudadanoDashboard($usuario_id) {
        return [
            'total_solicitudes' => $this->solicitud->contarPorUsuario($usuario_id),
            'solicitudes_por_estado' => $this->solicitud->estadisticasPorEstadoUsuario($usuario_id),
            // 'solicitudes_recientes' => $this->solicitud->obtenerPorUsuario($usuario_id, 5),
            'evolucion_solicitudes' => $this->solicitud->evolucionSolicitudesUsuario($usuario_id),
            'tiempo_promedio' => $this->solicitud->tiempoPromedioResolucionUsuario($usuario_id)
        ];
    }

    // Método para obtener solicitudes con filtros
    public function obtenerSolicitudesFiltradas($filtros) {
        return $this->solicitud->obtenerConFiltros($filtros);
    }

    // Obtener todas las categorías
    public function obtenerCategorias() {
        return $this->categoria->obtenerTodas();
    }

    // Obtener todos los estados
    public function obtenerEstados() {
        return $this->estado->obtenerTodos();
    }
}
?>
