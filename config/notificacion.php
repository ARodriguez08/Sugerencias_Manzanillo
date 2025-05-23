<?php
class Notification {
    private $conn;
    private $table_name = "notificaciones";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Crear una nueva notificación
    public function crear($usuario_id, $titulo, $mensaje, $solicitud_id = null) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (usuario_id, titulo, mensaje, solicitud_id, leida) 
                  VALUES (:usuario_id, :titulo, :mensaje, :solicitud_id, 0)";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitizar datos
        $titulo = htmlspecialchars(strip_tags($titulo));
        $mensaje = htmlspecialchars(strip_tags($mensaje));
        
        // Vincular valores
        $stmt->bindParam(":usuario_id", $usuario_id, PDO::PARAM_INT);
        $stmt->bindParam(":titulo", $titulo, PDO::PARAM_STR);
        $stmt->bindParam(":mensaje", $mensaje, PDO::PARAM_STR);
        if ($solicitud_id === null) {
            $stmt->bindValue(":solicitud_id", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":solicitud_id", $solicitud_id, PDO::PARAM_INT);
        }
        
        // Ejecutar consulta
        try {
            if ($stmt->execute()) {
                return true;
            }
        } catch (PDOException $e) {
            error_log("Error al crear notificación: " . $e->getMessage());
        }
        
        return false;
    }
    
    // Obtener notificaciones no leídas de un usuario
    public function obtenerNoLeidas($usuario_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE usuario_id = :usuario_id AND leida = 0 
                  ORDER BY fecha_creacion DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":usuario_id", $usuario_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Obtener todas las notificaciones de un usuario
    public function obtenerTodas($usuario_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE usuario_id = :usuario_id 
                  ORDER BY fecha_creacion DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":usuario_id", $usuario_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Marcar notificación como leída
    public function marcarLeida($id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET leida = 1 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }
    
    // Marcar todas las notificaciones de un usuario como leídas
    public function marcarTodasLeidas($usuario_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET leida = 1 
                  WHERE usuario_id = :usuario_id AND leida = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":usuario_id", $usuario_id);
        
        return $stmt->execute();
    }
    
    // Contar notificaciones no leídas de un usuario
    public function contarNoLeidas($usuario_id) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                  WHERE usuario_id = :usuario_id AND leida = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":usuario_id", $usuario_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
}
?>
