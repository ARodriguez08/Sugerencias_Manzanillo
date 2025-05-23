<?php
// Incluir archivo de seguridad
include_once '../../config/security.php';

// Generar token CSRF
$csrf_token = Security::generateCSRFToken();

// Incluir clases necesarias
if (!class_exists('Solicitud')) {
    include_once '../../modelo/Solicitud.php';
}
if (!class_exists('Sugerencia')) {
    include_once '../../modelo/Sugerencia.php';
}
if (!class_exists('Notification')) {
    include_once '../../config/notification.php';
}

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

// Obtener estadísticas

// 1. Total de solicitudes del usuario
$query_total_solicitudes = "SELECT COUNT(*) as total FROM solicitudes WHERE usuario_id = :usuario_id";
$stmt_total_solicitudes = $db->prepare($query_total_solicitudes);
$stmt_total_solicitudes->bindParam(':usuario_id', $usuario_id);
$stmt_total_solicitudes->execute();
$total_solicitudes = $stmt_total_solicitudes->fetch(PDO::FETCH_ASSOC)['total'];

// 2. Solicitudes por estado
$query_por_estado = "SELECT e.nombre, e.color, COUNT(s.id) as total 
                     FROM solicitudes s 
                     JOIN estados e ON s.estado_id = e.id 
                     WHERE s.usuario_id = :usuario_id 
                     GROUP BY s.estado_id";
$stmt_por_estado = $db->prepare($query_por_estado);
$stmt_por_estado->bindParam(':usuario_id', $usuario_id);
$stmt_por_estado->execute();
$solicitudes_por_estado = $stmt_por_estado->fetchAll(PDO::FETCH_ASSOC);

// 3. Total de sugerencias del usuario
$query_total_sugerencias = "SELECT COUNT(*) as total FROM sugerencias WHERE usuario_id = :usuario_id";
$stmt_total_sugerencias = $db->prepare($query_total_sugerencias);
$stmt_total_sugerencias->bindParam(':usuario_id', $usuario_id);
$stmt_total_sugerencias->execute();
$total_sugerencias = $stmt_total_sugerencias->fetch(PDO::FETCH_ASSOC)['total'];

// 4. Solicitudes recientes (últimas 5)
$query_recientes = "SELECT s.id, s.titulo, s.fecha_creacion, c.nombre as categoria, c.color as categoria_color, 
                    e.nombre as estado, e.color as estado_color 
                    FROM solicitudes s 
                    JOIN categorias c ON s.categoria_id = c.id 
                    JOIN estados e ON s.estado_id = e.id 
                    WHERE s.usuario_id = :usuario_id 
                    ORDER BY s.fecha_creacion DESC LIMIT 5";
$stmt_recientes = $db->prepare($query_recientes);
$stmt_recientes->bindParam(':usuario_id', $usuario_id);
$stmt_recientes->execute();
$solicitudes_recientes = $stmt_recientes->fetchAll(PDO::FETCH_ASSOC);

// 5. Sugerencias recientes (últimas 3)
$query_sugerencias = "SELECT s.id, s.titulo, s.fecha_creacion, s.estado_id, e.nombre as estado, e.color as estado_color 
                      FROM sugerencias s 
                      JOIN estados e ON s.estado_id = e.id 
                      WHERE s.usuario_id = :usuario_id 
                      ORDER BY s.fecha_creacion DESC LIMIT 3";
$stmt_sugerencias = $db->prepare($query_sugerencias);
$stmt_sugerencias->bindParam(':usuario_id', $usuario_id);
$stmt_sugerencias->execute();
$sugerencias_recientes = $stmt_sugerencias->fetchAll(PDO::FETCH_ASSOC);

// 6. Obtener notificaciones recientes
$notificaciones = $notificacion->obtenerNoLeidas($usuario_id);
$notificaciones_array = [];
while ($row = $notificaciones->fetch(PDO::FETCH_ASSOC)) {
    $notificaciones_array[] = $row;
}

// 7. Datos para el gráfico de solicitudes por categoría
$query_categorias = "SELECT c.nombre, c.color, COUNT(s.id) as total 
                     FROM solicitudes s 
                     JOIN categorias c ON s.categoria_id = c.id 
                     WHERE s.usuario_id = :usuario_id 
                     GROUP BY s.categoria_id";
$stmt_categorias = $db->prepare($query_categorias);
$stmt_categorias->bindParam(':usuario_id', $usuario_id);
$stmt_categorias->execute();
$categorias_data = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

// 8. Datos para el gráfico de timeline de solicitudes
$query_timeline = "SELECT DATE_FORMAT(fecha_creacion, '%Y-%m') as mes, COUNT(*) as total 
                   FROM solicitudes 
                   WHERE usuario_id = :usuario_id 
                   GROUP BY DATE_FORMAT(fecha_creacion, '%Y-%m') 
                   ORDER BY mes ASC 
                   LIMIT 6";
$stmt_timeline = $db->prepare($query_timeline);
$stmt_timeline->bindParam(':usuario_id', $usuario_id);
$stmt_timeline->execute();
$timeline_data = $stmt_timeline->fetchAll(PDO::FETCH_ASSOC);

// 9. Tiempo promedio de resolución de solicitudes
$query_tiempo = "SELECT AVG(DATEDIFF(fecha_resolucion, fecha_creacion)) as promedio 
                 FROM solicitudes 
                 WHERE usuario_id = :usuario_id AND fecha_resolucion IS NOT NULL";
$stmt_tiempo = $db->prepare($query_tiempo);
$stmt_tiempo->bindParam(':usuario_id', $usuario_id);
$stmt_tiempo->execute();
$tiempo_promedio = $stmt_tiempo->fetch(PDO::FETCH_ASSOC)['promedio'];
$tiempo_promedio = $tiempo_promedio ? round($tiempo_promedio, 1) : 'N/A';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Solicitudes</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
</head>
<body>
    <div class="container">
        <h1>Panel de Solicitudes</h1>
        <div class="stats">
            <div class="stat">
                <h3>Total de Solicitudes</h3>
                <p><?= $total_solicitudes ?></p>
            </div>
            <div class="stat">
                <h3>Total de Sugerencias</h3>
                <p><?= $total_sugerencias ?></p>
            </div>
            <div class="stat">
                <h3>Tiempo Promedio de Resolución</h3>
                <p><?= $tiempo_promedio ?> días</p>
            </div>
        </div>

        <h2>Solicitudes por Estado</h2>
        <ul>
            <?php foreach ($solicitudes_por_estado as $estado): ?>
                <li style="color: <?= $estado['color'] ?>;">
                    <?= $estado['nombre'] ?>: <?= $estado['total'] ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <h2>Solicitudes Recientes</h2>
        <table>
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Fecha</th>
                    <th>Categoría</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($solicitudes_recientes as $solicitud): ?>
                    <tr>
                        <td><?= htmlspecialchars($solicitud['titulo']) ?></td>
                        <td><?= $solicitud['fecha_creacion'] ?></td>
                        <td style="color: <?= $solicitud['categoria_color'] ?>;">
                            <?= $solicitud['categoria'] ?>
                        </td>
                        <td style="color: <?= $solicitud['estado_color'] ?>;">
                            <?= $solicitud['estado'] ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Sugerencias Recientes</h2>
        <ul>
            <?php foreach ($sugerencias_recientes as $sugerencia): ?>
                <li>
                    <?= htmlspecialchars($sugerencia['titulo']) ?> - 
                    <?= $sugerencia['fecha_creacion'] ?> - 
                    <span style="color: <?= $sugerencia['estado_color'] ?>;">
                        <?= $sugerencia['estado'] ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>

        <h2>Notificaciones Recientes</h2>
        <ul>
            <?php foreach ($notificaciones_array as $notificacion): ?>
                <li><?= htmlspecialchars($notificacion['mensaje']) ?> - <?= $notificacion['fecha'] ?></li>
            <?php endforeach; ?>
        </ul>

        <h2>Gráfico de Solicitudes por Categoría</h2>
        <ul>
            <?php foreach ($categorias_data as $categoria): ?>
                <li style="color: <?= $categoria['color'] ?>;">
                    <?= $categoria['nombre'] ?>: <?= $categoria['total'] ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <h2>Timeline de Solicitudes</h2>
        <ul>
            <?php foreach ($timeline_data as $timeline): ?>
                <li><?= $timeline['mes'] ?>: <?= $timeline['total'] ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</body>
</html>