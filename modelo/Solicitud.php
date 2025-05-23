<?php
class Solicitud
{
    private $pdo;

    public function __construct()
    {
        $host = 'localhost';
        $db   = 'sugerencias_manzanillo';
        $user = 'root';
        $pass = '';
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new \PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    // Obtener todas las solicitudes
    public function obtenerSolicitudes()
    {
        $stmt = $this->pdo->query("SELECT s.*, c.nombre AS categoria, e.nombre AS estado, u.nombre AS usuario
            FROM solicitudes s
            JOIN categorias c ON s.categoria_id = c.id
            JOIN estados e ON s.estado_id = e.id
            JOIN usuarios u ON s.usuario_id = u.id
            ORDER BY s.fecha_creacion DESC");
        return $stmt->fetchAll();
    }

    // Recibir y guardar una nueva solicitud
    public function recibirSolicitud($datos)
    {
        if (
            empty($datos['titulo']) ||
            empty($datos['descripcion']) ||
            empty($datos['categoria_id']) ||
            empty($datos['usuario_id'])
        ) {
            return ['exito' => false, 'mensaje' => 'Título, descripción, categoría y usuario son obligatorios.'];
        }

        $estado_id = 1;
        $prioridad = isset($datos['prioridad']) ? $datos['prioridad'] : 'Media';

        $sql = "INSERT INTO solicitudes (titulo, descripcion, categoria_id, estado_id, usuario_id, latitud, longitud, direccion, prioridad)
                VALUES (:titulo, :descripcion, :categoria_id, :estado_id, :usuario_id, :latitud, :longitud, :direccion, :prioridad)";
        $stmt = $this->pdo->prepare($sql);

        $stmt->bindValue(':titulo', $datos['titulo']);
        $stmt->bindValue(':descripcion', $datos['descripcion']);
        $stmt->bindValue(':categoria_id', $datos['categoria_id']);
        $stmt->bindValue(':estado_id', $estado_id);
        $stmt->bindValue(':usuario_id', $datos['usuario_id']);
        $stmt->bindValue(':latitud', isset($datos['latitud']) ? $datos['latitud'] : null);
        $stmt->bindValue(':longitud', isset($datos['longitud']) ? $datos['longitud'] : null);
        $stmt->bindValue(':direccion', isset($datos['direccion']) ? $datos['direccion'] : null);
        $stmt->bindValue(':prioridad', $prioridad);

        try {
            $stmt->execute();
            return ['exito' => true, 'mensaje' => 'Solicitud recibida correctamente.'];
        } catch (\PDOException $e) {
            return ['exito' => false, 'mensaje' => 'Error al guardar la solicitud: ' . $e->getMessage()];
        }
    }

public function contarTotal() {
    $query = "SELECT COUNT(*) as total FROM solicitudes";
    $stmt = $this->pdo->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['total'] : 0;
}

public function estadisticasPorCategoria() {
    $query = "SELECT categoria_id, COUNT(*) as total FROM solicitudes GROUP BY categoria_id";
    $stmt = $this->pdo->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function estadisticasPorEstado() {
    $query = "SELECT estado_id, COUNT(*) as total FROM solicitudes GROUP BY estado_id";
    $stmt = $this->pdo->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function obtenerRecientes($limit = 5) {
    $query = "SELECT * FROM solicitudes ORDER BY fecha_creacion DESC LIMIT ?";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    public function obtenerPorEstado($estado_id) {
        $query = "SELECT * FROM solicitudes WHERE estado_id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(1, $estado_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorCategoria($categoria_id) {
        $query = "SELECT * FROM solicitudes WHERE categoria_id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(1, $categoria_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerRecientesPorUsuario($usuario_id, $limit = 5) {
        $query = "SELECT * FROM solicitudes WHERE usuario_id = ? ORDER BY fecha_creacion DESC LIMIT ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(1, $usuario_id, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarPorUsuario($usuario_id) {
        $query = "SELECT COUNT(*) as total FROM solicitudes WHERE usuario_id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(1, $usuario_id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['total'] : 0;
    }

    public function estadisticasPorEstadoUsuario($usuario_id) {
        $query = "SELECT estado_id, COUNT(*) as total FROM solicitudes WHERE usuario_id = ? GROUP BY estado_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(1, $usuario_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function estadisticasPorCategoriaUsuario($usuario_id) {
        $query = "SELECT categoria_id, COUNT(*) as total FROM solicitudes WHERE usuario_id = ? GROUP BY categoria_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(1, $usuario_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarPorFuncionario($funcionario_id) {
        $query = "SELECT COUNT(*) as total FROM solicitudes WHERE funcionario_id = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(1, $funcionario_id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['total'] : 0;
    }

    public function estadisticasPorEstadoFuncionario($funcionario_id) {
        $query = "SELECT estado_id, COUNT(*) as total FROM solicitudes WHERE funcionario_id = ? GROUP BY estado_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(1, $funcionario_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorFuncionario($funcionario_id, $limit = null) {
        $query = "SELECT * FROM solicitudes WHERE funcionario_id = ? ORDER BY fecha_creacion DESC";
        if ($limit !== null) {
            $query .= " LIMIT ?";
        }
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(1, $funcionario_id, PDO::PARAM_INT);
        if ($limit !== null) {
            $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function rendimientoMensualFuncionario($funcionario_id) {
        $query = "SELECT MONTH(fecha_creacion) as mes, COUNT(*) as total FROM solicitudes WHERE funcionario_id = ? GROUP BY mes";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(1, $funcionario_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function topCategoriasLentasFuncionario($funcionario_id) {
        $query = "SELECT categoria_id, AVG(DATEDIFF(fecha_resolucion, fecha_creacion)) as tiempo_promedio FROM solicitudes WHERE funcionario_id = ? AND fecha_resolucion IS NOT NULL GROUP BY categoria_id ORDER BY tiempo_promedio DESC LIMIT 3";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(1, $funcionario_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function evolucionSolicitudesUsuario($usuario_id) {
        $query = "SELECT MONTH(fecha_creacion) as mes, COUNT(*) as total FROM solicitudes WHERE usuario_id = ? GROUP BY mes";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(1, $usuario_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function tiempoPromedioResolucionUsuario($usuario_id) {
        $query = "SELECT AVG(DATEDIFF(fecha_resolucion, fecha_creacion)) as tiempo_promedio FROM solicitudes WHERE usuario_id = ? AND fecha_resolucion IS NOT NULL";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(1, $usuario_id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['tiempo_promedio'] : null;
    }

    public function obtenerConFiltros($filtros) {
        $query = "SELECT * FROM solicitudes WHERE 1=1";
        $params = [];
        if (isset($filtros['usuario_id'])) {
            $query .= " AND usuario_id = ?";
            $params[] = $filtros['usuario_id'];
        }
        if (isset($filtros['estado_id'])) {
            $query .= " AND estado_id = ?";
            $params[] = $filtros['estado_id'];
        }
        if (isset($filtros['categoria_id'])) {
            $query .= " AND categoria_id = ?";
            $params[] = $filtros['categoria_id'];
        }
        $stmt = $this->pdo->prepare($query);
        foreach ($params as $i => $param) {
            $stmt->bindValue($i + 1, $param, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
}
