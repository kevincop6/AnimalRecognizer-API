<?php
// RUTA: api/animales/recomendar.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

// Rutas a recursos centrales
require_once '../../config/db.php';     
require_once '../../includes/Auth.php'; 

// Roles permitidos (todos los autenticados)
$ROLES_PERMITIDOS = ['admin', 'moderador', 'estandar']; 

// Solo se permite el método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["mensaje" => "Método no permitido."]);
    exit;
}

// ----------------------------------------------------
// 1. OBTENER TOKEN E ID DE EXCLUSIÓN
// ----------------------------------------------------

// Leer datos del cuerpo POST (o JSON body)
$data = json_decode(file_get_contents("php://input"), true);

// Priorizamos la lectura desde JSON body, pero verificamos también $_POST
$id_excluido = $data['id_excluido'] ?? ($_POST['id_excluido'] ?? null);
$token_body = $data['token'] ?? ($_POST['token'] ?? null);

// ----------------------------------------------------
// 2. AUTENTICACIÓN
// ----------------------------------------------------
$auth = new Auth($pdo);
$token_header = $auth->obtenerTokenDeCabecera();
$token_final = $token_header ?: $token_body;

if (empty($token_final)) {
    http_response_code(401);
    echo json_encode(["mensaje" => "Acceso denegado. Token de autorización no proporcionado."]);
    exit;
}

$usuarioData = $auth->validarToken($token_final);

if (!$usuarioData || !in_array($usuarioData['rol'], $ROLES_PERMITIDOS)) {
    http_response_code(401);
    echo json_encode(["mensaje" => "Acceso denegado. Token inválido o expirado."]);
    exit;
}

// ----------------------------------------------------
// 3. VALIDACIÓN DE ENTRADA Y LÍMITE
// ----------------------------------------------------

if (empty($id_excluido) || !is_numeric($id_excluido)) {
    http_response_code(400);
    echo json_encode(["mensaje" => "Se requiere el ID del animal a excluir (id_excluido) y debe ser numérico."]);
    exit;
}

$LIMITE = 5; // Límite de 5 recomendaciones

// ----------------------------------------------------
// 4. LÓGICA DE RECOMENDACIÓN (Selección Aleatoria)
// ----------------------------------------------------
try {
    $sql = "
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
            RAND() -- 🚩 ¡CLÁUSULA DE ORDEN ALEATORIO GARANTIZADA!
        LIMIT 
            :limite";
            
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id_excluido', (int)$id_excluido, PDO::PARAM_INT);
    $stmt->bindValue(':limite', $LIMITE, PDO::PARAM_INT);
    
    $stmt->execute();
    $recomendaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Si no se obtuvieron resultados, devuelve un mensaje.
    if (count($recomendaciones) === 0) {
        http_response_code(200);
        echo json_encode(["mensaje" => "No hay otros animales para recomendar."]);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        "mensaje" => "Recomendaciones generadas exitosamente.",
        "recomendaciones" => $recomendaciones
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["mensaje" => "Error interno del servidor: " . $e->getMessage()]);
}

?>