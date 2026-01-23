<?php
// RUTA: api/animales/leer_clases.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../../config/db.php';
require_once '../../includes/Auth.php';

// ---------------------------------------------
// 0. CORS / MÉTODO HTTP
// ---------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "status"  => false,
        "mensaje" => "Método no permitido."
    ]);
    exit;
}

// ---------------------------------------------
// 1. OBTENER TOKEN SOLO DESDE POST / BODY
// ---------------------------------------------
$auth = new Auth($pdo);

// POST normal (form-data / x-www-form-urlencoded)
$token_post = $_POST['token'] ?? null;

// JSON body
$raw_body  = file_get_contents("php://input");
$json_body = json_decode($raw_body, true);
$token_json = $json_body['token'] ?? null;

// Consolidar (SIN CABECERAS)
$token_final = $token_post ?: $token_json;

if (empty($token_final)) {
    http_response_code(401);
    echo json_encode([
        "status"  => false,
        "mensaje" => "Token no proporcionado en el POST."
    ]);
    exit;
}

// ---------------------------------------------
// 2. VALIDAR TOKEN
// ---------------------------------------------
$usuarioData = $auth->validarToken($token_final);

if (!$usuarioData) {
    http_response_code(401);
    echo json_encode([
        "status"  => false,
        "mensaje" => "Token inválido o expirado."
    ]);
    exit;
}

// ---------------------------------------------
// 3. LEER ARCHIVO clases.json
// ---------------------------------------------
$ruta_base = dirname(dirname(__DIR__));
$ruta_json = $ruta_base
           . DIRECTORY_SEPARATOR . 'public'
           . DIRECTORY_SEPARATOR . 'categorias'
           . DIRECTORY_SEPARATOR . 'clases.json';

if (!file_exists($ruta_json)) {
    http_response_code(404);
    echo json_encode([
        "status"  => false,
        "mensaje" => "Archivo clases.json no encontrado."
    ]);
    exit;
}

$contenido = file_get_contents($ruta_json);

if ($contenido === false) {
    http_response_code(500);
    echo json_encode([
        "status"  => false,
        "mensaje" => "No se pudo leer el archivo clases.json."
    ]);
    exit;
}

// ---------------------------------------------
// 4. RESPUESTA
// ---------------------------------------------
echo json_encode([
    "status" => true,
    "data"   => json_decode($contenido, true)
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;
