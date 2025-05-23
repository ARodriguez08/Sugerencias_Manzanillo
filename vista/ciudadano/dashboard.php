<?php
// Incluir el security.php
include_once 'config/security.php';

// Inicializar variables
$csrf_token = Security::generateCSRFToken();

// Incluir modelos necesarios
if (!class_exists('Solicitud')) {
    include_once 'modelo/Solicitud.php';
}
if (!class_exists('Sugerencia')) {
    include_once 'modelo/Sugerencia.php';
}

// Obtener la conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Inicializar objetos
$solicitud = new Solicitud($db);
$sugerencia = new Sugerencia($db);

// Definir el ID del usuario actual
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

<!-- Incluir Bootstrap CSS (versión 5.3.0-alpha1) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Incluir CSS externo -->
<link rel="stylesheet" href="../ciudadano/assets/css/app_dashboard.css">

<!-- Cabecera del Dashboard -->
<div class="row">
    <div class="col-md-12">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard Ciudadano
            </h1>
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
</div>

<!-- Modal Nueva Solicitud -->
<div class="modal fade" id="modalNuevaSolicitud" tabindex="-1" aria-labelledby="modalNuevaSolicitudLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" enctype="multipart/form-data" id="formNuevaSolicitud">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalNuevaSolicitudLabel">Nueva Solicitud</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
          <div class="mb-3">
            <label for="solicitud_titulo" class="form-label">Título</label>
            <input type="text" class="form-control" id="solicitud_titulo" name="solicitud_titulo" required>
          </div>
          <div class="mb-3">
            <label for="solicitud_descripcion" class="form-label">Descripción</label>
            <textarea class="form-control" id="solicitud_descripcion" name="solicitud_descripcion" rows="3" required></textarea>
          </div>
          <div class="mb-3">
            <label for="solicitud_categoria" class="form-label">Categoría</label>
            <select class="form-select" id="solicitud_categoria" name="solicitud_categoria" required>
              <option value="">Seleccione una categoría</option>
              <?php
              $stmt = $db->query("SELECT id, nombre FROM categorias WHERE activo=1");
              while ($cat = $stmt->fetch(PDO::FETCH_ASSOC)) {
                  echo '<option value="'.intval($cat['id']).'">'.htmlspecialchars($cat['nombre']).'</option>';
              }
              ?>
            </select>
          </div>
          <div class="mb-3">
            <label for="solicitud_archivo" class="form-label">Archivo (opcional)</label>
            <input type="file" class="form-control" id="solicitud_archivo" name="solicitud_archivo">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="crear_solicitud" class="btn btn-primary">Crear Solicitud</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Modal Nueva Sugerencia -->
<div class="modal fade" id="modalNuevaSugerencia" tabindex="-1" aria-labelledby="modalNuevaSugerenciaLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" id="formNuevaSugerencia">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalNuevaSugerenciaLabel">Nueva Sugerencia</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
          <div class="mb-3">
            <label for="sugerencia_titulo" class="form-label">Título</label>
            <input type="text" class="form-control" id="sugerencia_titulo" name="sugerencia_titulo" required>
          </div>
          <div class="mb-3">
            <label for="sugerencia_descripcion" class="form-label">Descripción</label>
            <textarea class="form-control" id="sugerencia_descripcion" name="sugerencia_descripcion" rows="3" required></textarea>
          </div>
          <div class="mb-3">
            <label for="sugerencia_categoria" class="form-label">Categoría</label>
            <select class="form-select" id="sugerencia_categoria" name="sugerencia_categoria" required>
              <option value="">Seleccione una categoría</option>
              <?php
              $stmt = $db->query("SELECT id, nombre FROM categorias WHERE activo=1");
              while ($cat = $stmt->fetch(PDO::FETCH_ASSOC)) {
                  echo '<option value="'.intval($cat['id']).'">'.htmlspecialchars($cat['nombre']).'</option>';
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

<?php
// Procesar Nueva Solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_solicitud'])) {
    if (Security::validateCSRFToken($_POST['csrf_token'])) {
        $titulo = trim($_POST['solicitud_titulo']);
        $descripcion = trim($_POST['solicitud_descripcion']);
        $categoria_id = intval($_POST['solicitud_categoria']);
        $usuario_id = $_SESSION['usuario_id'];
        $estado_id = 1;

        $stmt = $db->prepare("INSERT INTO solicitudes (titulo, descripcion, categoria_id, estado_id, usuario_id, fecha_creacion) VALUES (?, ?, ?, ?, ?, NOW())");

        if ($stmt->execute([$titulo, $descripcion, $categoria_id, $estado_id, $usuario_id])) {
            $solicitud_id = $db->lastInsertId();

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

            echo '<div class="alert alert-success mt-3">Solicitud creada correctamente.</div>';
            echo '<script>setTimeout(() => location.reload(), 1500);</script>';
        } else {
            echo '<div class="alert alert-danger mt-3">Error al crear la solicitud.</div>';
        }
    } else {
        echo '<div class="alert alert-danger mt-3">Token CSRF inválido.</div>';
    }
}

// Procesar Nueva Sugerencia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_sugerencia'])) {
    if (Security::validateCSRFToken($_POST['csrf_token'])) {
        $titulo = trim($_POST['sugerencia_titulo']);
        $descripcion = trim($_POST['sugerencia_descripcion']);
        $categoria_id = intval($_POST['sugerencia_categoria']);
        $usuario_id = $_SESSION['usuario_id'];
        $estado_id = 1;

        $stmt = $db->prepare("INSERT INTO sugerencias (titulo, descripcion, usuario_id, categoria_id, estado_id, fecha_creacion) VALUES (?, ?, ?, ?, ?, NOW())");
        if ($stmt->execute([$titulo, $descripcion, $usuario_id, $categoria_id, $estado_id])) {
            echo '<div class="alert alert-success mt-3">Sugerencia creada correctamente.</div>';
            echo '<script>setTimeout(() => location.reload(), 1500);</script>';
        } else {
            echo '<div class="alert alert-danger mt-3">Error al crear la sugerencia.</div>';
        }
    } else {
        echo '<div class="alert alert-danger mt-3">Token CSRF inválido.</div>';
    }
}
?>

<!-- Resumen, Gráficos, Solicitudes Recientes, Accesos Rápidos -->
<div class="row">
    <!-- Aquí puedes continuar con las tarjetas resumen, gráficos, etc. -->
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="../ciudadano/assets/js/app_dashboard.js"></script>
