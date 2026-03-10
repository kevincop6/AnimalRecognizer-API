<?php
// RUTA: web-admin/includes/navbar.php

if (!isset($adminUsuario) || !isset($adminRol)) {
    header("Location: index.php");
    exit;
}

$pagina_actual = basename($_SERVER['PHP_SELF']);
$titulos = [
    'dashboard.php'     => 'Dashboard',
    'animales.php'      => 'Gestión de Animales',
    'avistamientos.php' => 'Avistamientos',
    'usuarios.php'      => 'Gestión de Usuarios',
    'media.php'         => 'Galería de Media',
    'configuracion.php' => 'Configuración',
];
$titulo_pagina = $titulos[$pagina_actual] ?? 'Panel Admin';

if (!defined('AVATAR_DEFAULT')) {
    define('AVATAR_DEFAULT', 'https://cdn.pixabay.com/photo/2023/02/18/11/00/icon-7797704_640.png');
}

$adminFotoPerfil = $adminFotoPerfil ?? AVATAR_DEFAULT;
?>

<div class="navbar-top">

    <div class="nav-left">
        <button class="navbar-toggler align-self-center" type="button" data-toggle="minimize" title="Colapsar menú">
            <span class="mdi mdi-menu"></span>
        </button>
        <h5 class="page-title"><?php echo $titulo_pagina; ?></h5>
    </div>

    <div class="nav-right">

        <!-- ==============================
             DROPDOWN USUARIO
        ============================== -->
        <div class="admin-dropdown" id="adminDropdownWrapper">

            <!-- Botón que abre el dropdown -->
            <div class="admin-info" id="adminDropdownToggle" title="Ver opciones de cuenta">
                <div class="admin-avatar">
                    <img
                        src="<?php echo htmlspecialchars($adminFotoPerfil); ?>"
                        alt="<?php echo htmlspecialchars($adminUsuario); ?>"
                        onerror="this.src='<?php echo AVATAR_DEFAULT; ?>'"
                    >
                </div>
                <div>
                    <div class="admin-name">
                        <?php echo htmlspecialchars($adminNombre ?? $adminUsuario); ?>
                    </div>
                    <div class="admin-role">
                        <?php echo ucfirst(htmlspecialchars($adminRol)); ?>
                    </div>
                </div>
                <!-- Flecha indicadora -->
                <i class="mdi mdi-chevron-down admin-dropdown-arrow"></i>
            </div>

            <!-- Panel del dropdown -->
            <div class="admin-dropdown-menu" id="adminDropdownMenu">

                <!-- Cabecera con datos del usuario -->
                <div class="dropdown-header">
                    <img
                        src="<?php echo htmlspecialchars($adminFotoPerfil); ?>"
                        alt="<?php echo htmlspecialchars($adminUsuario); ?>"
                        onerror="this.src='<?php echo AVATAR_DEFAULT; ?>'"
                        class="dropdown-avatar"
                    >
                    <div class="dropdown-user-info">
                        <strong><?php echo htmlspecialchars($adminNombre ?? $adminUsuario); ?></strong>
                        <span>@<?php echo htmlspecialchars($adminUsuario); ?></span>
                        <span class="dropdown-role-badge">
                            <?php echo ucfirst(htmlspecialchars($adminRol)); ?>
                        </span>
                    </div>
                </div>

                <div class="dropdown-divider"></div>

                <!-- Opciones del menú -->
                <a href="perfil.php" class="dropdown-item-custom">
                    <i class="mdi mdi-account-outline"></i>
                    Mi perfil
                </a>
                <a href="configuracion.php" class="dropdown-item-custom">
                    <i class="mdi mdi-cog-outline"></i>
                    Configuración
                </a>

                <div class="dropdown-divider"></div>

                <!-- Cerrar sesión -->
                <a href="logout.php" class="dropdown-item-custom dropdown-logout">
                    <i class="mdi mdi-logout"></i>
                    Cerrar sesión
                </a>

            </div>
        </div>
        <!-- FIN DROPDOWN -->

    </div>
</div>
