<?php
// RUTA: api/usuarios/verificar_sesion.php

// Configuración de cabeceras
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// 1. Seguridad: Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido. Se requiere POST."]);
    exit;
}

require_once '../../config/db.php';
require_once '../../includes/Auth.php';

// 2. Recibir el token estrictamente por POST
// La app debe enviar un campo llamado 'token' en el cuerpo del Form-Data
$tokenRecibido = $_POST['token'] ?? '';

if (empty($tokenRecibido)) {
    http_response_code(400); // Bad Request
    echo json_encode([
        "activo" => false, 
        "mensaje" => "No se envió el campo 'token' en el POST."
    ]);
    exit;
}

try {
    $auth = new Auth($pdo);
    
    // 3. Llamar a la función de Auth.php
    $datosUsuario = $auth->validarToken($tokenRecibido);

    if ($datosUsuario) {
        // --- CASO 1: SESIÓN ACTIVA ---
        http_response_code(200);
        echo json_encode([
            "activo" => true,
            "mensaje" => "Sesión activa y válida.",
            "usuario" => [
                "id" => $datosUsuario['usuario_id'],
                "nombre" => $datosUsuario['nombre_completo'],
                "usuario" => $datosUsuario['nombre_usuario'],
                "rol" => $datosUsuario['rol']
            ]
        ]);
    } else {
        // --- CASO 2: SESIÓN INVÁLIDA (Expirada, cerrada o inexistente) ---
        // Devolvemos 200 OK para que la app lea el JSON, pero con "activo": false
        http_response_code(200);
        echo json_encode([
            "activo" => false,
            "mensaje" => "La sesión ha caducado o se cerró en otro dispositivo."
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error del servidor: " . $e->getMessage()]);
}
?>