<?php
// RUTA: web-admin/ajax/login_proxy.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Rutas a la lógica central
require_once '../../config/db.php';     
require_once '../includes/AdminAuth.php'; // 🚩 APUNTA A LA NUEVA CLASE AUTÓNOMA

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new Exception("Método no permitido.");
    }
    
    // Obtener datos del post
    $usuario_correo = $_POST['usuario_correo'] ?? '';
    $password = $_POST['password'] ?? '';
    $persistir = ($_POST['persistir'] ?? '0') === '1'; 

    if (empty($usuario_correo) || empty($password)) {
        http_response_code(400);
        throw new Exception("Usuario/Correo y Contraseña son requeridos.");
    }

    // 🚩 INSTANCIAMOS LA CLASE ADMINAUTH
    $auth = new AdminAuth($pdo); // $pdo viene de db.php
    
    // Ejecutar el login 
    $loginResult = $auth->login($usuario_correo, $password, $persistir);

    // CONSTRUIR RESPUESTA JSON ESTÁNDAR DE API
    http_response_code(200);
    echo json_encode([
        "mensaje" => "Inicio de sesión exitoso", 
        "token" => $loginResult['token'],
        "usuario" => [
            "id" => $loginResult['id'],
            "nombre" => $loginResult['nombre'],
            "usuario" => $loginResult['nombre_usuario'],
            "rol" => $loginResult['rol']
        ]
    ]);

} catch (Exception $e) {
    // Manejo de errores 
    $isAuthError = (strpos($e->getMessage(), 'Credenciales incorrectas') !== false || strpos($e->getMessage(), 'acceso no autorizado') !== false || strpos(strtolower($e->getMessage()), 'desactivada') !== false);
    $statusCode = $isAuthError ? 401 : 500;
    
    http_response_code($statusCode);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>