<?php
// RUTA: api/usuarios/generar_llave_api.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

require_once '../../config/db.php';     
require_once '../../includes/Auth.php'; // Auth.php ahora carga phpseclib

$ROLES_REQUERIDOS = ['admin'];

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["mensaje" => "Método no permitido."]);
    exit;
}

// ----------------------------------------------------
// 1. OBTENCIÓN DE DATOS (Token y PIN)
// ----------------------------------------------------
$auth = new Auth($pdo);

// Leer datos directamente desde $_POST (Campos individuales)
$token_input = $_POST['token'] ?? null; 
$pin_acceso = $_POST['pin'] ?? null;

// Definir token final (Prioridad: Header > $_POST)
$token_header = $auth->obtenerTokenDeCabecera();
$token_final = $token_header ?: $token_input;


// ----------------------------------------------------
// 2. VALIDACIÓN DE ENTRADA Y AUTENTICACIÓN
// ----------------------------------------------------

if (empty($token_final)) {
    http_response_code(401);
    echo json_encode(["mensaje" => "Acceso denegado. Token no proporcionado."]);
    exit;
}
if (empty($pin_acceso)) {
    http_response_code(400);
    echo json_encode(["mensaje" => "Se requiere el PIN (passphrase) para generar la llave."]);
    exit;
}

$usuarioData = $auth->validarToken($token_final);

if (!$usuarioData || !in_array($usuarioData['rol'], $ROLES_REQUERIDOS)) {
    http_response_code(403);
    echo json_encode(["mensaje" => "Acceso denegado. Se requiere rol de Administrador."]);
    exit;
}

try {
    // 3. LLAMADA A LA FUNCIÓN DE GENERACIÓN DE LLAVE (Usa phpseclib)
    $resultado = $auth->generarLlaveAdmin(
        $usuarioData['usuario_id'], 
        $pin_acceso
    );

    http_response_code(201); // Created
    echo json_encode($resultado);

} catch (Exception $e) {
    // Esto captura cualquier fallo de la librería o de permisos.
    http_response_code(500);
    echo json_encode(["mensaje" => "Error al generar llave: " . $e->getMessage()]);
}
?>