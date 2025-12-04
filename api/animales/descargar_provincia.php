<?php
// RUTA: api/animales/descargar_provincia.php (FORZANDO DESCARGA DE ARCHIVO)

// Configuramos las cabeceras para el protocolo de descarga
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

// Rutas a recursos centrales
require_once '../../config/db.php';     
require_once '../../includes/Auth.php'; 

// ... (Manejo de OPTIONS y Método POST - El código anterior permanece igual) ...

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["mensaje" => "Método no permitido."]);
    exit;
}

// ----------------------------------------------------
// 1. OBTENER PROVINCIA Y TOKEN
// ----------------------------------------------------
// ... (Lectura de token y provincia desde POST/JSON) ...
$data_raw = file_get_contents("php://input");
$data_json = json_decode($data_raw, true);
$provincia_raw = $_POST['provincia'] ?? ($data_json['provincia'] ?? null);
$token_final = $_POST['token'] ?? ($data_json['token'] ?? null);

// 2. AUTENTICACIÓN
$auth = new Auth($pdo);
if (empty($token_final) || !$auth->validarToken($token_final)) {
    http_response_code(401);
    echo json_encode(["mensaje" => "Acceso denegado. Token inválido o no proporcionado."]);
    exit;
}
// ----------------------------------------------------

if (empty($provincia_raw)) {
    http_response_code(400);
    echo json_encode(["mensaje" => "Se requiere la provincia para la descarga."]);
    exit;
}

try {
    // Normalización de la provincia
    $provincia_normalizada = strtolower($provincia_raw);
    $provincia_normalizada = str_replace(
        ['á', 'é', 'í', 'ó', 'ú', 'ñ'], 
        ['a', 'e', 'i', 'o', 'u', 'n'], 
        $provincia_normalizada
    );

    $ruta_base = dirname(dirname(__DIR__)); 
    $ruta_archivo = $ruta_base . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "json" . DIRECTORY_SEPARATOR . $provincia_normalizada . ".json";
    
    // Verificación de archivo
    if (!file_exists($ruta_archivo)) {
        http_response_code(404);
        echo json_encode(["mensaje" => "Archivo no encontrado para la provincia: " . $provincia_raw]);
        exit;
    }

    // 🚩 LÓGICA DE DESCARGA FORZADA
    
    // 1. Limpiar cualquier búfer de salida para evitar archivos corruptos
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // 2. Establecer cabeceras necesarias para la descarga binaria
    header('Content-Description: File Transfer');
    header('Content-Type: application/json'); // Mantiene el tipo para la descarga
    header('Content-Disposition: attachment; filename="' . $provincia_normalizada . '.json"'); // Fuerza el nombre de archivo
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($ruta_archivo)); // 🚩 Crucial para el progreso de la descarga en Android
    
    // 3. Leer y servir el archivo raw
    http_response_code(200);
    readfile($ruta_archivo);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["mensaje" => "Error interno del servidor: " . $e->getMessage()]);
}

?>