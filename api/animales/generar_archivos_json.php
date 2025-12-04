<?php
// RUTA: api/animales/generar_archivos_json.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

require_once '../../config/db.php';     
require_once '../../includes/Auth.php'; 
require_once '../../includes/AnimalProcessor.php'; 

$ROLES_REQUERIDOS = ['admin'];

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["mensaje" => "Método no permitido."]);
    exit;
}

// ----------------------------------------------------
// 1. OBTENCIÓN DE DATOS (Lectura robusta de POST individuales)
// ----------------------------------------------------
$auth = new Auth($pdo);

// 1.1. Leer datos del cuerpo POST (o JSON body)
$token_post = $_POST['token'] ?? null;
$pin_post = $_POST['pin'] ?? null;

// 1.2. Fallback: Intentar leer el cuerpo JSON
$data_raw = file_get_contents("php://input");
$data_json = json_decode($data_raw, true);

$token_body_json = $data_json['token'] ?? null;
$pin_body_json = $data_json['pin'] ?? null;


// 1.3. Consolidar Token y PIN (Prioridad: Header > POST > JSON)
$token_header = $auth->obtenerTokenDeCabecera();
$token_final = $token_header ?: $token_post ?: $token_body_json;
$pin_acceso = $pin_post ?: $pin_body_json; 


if (empty($token_final)) {
    http_response_code(401);
    echo json_encode(["mensaje" => "Acceso denegado. Token no proporcionado."]);
    exit;
}
if (empty($pin_acceso)) {
    http_response_code(400);
    echo json_encode(["mensaje" => "Se requiere el PIN de acceso para descifrar la llave."]);
    exit;
}


// ----------------------------------------------------
// 2. AUTENTICACIÓN, AUTORIZACIÓN E IDENTIFICACIÓN DE LLAVE
// ----------------------------------------------------
$usuarioData = $auth->validarToken($token_final);

// Verificar rol de administrador
if (!$usuarioData || !in_array($usuarioData['rol'], $ROLES_REQUERIDOS)) {
    http_response_code(403);
    echo json_encode(["mensaje" => "Acceso denegado. Se requiere rol de Administrador."]);
    exit;
}

try {
    $usuario_id = $usuarioData['usuario_id']; // ID del administrador

    // 2.1. Buscar la metadata de la llave USANDO EL ID DEL USUARIO
    $sql_llave = "SELECT identificador_llave 
                  FROM llaves_criptograficas 
                  WHERE usuario_id = :uid AND revocada = 0
                  ORDER BY fecha_creacion DESC LIMIT 1"; 
    
    $stmt_llave = $pdo->prepare($sql_llave);
    $stmt_llave->execute([':uid' => $usuario_id]);
    $llave_data = $stmt_llave->fetch(PDO::FETCH_ASSOC);

    if (!$llave_data) {
        throw new Exception("No se encontró una llave criptográfica activa vinculada a este administrador.");
    }
    
    $identificador_llave = $llave_data['identificador_llave'];
    
    // ----------------------------------------------------
    // 3. VERIFICACIÓN Y DESCIFRADO DE LA LLAVE
    // ----------------------------------------------------
    
    $ruta_base_proyecto = dirname(dirname(__DIR__)); 
    $ruta_privada = $ruta_base_proyecto . '/public/llaves/' . $identificador_llave . "_privada.pem"; 

    if (!file_exists($ruta_privada)) {
        throw new Exception("Archivo de llave privada no encontrado en el sistema. Revise la generación.");
    }
    
    $key_content = file_get_contents($ruta_privada);

    // Intentar descifrar/Cargar la llave usando el PIN
    $rsa_resource = openssl_pkey_get_private($key_content, $pin_acceso);
    
    if ($rsa_resource === false) {
        throw new Exception("Fallo de autenticación de llave: PIN incorrecto o llave corrupta.");
    }
    
    // ----------------------------------------------------
    // 4. EJECUTAR TAREA PRIVILEGIADA (Generación de JSON)
    // ----------------------------------------------------
    
    $processor = new AnimalProcessor($pdo);
    $resultados = $processor->generateAndSaveProvinceJSONs(); // Tarea de alto consumo
    
    http_response_code(200);
    echo json_encode([
        "mensaje" => "Tarea completada: Archivos JSON generados y almacenados con éxito.",
        "archivos_generados" => $resultados
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["mensaje" => "Error de tarea privilegiada: " . $e->getMessage()]);
}
?>