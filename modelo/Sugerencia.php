<?php
class Sugerencia {
    private $conn;
    private $table_name = "sugerencias";

    public $id;
    public $titulo;
    public $descripcion;
    public $usuario_id;
    public $categoria_id;
    public $estado_id; // 1: Nueva, 2: En revisión, 3: Aprobada, 4: Rechazada
    public $funcionario_id;
    public $respuesta;
    public $fecha_creacion;
    public $fecha_actualizacion;
    public $fecha_revision;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear una nueva sugerencia
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (titulo, descripcion, usuario_id, categoria_id, estado_id) 
                  VALUES (:titulo, :descripcion, :usuario_id, :categoria_id, :estado_id)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos
        $this->titulo = htmlspecialchars(strip_tags($this->titulo));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));

        // Vincular valores
        $stmt->bindParam(":titulo", $this->titulo);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":usuario_id", $this->usuario_id);
        $stmt->bindParam(":categoria_id", $this->categoria_id);
        $stmt->bindParam(":estado_id", $this->estado_id);

        // Ejecutar consulta
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }

        return false;
    }

    // Obtener todas las sugerencias
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

    // Obtener sugerencias por estado
    public function obtenerPorEstado($estado_id, $page = 1, $items_per_page = 10) {
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
                WHERE s.estado_id = :estado_id
                ORDER BY s.fecha_creacion DESC
                LIMIT :offset, :items_per_page";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':estado_id', $estado_id);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':items_per_page', $items_per_page, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    // Obtener sugerencias de un usuario
    public function obtenerPorUsuario($usuario_id, $page = 1, $items_per_page = 10) {
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
                WHERE s.usuario_id = :usuario_id
                ORDER BY s.fecha_creacion DESC
                LIMIT :offset, :items_per_page";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':items_per_page', $items_per_page, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    // Obtener una sugerencia por ID
    public function obtenerPorId($id) {
        $query = "SELECT s.*, c.nombre as categoria_nombre, c.color as categoria_color, 
                       e.nombre as estado_nombre, e.color as estado_color,
                       u.nombre as usuario_nombre, u.apellidos as usuario_apellidos,
                       f.nombre as funcionario_nombre, f.apellidos as funcionario_apellidos
                FROM " . $this->table_name . " s
                LEFT JOIN categorias c ON s.categoria_id = c.id
                LEFT JOIN estados e ON s.estado_id = e.id
                LEFT JOIN usuarios u ON s.usuario_id = u.id
                LEFT JOIN usuarios f ON s.funcionario_id = f.id
                WHERE s.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->id = $row['id'];
            $this->titulo = $row['titulo'];
            $this->descripcion = $row['descripcion'];
            $this->usuario_id = $row['usuario_id'];
            $this->categoria_id = $row['categoria_id'];
            $this->estado_id = $row['estado_id'];
            $this->funcionario_id = $row['funcionario_id'];
            $this->respuesta = $row['respuesta'];
            $this->fecha_creacion = $row['fecha_creacion'];
            $this->fecha_actualizacion = $row['fecha_actualizacion'];
            $this->fecha_revision = $row['fecha_revision'];
            
            return $row;
        }
        
        return false;
    }

    // Actualizar estado de sugerencia
    public function actualizarEstado($id, $estado_id, $funcionario_id = null, $respuesta = null) {
        $query = "UPDATE " . $this->table_name . " 
                  SET estado_id = :estado_id, 
                      funcionario_id = :funcionario_id, 
                      respuesta = :respuesta, 
                      fecha_actualizacion = NOW(), 
                      fecha_revision = CASE WHEN :estado_id IN (3, 4) THEN NOW() ELSE fecha_revision END
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        
        // Sanitizar respuesta si existe
        if ($respuesta) {
            $respuesta = htmlspecialchars(strip_tags($respuesta));
        }
        
        // Vincular valores
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':estado_id', $estado_id);
        $stmt->bindParam(':funcionario_id', $funcionario_id);
        $stmt->bindParam(':respuesta', $respuesta);
        
        // Ejecutar consulta
        return $stmt->execute();
    }

    // Contar total de sugerencias
    public function contarTotal() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }

    // Contar sugerencias por estado
    public function contarPorEstado($estado_id) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE estado_id = :estado_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':estado_id', $estado_id);
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
}
?>
