<?php
// Eliminate any accidental whitespace before this PHP block!
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sugerencias-Manzanillo</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!-- Font Awesome (¿es necesario si ya usas Material Icons?) -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Icons&family=Arial&display=swap">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- Flatpickr para calendarios -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="../admin/assets/css/diseño.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../admin/assets/js/graficos.js"></script>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <i class="material-icons me-2">business</i>Sugerencias-Manzanillo
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link <?php echo (htmlspecialchars($_GET['page'] ?? '')) === '' ? 'active' : ''; ?>" href="index.php">
                                <i class="material-icons me-1">home</i> Inicio
                            </a>
                        </li>
                        <?php if (!isset($_SESSION['usuario_id'])): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo (htmlspecialchars($_GET['page'] ?? '')) === 'login' ? 'active' : ''; ?>" href="index.php?page=login">
                                    <i class="material-icons me-1">login</i> Iniciar Sesión
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo (htmlspecialchars($_GET['page'] ?? '')) === 'registro' ? 'active' : ''; ?>" href="index.php?page=registro">
                                    <i class="material-icons me-1">person_add</i> Registrarse
                                </a>
                            </li>
                        <?php else: ?>
                            <?php if (isset($_SESSION['usuario_rol_id']) && $_SESSION['usuario_rol_id'] == 1): ?>
                                <!-- Menú de Administrador -->
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="material-icons me-1">dashboard</i> Dashboard
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                                        <li>
                                            <a class="dropdown-item" href="index.php?page=admin_dashboard">
                                                <i class="material-icons me-1">bar_chart</i> Estadísticas
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="index.php?page=admin_usuarios">
                                                <i class="material-icons me-1">group</i> Usuarios
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="index.php?page=admin_categorias">
                                                <i class="material-icons me-1">label</i> Categorías
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="index.php?page=admin_reportes">
                                                <i class="material-icons me-1">description</i> Reportes
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            <?php elseif (isset($_SESSION['usuario_rol_id']) && $_SESSION['usuario_rol_id'] == 2): ?>
                                <!-- Menú de Funcionario -->
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="funcionarioDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="material-icons me-1">work</i> Panel
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="funcionarioDropdown">
                                        <li>
                                            <a class="dropdown-item" href="index.php?page=funcionario_dashboard">
                                                <i class="material-icons me-1">dashboard</i> Dashboard
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="index.php?page=funcionario_reportes">
                                                <i class="material-icons me-1">bar_chart</i> Reportes
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            <?php else: ?>
                                <!-- Menú de Ciudadano -->
                                <li class="nav-item">
                                    <a class="nav-link" href="index.php?page=ciudadano_dashboard">
                                        <i class="material-icons me-1">person</i> Mi Panel
                                    </a>
                                </li>
                            <?php endif; ?>
                            <!-- Perfil de usuario -->
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="perfilDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="material-icons me-1">account_circle</i>
                                    <?php echo (!empty($_SESSION['usuario_nombre'])) ? htmlspecialchars($_SESSION['usuario_nombre']) : 'Usuario'; ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="perfilDropdown">
                                    
                                    <li>
                                        <a class="dropdown-item" href="index.php?page=logout">
                                            <i class="material-icons me-1">logout</i> Cerrar Sesión
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <main class="container py-4">
