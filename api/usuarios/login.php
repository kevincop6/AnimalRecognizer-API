<?php
// RUTA: api/usuarios/login.php

// 1. Configuración de Cabeceras
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// 2. Seguridad: Bloquear si no es POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); 
    echo json_encode(["error" => "Método no permitido. Se requiere POST."]);
    exit;
}

require_once '../../config/db.php';
require_once '../../includes/Auth.php';

// 3. Recibir datos desde $_POST
// NOTA: Esta línea permite que el Frontend sea flexible.
// Puede enviar el campo como "usuario_o_correo", "correo" o "nombre_usuario".
$loginInput = $_POST['usuario_o_correo'] ?? $_POST['correo'] ?? $_POST['nombre_usuario'] ?? '';
$password   = $_POST['password'] ?? '';
$dispositivo = $_POST['dispositivo'] ?? 'Dispositivo Desconocido';

// 4. Validar que no lleguen vacíos
if (empty($loginInput) || empty($password)) {
    http_response_code(400);
    echo json_encode(["error" => "Debes ingresar tu usuario/correo y contraseña."]);
    exit;
}

try {
    $auth = new Auth($pdo);
    
    // 5. Llamar a la lógica (Auth.php se encarga de cerrar la sesión vieja y abrir la nueva)
    $resultado = $auth->login($loginInput, $password, $dispositivo);

    // 6. Responder con éxito
    http_response_code(200);
    echo json_encode([
        "mensaje" => "Inicio de sesión exitoso",
        "token" => $resultado['token'],
        "usuario" => $resultado['usuario'],
        // Opcional: devolver fecha de expiración por si la app la necesita
        // "expira" => $resultado['expira'] 
    ]);

} catch (Exception $e) {
    // 401: Unauthorized (Credenciales malas o cuenta desactivada)
    http_response_code(401); 
    echo json_encode(["error" => $e->getMessage()]);
}
?>