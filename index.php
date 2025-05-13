<?php
// Iniciar sesión
session_start();

// Incluir controladores
include_once './controlador/UsuarioController.php';
include_once './controlador/DashboardController.php';

// Crear instancias de controladores
$usuarioControlador = new UsuarioControlador();
$dashboardControlador = new DashboardControlador();

// Determinar la página a mostrar
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Verificar si el usuario está autenticado para páginas protegidas
$paginas_protegidas = [
    'admin_dashboard', 'admin_usuarios', 'admin_solicitudes', 'admin_sugerencias', 'admin_categorias', 'admin_reportes', 'admin_funcionarios',
    'funcionario_dashboard', 'funcionario_solicitudes', 'funcionario_sugerencias', 'funcionario_reportes',
    'ciudadano_dashboard', 'ciudadano_solicitudes', 'ciudadano_sugerencias', 'ciudadano_historial',
    'mi_perfil', 'cambiar_password', 'nueva_solicitud', 'nueva_sugerencia', 'notificaciones'
];

if (in_array($page, $paginas_protegidas) && !isset($_SESSION['usuario_id'])) {
    header("Location: index.php?page=login");
    exit;
}

// Verificar permisos de administrador
$paginas_admin = [
    'admin_dashboard', 'admin_usuarios', 'admin_solicitudes', 'admin_sugerencias', 
    'admin_categorias', 'admin_reportes', 'admin_funcionarios'
];
if (in_array($page, $paginas_admin) && $_SESSION['usuario_rol_id'] != 1) {
    header("Location: index.php");
    exit;
}

// Verificar permisos de funcionario
$paginas_funcionario = [
    'funcionario_dashboard', 'funcionario_solicitudes', 'funcionario_sugerencias', 'funcionario_reportes'
];
if (in_array($page, $paginas_funcionario) && $_SESSION['usuario_rol_id'] != 2 && $_SESSION['usuario_rol_id'] != 1) {
    header("Location: index.php");
    exit;
}

// Verificar permisos de ciudadano
$paginas_ciudadano = [
    'ciudadano_dashboard', 'ciudadano_solicitudes', 'ciudadano_sugerencias', 'ciudadano_historial'
];
if (in_array($page, $paginas_ciudadano) && $_SESSION['usuario_rol_id'] != 3 && $_SESSION['usuario_rol_id'] != 1) {
    header("Location: index.php");
    exit;
}

// Procesar logout
if ($page === 'logout') {
    $usuarioControlador->logout();
}

// Incluir el encabezado
include_once 'vista/layouts/header.php';

// Cargar la página correspondiente
switch ($page) {
    case 'login':
        $error_message = $usuarioControlador->login();
        include_once 'vista/login.php';
        break;
    case 'registro':
        $error_message = $usuarioControlador->registrar();
        $roles = $usuarioControlador->obtenerRoles();
        include_once 'vista/registro.php';
        break;
    case 'admin_dashboard':
        $datos_dashboard = $dashboardControlador->obtenerDatosAdminDashboard();
        include_once 'vista/admin/dashboard.php';
        break;
    case 'admin_usuarios':
        $page_num = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
        $datos_usuarios = $usuarioControlador->obtenerUsuarios($page_num);
        $roles = $usuarioControlador->obtenerRoles();
        include_once 'vista/admin/usuarios.php';
        break;
    case 'admin_solicitudes':
        $page_num = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
        $filtro = isset($_GET['filtro']) ? $_GET['filtro'] : '';
        $estado = isset($_GET['estado']) ? $_GET['estado'] : '';
        $solicitudes = $solicitudControlador->obtenerSolicitudes($page_num, $filtro, $estado);
        $estados = $solicitudControlador->obtenerEstados();
        include_once 'vista/admin/solicitudes.php';
        break;
    case 'admin_sugerencias':
        $page_num = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
        $filtro = isset($_GET['filtro']) ? $_GET['filtro'] : '';
        $estado = isset($_GET['estado']) ? $_GET['estado'] : '';
        $sugerencias = $sugerenciaControlador->obtenerSugerencias($page_num, $filtro, $estado);
        $estados = $sugerenciaControlador->obtenerEstados();
        include_once 'vista/admin/sugerencias.php';
        break;
    case 'funcionario_dashboard':
        $datos_dashboard = $dashboardControlador->obtenerDatosFuncionarioDashboard($_SESSION['usuario_id']);
        include_once 'vista/funcionario/dashboard.php';
        break;
    case 'ciudadano_dashboard':
        $datos_dashboard = $dashboardControlador->obtenerDatosCiudadanoDashboard($_SESSION['usuario_id']);
        include_once 'vista/ciudadano/dashboard.php';
        break;
    case 'contacto':
        include_once 'vista/contacto.php';
        break;
    case 'terminos':
        include_once 'vista/terminos.php';
        break;
    case 'privacidad':
        include_once 'vista/privacidad.php';
        break;
    case 'faq':
        include_once 'vista/faq.php';
        break;
    default:
        include_once 'vista/home.php';
        break;
}

// Incluir el pie de página
include_once 'vista/layouts/footer.php';
?>
