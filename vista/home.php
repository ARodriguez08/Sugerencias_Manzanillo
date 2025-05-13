<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<div class="row align-items-center py-5">
    <div class="col-lg-6 mb-4 mb-lg-0">
        <h1 class="display-4 fw-bold text-primary">Sugerencias-Manzanillo</h1>
        <p class="lead">
            Plataforma web integral para la modernización de los canales de comunicación y gestión de solicitudes ciudadanas.
        </p>
        <p>
            Facilitamos la interacción directa entre los ciudadanos y la administración pública, permitiendo una respuesta más rápida, eficiente y transparente ante los problemas que afectan la calidad de vida urbana.
        </p>
        <div class="d-grid gap-3 d-md-flex justify-content-md-start mt-4">
            <?php if (!isset($_SESSION['usuario_id'])): ?>
                <a href="index.php?page=login" class="btn btn-primary btn-lg px-4">Iniciar Sesión</a>
                <a href="index.php?page=registro" class="btn btn-outline-secondary btn-lg px-4">Registrarse</a>
            <?php else: ?>
                <?php if ($_SESSION['usuario_rol_id'] == 1): ?>
                    <a href="index.php?page=admin_dashboard" class="btn btn-primary btn-lg px-4">Dashboard</a>
                <?php elseif ($_SESSION['usuario_rol_id'] == 2): ?>
                    <a href="index.php?page=funcionario_dashboard" class="btn btn-primary btn-lg px-4">Dashboard</a>
                <?php else: ?>
                    <a href="index.php?page=ciudadano_dashboard" class="btn btn-primary btn-lg px-4">Mis Solicitudes</a>
                <?php endif; ?>
                <a href="index.php?page=nueva_solicitud" class="btn btn-success btn-lg px-4">Nueva Solicitud</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-6 text-center">
        <img src="assets/img/hero-image.svg" class="img-fluid" alt="Sugerencias Manzanillo">
    </div>
</div>

<div class="row py-5">
    <div class="col-12">
        <h2 class="text-center mb-5">¿Cómo funciona?</h2>
    </div>
    <?php
    $steps = [
        ['icon' => 'fas fa-user-plus', 'title' => '1. Regístrate', 'text' => 'Crea tu cuenta en nuestra plataforma para comenzar a reportar incidentes o sugerencias.'],
        ['icon' => 'fas fa-clipboard-list', 'title' => '2. Reporta', 'text' => 'Envía tu reporte con descripción, ubicación y fotos del incidente o sugerencia.'],
        ['icon' => 'fas fa-check-circle', 'title' => '3. Seguimiento', 'text' => 'Recibe actualizaciones sobre el estado de tu solicitud hasta su resolución.']
    ];
    foreach ($steps as $step): ?>
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="<?= $step['icon'] ?> fa-3x text-primary"></i>
                    </div>
                    <h5 class="card-title"><?= $step['title'] ?></h5>
                    <p class="card-text"><?= $step['text'] ?></p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row py-5 bg-light rounded-3 mb-5">
    <div class="col-12">
        <h2 class="text-center mb-5">Categorías de Solicitudes</h2>
    </div>
    <?php
    $categories = [
        ['icon' => 'fas fa-lightbulb', 'color' => '#f1c40f', 'title' => 'Alumbrado Público', 'text' => 'Reporta problemas con el alumbrado público en tu comunidad.'],
        ['icon' => 'fas fa-trash', 'color' => '#27ae60', 'title' => 'Recolección de Residuos', 'text' => 'Reporta problemas con la recolección de basura.'],
        ['icon' => 'fas fa-road', 'color' => '#e74c3c', 'title' => 'Baches', 'text' => 'Reporta baches y fallas en la infraestructura vial.'],
        ['icon' => 'fas fa-shield-alt', 'color' => '#3498db', 'title' => 'Seguridad Ciudadana', 'text' => 'Reporta problemas de seguridad en tu comunidad.'],
        ['icon' => 'fas fa-tree', 'color' => '#2ecc71', 'title' => 'Parques y Espacios Públicos', 'text' => 'Reporta problemas en parques y espacios públicos.'],
        ['icon' => 'fas fa-tint', 'color' => '#3498db', 'title' => 'Agua Potable', 'text' => 'Reporta problemas con el suministro de agua potable.'],
        ['icon' => 'fas fa-tint-slash', 'color' => '#8e44ad', 'title' => 'Drenaje', 'text' => 'Reporta problemas con el sistema de drenaje.']
    ];
    foreach ($categories as $category): ?>
        <div class="col-sm-6 col-md-4 col-lg-3 mb-4">
            <div class="card h-100 shadow-sm text-center border-0">
                <div class="card-body">
                    <div class="mb-3">
                        <i class="<?= $category['icon'] ?> fa-2x" style="color: <?= $category['color'] ?>;"></i>
                    </div>
                    <h5 class="card-title"><?= $category['title'] ?></h5>
                    <p class="card-text"><?= $category['text'] ?></p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row py-5">
    <div class="col-12">
        <h2 class="text-center mb-5">Beneficios</h2>
    </div>
    <?php
    $benefits = [
        ['icon' => 'fas fa-tachometer-alt', 'title' => 'Eficiencia', 'text' => 'Mejora la eficiencia operativa de la alcaldía y agiliza la asignación de recursos.'],
        ['icon' => 'fas fa-eye', 'title' => 'Transparencia', 'text' => 'Aumenta la transparencia y confianza ciudadana con seguimiento en tiempo real.'],
        ['icon' => 'fas fa-leaf', 'title' => 'Ecológico', 'text' => 'Reduce la carga administrativa y el uso de papel con procesos digitales.'],
        ['icon' => 'fas fa-chart-bar', 'title' => 'Datos', 'text' => 'Permite tomar decisiones basadas en datos reales y estadísticas precisas.']
    ];
    foreach ($benefits as $benefit): ?>
        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title d-flex align-items-center justify-content-center">
                        <i class="<?= $benefit['icon'] ?> text-primary me-2"></i> <?= $benefit['title'] ?>
                    </h5>
                    <p class="card-text text-center"><?= $benefit['text'] ?></p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
