<?php
// RUTA: api/usuarios/logout.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

require_once '../../config/db.php';
require_once '../../includes/Auth.php';

try {
    $auth = new Auth($pdo);
    
    // 1. Obtener el token actual del Header
    $token = $auth->obtenerTokenDeCabecera();

    if ($token) {
        // 2. Cerrar esa sesión específicamente
        $auth->logout($token);
        http_response_code(200);
        echo json_encode(["mensaje" => "Sesión cerrada correctamente."]);
    } else {
        http_response_code(400);
        echo json_encode(["error" => "No se proporcionó token para cerrar."]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>