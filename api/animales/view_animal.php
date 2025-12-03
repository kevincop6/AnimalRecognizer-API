<?php
// RUTA: api/animales/view_animal.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

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
// 1. OBTENER TOKEN E ID (Priorizando $_POST, luego JSON)
// ----------------------------------------------------

// 1.1. PRIORIZAR LA LECTURA DESDE $_POST (Método Formulario tradicional)
$animal_id = $_POST['id'] ?? null;
$token_body = $_POST['token'] ?? null; // Si el token viene directamente en el cuerpo POST

// 1.2. FALLBACK: Si no vino por $_POST, intenta leer el cuerpo JSON (para JSON body)
if ($animal_id === null || $token_body === null) {
    // Lectura del cuerpo JSON crudo
    $data = json_decode(file_get_contents("php://input"), true);
    // Solo asigna si la variable aún es null
    $animal_id = $animal_id ?? ($data['id'] ?? null); 
    $token_body = $token_body ?? ($data['token'] ?? null);
}

// ----------------------------------------------------
// 2. AUTENTICACIÓN
// ----------------------------------------------------
$auth = new Auth($pdo);

// 2.1. Intentar obtener el token de la cabecera (MÉTODO ESTÁNDAR)
$token_header = $auth->obtenerTokenDeCabecera();

// 2.2. Definir el token final (Prioridad: Header > POST Body)
$token_final = $token_header ?: $token_body;


if (empty($token_final)) {
    http_response_code(401);
    echo json_encode(["mensaje" => "Acceso denegado. Token de autorización no proporcionado."]);
    exit;
}

// 2.3. Validar el token final
$usuarioData = $auth->validarToken($token_final);

if (!$usuarioData) {
    http_response_code(401);
    echo json_encode(["mensaje" => "Acceso denegado. Token inválido o expirado."]);
    exit;
}

// ----------------------------------------------------
// 3. VALIDACIÓN DEL ID
// ----------------------------------------------------
if (empty($animal_id) || !is_numeric($animal_id)) {
    http_response_code(400);
    echo json_encode(["mensaje" => "Se requiere el ID del animal."]);
    exit;
}

// ----------------------------------------------------
// 4. OBTENER DATOS Y ENSAMBLAR JSON (El resto de la lógica no cambia)
// ----------------------------------------------------
try {
    // 4.1. Obtener detalles del animal 
    $sql_animal = "SELECT * FROM animales WHERE id = :id";
    $stmt_animal = $pdo->prepare($sql_animal);
    $stmt_animal->bindParam(':id', $animal_id, PDO::PARAM_INT);
    $stmt_animal->execute();
    $animal = $stmt_animal->fetch(PDO::FETCH_ASSOC);

    // ... (El resto del código de ensamblaje JSON sigue aquí) ...
    if (!$animal) {
        http_response_code(404);
        echo json_encode(["mensaje" => "Animal no encontrado con el ID proporcionado."]);
        exit;
    }

    // 4.2. Obtener URLs de las imágenes desde la tabla media_archivos
    $sql_imagenes = "SELECT url_archivo, es_principal, tipo_almacenamiento FROM media_archivos WHERE tipo_entidad = 'animal' AND entidad_id = :id AND estado = 'visible' ORDER BY es_principal DESC, fecha_subida DESC";
    $stmt_imagenes = $pdo->prepare($sql_imagenes);
    $stmt_imagenes->bindParam(':id', $animal_id, PDO::PARAM_INT);
    $stmt_imagenes->execute();
    $media_urls = $stmt_imagenes->fetchAll(PDO::FETCH_ASSOC);

    // 4.3. Decodificar campos JSON internos y ensamblar la respuesta
    
    $animal['taxonomia'] = json_decode($animal['taxonomia'] ?? 'null', true);
    $animal['distribucion'] = json_decode($animal['distribucion'] ?? 'null', true);
    $animal['descripcion'] = json_decode($animal['descripcion'] ?? 'null', true);
    $animal['imagenes_datos_adicionales'] = json_decode($animal['imagenes'] ?? '[]', true); 
    
    $animal['urls_archivos_media'] = $media_urls; 

    http_response_code(200);
    echo json_encode([
        "mensaje" => "Datos del animal recuperados exitosamente.",
        "animal" => $animal
    ], JSON_PRETTY_PRINT); 
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["mensaje" => "Error interno del servidor: " . $e->getMessage()]);
}

?>