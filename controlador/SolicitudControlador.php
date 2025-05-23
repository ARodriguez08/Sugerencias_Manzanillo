<?php

class SolicitudControlador
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
        // Validación básica
        if (
            empty($datos['titulo']) ||
            empty($datos['descripcion']) ||
            empty($datos['categoria_id']) ||
            empty($datos['usuario_id'])
        ) {
            return ['exito' => false, 'mensaje' => 'Título, descripción, categoría y usuario son obligatorios.'];
        }

        // Estado inicial: 1 (Nueva)
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

    // Obtener todas las sugerencias
    public function obtenerSugerencias()
    {
        $stmt = $this->pdo->query("SELECT s.*, c.nombre AS categoria, e.nombre AS estado, u.nombre AS usuario
            FROM sugerencias s
            JOIN categorias c ON s.categoria_id = c.id
            JOIN estados e ON s.estado_id = e.id
            JOIN usuarios u ON s.usuario_id = u.id
            ORDER BY s.fecha_creacion DESC");
        return $stmt->fetchAll();
    }

    // Recibir y guardar una nueva sugerencia
    public function recibirSugerencia($datos)
    {
        if (
            empty($datos['titulo']) ||
            empty($datos['descripcion']) ||
            empty($datos['categoria_id']) ||
            empty($datos['usuario_id'])
        ) {
            return ['exito' => false, 'mensaje' => 'Título, descripción, categoría y usuario son obligatorios.'];
        }

        $estado_id = 1; // Nueva
        $sql = "INSERT INTO sugerencias (titulo, descripcion, usuario_id, categoria_id, estado_id)
                VALUES (:titulo, :descripcion, :usuario_id, :categoria_id, :estado_id)";
        $stmt = $this->pdo->prepare($sql);

        $stmt->bindValue(':titulo', $datos['titulo']);
        $stmt->bindValue(':descripcion', $datos['descripcion']);
        $stmt->bindValue(':usuario_id', $datos['usuario_id']);
        $stmt->bindValue(':categoria_id', $datos['categoria_id']);
        $stmt->bindValue(':estado_id', $estado_id);

        try {
            $stmt->execute();
            return ['exito' => true, 'mensaje' => 'Sugerencia recibida correctamente.'];
        } catch (\PDOException $e) {
            return ['exito' => false, 'mensaje' => 'Error al guardar la sugerencia: ' . $e->getMessage()];
        }
    }

    // (Place this at the end of the Solicitud class, before the closing bracket)
    public function obtenerRecientes($limite = 5) {
        $query = "SELECT * FROM solicitudes ORDER BY fecha_creacion DESC LIMIT :limite";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}