<?php
// RUTA: api/animales/count_animal.php

// Configuración de cabeceras
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");

// 1. Seguridad: Solo permitir GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido. Se requiere GET."]);
    exit;
}

// Inclusión de archivos necesarios (ajusta las rutas si es necesario)
require_once '../../config/db.php';
require_once '../../includes/AnimalProcessor.php';
// Nota: Puedes necesitar incluir la clase Auth si requieres autenticación aquí

try {
    // 2. Autenticación (Opcional, pero recomendado para APIs):
    // if (!$auth->validarToken(...)) { ... } 
    // Por ahora, solo se ejecuta la lógica.

    // 3. Instanciar la clase y obtener los conteos
    $processor = new AnimalProcessor($pdo);
    $conteos = $processor->contarAnimalesPorProvincia();

    // 4. Respuesta exitosa
    http_response_code(200);
    echo json_encode([
        "mensaje" => "Conteo de animales por provincia completado. Los animales 'Nacional' se incluyen en cada provincia.",
        "conteos" => $conteos
    ]);

} catch (Exception $e) {
    // 5. Manejo de errores
    http_response_code(500);
    echo json_encode(["error" => "Error del servidor al procesar el conteo: " . $e->getMessage()]);
}
?>