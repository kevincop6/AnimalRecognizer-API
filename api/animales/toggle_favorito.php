<?php
// RUTA: api/animales/toggle_favorito.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

require_once '../../config/db.php';
require_once '../../includes/Auth.php';

// ----------------------------------------------------
// 0. PREFLIGHT
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
// 1. LEER TOKEN E ID (POST o JSON)
// ----------------------------------------------------
$data_raw  = file_get_contents("php://input");
$data_json = json_decode($data_raw, true);

$animal_id   = $_POST['animal_id'] ?? ($data_json['animal_id'] ?? null);
$token_final = $_POST['token']     ?? ($data_json['token'] ?? null);

// ----------------------------------------------------
// 2. AUTENTICACIÓN
// ----------------------------------------------------
$auth = new Auth($pdo);

if (empty($token_final)) {
    http_response_code(401);
    echo json_encode(["mensaje" => "Token no proporcionado."]);
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
// 3. VALIDAR ANIMAL
// ----------------------------------------------------
if (empty($animal_id) || !is_numeric($animal_id)) {
    http_response_code(400);
    echo json_encode(["mensaje" => "ID de animal inválido."]);
    exit;
}

$animal_id = (int)$animal_id;

// Verificar que el animal exista
$stmt = $pdo->prepare("SELECT id FROM animales WHERE id = :id");
$stmt->bindParam(':id', $animal_id, PDO::PARAM_INT);
$stmt->execute();

if (!$stmt->fetchColumn()) {
    http_response_code(404);
    echo json_encode(["mensaje" => "El animal no existe."]);
    exit;
}

// ----------------------------------------------------
// 4. TOGGLE FAVORITO
// ----------------------------------------------------
try {

    // ¿Ya es favorito?
    $sql_check = "
        SELECT id
        FROM usuarios_animales_favoritos
        WHERE usuario_id = :usuario_id
          AND animal_id = :animal_id
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql_check);
    $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
    $stmt->bindParam(':animal_id', $animal_id, PDO::PARAM_INT);
    $stmt->execute();

    $favorito_id = $stmt->fetchColumn();

    if ($favorito_id) {
        // ❌ Quitar favorito
        $sql_delete = "
            DELETE FROM usuarios_animales_favoritos
            WHERE id = :id
        ";
        $stmt = $pdo->prepare($sql_delete);
        $stmt->bindParam(':id', $favorito_id, PDO::PARAM_INT);
        $stmt->execute();

        http_response_code(200);
        echo json_encode([
            "mensaje" => "Animal eliminado de favoritos.",
            "es_favorito" => false
        ], JSON_PRETTY_PRINT);

    } else {
        // ⭐ Añadir favorito
        $sql_insert = "
            INSERT INTO usuarios_animales_favoritos (usuario_id, animal_id)
            VALUES (:usuario_id, :animal_id)
        ";
        $stmt = $pdo->prepare($sql_insert);
        $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmt->bindParam(':animal_id', $animal_id, PDO::PARAM_INT);
        $stmt->execute();

        http_response_code(200);
        echo json_encode([
            "mensaje" => "Animal añadido a favoritos.",
            "es_favorito" => true
        ], JSON_PRETTY_PRINT);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "mensaje" => "Error interno del servidor.",
        "detalle" => $e->getMessage()
    ]);
}
