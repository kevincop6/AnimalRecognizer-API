<?php
// RUTA: api/animales/view_animal.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

// Recursos centrales
require_once '../../config/db.php';
require_once '../../includes/Auth.php';

// ----------------------------------------------------
// 0. MANEJO DE PRE-FLIGHT (OPTIONS)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["mensaje" => "Método no permitido."]);
    exit;
}

// ----------------------------------------------------
// 1. OBTENER TOKEN E ID
// ----------------------------------------------------
$data_raw  = file_get_contents("php://input");
$data_json = json_decode($data_raw, true);

$animal_id   = $_POST['id']    ?? ($data_json['id']    ?? null);
$token_final = $_POST['token'] ?? ($data_json['token'] ?? null);

// ----------------------------------------------------
// 2. AUTENTICACIÓN
// ----------------------------------------------------
$auth = new Auth($pdo);

if (empty($token_final)) {
    http_response_code(401);
    echo json_encode(["mensaje" => "Acceso denegado. Token no proporcionado."]);
    exit;
}

$usuarioData = $auth->validarToken($token_final);

if (!$usuarioData) {
    http_response_code(401);
    echo json_encode(["mensaje" => "Token inválido o expirado."]);
    exit;
}

$usuario_id = (int)$usuarioData['usuario_id'];

// ----------------------------------------------------
// 3. VALIDACIÓN DEL ID
// ----------------------------------------------------
if (empty($animal_id) || !is_numeric($animal_id)) {
    http_response_code(400);
    echo json_encode(["mensaje" => "Se requiere el ID del animal."]);
    exit;
}

$animal_id = (int)$animal_id;

// ----------------------------------------------------
// 4. OBTENER DATOS
// ----------------------------------------------------
try {

    // 4.1 DETALLES DEL ANIMAL
    $sql_animal = "SELECT * FROM animales WHERE id = :id";
    $stmt = $pdo->prepare($sql_animal);
    $stmt->bindParam(':id', $animal_id, PDO::PARAM_INT);
    $stmt->execute();
    $animal = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$animal) {
        http_response_code(404);
        echo json_encode(["mensaje" => "Animal no encontrado."]);
        exit;
    }

    // 4.2 IMÁGENES DEL ANIMAL
    $sql_imagenes = "
        SELECT url_archivo, es_principal, tipo_almacenamiento
        FROM media_archivos
        WHERE tipo_entidad = 'animal'
          AND entidad_id = :id
          AND estado = 'visible'
        ORDER BY es_principal DESC, fecha_subida DESC
    ";
    $stmt = $pdo->prepare($sql_imagenes);
    $stmt->bindParam(':id', $animal_id, PDO::PARAM_INT);
    $stmt->execute();
    $media_urls = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4.3 VERIFICAR SI ES FAVORITO ⭐
    $sql_favorito = "
        SELECT 1
        FROM usuarios_animales_favoritos
        WHERE usuario_id = :usuario_id
          AND animal_id = :animal_id
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql_favorito);
    $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
    $stmt->bindParam(':animal_id', $animal_id, PDO::PARAM_INT);
    $stmt->execute();

    $es_favorito = $stmt->fetchColumn() ? true : false;

    // 4.4 RECOMENDACIONES
    $LIMITE_RECOMENDACIONES = 5;

    $sql_recomendar = "
        SELECT 
            a.id,
            a.nombre_cientifico AS nombre,
            m.url_archivo AS imagen_principal
        FROM animales a
        LEFT JOIN media_archivos m
            ON a.id = m.entidad_id
           AND m.tipo_entidad = 'animal'
           AND m.es_principal = 1
        WHERE a.id != :id_excluido
        ORDER BY RAND()
        LIMIT :limite
    ";

    $stmt = $pdo->prepare($sql_recomendar);
    $stmt->bindValue(':id_excluido', $animal_id, PDO::PARAM_INT);
    $stmt->bindValue(':limite', $LIMITE_RECOMENDACIONES, PDO::PARAM_INT);
    $stmt->execute();
    $recomendaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ------------------------------------------------
    // 5. ENSAMBLAR JSON FINAL
    // ------------------------------------------------
    $animal['taxonomia']    = json_decode($animal['taxonomia'], true);
    $animal['distribucion'] = json_decode($animal['distribucion'], true);
    $animal['descripcion']  = json_decode($animal['descripcion'], true);

    $animal['imagenes']    = $media_urls;
    $animal['es_favorito'] = $es_favorito;

    http_response_code(200);
    echo json_encode([
        "mensaje" => "Datos del animal obtenidos correctamente.",
        "animal" => $animal,
        "recomendaciones" => $recomendaciones
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "mensaje" => "Error interno del servidor.",
        "detalle" => $e->getMessage()
    ]);
}
