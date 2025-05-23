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

// Consultas y cálculos
$query_total_solicitudes = "SELECT COUNT(*) as total FROM solicitudes WHERE usuario_id = :usuario_id";
$stmt_total_solicitudes = $db->prepare($query_total_solicitudes);
$stmt_total_solicitudes->bindParam(':usuario_id', $usuario_id);
$stmt_total_solicitudes->execute();
$total_solicitudes = $stmt_total_solicitudes->fetch(PDO::FETCH_ASSOC)['total'];

$query_por_estado = "SELECT e.nombre, e.color, COUNT(s.id) as total 
                     FROM solicitudes s 
                     JOIN estados e ON s.estado_id = e.id 
                     WHERE s.usuario_id = :usuario_id 
                     GROUP BY s.estado_id";
$stmt_por_estado = $db->prepare($query_por_estado);
$stmt_por_estado->bindParam(':usuario_id', $usuario_id);
$stmt_por_estado->execute();
$solicitudes_por_estado = $stmt_por_estado->fetchAll(PDO::FETCH_ASSOC);

$query_total_sugerencias = "SELECT COUNT(*) as total FROM sugerencias WHERE usuario_id = :usuario_id";
$stmt_total_sugerencias = $db->prepare($query_total_sugerencias);
$stmt_total_sugerencias->bindParam(':usuario_id', $usuario_id);
$stmt_total_sugerencias->execute();
$total_sugerencias = $stmt_total_sugerencias->fetch(PDO::FETCH_ASSOC)['total'];

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

$query_sugerencias = "SELECT s.id, s.titulo, s.fecha_creacion, s.estado_id, e.nombre as estado, e.color as estado_color 
                      FROM sugerencias s 
                      JOIN estados e ON s.estado_id = e.id 
                      WHERE s.usuario_id = :usuario_id 
                      ORDER BY s.fecha_creacion DESC LIMIT 3";
$stmt_sugerencias = $db->prepare($query_sugerencias);
$stmt_sugerencias->bindParam(':usuario_id', $usuario_id);
$stmt_sugerencias->execute();
$sugerencias_recientes = $stmt_sugerencias->fetchAll(PDO::FETCH_ASSOC);

$notificaciones = $notificacion->obtenerNoLeidas($usuario_id);
$notificaciones_array = [];
while ($row = $notificaciones->fetch(PDO::FETCH_ASSOC)) {
    $notificaciones_array[] = $row;
}

$query_categorias = "SELECT c.nombre, c.color, COUNT(s.id) as total 
                     FROM solicitudes s 
                     JOIN categorias c ON s.categoria_id = c.id 
                     WHERE s.usuario_id = :usuario_id 
                     GROUP BY s.categoria_id";
$stmt_categorias = $db->prepare($query_categorias);
$stmt_categorias->bindParam(':usuario_id', $usuario_id);
$stmt_categorias->execute();
$categorias_data = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

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

$query_tiempo = "SELECT AVG(DATEDIFF(fecha_resolucion, fecha_creacion)) as promedio 
                 FROM solicitudes 
                 WHERE usuario_id = :usuario_id AND fecha_resolucion IS NOT NULL";
$stmt_tiempo = $db->prepare($query_tiempo);
$stmt_tiempo->bindParam(':usuario_id', $usuario_id);
$stmt_tiempo->execute();
$tiempo_promedio = $stmt_tiempo->fetch(PDO::FETCH_ASSOC)['promedio'];
$tiempo_promedio = $tiempo_promedio ? round($tiempo_promedio, 1) : 'N/A';
?>

<!-- Aquí puedes incluir el HTML del dashboard -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial del Ciudadano</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Historial del Ciudadano</h1>
    </header>
    <main>
        <section>
            <h2>Resumen</h2>
            <p>Total de Solicitudes: <?php echo $total_solicitudes; ?></p>
            <p>Total de Sugerencias: <?php echo $total_sugerencias; ?></p>
            <p>Tiempo Promedio de Resolución: <?php echo $tiempo_promedio; ?> días</p>
        </section>
        <section>
            <h2>Solicitudes por Estado</h2>
            <ul>
                <?php foreach ($solicitudes_por_estado as $estado): ?>
                    <li style="color: <?php echo $estado['color']; ?>">
                        <?php echo $estado['nombre']; ?>: <?php echo $estado['total']; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <section>
            <h2>Solicitudes Recientes</h2>
            <ul>
                <?php foreach ($solicitudes_recientes as $solicitud): ?>
                    <li>
                        <strong><?php echo $solicitud['titulo']; ?></strong> - 
                        <?php echo $solicitud['categoria']; ?> 
                        (<span style="color: <?php echo $solicitud['categoria_color']; ?>">
                            <?php echo $solicitud['estado']; ?>
                        </span>)
                        <br>
                        Fecha: <?php echo $solicitud['fecha_creacion']; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <section>
            <h2>Sugerencias Recientes</h2>
            <ul>
                <?php foreach ($sugerencias_recientes as $sugerencia): ?>
                    <li>
                        <strong><?php echo $sugerencia['titulo']; ?></strong> - 
                        Estado: <span style="color: <?php echo $sugerencia['estado_color']; ?>">
                            <?php echo $sugerencia['estado']; ?>
                        </span>
                        <br>
                        Fecha: <?php echo $sugerencia['fecha_creacion']; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <section>
            <h2>Notificaciones</h2>
            <ul>
                <?php foreach ($notificaciones_array as $notificacion): ?>
                    <li><?php echo $notificacion['mensaje']; ?> - 
                        Fecha: <?php echo $notificacion['fecha']; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <section>
            <h2>Solicitudes por Categoría</h2>
            <ul>
                <?php foreach ($categorias_data as $categoria): ?>
                    <li style="color: <?php echo $categoria['color']; ?>">
                        <?php echo $categoria['nombre']; ?>: <?php echo $categoria['total']; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <section>
            <h2>Historial de Solicitudes (Últimos 6 Meses)</h2>
            <ul>
                <?php foreach ($timeline_data as $timeline): ?>
                    <li>
                        Mes: <?php echo $timeline['mes']; ?> - 
                        Total: <?php echo $timeline['total']; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    </main>
</body>
</html>