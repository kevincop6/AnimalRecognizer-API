<?php
// RUTA: api/usuarios/registro.php

// 1. Cabeceras (La respuesta será JSON, pero la entrada es POST normal)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// 2. Seguridad: Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido. Debes enviar una petición POST."]);
    exit;
}

// 3. Incluir archivos de configuración y lógica
require_once '../../config/db.php';
require_once '../../includes/Auth.php';

// 4. Recibir los datos UNO POR UNO desde $_POST
// Usamos el operador '??' para asignar una cadena vacía si el campo no se envía.
$nombre_completo = $_POST['nombre_completo'] ?? '';
$nombre_usuario  = $_POST['nombre_usuario'] ?? '';
$correo          = $_POST['correo'] ?? '';
$password        = $_POST['password'] ?? '';
$biografia       = $_POST['biografia'] ?? '';

// 5. Validar campos obligatorios
if (empty($nombre_completo) || empty($correo) || empty($password)) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Faltan datos. 'nombre_completo', 'correo' y 'password' son obligatorios."]);
    exit;
}

// Lógica para nombre de usuario por defecto
if (empty($nombre_usuario)) {
    $partes_correo = explode("@", $correo);
    $nombre_usuario = $partes_correo[0];
}

try {
    // 6. Instanciar la clase y registrar
    $auth = new Auth($pdo);
    
    $nuevoId = $auth->registrarUsuario(
        $nombre_completo,
        $nombre_usuario,
        $correo,
        $password,
        $biografia
    );

    // 7. Responder con éxito
    http_response_code(201); // Created
    echo json_encode([
        "mensaje" => "Usuario registrado exitosamente",
        "id_usuario" => $nuevoId
    ]);

} catch (Exception $e) {
    http_response_code(409); // Conflict (ej: correo repetido)
    echo json_encode(["error" => $e->getMessage()]);
}
?>