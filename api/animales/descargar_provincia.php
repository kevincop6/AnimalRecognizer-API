<?php
// RUTA: api/animales/descargar_provincia.php (GATEWAY SEGURO)

// Headers estándar para APIs JSON
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

// Rutas a recursos centrales
require_once '../../config/db.php';     
require_once '../../includes/Auth.php'; 

// ----------------------------------------------------
// 0. MANEJO DE PRE-FLIGHT (OPTIONS)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Solo se permite el método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["mensaje" => "Método no permitido."]);
    exit;
}

// ----------------------------------------------------
// 1. AUTENTICACIÓN Y OBTENCIÓN DE DATOS
// ----------------------------------------------------

// Lectura de provincia y token desde POST/JSON
$data_raw = file_get_contents("php://input");
$data_json = json_decode($data_raw, true);

$provincia_raw = $_POST['provincia'] ?? ($data_json['provincia'] ?? null);
$token_final = $_POST['token'] ?? ($data_json['token'] ?? null);

$auth = new Auth($pdo);

if (empty($token_final) || !$auth->validarToken($token_final)) {
    http_response_code(401);
    echo json_encode(["mensaje" => "Acceso denegado. Token inválido o no proporcionado."]);
    exit;
}

// ----------------------------------------------------
// 2. CONSTRUCCIÓN Y LECTURA DEL ARCHIVO (NORMALIZACIÓN COMPLETA)
// ----------------------------------------------------

if (empty($provincia_raw)) {
    http_response_code(400);
    echo json_encode(["mensaje" => "Se requiere la provincia para la descarga."]);
    exit;
}

try {
    // 🚩 NORMALIZACIÓN CRÍTICA: Eliminar espacios y tildes, y minúsculas (ej: 'San Jose' -> 'sanjose')
    $provincia_normalizada = strtolower($provincia_raw);
    $provincia_normalizada = str_replace(
        [' ', 'á', 'é', 'í', 'ó', 'ú', 'ñ'], 
        ['', 'a', 'e', 'i', 'o', 'u', 'n'], 
        $provincia_normalizada
    );

    // Construcción de la ruta absoluta para acceder al archivo protegido
    $ruta_base = dirname(dirname(__DIR__)); 
    $nombre_archivo = $provincia_normalizada . ".json";
    $ruta_archivo = $ruta_base . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "json" . DIRECTORY_SEPARATOR . $nombre_archivo;
    
    // Verificación de archivo
    if (!file_exists($ruta_archivo)) {
        http_response_code(404);
        echo json_encode(["mensaje" => "Archivo no encontrado para la provincia: " . $provincia_raw]);
        exit;
    }

    // ----------------------------------------------------
    // 3. LÓGICA DE DESCARGA FORZADA (readfile)
    // ----------------------------------------------------
    
    // 1. Limpiar búfer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // 2. Establecer cabeceras cruciales para la descarga en Android
    header('Content-Description: File Transfer');
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"'); 
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($ruta_archivo)); 
    
    // 3. Servir el archivo
    http_response_code(200);
    readfile($ruta_archivo);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["mensaje" => "Error interno del servidor: " . $e->getMessage()]);
}

?>