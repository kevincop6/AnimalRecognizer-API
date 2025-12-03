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
    </style>
</head>
<body>
    <div class="container">
        <h1>🐾 API de Avistamientos de Fauna (Costa Rica)</h1>
        
        <p class="status-line">
            ✅ **Estado:** La plataforma está en línea y funcionando correctamente.
        </p>

        <p>⏰ **Zona Horaria:** América/Costa_Rica (UTC-6)</p>
        
        <hr>

        <h2>Funcionalidades de la Plataforma:</h2>
        <ul>
            <li>👤 **Autenticación y Sesiones:** Manejo de Tokens de Acceso y registro de usuarios.</li>
            <li>💾 **Datos Offline:** Generación de archivos provinciales estáticos (solo metadatos) para descarga de la aplicación móvil.</li>
            <li>🔗 **Interacción Social:** Recepción de avistamientos geolocalizados.</li>
            <li>📷 **Gestión de Archivos:** Almacenamiento y vinculación de imágenes.</li>
        </ul>
        
        <p style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 10px;">
            Para consumir los servicios, consulte la documentación oficial de la API.
        </p>
    </div>
</body>
</html>