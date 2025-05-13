<?php
class Usuario {
    private $conn;
    private $table_name = "usuarios";

    public $id;
    public $nombre;
    public $apellidos;
    public $email;
    public $password;
    public $telefono;
    public $direccion;
    public $rol_id;
    public $activo;
    public $fecha_registro;
    public $ultima_actualizacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear un nuevo usuario
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nombre=:nombre, apellidos=:apellidos, email=:email, 
                      password=:password, telefono=:telefono, direccion=:direccion, 
                      rol_id=:rol_id, activo=:activo";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->apellidos = htmlspecialchars(strip_tags($this->apellidos));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->telefono = htmlspecialchars(strip_tags($this->telefono));
        $this->direccion = htmlspecialchars(strip_tags($this->direccion));
        
        // Hash de la contraseña
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);

        // Vincular valores
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellidos", $this->apellidos);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $password_hash);
        $stmt->bindParam(":telefono", $this->telefono);
        $stmt->bindParam(":direccion", $this->direccion);
        $stmt->bindParam(":rol_id", $this->rol_id);
        $stmt->bindParam(":activo", $this->activo);

        // Ejecutar consulta
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Verificar si el email ya existe
    public function emailExiste() {
        $query = "SELECT id, nombre, apellidos, password, rol_id 
                FROM " . $this->table_name . " 
                WHERE email = ? 
                LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $this->email = htmlspecialchars(strip_tags($this->email));
        $stmt->bindParam(1, $this->email);
        $stmt->execute();

        $num = $stmt->rowCount();

        if($num > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->id = $row['id'];
            $this->nombre = $row['nombre'];
            $this->apellidos = $row['apellidos'];
            $this->password = $row['password'];
            $this->rol_id = $row['rol_id'];
            
            return true;
        }

        return false;
    }

    // Iniciar sesión / verificar credenciales
    public function login() {
        if($this->emailExiste()) {
            if(password_verify($this->password, $this->password)) {
                return true;
            }
        }
        return false;
    }

    // Obtener todos los usuarios
    public function obtenerTodos($page = 1, $items_per_page = 10) {
        $offset = ($page - 1) * $items_per_page;
        
        $query = "SELECT u.*, r.nombre as rol_nombre 
                FROM " . $this->table_name . " u
                LEFT JOIN roles r ON u.rol_id = r.id
                ORDER BY u.fecha_registro DESC
                LIMIT :offset, :items_per_page";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':items_per_page', $items_per_page, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    // Obtener un usuario por ID
    public function obtenerPorId($id) {
        $query = "SELECT u.*, r.nombre as rol_nombre 
                FROM " . $this->table_name . " u
                LEFT JOIN roles r ON u.rol_id = r.id
                WHERE u.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->id = $row['id'];
            $this->nombre = $row['nombre'];
            $this->apellidos = $row['apellidos'];
            $this->email = $row['email'];
            $this->telefono = $row['telefono'];
            $this->direccion = $row['direccion'];
            $this->rol_id = $row['rol_id'];
            $this->activo = $row['activo'];
            $this->fecha_registro = $row['fecha_registro'];
            $this->ultima_actualizacion = $row['ultima_actualizacion'];
            
            return true;
        }
        
        return false;
    }

    // Actualizar usuario
    public function actualizar() {
        $query = "UPDATE " . $this->table_name . " 
                SET nombre=:nombre, apellidos=:apellidos, email=:email, 
                    telefono=:telefono, direccion=:direccion, 
                    rol_id=:rol_id, activo=:activo 
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->apellidos = htmlspecialchars(strip_tags($this->apellidos));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->telefono = htmlspecialchars(strip_tags($this->telefono));
        $this->direccion = htmlspecialchars(strip_tags($this->direccion));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Vincular valores
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellidos", $this->apellidos);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":telefono", $this->telefono);
        $stmt->bindParam(":direccion", $this->direccion);
        $stmt->bindParam(":rol_id", $this->rol_id);
        $stmt->bindParam(":activo", $this->activo);
        $stmt->bindParam(":id", $this->id);

        // Ejecutar consulta
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Actualizar contraseña
    public function actualizarPassword() {
        $query = "UPDATE " . $this->table_name . " 
                SET password = :password 
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Hash de la contraseña
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);
        
        // Vincular valores
        $stmt->bindParam(":password", $password_hash);
        $stmt->bindParam(":id", $this->id);

        // Ejecutar consulta
        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Eliminar usuario
    public function eliminar() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Contar total de usuarios
    public function contarTotal() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }

    public function obtenerUsuariosPorRol($rol_id) {
        $query = "SELECT u.*, r.nombre as rol_nombre 
                  FROM " . $this->table_name . " u
                  LEFT JOIN roles r ON u.rol_id = r.id
                  WHERE u.rol_id = :rol_id
                  ORDER BY u.fecha_registro DESC";
    
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':rol_id', $rol_id);
        $stmt->execute();
    
        return $stmt;
    }
}
?>
