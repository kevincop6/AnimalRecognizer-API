<?php
// RUTA: web-admin/ajax/get_animales.php
// Retorna la lista paginada de animales con foto principal

header('Content-Type: application/json');
session_start();

require_once '../../config/db.php';
require_once '../includes/AdminAuth.php';

// Validar sesión por cookie
$token = $_COOKIE['admin_token'] ?? null;
if (!$token) { echo json_encode(['success' => false, 'message' => 'No autorizado']); exit; }

$stmtCheck = $pdo->prepare("
    SELECT s.activo, s.fecha_expiracion, u.rol
    FROM sesiones_usuarios s
    INNER JOIN usuarios u ON u.id = s.usuario_id
    WHERE s.token = :token LIMIT 1
");
$stmtCheck->execute([':token' => $token]);
$sesion = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if (!$sesion || (int)$sesion['activo'] === 0 || strtotime($sesion['fecha_expiracion']) < time()) {
    echo json_encode(['success' => false, 'message' => 'Sesión inválida']); exit;
}

// Parámetros de entrada
$pagina    = max(1, (int)($_GET['pagina']    ?? 1));
$porPagina = max(5,  min(50, (int)($_GET['por_pagina'] ?? 10)));
$buscar    = trim($_GET['buscar']    ?? '');
$provincia = trim($_GET['provincia'] ?? '');
$offset    = ($pagina - 1) * $porPagina;

// Construcción dinámica del WHERE con parámetros seguros (PDO)
$where  = "WHERE 1=1";
$params = [];

if ($buscar !== '') {
    $where .= " AND (a.nombre_comun LIKE :buscar OR a.nombre_cientifico LIKE :buscar OR a.nombre_ingles LIKE :buscar)";
    $params[':buscar'] = '%' . $buscar . '%';
}
if ($provincia !== '') {
    $where .= " AND a.provincia_region = :provincia";
    $params[':provincia'] = $provincia;
}

// Total de registros para paginación
$sqlTotal = "SELECT COUNT(*) FROM animales a $where";
$stmtTotal = $pdo->prepare($sqlTotal);
$stmtTotal->execute($params);
$total = (int)$stmtTotal->fetchColumn();

$totalPaginas = max(1, ceil($total / $porPagina));

// Consulta principal con foto principal via LEFT JOIN
$sql = "
    SELECT
        a.id,
        a.nombre_comun,
        a.nombre_cientifico,
        a.nombre_ingles,
        a.provincia_region,
        DATE_FORMAT(a.fecha_creacion, '%d/%m/%Y') AS fecha_creacion,
        -- Foto principal del animal desde media_archivos
        COALESCE(m.url_archivo, NULL) AS foto
    FROM animales a
    LEFT JOIN media_archivos m
        ON  m.tipo_entidad = 'animal'
        AND m.entidad_id   = a.id
        AND m.es_principal = 1
        AND m.estado       = 'visible'
    $where
    ORDER BY a.id DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);

// Bind params individuales (LIMIT/OFFSET requieren PDO::PARAM_INT)
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val, PDO::PARAM_STR);
}
$stmt->bindValue(':limit',  $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,    PDO::PARAM_INT);
$stmt->execute();
$animales = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success'       => true,
    'data'          => $animales,
    'total'         => $total,
    'total_paginas' => $totalPaginas,
    'pagina_actual' => $pagina,
]);
