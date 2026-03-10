<?php
// RUTA: web-admin/includes/sidebar.php
// Sidebar reutilizable. Hace sus propias consultas para los badges.

if (!isset($pdo)) {
    die("Error: conexión a BD no disponible en sidebar.");
}

// Badges del menú
try {
    $sidebar_total_animales = (int)$pdo->query("SELECT COUNT(*) FROM animales")->fetchColumn();
} catch (Exception $e) { $sidebar_total_animales = 0; }

try {
    $sidebar_total_usuarios = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE estado = 1")->fetchColumn();
} catch (Exception $e) { $sidebar_total_usuarios = 0; }

try {
    $sidebar_total_avistamientos = (int)$pdo->query("SELECT COUNT(*) FROM avistamientos")->fetchColumn();
} catch (Exception $e) { $sidebar_total_avistamientos = 0; }

// Detectar página activa para marcar el nav-link
$pagina_actual = basename($_SERVER['PHP_SELF']);

// Iniciales del admin para el logo de la marca (si $adminNombre está disponible)
$iniciales_admin = '';
if (!empty($adminNombre)) {
    $partes = explode(' ', trim($adminNombre));
    $iniciales_admin = strtoupper(substr($partes[0], 0, 1) . (isset($partes[1]) ? substr($partes[1], 0, 1) : ''));
}
?>

<nav class="sidebar">
    <!-- BRAND -->
    <div class="sidebar-brand">
        <div class="brand-logo">
            <i class="mdi mdi-paw"></i>
        </div>
        <div class="brand-text">
            <h5>AnimalRecognizer</h5>
            <small>Administración</small>
        </div>
    </div>

    <!-- MENÚ -->
    <ul class="nav flex-column mt-2 pb-4" style="list-style:none; padding-left:0;">
<li class="nav-item nav-profile">
              <a href="#" class="nav-link">
                <div class="nav-profile-image">
                  <img src="<?php echo $adminFotoPerfil; ?>" alt="profile" />
                  <span class="login-status online"></span>
                  <!--change to offline or busy as needed-->
                </div>
                <div class="nav-profile-text d-flex flex-column">
                  <span class="font-weight-bold mb-2">David Grey. H</span>
                  <span class="text-secondary text-small">Project Manager</span>
                </div>
                <i class="mdi mdi-bookmark-check text-success nav-profile-badge"></i>
              </a>
            </li>
        <li class="nav-section">Principal</li>

        <li class="nav-item">
            <a class="nav-link <?php echo $pagina_actual === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                <i class="mdi mdi-view-dashboard-outline"></i>
                Dashboard
            </a>
        </li>

        <li class="nav-section">Fauna</li>

        <li class="nav-item">
            <a class="nav-link <?php echo $pagina_actual === 'animales.php' ? 'active' : ''; ?>" href="animales.php">
                <i class="mdi mdi-paw-outline"></i>
                Animales
                <span class="nav-badge"><?php echo $sidebar_total_animales; ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $pagina_actual === 'avistamientos.php' ? 'active' : ''; ?>" href="avistamientos.php">
                <i class="mdi mdi-binoculars"></i>
                Avistamientos
                <span class="nav-badge"><?php echo $sidebar_total_avistamientos; ?></span>
            </a>
        </li>

        <li class="nav-section">Comunidad</li>

        <li class="nav-item">
            <a class="nav-link <?php echo $pagina_actual === 'usuarios.php' ? 'active' : ''; ?>" href="usuarios.php">
                <i class="mdi mdi-account-group-outline"></i>
                Usuarios
                <span class="nav-badge"><?php echo $sidebar_total_usuarios; ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $pagina_actual === 'media.php' ? 'active' : ''; ?>" href="media.php">
                <i class="mdi mdi-image-multiple-outline"></i>
                Media
            </a>
        </li>

        <li class="nav-section">Sistema</li>

        <li class="nav-item">
            <a class="nav-link <?php echo $pagina_actual === 'configuracion.php' ? 'active' : ''; ?>" href="configuracion.php">
                <i class="mdi mdi-cog-outline"></i>
                Configuración
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="logout.php">
                <i class="mdi mdi-logout"></i>
                Cerrar sesión
            </a>
        </li>

    </ul>
</nav>
