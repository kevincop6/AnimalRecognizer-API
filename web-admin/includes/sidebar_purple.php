<?php
// RUTA: web-admin/includes/sidebar_purple.php

$totalAnimalesSidebar = isset($stats_animales['total_animales'])
    ? (int)$stats_animales['total_animales']
    : 0;

$totalUsuariosSidebar = isset($stats_usuarios['total_usuarios'])
    ? (int)$stats_usuarios['total_usuarios']
    : 0;
?>
<nav class="sidebar">
    <div class="sidebar-brand">
        <i class="mdi mdi-paw"></i>
        <div>
            <h3>AnimalRecognizer</h3>
            <small class="text-muted">Admin Panel</small>
        </div>
    </div>

    <ul class="nav flex-column">
        <li class="nav-item nav-category">Principal</li>
        <li class="nav-item">
            <a class="nav-link active" href="dashboard.php">
                <i class="mdi mdi-view-dashboard-outline"></i>
                <span class="menu-title ml-2">Dashboard</span>
            </a>
        </li>

        <li class="nav-item nav-category">Fauna</li>
        <li class="nav-item">
            <a class="nav-link" href="animales.php">
                <i class="mdi mdi-database"></i>
                <span class="menu-title ml-2">Animales</span>
                <span class="badge badge-light ml-auto"><?php echo $totalAnimalesSidebar; ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="avistamientos.php">
                <i class="mdi mdi-binoculars"></i>
                <span class="menu-title ml-2">Avistamientos</span>
            </a>
        </li>

        <li class="nav-item nav-category">Comunidad</li>
        <li class="nav-item">
            <a class="nav-link" href="usuarios.php">
                <i class="mdi mdi-account-group"></i>
                <span class="menu-title ml-2">Usuarios</span>
                <span class="badge badge-light ml-auto"><?php echo $totalUsuariosSidebar; ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="media.php">
                <i class="mdi mdi-image-multiple"></i>
                <span class="menu-title ml-2">Media</span>
            </a>
        </li>

        <li class="nav-item nav-category">Configuración</li>
        <li class="nav-item">
            <a class="nav-link" href="configuracion.php">
                <i class="mdi mdi-cog-outline"></i>
                <span class="menu-title ml-2">Ajustes</span>
            </a>
        </li>

        <li class="nav-item nav-category">App pública</li>
        <li class="nav-item">
            <a class="nav-link" href="../index.php" target="_blank">
                <i class="mdi mdi-open-in-new"></i>
                <span class="menu-title ml-2">Ver app</span>
            </a>
        </li>
    </ul>
</nav>
