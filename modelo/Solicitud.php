<?php
class Solicitud {
    private $conn;
    private $table_name = "solicitudes";

    public $id;
    public $titulo;
    public $descripcion;
    public $categoria_id;
    public $estado_id;
    public $usuario_id;
    public $funcionario_id;
    public $latitud;
    public $longitud;
    public $direccion;
    public $fecha_creacion;
    public $fecha_actualizacion;
    public $fecha_resolucion;
    public $prioridad;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Obtener todas las solicitudes
    public function obtenerTodas($page = 1, $items_per_page = 10) {
        $offset = ($page - 1) * $items_per_page;
        
        $query = "SELECT s.*, c.nombre as categoria_nombre, c.color as categoria_color, 
                         e.nombre as estado_nombre, e.color as estado_color,
                         u.nombre as usuario_nombre, u.apellidos as usuario_apellidos,
                         f.nombre as funcionario_nombre, f.apellidos as funcionario_apellidos
                  FROM " . $this->table_name . " s
                  LEFT JOIN categorias c ON s.categoria_id = c.id
                  LEFT JOIN estados e ON s.estado_id = e.id
                  LEFT JOIN usuarios u ON s.usuario_id = u.id
                  LEFT JOIN usuarios f ON s.funcionario_id = f.id
                  ORDER BY s.fecha_creacion DESC
                  LIMIT :offset, :items_per_page";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':items_per_page', $items_per_page, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    // Contar total de solicitudes
    public function contarTotal() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }

    // Obtener estadísticas por categoría
    public function estadisticasPorCategoria() {
        $query = "SELECT c.nombre, c.color, COUNT(s.id) as total
                  FROM " . $this->table_name . " s
                  LEFT JOIN categorias c ON s.categoria_id = c.id
                  GROUP BY s.categoria_id
                  ORDER BY total DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    // Obtener estadísticas por estado
    public function estadisticasPorEstado() {
        $query = "SELECT e.nombre, e.color, COUNT(s.id) as total
                  FROM " . $this->table_name . " s
                  LEFT JOIN estados e ON s.estado_id = e.id
                  GROUP BY s.estado_id
                  ORDER BY total DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    // Obtener solicitudes recientes
    public function obtenerRecientes($limit = 5) {
        $query = "SELECT s.*, c.nombre as categoria_nombre, c.color as categoria_color, 
                         e.nombre as estado_nombre, e.color as estado_color
                  FROM " . $this->table_name . " s
                  LEFT JOIN categorias c ON s.categoria_id = c.id
                  LEFT JOIN estados e ON s.estado_id = e.id
                  ORDER BY s.fecha_creacion DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    // Métodos agregados
    public function obtenerPorUsuario($usuario_id) {
        $query = "SELECT s.*, c.nombre as categoria_nombre, c.color as categoria_color, 
                         e.nombre as estado_nombre, e.color as estado_color
                  FROM " . $this->table_name . " s
                  LEFT JOIN categorias c ON s.categoria_id = c.id
                  LEFT JOIN estados e ON s.estado_id = e.id
                  WHERE s.usuario_id = :usuario_id
                  ORDER BY s.fecha_creacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->execute();

        return $stmt;
    }

    public function obtenerPorEstado($estado_id) {
        $query = "SELECT s.*, c.nombre as categoria_nombre, c.color as categoria_color,
                         u.nombre as usuario_nombre, u.apellidos as usuario_apellidos
                  FROM " . $this->table_name . " s
                  LEFT JOIN categorias c ON s.categoria_id = c.id
                  LEFT JOIN usuarios u ON s.usuario_id = u.id
                  WHERE s.estado_id = :estado_id
                  ORDER BY s.fecha_creacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':estado_id', $estado_id);
        $stmt->execute();

        return $stmt;
    }

    public function obtenerPorCategoria($categoria_id) {
        $query = "SELECT s.*, e.nombre as estado_nombre, e.color as estado_color,
                         u.nombre as usuario_nombre, u.apellidos as usuario_apellidos
                  FROM " . $this->table_name . " s
                  LEFT JOIN estados e ON s.estado_id = e.id
                  LEFT JOIN usuarios u ON s.usuario_id = u.id
                  WHERE s.categoria_id = :categoria_id
                  ORDER BY s.fecha_creacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':categoria_id', $categoria_id);
        $stmt->execute();

        return $stmt;
    }

    public function obtenerRecientesPorUsuario($usuario_id, $limit = 5) {
        $query = "SELECT s.*, c.nombre as categoria_nombre, c.color as categoria_color, 
                         e.nombre as estado_nombre, e.color as estado_color
                  FROM " . $this->table_name . " s
                  LEFT JOIN categorias c ON s.categoria_id = c.id
                  LEFT JOIN estados e ON s.estado_id = e.id
                  WHERE s.usuario_id = :usuario_id
                  ORDER BY s.fecha_creacion DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    public function contarPorUsuario($usuario_id) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE usuario_id = :usuario_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }

    public function estadisticasPorEstadoUsuario($usuario_id) {
        $query = "SELECT e.nombre, e.color, COUNT(s.id) as total
                  FROM " . $this->table_name . " s
                  LEFT JOIN estados e ON s.estado_id = e.id
                  WHERE s.usuario_id = :usuario_id
                  GROUP BY s.estado_id
                  ORDER BY total DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->execute();
        
        return $stmt;
    }

    public function estadisticasPorCategoriaUsuario($usuario_id) {
        $query = "SELECT c.nombre, c.color, COUNT(s.id) as total
                  FROM " . $this->table_name . " s
                  LEFT JOIN categorias c ON s.categoria_id = c.id
                  WHERE s.usuario_id = :usuario_id
                  GROUP BY s.categoria_id
                  ORDER BY total DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->execute();
        
        return $stmt;
    }

    public function contarPorFuncionario($funcionario_id) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE funcionario_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$funcionario_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
    
    public function estadisticasPorEstadoFuncionario($funcionario_id) {
        $query = "SELECT e.nombre, e.color, COUNT(s.id) as total 
                  FROM " . $this->table_name . " s
                  JOIN estados e ON s.estado_id = e.id
                  WHERE s.funcionario_id = ?
                  GROUP BY s.estado_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$funcionario_id]);
        return $stmt;
    }
    
    public function rendimientoMensualFuncionario($funcionario_id) {
        $query = "SELECT DATE_FORMAT(fecha_resolucion, '%Y-%m') as mes, 
                  COUNT(*) as resueltas,
                  AVG(DATEDIFF(fecha_resolucion, fecha_creacion)) as tiempo_promedio
                  FROM " . $this->table_name . "
                  WHERE funcionario_id = ? AND fecha_resolucion IS NOT NULL
                  GROUP BY DATE_FORMAT(fecha_resolucion, '%Y-%m')
                  ORDER BY mes ASC
                  LIMIT 6";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$funcionario_id]);
        return $stmt;
    }
    
    public function topCategoriasLentasFuncionario($funcionario_id) {
        $query = "SELECT c.nombre, c.color, 
                  AVG(DATEDIFF(s.fecha_resolucion, s.fecha_creacion)) as tiempo_promedio,
                  COUNT(s.id) as total
                  FROM " . $this->table_name . " s
                  JOIN categorias c ON s.categoria_id = c.id
                  WHERE s.funcionario_id = ? AND s.fecha_resolucion IS NOT NULL
                  GROUP BY s.categoria_id
                  ORDER BY tiempo_promedio DESC
                  LIMIT 3";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$funcionario_id]);
        return $stmt;
    }
    
    public function evolucionSolicitudesUsuario($usuario_id) {
        $query = "SELECT DATE_FORMAT(fecha_creacion, '%Y-%m') as mes, COUNT(*) as total
                  FROM " . $this->table_name . "
                  WHERE usuario_id = ?
                  GROUP BY DATE_FORMAT(fecha_creacion, '%Y-%m')
                  ORDER BY mes ASC
                  LIMIT 6";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$usuario_id]);
        return $stmt;
    }
    
    public function tiempoPromedioResolucionUsuario($usuario_id) {
        $query = "SELECT AVG(DATEDIFF(fecha_resolucion, fecha_creacion)) as promedio
                  FROM " . $this->table_name . "
                  WHERE usuario_id = ? AND fecha_resolucion IS NOT NULL";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$usuario_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['promedio'] ? round($row['promedio'], 1) : 0;
    }

    public function obtenerConFiltros($filtros) {
        // Example implementation: Adjust the query based on your database structure and filters
        $query = "SELECT * FROM solicitudes WHERE 1=1";
        
        foreach ($filtros as $campo => $valor) {
            $query .= " AND " . $campo . " = :" . $campo;
        }

        $stmt = $this->conn->prepare($query);

        foreach ($filtros as $campo => $valor) {
            $stmt->bindValue(':' . $campo, $valor);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function obtenerPorFuncionario($funcionario_id, $limit = 8) {
        $query = "SELECT * FROM solicitudes WHERE funcionario_id = :funcionario_id ORDER BY fecha_creacion DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':funcionario_id', $funcionario_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
