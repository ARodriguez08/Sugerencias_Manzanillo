<?php
// Incluir modelos necesarios
include_once 'config/security.php';
include_once 'modelo/Sugerencia.php';

// Obtener la conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Inicializar objetos
$sugerencia = new Sugerencia($db);

// Obtener sugerencias del usuario actual
$page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$items_per_page = 10;

$sugerencias = $sugerencia->obtenerPorUsuario($_SESSION['usuario_id'], $page, $items_per_page);

// Contar total de sugerencias del usuario
$query_total = "SELECT COUNT(*) as total FROM sugerencias WHERE usuario_id = :usuario_id";
$stmt_total = $db->prepare($query_total);
$stmt_total->bindParam(':usuario_id', $_SESSION['usuario_id']);
$stmt_total->execute();
$total_sugerencias = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];

$total_pages = ceil($total_sugerencias / $items_per_page);

// Contar sugerencias por estado
$query_stats = "SELECT 
                (SELECT COUNT(*) FROM sugerencias WHERE usuario_id = :usuario_id AND estado_id = 1) as nuevas,
                (SELECT COUNT(*) FROM sugerencias WHERE usuario_id = :usuario_id AND estado_id = 2) as en_revision,
                (SELECT COUNT(*) FROM sugerencias WHERE usuario_id = :usuario_id AND estado_id = 3) as aprobadas,
                (SELECT COUNT(*) FROM sugerencias WHERE usuario_id = :usuario_id AND estado_id = 4) as rechazadas";

$stmt_stats = $db->prepare($query_stats);
$stmt_stats->bindParam(':usuario_id', $_SESSION['usuario_id']);
$stmt_stats->execute();
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-lightbulb me-2"></i>Mis Sugerencias
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php?page=nueva_sugerencia" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Nueva Sugerencia
        </a>
    </div>
</div>

<!-- Estadísticas -->
<div class="row">
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total de Sugerencias</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_sugerencias; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-lightbulb fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            En Revisión</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['en_revision']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-search fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Aprobadas</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['aprobadas']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                            Rechazadas</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['rechazadas']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lista de Sugerencias -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-list me-1"></i> Mis Sugerencias
        </h6>
        <a href="index.php?page=nueva_sugerencia" class="btn btn-sm btn-primary">
            <i class="fas fa-plus me-1"></i> Nueva Sugerencia
        </a>
    </div>
    <div class="card-body">
        <?php if ($total_sugerencias > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Categoría</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $sugerencias->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo Security::escapeOutput($row['titulo']); ?></td>
                            <td>
                                <span class="badge" style="background-color: <?php echo $row['categoria_color']; ?>">
                                    <?php echo Security::escapeOutput($row['categoria_nombre']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge" style="background-color: <?php echo $row['estado_color']; ?>">
                                    <?php echo Security::escapeOutput($row['estado_nombre']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_creacion'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#verSugerenciaModal" 
                                        data-id="<?php echo $row['id']; ?>"
                                        data-titulo="<?php echo Security::escapeOutput($row['titulo']); ?>"
                                        data-descripcion="<?php echo Security::escapeOutput($row['descripcion']); ?>"
                                        data-categoria="<?php echo Security::escapeOutput($row['categoria_nombre']); ?>"
                                        data-estado="<?php echo Security::escapeOutput($row['estado_nombre']); ?>"
                                        data-fecha="<?php echo date('d/m/Y H:i', strtotime($row['fecha_creacion'])); ?>"
                                        data-respuesta="<?php echo Security::escapeOutput($row['respuesta']); ?>"
                                        data-funcionario="<?php echo isset($row['funcionario_nombre']) ? Security::escapeOutput($row['funcionario_nombre'] . ' ' . $row['funcionario_apellidos']) : 'Pendiente de asignación'; ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Paginación de sugerencias">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="index.php?page=ciudadano_mis_sugerencias&page_num=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="alert alert-info" role="alert">
                <h4 class="alert-heading">¡No tienes sugerencias!</h4>
                <p>Aún no has enviado ninguna sugerencia. Las sugerencias son una excelente manera de contribuir a mejorar nuestra comunidad.</p>
                <hr>
                <p class="mb-0">Haz clic en el botón "Nueva Sugerencia" para enviar tu primera propuesta.</p>
            </div>
            
            <div class="text-center mt-4">
                <a href="index.php?page=nueva_sugerencia" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Nueva Sugerencia
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
