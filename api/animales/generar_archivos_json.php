<?php
// RUTA: api/animales/generar_archivos_json.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido. Use GET."]);
    exit;
}

require_once '../../config/db.php';
require_once '../../includes/AnimalProcessor.php'; 

try {
    $processor = new AnimalProcessor($pdo);
    $resultados = $processor->generateAndSaveProvinceJSONs();

    http_response_code(200);
    echo json_encode([
        "estado" => "Completado",
        "mensaje" => "Los archivos JSON provinciales han sido generados y guardados en public/json/",
        "detalles" => $resultados
    ], JSON_UNESCAPED_UNICODE); // Devolvemos la respuesta del endpoint sin escapar

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error de base de datos: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error en el procesamiento: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>