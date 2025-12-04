<?php
// RUTA: api/usuarios/generar_llave_api.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

require_once '../../config/db.php';     
require_once '../../includes/Auth.php'; 

$ROLES_REQUERIDOS = ['admin'];

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["mensaje" => "Método no permitido."]);
    exit;
}

// ----------------------------------------------------
// 1. OBTENCIÓN DE TOKEN Y PIN (PRIORIZANDO $_POST)
// ----------------------------------------------------
$auth = new Auth($pdo);

// 1.1. PRIORIDAD 1: Leer Token y PIN individualmente desde $_POST
$token_post = $_POST['token'] ?? null; 
$pin = $_POST['pin'] ?? null;

// 1.2. FALLBACK: Leer Token y PIN del JSON Body (si no vino por $_POST)
if ($token_post === null || $pin === null) {
    $data = json_decode(file_get_contents("php://input"), true);
    $token_post = $token_post ?? ($data['token'] ?? null);
    $pin = $pin ?? ($data['pin'] ?? null);
}

// 1.3. DEFINIR TOKEN FINAL (Header como fuente más segura, si existe)
$token_header = $auth->obtenerTokenDeCabecera();
$token_final = $token_header ?: $token_post; // Prioridad: Header > $_POST/JSON


// ----------------------------------------------------
// 2. AUTENTICACIÓN Y AUTORIZACIÓN
// ----------------------------------------------------

if (empty($token_final)) {
    http_response_code(401);
    echo json_encode(["mensaje" => "Acceso denegado. Token no proporcionado."]);
    exit;
}
if (empty($pin)) {
    http_response_code(400);
    echo json_encode(["mensaje" => "Se requiere un PIN (passphrase) para proteger la llave."]);
    exit;
}

$usuarioData = $auth->validarToken($token_final);

if (!$usuarioData || !in_array($usuarioData['rol'], $ROLES_REQUERIDOS)) {
    http_response_code(403);
    echo json_encode(["mensaje" => "Acceso denegado. Se requiere ser Administrador."]);
    exit;
}

// ----------------------------------------------------
// 3. GENERAR LLAVES
// ----------------------------------------------------
try {
    // La lógica de la clase Auth verifica si el usuario es 'admin'
    $resultado = $auth->generarLlaveAdmin($usuarioData['usuario_id'], $pin);

    http_response_code(201); // Created
    echo json_encode($resultado);

} catch (Exception $e) {
    $statusCode = (strpos($e->getMessage(), 'administradores') !== false) ? 403 : 500;
    http_response_code($statusCode);
    echo json_encode(["mensaje" => "Error al generar llave: " . $e->getMessage()]);
}
?>