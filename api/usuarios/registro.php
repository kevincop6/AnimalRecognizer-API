<?php
// RUTA: api/usuarios/registro.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Rutas a recursos centrales
require_once '../../config/db.php';     
require_once '../../includes/Auth.php'; 

// Solo se permite el método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["mensaje" => "Método no permitido."]);
    exit;
}

// ----------------------------------------------------
// 1. OBTENER Y VALIDAR DATOS DE ENTRADA (Lectura directa de $_POST)
// ----------------------------------------------------

$nombre_completo = $_POST['nombre_completo'] ?? '';
$nombre_usuario = $_POST['nombre_usuario'] ?? '';
$correo = $_POST['correo'] ?? '';
$password = $_POST['password'] ?? '';
$biografia = $_POST['biografia'] ?? '';

if (empty($nombre_completo) || empty($nombre_usuario) || empty($correo) || empty($password)) {
    http_response_code(400);
    echo json_encode(["mensaje" => "Todos los campos principales son requeridos."]);
    exit;
}

// ----------------------------------------------------
// 2. REGISTRAR USUARIO Y CONFIGURACIÓN
// ----------------------------------------------------
try {
    $auth = new Auth($pdo);
    
    // Llama a la función centralizada en Auth.php (que maneja la inserción en 'usuarios' y 'configuracion_usuario')
    $usuario_id = $auth->registrarUsuario(
        $nombre_completo, 
        $nombre_usuario, 
        $correo, 
        $password, 
        $biografia
    );

    // ----------------------------------------------------
    // 3. RESPUESTA EXITOSA
    // ----------------------------------------------------
    http_response_code(201); // Created
    echo json_encode([
        "mensaje" => "Usuario y configuración registrados exitosamente.",
        "id" => $usuario_id
    ]);

} catch (PDOException $e) {
    // Manejo de errores de base de datos
    http_response_code(500);
    echo json_encode(["mensaje" => "Error de base de datos durante el registro: " . $e->getMessage()]);

} catch (Exception $e) {
    // Manejo de excepciones (ej: El correo o usuario ya existen, error en lógica de Auth)
    if (strpos($e->getMessage(), 'ya existen') !== false) {
        http_response_code(409); // Conflict
    } else {
        http_response_code(500);
    }
    echo json_encode(["mensaje" => $e->getMessage()]);
}
?>