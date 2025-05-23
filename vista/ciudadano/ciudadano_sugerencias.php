<?php
include_once 'config/security.php';
include_once 'modelo/Solicitud.php';
include_once 'modelo/Sugerencia.php';
include_once 'config/notification.php';
include_once 'config/database.php';

// Generar token CSRF
$csrf_token = Security::generateCSRFToken();

// Obtener la conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Inicializar objetos
$solicitud = new Solicitud($db);
$sugerencia = new Sugerencia($db);
$notificacion = new Notification($db);

// Definir el ID del usuario actual
session_start();
$usuario_id = $_SESSION['usuario_id'];

// Consultas para estadísticas
$query_total_solicitudes = "SELECT COUNT(*) as total FROM solicitudes WHERE usuario_id = :usuario_id";
$stmt_total_solicitudes = $db->prepare($query_total_solicitudes);
$stmt_total_solicitudes->bindParam(':usuario_id', $usuario_id);
$stmt_total_solicitudes->execute();
$total_solicitudes = $stmt_total_solicitudes->fetch(PDO::FETCH_ASSOC)['total'];

$query_total_sugerencias = "SELECT COUNT(*) as total FROM sugerencias WHERE usuario_id = :usuario_id";
$stmt_total_sugerencias = $db->prepare($query_total_sugerencias);
$stmt_total_sugerencias->bindParam(':usuario_id', $usuario_id);
$stmt_total_sugerencias->execute();
$total_sugerencias = $stmt_total_sugerencias->fetch(PDO::FETCH_ASSOC)['total'];

$query_recientes = "SELECT id, titulo, fecha_creacion FROM solicitudes WHERE usuario_id = :usuario_id ORDER BY fecha_creacion DESC LIMIT 5";
$stmt_recientes = $db->prepare($query_recientes);
$stmt_recientes->bindParam(':usuario_id', $usuario_id);
$stmt_recientes->execute();
$solicitudes_recientes = $stmt_recientes->fetchAll(PDO::FETCH_ASSOC);

$query_sugerencias = "SELECT id, titulo, fecha_creacion FROM sugerencias WHERE usuario_id = :usuario_id ORDER BY fecha_creacion DESC LIMIT 3";
$stmt_sugerencias = $db->prepare($query_sugerencias);
$stmt_sugerencias->bindParam(':usuario_id', $usuario_id);
$stmt_sugerencias->execute();
$sugerencias_recientes = $stmt_sugerencias->fetchAll(PDO::FETCH_ASSOC);

$notificaciones = $notificacion->obtenerNoLeidas($usuario_id);
$notificaciones_array = [];
while ($row = $notificaciones->fetch(PDO::FETCH_ASSOC)) {
    $notificaciones_array[] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Ciudadano</title>
    <link rel="stylesheet" href="path/to/bootstrap.css">
    <link rel="stylesheet" href="path/to/fontawesome.css">
</head>
<body>
    <div class="container mt-4">
        <h1 class="mb-4">Dashboard Ciudadano</h1>
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5>Total Solicitudes</h5>
                        <p><?php echo $total_solicitudes; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5>Total Sugerencias</h5>
                        <p><?php echo $total_sugerencias; ?></p>
                    </div>
                </div>
            </div>
        </div>
        <h2 class="mt-4">Solicitudes Recientes</h2>
        <ul>
            <?php foreach ($solicitudes_recientes as $solicitud): ?>
                <li><?php echo htmlspecialchars($solicitud['titulo']); ?> - <?php echo $solicitud['fecha_creacion']; ?></li>
            <?php endforeach; ?>
        </ul>
        <h2 class="mt-4">Sugerencias Recientes</h2>
        <ul>
            <?php foreach ($sugerencias_recientes as $sugerencia): ?>
                <li><?php echo htmlspecialchars($sugerencia['titulo']); ?> - <?php echo $sugerencia['fecha_creacion']; ?></li>
            <?php endforeach; ?>
        </ul>
        <h2 class="mt-4">Notificaciones</h2>
        <ul>
            <?php foreach ($notificaciones_array as $notif): ?>
                <li><?php echo htmlspecialchars($notif['titulo']); ?> - <?php echo $notif['fecha_creacion']; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</body>
</html>