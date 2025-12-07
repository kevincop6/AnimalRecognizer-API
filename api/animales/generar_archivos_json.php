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
$DS = DIRECTORY_SEPARATOR;

// ---------------------------------------------
// 0. MANEJO DE MÉTODO HTTP / CORS
// ---------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "status"  => false,
        "mensaje" => "Método no permitido."
    ]);
    exit;
}

// ---------------------------------------------
// 1. OBTENCIÓN DE DATOS (Token + PIN)
// ---------------------------------------------
$auth = new Auth($pdo);

// 1.1. Leer datos POST
$token_post = $_POST['token'] ?? null;
$pin_post   = $_POST['pin']   ?? null;

// 1.2. Leer JSON body
$data_raw  = file_get_contents("php://input");
$data_json = json_decode($data_raw, true);

$token_body_json = $data_json['token'] ?? null;
$pin_body_json   = $data_json['pin']   ?? null;

// 1.3. Consolidar Token y PIN
$token_header = $auth->obtenerTokenDeCabecera();
$token_final  = $token_header ?: $token_post ?: $token_body_json;
$pin_acceso   = $pin_post ?: $pin_body_json;

if (empty($token_final)) {
    http_response_code(401);
    echo json_encode([
        "status"  => false,
        "mensaje" => "Acceso denegado. Token no proporcionado."
    ]);
    exit;
}

if (empty($pin_acceso)) {
    http_response_code(400);
    echo json_encode([
        "status"  => false,
        "mensaje" => "Se requiere el PIN de acceso para descifrar la llave."
    ]);
    exit;
}

// ---------------------------------------------
// 2. AUTENTICACIÓN Y LLAVE ACTIVA
// ---------------------------------------------
try {
    $usuarioData = $auth->validarToken($token_final);

    if (!$usuarioData || !in_array($usuarioData['rol'], $ROLES_REQUERIDOS, true)) {
        http_response_code(403);
        echo json_encode([
            "status"  => false,
            "mensaje" => "Acceso denegado. Se requiere rol de Administrador."
        ]);
        exit;
    }

    $usuario_id = (int)$usuarioData['usuario_id'];

    // 2.1. Buscar llave activa
    $sql_llave = "
        SELECT identificador_llave
        FROM llaves_criptograficas
        WHERE usuario_id = :uid
          AND revocada = 0
        ORDER BY fecha_creacion DESC
        LIMIT 1
    ";

    $stmt_llave = $pdo->prepare($sql_llave);
    $stmt_llave->execute([':uid' => $usuario_id]);
    $llave_data = $stmt_llave->fetch(PDO::FETCH_ASSOC);

    if (!$llave_data) {
        throw new Exception("No se encontró una llave criptográfica activa para este administrador.");
    }

    $identificador_llave = $llave_data['identificador_llave'];

    // ---------------------------------------------
    // 3. VERIFICACIÓN DE OPENSSL Y LECTURA DEL ARCHIVO
    // ---------------------------------------------
    if (!extension_loaded('openssl')) {
        throw new Exception("La extensión OpenSSL no está disponible en este servidor.");
    }

    while (openssl_error_string()) { /* limpiar errores previos */ }

    // Rutas correctas
    $ruta_base_proyecto = dirname(dirname(__DIR__));
    $ruta_llaves        = $ruta_base_proyecto . $DS . 'public' . $DS . 'llaves' . $DS;

    $ruta_privada = $ruta_llaves . $identificador_llave . "_privada.pem";

    if (!file_exists($ruta_privada)) {
        throw new Exception("Archivo de llave privada no encontrado. Revise si fue eliminada o revocada.");
    }

    $key_content = file_get_contents($ruta_privada);
    if ($key_content === false) {
        throw new Exception("No se pudo leer el archivo de llave privada.");
    }

    $rsa_resource = openssl_pkey_get_private($key_content, $pin_acceso);

    if (!$rsa_resource) {
        $errores = [];
        while ($e = openssl_error_string()) {
            $errores[] = $e;
        }
        $detalle = $errores ? implode(" | ", $errores) : "PIN incorrecto o llave dañada.";
        throw new Exception("Fallo de autenticación de llave: " . $detalle);
    }

    // ---------------------------------------------
    // 4. EJECUTAR LA TAREA PRIVILEGIADA
    // ---------------------------------------------
    $processor  = new AnimalProcessor($pdo);
    $resultados = $processor->generateAndSaveProvinceJSONs();

    openssl_pkey_free($rsa_resource);

    http_response_code(200);
    echo json_encode([
        "status"            => true,
        "mensaje"           => "Tarea completada: Archivos JSON generados y almacenados con éxito.",
        "archivos_generados"=> $resultados
    ], JSON_PRETTY_PRINT);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status"  => false,
        "mensaje" => "Error de tarea privilegiada: " . $e->getMessage()
    ]);
    exit;
}
