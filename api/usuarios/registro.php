<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// Incluimos DB y la Clase Auth
require_once '../../config/db.php';
require_once '../../includes/Auth.php';

$data = json_decode(file_get_contents("php://input"));

// Validación simple de entrada
if (empty($data->nombre_completo) || empty($data->correo) || empty($data->password)) {
    http_response_code(400);
    echo json_encode(["error" => "Faltan datos obligatorios."]);
    exit;
}

try {
    // Instanciamos la clase y llamamos a la función
    $auth = new Auth($pdo);
    
    $nuevoId = $auth->registrarUsuario(
        $data->nombre_completo,
        $data->nombre_usuario,
        $data->correo,
        $data->password,
        $data->biografia ?? ""
    );

    http_response_code(201);
    echo json_encode(["mensaje" => "Usuario creado", "id" => $nuevoId]);

} catch (Exception $e) {
    // Capturamos el error que lanza la función (ej: "Correo ya existe")
    http_response_code(409); // Conflict
    echo json_encode(["error" => $e->getMessage()]);
}
?>