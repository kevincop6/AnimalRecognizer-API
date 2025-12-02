<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once '../../config/db.php';
require_once '../../includes/Auth.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->correo) || empty($data->password)) {
    http_response_code(400);
    echo json_encode(["error" => "Correo y contraseña requeridos."]);
    exit;
}

try {
    $auth = new Auth($pdo);
    
    // Llamamos a la función login
    $resultado = $auth->login(
        $data->correo, 
        $data->password, 
        $data->dispositivo ?? "Móvil Desconocido"
    );

    http_response_code(200);
    echo json_encode([
        "mensaje" => "Login exitoso",
        "data" => $resultado
    ]);

} catch (Exception $e) {
    // Si la contraseña está mal o el usuario no existe, entra aquí
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => $e->getMessage()]);
}
?>