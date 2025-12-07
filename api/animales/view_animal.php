<?php
// RUTA: api/animales/view_animal.php

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
// 1. OBTENER TOKEN E ID (Lectura desde $_POST y JSON body)
// ----------------------------------------------------

// Leer el cuerpo JSON crudo
$data_raw = file_get_contents("php://input");
$data_json = json_decode($data_raw, true);

// Lectura de Token e ID
$animal_id = $_POST['id'] ?? ($data_json['id'] ?? null);
$token_final = $_POST['token'] ?? ($data_json['token'] ?? null);

// ----------------------------------------------------
// 2. AUTENTICACIÓN
// ----------------------------------------------------
$auth = new Auth($pdo);

if (empty($token_final)) {
    http_response_code(401);
    echo json_encode(["mensaje" => "Acceso denegado. Token de autorización no proporcionado."]);
    exit;
}

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
// 4. OBTENER DATOS Y ENSAMBLAR JSON
// ----------------------------------------------------
try {
    // 4.1. OBTENER DETALLES DEL ANIMAL (Consulta principal)
    $sql_animal = "SELECT * FROM animales WHERE id = :id";
    $stmt_animal = $pdo->prepare($sql_animal);
    $stmt_animal->bindParam(':id', $animal_id, PDO::PARAM_INT);
    $stmt_animal->execute();
    $animal = $stmt_animal->fetch(PDO::FETCH_ASSOC);

    if (!$animal) {
        http_response_code(404);
        echo json_encode(["mensaje" => "Animal no encontrado con el ID proporcionado."]);
        exit;
    }

    // 4.2. OBTENER URLs de las imágenes
    $sql_imagenes = "SELECT url_archivo, es_principal, tipo_almacenamiento FROM media_archivos WHERE tipo_entidad = 'animal' AND entidad_id = :id AND estado = 'visible' ORDER BY es_principal DESC, fecha_subida DESC";
    $stmt_imagenes = $pdo->prepare($sql_imagenes);
    $stmt_imagenes->bindParam(':id', $animal_id, PDO::PARAM_INT);
    $stmt_imagenes->execute();
    $media_urls = $stmt_imagenes->fetchAll(PDO::FETCH_ASSOC);

    // 4.3. OBTENER 5 RECOMENDACIONES (Lógica de bajo consumo de servidor)
    $LIMITE_RECOMENDACIONES = 5;
    $sql_recomendar = "
        SELECT 
            a.id, 
            a.nombre_cientifico AS nombre, 
            m.url_archivo AS imagen_principal
        FROM 
            animales a
        LEFT JOIN 
            media_archivos m ON a.id = m.entidad_id 
                            AND m.tipo_entidad = 'animal' 
                            AND m.es_principal = 1
        WHERE 
            a.id != :id_excluido 
        ORDER BY 
            RAND()
        LIMIT 
            :limite";
            
    $stmt_recomendar = $pdo->prepare($sql_recomendar);
    $stmt_recomendar->bindValue(':id_excluido', $animal_id, PDO::PARAM_INT);
    $stmt_recomendar->bindValue(':limite', $LIMITE_RECOMENDACIONES, PDO::PARAM_INT);
    $stmt_recomendar->execute();
    $recomendaciones = $stmt_recomendar->fetchAll(PDO::FETCH_ASSOC);


    // 4.4. Decodificar y Ensamblar JSON Final
    $animal['taxonomia'] = json_decode($animal['taxonomia'] ?? 'null', true);
    $animal['distribucion'] = json_decode($animal['distribucion'] ?? 'null', true);
    $animal['descripcion'] = json_decode($animal['descripcion'] ?? 'null', true);
    $animal['imagenes_datos_adicionales'] = json_decode($animal['imagenes'] ?? '[]', true); 
    $animal['urls_archivos_media'] = $media_urls; 

    http_response_code(200);
    echo json_encode([
        "mensaje" => "Datos del animal y recomendaciones recuperados exitosamente.",
        "animal" => $animal,
        "recomendaciones" => $recomendaciones // 🚩 Nuevo campo con las 5 recomendaciones
    ], JSON_PRETTY_PRINT); 
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["mensaje" => "Error interno del servidor: " . $e->getMessage()]);
}

?>