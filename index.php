<?php
// RUTA: api_avistamientos/index.php (FINAL - SÓLO ESTÁTICO)

// Configuraciones mínimas necesarias para la respuesta del servidor.
header("Content-Type: text/html; charset=UTF-8");
date_default_timezone_set('America/Costa_Rica');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>API de Avistamientos - Raíz</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; background-color: #f4f4f9; }
        .container { max-width: 800px; margin: auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        code { background: #ecf0f1; padding: 2px 4px; border-radius: 4px; color: #c0392b; }
        ul { list-style-type: none; padding: 0; }
        .status-line { font-weight: bold; color: #27ae60; }
        .link-box { margin-top: 25px; padding: 15px; background: #ecf9ff; border-left: 4px solid #3498db; border-radius: 5px; }
        .link-box a { color: #2980b9; font-weight: bold; text-decoration: none; }
        .link-box a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🐾 API de Avistamientos de Fauna (Costa Rica)</h1>
        
        <p class="status-line">
            ✅ <strong>Estado:</strong> La plataforma está en línea y funcionando correctamente.
        </p>

        <p>⏰ <strong>Zona Horaria:</strong> América/Costa_Rica (UTC-6)</p>
        
        <hr>

        <h2>Funcionalidades de la Plataforma:</h2>
        <ul>
            <li>👤 <strong>Autenticación y Sesiones:</strong> Manejo de Tokens de Acceso y registro de usuarios.</li>
            <li>💾 <strong>Datos Offline:</strong> Generación de archivos provinciales estáticos (solo metadatos) para la app móvil.</li>
            <li>🔗 <strong>Interacción Social:</strong> Recepción de avistamientos geolocalizados.</li>
            <li>📷 <strong>Gestión de Archivos:</strong> Almacenamiento y vinculación de imágenes.</li>
        </ul>

        <div class="link-box">
            🔧 <strong>Herramienta recomendada:</strong><br>
            <a href="diagnostico.php" target="_blank">➡️ Ejecutar Diagnóstico del Sistema</a>
        </div>
        
        <p style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 10px;">
            Para consumir los servicios, consulte la documentación oficial de la API.
        </p>
    </div>
</body>
</html>
