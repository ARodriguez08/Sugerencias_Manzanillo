<?php
// =================== CONFIGURACIÓN Y SEGURIDAD ===================
// Make sure no output is sent before headers
ob_start();
include_once 'config/security.php';

// =================== PROCESAMIENTO DE FORMULARIOS ===================

// --- Procesar Nueva Solicitud ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_solicitud'])) {
    if (Security::validateCSRFToken($_POST['csrf_token'])) {
        $titulo = trim($_POST['solicitud_titulo']);
        $descripcion = trim($_POST['solicitud_descripcion']);
        $categoria_id = intval($_POST['solicitud_categoria']);
        $usuario_id = $_SESSION['usuario_id'];
        $estado_id = 1;

        $database = new Database();
        $db = $database->getConnection();

        $stmt = $db->prepare("INSERT INTO solicitudes (titulo, descripcion, categoria_id, estado_id, usuario_id, fecha_creacion) VALUES (?, ?, ?, ?, ?, NOW())");

        if ($stmt->execute([$titulo, $descripcion, $categoria_id, $estado_id, $usuario_id])) {
            $solicitud_id = $db->lastInsertId();

            // Procesar archivo adjunto
            if (isset($_FILES['solicitud_archivo']) && $_FILES['solicitud_archivo']['error'] === UPLOAD_ERR_OK) {
                $nombre_original = basename($_FILES['solicitud_archivo']['name']);
                $tipo = $_FILES['solicitud_archivo']['type'];
                $tamano = $_FILES['solicitud_archivo']['size'];
                $ext = pathinfo($nombre_original, PATHINFO_EXTENSION);
                $nuevo_nombre = uniqid('sol_') . '.' . $ext;
                $ruta_destino = '../ciudadano/uploads/' . $nuevo_nombre;

                if (!is_dir('../ciudadano/uploads')) {
                    mkdir('../ciudadano/uploads', 0777, true);
                }

                if (move_uploaded_file($_FILES['solicitud_archivo']['tmp_name'], $ruta_destino)) {
                    $stmt_arch = $db->prepare("INSERT INTO archivos (solicitud_id, nombre_archivo, ruta_archivo, tipo_archivo, tamano, usuario_id) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt_arch->execute([$solicitud_id, $nombre_original, $nuevo_nombre, $tipo, $tamano, $usuario_id]);
                }
            }

            ob_end_clean(); // Clean the output buffer before redirect
            header('Location: ' . $_SERVER['REQUEST_URI'] . '?success=solicitud');
            exit;
        } else {
            ob_end_clean();
            header('Location: ' . $_SERVER['REQUEST_URI'] . '?error=solicitud');
            exit;
        }
    } else {
        ob_end_clean();
        header('Location: ' . $_SERVER['REQUEST_URI'] . '?error=csrf');
        exit;
    }
}

// --- Procesar Nueva Sugerencia ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_sugerencia'])) {
    if (Security::validateCSRFToken($_POST['csrf_token'])) {
        $titulo = trim($_POST['sugerencia_titulo']);
        $descripcion = trim($_POST['sugerencia_descripcion']);
        $categoria_id = intval($_POST['sugerencia_categoria']);
        $usuario_id = $_SESSION['usuario_id'];
        $estado_id = 1;

        $database = new Database();
        $db = $database->getConnection();

        $stmt = $db->prepare("INSERT INTO sugerencias (titulo, descripcion, usuario_id, categoria_id, estado_id, fecha_creacion) VALUES (?, ?, ?, ?, ?, NOW())");
        if ($stmt->execute([$titulo, $descripcion, $usuario_id, $categoria_id, $estado_id])) {
            ob_end_clean();
            header('Location: ' . $_SERVER['REQUEST_URI'] . '?success=sugerencia');
            exit;
        } else {
            ob_end_clean();
            header('Location: ' . $_SERVER['REQUEST_URI'] . '?error=sugerencia');
            exit;
        }
    } else {
        ob_end_clean();
        header('Location: ' . $_SERVER['REQUEST_URI'] . '?error=csrf');
        exit;
    }
}

// =================== INICIALIZACIÓN Y DATOS ===================
$csrf_token = Security::generateCSRFToken();

if (!class_exists('Solicitud')) include_once 'modelo/Solicitud.php';
if (!class_exists('Sugerencia')) include_once 'modelo/Sugerencia.php';

$database = new Database();
$db = $database->getConnection();

$solicitud = new Solicitud($db);
$sugerencia = new Sugerencia($db);

$usuario_id = $_SESSION['usuario_id'];

// --- Estadísticas ---
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

// =================== HTML Y VISTA ===================
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Ciudadano</title>
    <!-- =================== ESTILOS =================== -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../ciudadano/assets/css/app_dashboard.css">
</head>
<body>
<?php
// =================== MENSAJES ===================
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'solicitud') {
        echo '<div class="alert alert-success mt-3">Solicitud creada correctamente.</div>';
    } elseif ($_GET['success'] === 'sugerencia') {
        echo '<div class="alert alert-success mt-3">Sugerencia creada correctamente.</div>';
    }
}
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'solicitud') {
        echo '<div class="alert alert-danger mt-3">Error al crear la solicitud.</div>';
    } elseif ($_GET['error'] === 'sugerencia') {
        echo '<div class="alert alert-danger mt-3">Error al crear la sugerencia.</div>';
    } elseif ($_GET['error'] === 'csrf') {
        echo '<div class="alert alert-danger mt-3">Token CSRF inválido.</div>';
    }
}
?>

<div class="container mt-4">
    <!-- =================== CABECERA =================== -->
    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <h1 class="h3"><i class="fas fa-tachometer-alt me-2"></i>Dashboard Ciudadano</h1>
            <div>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevaSolicitud">
                    <i class="fas fa-plus-circle me-1"></i> Nueva Solicitud
                </button>
                <button type="button" class="btn btn-success btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#modalNuevaSugerencia">
                    <i class="fas fa-lightbulb me-1"></i> Nueva Sugerencia
                </button>
            </div>
        </div>
    </div>

    <!-- =================== RESUMEN =================== -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Solicitudes</h5>
                    <p class="card-text fs-2"><?php echo $total_solicitudes; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Sugerencias</h5>
                    <p class="card-text fs-2"><?php echo $total_sugerencias; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info mb-3">
                <div class="card-body">
                    <h5 class="card-title">Tiempo Promedio Resolución</h5>
                    <p class="card-text fs-2"><?php echo $tiempo_promedio; ?> días</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-secondary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Por Estado</h5>
                    <?php foreach ($solicitudes_por_estado as $estado): ?>
                        <span class="badge" style="background:<?php echo htmlspecialchars($estado['color']); ?>">
                            <?php echo htmlspecialchars($estado['nombre']); ?>: <?php echo $estado['total']; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- =================== SOLICITUDES Y SUGERENCIAS RECIENTES =================== -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    Últimas 5 Solicitudes
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Categoría</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($solicitudes_recientes as $sol): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sol['titulo']); ?></td>
                                <td>
                                    <span class="badge" style="background:<?php echo htmlspecialchars($sol['categoria_color']); ?>">
                                        <?php echo htmlspecialchars($sol['categoria']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge" style="background:<?php echo htmlspecialchars($sol['estado_color']); ?>">
                                        <?php echo htmlspecialchars($sol['estado']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($sol['fecha_creacion']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($solicitudes_recientes)): ?>
                            <tr><td colspan="4" class="text-center">Sin solicitudes recientes</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    Últimas 3 Sugerencias
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                    <?php foreach ($sugerencias_recientes as $sug): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><?php echo htmlspecialchars($sug['titulo']); ?></span>
                            <span class="badge" style="background:<?php echo htmlspecialchars($sug['estado_color']); ?>">
                                <?php echo htmlspecialchars($sug['estado']); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($sugerencias_recientes)): ?>
                        <li class="list-group-item text-center">Sin sugerencias recientes</li>
                    <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- =================== GRÁFICOS =================== -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Solicitudes por Categoría</div>
                <div class="card-body">
                    <canvas id="graficoCategorias"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Solicitudes por Mes</div>
                <div class="card-body">
                    <canvas id="graficoTimeline"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =================== MODAL NUEVA SOLICITUD =================== -->
<div class="modal fade" id="modalNuevaSolicitud" tabindex="-1" aria-labelledby="modalNuevaSolicitudLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNuevaSolicitudLabel">Nueva Solicitud</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3">
                        <label for="solicitud_titulo" class="form-label">Título</label>
                        <input type="text" class="form-control" name="solicitud_titulo" id="solicitud_titulo" required>
                    </div>
                    <div class="mb-3">
                        <label for="solicitud_descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" name="solicitud_descripcion" id="solicitud_descripcion" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="solicitud_categoria" class="form-label">Categoría</label>
                        <select class="form-select" name="solicitud_categoria" id="solicitud_categoria" required>
                            <option value="">Seleccione una categoría</option>
                            <?php
                            $cats = $db->query("SELECT id, nombre FROM categorias")->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($cats as $cat) {
                                echo '<option value="'.htmlspecialchars($cat['id']).'">'.htmlspecialchars($cat['nombre']).'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="solicitud_archivo" class="form-label">Archivo (opcional)</label>
                        <input type="file" class="form-control" name="solicitud_archivo" id="solicitud_archivo">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="crear_solicitud" class="btn btn-primary">Crear Solicitud</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- =================== MODAL NUEVA SUGERENCIA =================== -->
<div class="modal fade" id="modalNuevaSugerencia" tabindex="-1" aria-labelledby="modalNuevaSugerenciaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNuevaSugerenciaLabel">Nueva Sugerencia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3">
                        <label for="sugerencia_titulo" class="form-label">Título</label>
                        <input type="text" class="form-control" name="sugerencia_titulo" id="sugerencia_titulo" required>
                    </div>
                    <div class="mb-3">
                        <label for="sugerencia_descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" name="sugerencia_descripcion" id="sugerencia_descripcion" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="sugerencia_categoria" class="form-label">Categoría</label>
                        <select class="form-select" name="sugerencia_categoria" id="sugerencia_categoria" required>
                            <option value="">Seleccione una categoría</option>
                            <?php
                            foreach ($cats as $cat) {
                                echo '<option value="'.htmlspecialchars($cat['id']).'">'.htmlspecialchars($cat['nombre']).'</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="crear_sugerencia" class="btn btn-success">Crear Sugerencia</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- =================== SCRIPTS =================== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de Categorías
    var ctxCat = document.getElementById('graficoCategorias').getContext('2d');
    var categoriasData = <?php echo json_encode(array_column($categorias_data, 'total')); ?>;
    var categoriasLabels = <?php echo json_encode(array_column($categorias_data, 'nombre')); ?>;
    var categoriasColors = <?php echo json_encode(array_column($categorias_data, 'color')); ?>;
    new Chart(ctxCat, {
        type: 'doughnut',
        data: {
            labels: categoriasLabels,
            datasets: [{
                data: categoriasData,
                backgroundColor: categoriasColors
            }]
        }
    });

    // Gráfico de Timeline
    var ctxTime = document.getElementById('graficoTimeline').getContext('2d');
    var timelineLabels = <?php echo json_encode(array_column($timeline_data, 'mes')); ?>;
    var timelineData = <?php echo json_encode(array_column($timeline_data, 'total')); ?>;
    new Chart(ctxTime, {
        type: 'line',
        data: {
            labels: timelineLabels,
            datasets: [{
                label: 'Solicitudes',
                data: timelineData,
                fill: false,
                borderColor: '#007bff',
                tension: 0.1
            }]
        }
    });
});
</script>
</body>
</html>