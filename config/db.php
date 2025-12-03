<?php
// RUTA: config/db.php (GUARDIA DE SEGURIDAD CON BYPASS CONDICIONAL)

// Variables de Conexión
$host = 'localhost';
$db_name = 'animalrecognizer'; 
$username = 'root'; 
$password = ''; 

// Configuraciones globales
date_default_timezone_set('America/Costa_Rica');

// Variable global para que index.php pueda leer si hay un error
global $db_error_message;

try {
    $dsn = "mysql:host=$host;dbname=$db_name;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,      
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $username, $password, $options);

    $pdo->exec("SET time_zone = '-06:00';");
    $pdo->exec("SET NAMES 'utf8mb4';");

} catch (PDOException $e) {
    
    // 💥 LÓGICA DE BYPASS 💥
    // Si $db_bypass NO está definida, se asume que es un endpoint API que debe morir.
    if (!isset($db_bypass)) {
        // Modo Endpoint API: Respuesta JSON y terminación.
        http_response_code(500); 
        header('Content-Type: application/json'); 
        error_log("FATAL DB ERROR: " . $e->getMessage()); 

        echo json_encode([
            "status" => "error",
            "mensaje" => "Error crítico del servidor: Problemas de conexión a la base de datos."
        ]);
        exit; 
    } else {
        // Modo Health Check: Establecer el error para que index.php lo muestre en HTML
        $db_error_message = "DB_ERROR"; 
    }
}
?>