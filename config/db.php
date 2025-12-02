<?php
// config/db.php

// 1. Configuración de Credenciales
// Si estás en XAMPP local, el usuario suele ser 'root' y la contraseña vacía.
$host = 'localhost';
$db_name = 'animalrecognizer';
$username = 'root'; 
$password = ''; 

// 2. Configuración de Zona Horaria (Costa Rica)
date_default_timezone_set('America/Costa_Rica');

try {
    // 3. Crear la cadena de conexión (DSN)
    $dsn = "mysql:host=$host;dbname=$db_name;charset=utf8mb4";
    
    // 4. Opciones para mejorar la seguridad y el manejo de errores
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Si falla, lanza error visible
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,      // Devuelve datos como array asociativo
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Usa sentencias preparadas reales (Seguridad)
    ];

    // 5. Instanciar la conexión PDO
    $pdo = new PDO($dsn, $username, $password, $options);

    // 6. Forzar la zona horaria en MySQL también (Para que NOW() sea hora CR)
    $pdo->exec("SET time_zone = '-06:00';");
    $pdo->exec("SET NAMES 'utf8mb4';");

    // Descomenta la línea de abajo solo si quieres probar que conecta:
    // echo "¡Conexión exitosa a la base de datos! Hora actual: " . date('Y-m-d H:i:s');

} catch (PDOException $e) {
    // Si falla, mostramos un mensaje de error y detenemos todo
    // En producción, no deberías mostrar $e->getMessage() al público por seguridad
    die("Error de conexión a la Base de Datos: " . $e->getMessage());
}
?>