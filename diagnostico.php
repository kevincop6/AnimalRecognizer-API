<?php
// RUTA: diagnostico.php (EN EL DIRECTORIO RAÍZ DEL PROYECTO)

header("Content-Type: text/html; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// 🚩 RUTA CORREGIDA: Acceder a db.php directamente desde la raíz
require_once './config/db.php'; 

// ----------------------------------------------------
// 1. CARGAR INSTRUCCIONES DE SOPORTE DESDE JSON
// ----------------------------------------------------
$ruta_base = __DIR__; // 🚩 Nueva ruta base: El directorio actual (raíz del proyecto)
$ruta_instrucciones = $ruta_base . '/diagnostico_instrucciones.json'; // 🚩 Ruta simple al JSON

try {
    $instrucciones_json = file_get_contents($ruta_instrucciones);
    if ($instrucciones_json === FALSE) {
        throw new Exception("Error: Archivo de instrucciones JSON no encontrado. Ubicación: " . $ruta_instrucciones);
    }
    $instrucciones = json_decode($instrucciones_json, true);
    if ($instrucciones === null) {
         throw new Exception("Error: El archivo de instrucciones JSON está corrupto o mal formateado.");
    }
} catch (Exception $e) {
    http_response_code(500);
    die("<h1>ERROR CRÍTICO: No se puede cargar el archivo de diagnóstico.</h1><p>{$e->getMessage()}</p>");
}


// ----------------------------------------------------
// 2. EJECUTAR PRUEBAS
// ----------------------------------------------------

$diagnostico = [
    "estado_general" => "OK",
    "pruebas_criticas" => [],
    "fallos_detectados" => 0
];

// 2.1. PRUEBA DE EXTENSIONES PHP
$extensiones = ['openssl', 'pdo_mysql', 'json'];

foreach ($extensiones as $ext) {
    $estado = extension_loaded($ext);
    $meta = $instrucciones['extensiones'][$ext];
    
    if (!$estado) {
        $diagnostico["fallos_detectados"]++;
    }
    $diagnostico["pruebas_criticas"][] = [
        "componente" => "PHP: Extensión " . $ext,
        "estado" => $estado ? "OK" : "FALLO",
        "descripcion" => $meta['descripcion'],
        "solucion" => $estado ? "N/A" : $meta['solucion']
    ];
}


// 2.2. PRUEBA DE CONEXIÓN A LA BASE DE DATOS
$db_error = false;
try {
    $pdo->query("SELECT 1"); 
    $db_status = "OK";
    $db_solucion = "N/A";
} catch (PDOException $e) {
    $diagnostico["fallos_detectados"]++;
    $db_status = "FALLO";
    $db_solucion = $instrucciones['database']['solucion'] . " (Mensaje del Servidor: " . htmlspecialchars($e->getMessage()) . ")";
    $db_error = true;
}

$diagnostico["pruebas_criticas"][] = [
    "componente" => "Base de Datos",
    "estado" => $db_status,
    "descripcion" => $instrucciones['database']['descripcion'],
    "solucion" => $db_solucion
];


// 2.3. PRUEBA DE DISPONIBILIDAD DE DIRECTORIOS PROTEGIDOS (LECTURA/ESCRITURA)
$directorios_a_probar = ['/public/json/', '/public/llaves/'];

foreach ($directorios_a_probar as $dir_relativo) {
    // 🚩 Uso de la ruta base __DIR__ que ahora apunta a la raíz
    $ruta_absoluta = $ruta_base . $dir_relativo;
    $estado_disponibilidad = is_dir($ruta_absoluta);
    $estado_permisos = is_writable($ruta_absoluta);
    
    $fallo = (!$estado_disponibilidad || !$estado_permisos);
    if ($fallo) {
        $diagnostico["fallos_detectados"]++;
    }
    
    $mensaje_solucion = $instrucciones['directory']['solucion_permisos'];
    
    $diagnostico["pruebas_criticas"][] = [
        "componente" => "Directorio: " . $dir_relativo,
        "estado" => (!$fallo) ? "OK" : "FALLO",
        "descripcion" => $instrucciones['directory']['descripcion'],
        "solucion" => (!$fallo) ? "N/A" : $mensaje_solucion
    ];
}

// ----------------------------------------------------
// 3. GENERAR VISTA HTML
// ----------------------------------------------------

if ($diagnostico["fallos_detectados"] > 0) {
    $diagnostico["estado_general"] = "FALLA CRÍTICA (" . $diagnostico["fallos_detectados"] . " fallos)";
    $estado_color = "red";
    http_response_code(500); 
} else {
    $estado_color = "green";
    http_response_code(200); 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Diagnóstico de API</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { border-bottom: 2px solid #ccc; padding-bottom: 10px; color: #333; }
        .status { padding: 10px; border-radius: 5px; font-weight: bold; margin-bottom: 20px; text-align: center; color: white; }
        .status.green { background-color: #28a745; }
        .status.red { background-color: #dc3545; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; vertical-align: top; }
        th { background-color: #f2f2f2; }
        .ok { color: green; font-weight: bold; }
        .fail { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏥 Panel de Diagnóstico Crítico de la API</h1>
        
        <div class="status <?php echo $estado_color; ?>">
            ESTADO GENERAL DEL SISTEMA: <?php echo $diagnostico["estado_general"]; ?>
        </div>

        <h2>Detalles de las Pruebas Críticas</h2>
        <table>
            <thead>
                <tr>
                    <th>Componente</th>
                    <th>Estado</th>
                    <th>Descripción</th>
                    <th>Instrucciones de Solución</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($diagnostico["pruebas_criticas"] as $prueba): ?>
                <tr>
                    <td><?php echo $prueba["componente"]; ?></td>
                    <td class="<?php echo ($prueba["estado"] == "OK" ? 'ok' : 'fail'); ?>">
                        <?php echo $prueba["estado"]; ?>
                    </td>
                    <td><?php echo $prueba["descripcion"]; ?></td>
                    <td><?php echo $prueba["solucion"]; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
<?php exit; ?>