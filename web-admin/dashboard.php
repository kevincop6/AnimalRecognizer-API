<?php
// RUTA: web-admin/dashboard.php

session_start();

$db_bypass = true;
require_once '../config/db.php';

if (isset($db_error_message) && $db_error_message === "DB_ERROR") {
    die("Error de conexión a la base de datos.");
}

require_once 'includes/AdminAuth.php';

$token = $_COOKIE['admin_token'] ?? null;
if (!$token) {
    header("Location: index.php");
    exit;
}

try {
    $stmtSesion = $pdo->prepare("
    SELECT
        s.usuario_id,
        s.token,
        s.fecha_expiracion,
        s.activo,
        u.rol,
        u.nombre_completo,
        u.nombre_usuario,
        COALESCE(
            m.url_archivo,
            'https://cdn.pixabay.com/photo/2023/02/18/11/00/icon-7797704_640.png'
        ) AS foto_perfil,
        COALESCE(m.tipo_almacenamiento, 'externo') AS foto_almacenamiento
    FROM sesiones_usuarios s
    INNER JOIN usuarios u
        ON u.id = s.usuario_id
    LEFT JOIN media_archivos m
        ON  m.tipo_entidad = 'usuario'
        AND m.entidad_id   = u.id
        AND m.es_principal = 1
        AND m.estado       = 'visible'
    WHERE s.token = :token
    LIMIT 1
");
$stmtSesion->execute([':token' => $token]);
$sesion = $stmtSesion->fetch(PDO::FETCH_ASSOC);

// --- Validaciones de sesión (sin cambios) ---
if (!$sesion || (int)$sesion['activo'] === 0 || strtotime($sesion['fecha_expiracion']) < time()) {
    if ($sesion && (int)$sesion['activo'] === 1) {
        $stmtInval = $pdo->prepare("UPDATE sesiones_usuarios SET activo = 0 WHERE token = :token");
        $stmtInval->execute([':token' => $token]);
    }
    setcookie('admin_token', '', time() - 3600, "/");
    header("Location: index.php");
    exit;
}

if ($sesion['rol'] !== 'admin' && $sesion['rol'] !== 'moderador') {
    setcookie('admin_token', '', time() - 3600, "/");
    header("Location: index.php");
    exit;
}

// --- Variables globales disponibles para todos los includes ---
$adminNombre     = $sesion['nombre_completo'];
$adminUsuario    = $sesion['nombre_usuario'];
$adminRol        = $sesion['rol'];
$adminFotoPerfil = $sesion['foto_perfil']; // Siempre tiene valor gracias al COALESCE

} catch (Exception $e) {
    die("Error de autenticación: " . $e->getMessage());
}

// -----------------------------------------------------------
// CONSULTAS DEL DASHBOARD
// -----------------------------------------------------------

// Total especies
$stats_animales = $pdo->query("
    SELECT COUNT(*) AS total_animales FROM animales
")->fetch(PDO::FETCH_ASSOC);

// Avistamientos últimos 30 días
$stats_avist = $pdo->query("
    SELECT
        COUNT(*) AS total_avistamientos,
        COUNT(DISTINCT usuario_id) AS usuarios_activos
    FROM avistamientos
    WHERE fecha_avistamiento >= DATE_SUB(NOW(), INTERVAL 30 DAY)
")->fetch(PDO::FETCH_ASSOC);

// Total avistamientos histórico
$total_avist_historico = (int)$pdo->query("
    SELECT COUNT(*) FROM avistamientos
")->fetchColumn();

// Usuarios activos
$stats_usuarios = $pdo->query("
    SELECT
        COUNT(*) AS total_usuarios,
        SUM(CASE WHEN rol = 'admin'      THEN 1 ELSE 0 END) AS admins,
        SUM(CASE WHEN rol = 'moderador'  THEN 1 ELSE 0 END) AS moderadores
    FROM usuarios
    WHERE estado = 1
")->fetch(PDO::FETCH_ASSOC);

// Top 5 animales más avistados
$top_animales = $pdo->query("
    SELECT
        a.nombre_comun,
        COUNT(av.id) AS conteo
    FROM avistamientos av
    LEFT JOIN animales a ON av.animal_id = a.id
    GROUP BY av.animal_id
    ORDER BY conteo DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Últimos 8 avistamientos
$recientes = $pdo->query("
    SELECT
        av.id,
        av.fecha_avistamiento,
        u.nombre_usuario,
        a.nombre_comun,
        a.nombre_cientifico
    FROM avistamientos av
    LEFT JOIN usuarios u ON av.usuario_id  = u.id
    LEFT JOIN animales a ON av.animal_id   = a.id
    ORDER BY av.fecha_avistamiento DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

// Distribución por todas las provincias
$por_provincia = $pdo->query("
    SELECT provincia_region, COUNT(*) AS total
    FROM animales
    WHERE provincia_region IS NOT NULL
    GROUP BY provincia_region
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Avistamientos por día últimos 7 días (para gráfico de línea)
$avist_por_dia = $pdo->query("
    SELECT
        DATE(fecha_avistamiento) AS dia,
        COUNT(*) AS conteo
    FROM avistamientos
    WHERE fecha_avistamiento >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(fecha_avistamiento)
    ORDER BY dia ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Colores por provincia
$colores_provincia = [
    'San Jose'   => '#4b6cb7',
    'Alajuela'   => '#1a9c5b',
    'Cartago'    => '#f57c00',
    'Heredia'    => '#e91e63',
    'Guanacaste' => '#9c27b0',
    'Puntarenas' => '#00bcd4',
    'Limón'      => '#ff5722',
    'Nacional'   => '#607d8b',
];

$page_title = 'Dashboard - AnimalRecognizer Admin';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include 'includes/head.php'; ?>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-panel">

    <?php include 'includes/navbar.php'; ?>

    <div class="content-wrapper">

        <!-- BREADCRUMB -->
        <div class="page-header">
            <h4>Dashboard</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">Inicio</li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </nav>
        </div>

        <!-- ================================
             FILA 1: TARJETAS DE ESTADÍSTICA
        ================================ -->
        <div class="row mb-4">

            <!-- Especies -->
            <div class="col-xl-4 col-md-6 mb-3">
                <div class="card">
                    <div class="stat-card blue">
                        <div class="stat-info">
                            <div class="stat-label">Especies Registradas</div>
                            <div class="stat-value"><?php echo (int)($stats_animales['total_animales'] ?? 0); ?></div>
                            <div class="stat-sub" style="color:#7c83a0;">
                                <i class="mdi mdi-map-marker-outline" style="font-size:13px;color:#4b6cb7;"></i>
                                Fauna de la Península de Osa
                            </div>
                        </div>
                        <div class="stat-icon">
                            <i class="fa-solid fa-paw"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Avistamientos 30d -->
            <div class="col-xl-4 col-md-6 mb-3">
                <div class="card">
                    <div class="stat-card green">
                        <div class="stat-info">
                            <div class="stat-label">Avistamientos (30 días)</div>
                            <div class="stat-value"><?php echo (int)($stats_avist['total_avistamientos'] ?? 0); ?></div>
                            <div class="stat-sub" style="color:#7c83a0;">
                                <i class="mdi mdi-account-outline" style="font-size:13px;color:#1a9c5b;"></i>
                                <?php echo (int)($stats_avist['usuarios_activos'] ?? 0); ?> usuarios activos
                            </div>
                        </div>
                        <div class="stat-icon">
                            <i class="fa-solid fa-binoculars"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usuarios activos -->
            <div class="col-xl-4 col-md-6 mb-3">
                <div class="card">
                    <div class="stat-card orange">
                        <div class="stat-info">
                            <div class="stat-label">Usuarios Activos</div>
                            <div class="stat-value"><?php echo (int)($stats_usuarios['total_usuarios'] ?? 0); ?></div>
                            <div class="stat-sub" style="color:#7c83a0;">
                                <i class="mdi mdi-shield-account-outline" style="font-size:13px;color:#f57c00;"></i>
                                <?php echo (int)($stats_usuarios['admins'] ?? 0); ?> admins &middot;
                                <?php echo (int)($stats_usuarios['moderadores'] ?? 0); ?> mods
                            </div>
                        </div>
                        <div class="stat-icon">
                            <i class="fa-solid fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ================================
             FILA 2: GRÁFICO LÍNEA + DISTRIBUCIÓN
        ================================ -->
        <div class="row mb-4">

            <!-- Gráfico avistamientos 7 días -->
            <div class="col-lg-8 mb-4">
                <div class="card h-100">
                    <div class="card-header-custom">
                        <h6><i class="mdi mdi-chart-line" style="color:#4b6cb7;margin-right:6px;"></i>Avistamientos últimos 7 días</h6>
                        <span class="header-badge">Histórico: <?php echo $total_avist_historico; ?></span>
                    </div>
                    <div class="card-body pt-3">
                        <canvas id="lineChart" style="height:240px;"></canvas>
                    </div>
                </div>
            </div>

            <!-- Distribución por provincias (barras) -->
            <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header-custom">
                        <h6><i class="mdi mdi-map-marker-multiple" style="color:#4b6cb7;margin-right:6px;"></i>Especies por provincia</h6>
                    </div>
                    <div class="card-body pt-3">
                        <?php
                        $totalParaPct = max((int)($stats_animales['total_animales'] ?? 1), 1);
                        if (count($por_provincia) === 0): ?>
                            <p class="text-muted text-center small">Sin datos.</p>
                        <?php else: ?>
                            <?php foreach ($por_provincia as $prov):
                                $nombre = $prov['provincia_region'];
                                $total  = (int)$prov['total'];
                                $pct    = round(($total / $totalParaPct) * 100);
                                $color  = $colores_provincia[$nombre] ?? '#607d8b';
                            ?>
                                <div class="progress-item">
                                    <div class="progress-header">
                                        <span><?php echo htmlspecialchars($nombre); ?></span>
                                        <small><?php echo $total; ?> · <?php echo $pct; ?>%</small>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar"
                                            style="width:<?php echo $pct; ?>%;background:<?php echo $color; ?>;"
                                            role="progressbar"
                                            aria-valuenow="<?php echo $pct; ?>"
                                            aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================================
             FILA 3: TABLA AVISTAMIENTOS + GRÁFICO DONA
        ================================ -->
        <div class="row">

            <!-- Tabla últimos avistamientos -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header-custom">
                        <h6><i class="mdi mdi-table" style="color:#4b6cb7;margin-right:6px;"></i>Últimos avistamientos</h6>
                        <a href="avistamientos.php" style="font-size:12px;color:#4b6cb7;">Ver todos</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Animal</th>
                                    <th>Nombre científico</th>
                                    <th>Usuario</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($recientes) === 0): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="mdi mdi-binoculars" style="font-size:24px;display:block;margin-bottom:6px;"></i>
                                            Sin avistamientos registrados.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recientes as $idx => $av): ?>
                                        <tr>
                                            <td><span class="badge-estado badge-info-soft"><?php echo $idx + 1; ?></span></td>
                                            <td><strong><?php echo htmlspecialchars($av['nombre_comun'] ?? 'Sin identificar'); ?></strong></td>
                                            <td><em style="color:#7c83a0;font-size:12px;"><?php echo htmlspecialchars($av['nombre_cientifico'] ?? '—'); ?></em></td>
                                            <td>
                                                <span style="display:inline-flex;align-items:center;gap:6px;">
                                                    <span style="width:24px;height:24px;border-radius:50%;background:#e8f0fe;color:#4b6cb7;display:inline-flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;">
                                                        <?php echo strtoupper(substr($av['nombre_usuario'] ?? '?', 0, 1)); ?>
                                                    </span>
                                                    <?php echo htmlspecialchars($av['nombre_usuario'] ?? 'Desconocido'); ?>
                                                </span>
                                            </td>
                                            <td style="color:#7c83a0;font-size:12px;">
                                                <?php echo date('d M Y, H:i', strtotime($av['fecha_avistamiento'])); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Gráfico dona top animales -->
            <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header-custom">
                        <h6><i class="mdi mdi-chart-donut" style="color:#4b6cb7;margin-right:6px;"></i>Top 5 más avistados</h6>
                    </div>
                    <div class="card-body d-flex flex-column align-items-center justify-content-center">
                        <?php if (count($top_animales) === 0): ?>
                            <p class="text-muted text-center small">Sin datos de avistamientos.</p>
                        <?php else: ?>
                            <canvas id="donutChart" style="max-height:200px;"></canvas>
                            <ul class="mt-3 mb-0 w-100" style="list-style:none;padding:0;">
                                <?php
                                $donut_colors = ['#4b6cb7','#1a9c5b','#f57c00','#e91e63','#9c27b0'];
                                foreach ($top_animales as $i => $an):
                                    $c = $donut_colors[$i] ?? '#607d8b';
                                ?>
                                <li style="display:flex;align-items:center;gap:8px;font-size:12px;margin-bottom:6px;">
                                    <span style="width:10px;height:10px;border-radius:50%;background:<?php echo $c;?>;flex-shrink:0;"></span>
                                    <span style="flex:1;color:#343a40;"><?php echo htmlspecialchars($an['nombre_comun']); ?></span>
                                    <strong style="color:#1a1a2e;"><?php echo $an['conteo']; ?></strong>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div><!-- end content-wrapper -->
</div><!-- end main-panel -->

<?php include 'includes/scripts.php'; ?>

<script>
// ---- Gráfico línea: avistamientos 7 días ----
const lineLabels = <?php
    $dias   = array_column($avist_por_dia, 'dia');
    $counts = array_map('intval', array_column($avist_por_dia, 'conteo'));
    echo json_encode($dias);
?>;
const lineData = <?php echo json_encode($counts); ?>;

new Chart(document.getElementById('lineChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: lineLabels.length > 0 ? lineLabels : ['Sin datos'],
        datasets: [{
            label: 'Avistamientos',
            data: lineData.length > 0 ? lineData : [0],
            borderColor: '#4b6cb7',
            backgroundColor: 'rgba(75,108,183,0.08)',
            borderWidth: 2.5,
            pointBackgroundColor: '#4b6cb7',
            pointRadius: 4,
            pointHoverRadius: 6,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: {
                grid: { display: false },
                ticks: { font: { size: 11 }, color: '#7c83a0' }
            },
            y: {
                beginAtZero: true,
                grid: { color: '#f0f3f6' },
                ticks: { stepSize: 1, font: { size: 11 }, color: '#7c83a0' }
            }
        }
    }
});

// ---- Gráfico dona: Top 5 animales ----
<?php if (count($top_animales) > 0): ?>
const donutLabels = <?php echo json_encode(array_column($top_animales, 'nombre_comun')); ?>;
const donutData   = <?php echo json_encode(array_map('intval', array_column($top_animales, 'conteo'))); ?>;
const donutColors = ['#4b6cb7','#1a9c5b','#f57c00','#e91e63','#9c27b0'];

new Chart(document.getElementById('donutChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: donutLabels,
        datasets: [{
            data: donutData,
            backgroundColor: donutColors,
            borderWidth: 2,
            borderColor: '#fff',
            hoverOffset: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        cutout: '68%'
    }
});
<?php endif; ?>
</script>
</body>
</html>
