<?php
// RUTA: api/avistamientos/detalle.php
header('Content-Type: application/json');

require_once '../../config/db.php';
require_once '../../includes/Auth.php';

try {

    // ==================================================
    // 1. Validar POST obligatorio
    // ==================================================
    if (!isset($_POST['token'], $_POST['avistamiento_id'])) {
        http_response_code(400);
        echo json_encode([
            "error" => "Datos incompletos"
        ]);
        exit;
    }

    $token = trim($_POST['token']);
    $avistamientoId = (int) $_POST['avistamiento_id'];

    // ==================================================
    // 2. Validar token
    // ==================================================
    $auth = new Auth($pdo);
    $usuarioToken = $auth->validarToken($token);

    if (!$usuarioToken) {
        http_response_code(401);
        echo json_encode([
            "error" => "Token inválido o expirado"
        ]);
        exit;
    }

    $usuarioId = (int) $usuarioToken['usuario_id'];

    // ==================================================
    // 3. Obtener avistamiento + animal (SOLO si está validado)
    // ==================================================
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.usuario_id,
            a.animal_id,
            a.titulo,
            a.descripcion,
            a.fecha_avistamiento,
            an.nombre_comun
        FROM avistamientos a
        INNER JOIN animales an ON an.id = a.animal_id
        WHERE a.id = ?
          AND a.validado = 1
        LIMIT 1
    ");
    $stmt->execute([$avistamientoId]);
    $avistamiento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$avistamiento) {
        http_response_code(403);
        echo json_encode([
            "error" => "Avistamiento no disponible"
        ]);
        exit;
    }

    $duenoAvistamientoId = (int) $avistamiento['usuario_id'];

    // ==================================================
    // 4. Registrar vista SOLO si NO es el dueño
    // ==================================================
    if ($usuarioId !== $duenoAvistamientoId) {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO avistamientos_vistas (avistamiento_id, usuario_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$avistamientoId, $usuarioId]);
    }

    // ==================================================
    // 5. Imágenes (solo visibles, principal primero)
    // ==================================================
    $stmt = $pdo->prepare("
        SELECT url_archivo
        FROM media_archivos
        WHERE tipo_entidad = 'avistamiento'
          AND entidad_id = ?
          AND estado = 'visible'
        ORDER BY es_principal DESC, fecha_subida ASC
    ");
    $stmt->execute([$avistamientoId]);
    $imagenes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // ==================================================
    // 6. Likes totales
    // ==================================================
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM avistamientos_likes
        WHERE avistamiento_id = ?
    ");
    $stmt->execute([$avistamientoId]);
    $totalLikes = (int) $stmt->fetchColumn();

    // ==================================================
    // 7. ¿Usuario dio like?
    // ==================================================
    $stmt = $pdo->prepare("
        SELECT 1
        FROM avistamientos_likes
        WHERE avistamiento_id = ?
          AND usuario_id = ?
        LIMIT 1
    ");
    $stmt->execute([$avistamientoId, $usuarioId]);
    $usuarioDioLike = $stmt->fetch() ? true : false;

    // ==================================================
    // 8. Vistas totales
    // ==================================================
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM avistamientos_vistas
        WHERE avistamiento_id = ?
    ");
    $stmt->execute([$avistamientoId]);
    $totalVistas = (int) $stmt->fetchColumn();

    // ==================================================
    // 9. Comentarios visibles
    // ==================================================
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM avistamientos_comentarios
        WHERE avistamiento_id = ?
          AND estado = 'activo'
    ");
    $stmt->execute([$avistamientoId]);
    $totalComentarios = (int) $stmt->fetchColumn();

    // ==================================================
    // 10. Respuesta final
    // ==================================================
    echo json_encode([
        "avistamiento" => [
            "id" => (int) $avistamiento['id'],
            "titulo" => $avistamiento['titulo'],
            "descripcion" => $avistamiento['descripcion'],
            "fecha" => $avistamiento['fecha_avistamiento'],
            "imagenes" => $imagenes,
            "es_dueno" => ($usuarioId === $duenoAvistamientoId),
            "animal" => [
                "id" => (int) $avistamiento['animal_id'],
                "nombre_comun" => $avistamiento['nombre_comun']
            ]
        ],
        "metricas" => [
            "likes" => $totalLikes,
            "vistas" => $totalVistas,
            "comentarios" => $totalComentarios,
            "usuario_dio_like" => $usuarioDioLike
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Error interno del servidor",
        "detalle" => $e->getMessage()
    ]);
}
