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
$ruta_base = __DIR__; // directorio raíz del proyecto
$ruta_instrucciones = $ruta_base . '/diagnostico_instrucciones.json';

try {
    $instrucciones_json = file_get_contents($ruta_instrucciones);
    if ($instrucciones_json === false) {
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
    "estado_general"   => "OK",
    "pruebas_criticas" => [],
    "fallos_detectados"=> 0
];

// 2.1. PRUEBA DE EXTENSIONES PHP
$extensiones = ['openssl', 'pdo_mysql', 'json'];

foreach ($extensiones as $ext) {
    $estado = extension_loaded($ext);
    $meta   = $instrucciones['extensiones'][$ext] ?? [
        'descripcion' => 'Sin descripción en JSON',
        'solucion'    => 'Sin solución definida en JSON'
    ];

    if (!$estado) {
        $diagnostico["fallos_detectados"]++;
        $solucion = $meta['solucion'];
    } else {
        // Muestra también la solución como referencia, pero indicando que no se requiere acción
        $solucion = "No se requiere acción. En caso de fallo futuro: " . $meta['solucion'];
    }

    $diagnostico["pruebas_criticas"][] = [
        "componente"  => "PHP: Extensión " . $ext,
        "estado"      => $estado ? "OK" : "FALLO",
        "descripcion" => $meta['descripcion'],
        "solucion"    => $solucion
    ];
}

// 2.2. PRUEBA DE CONEXIÓN A LA BASE DE DATOS
$db_error = false;
try {
    $pdo->query("SELECT 1");
    $db_status   = "OK";
    $db_solucion = "No se requiere acción. En caso de fallo futuro: " . ($instrucciones['database']['solucion'] ?? '');
} catch (PDOException $e) {
    $diagnostico["fallos_detectados"]++;
    $db_status   = "FALLO";
    $db_error    = true;
    $base_sol    = $instrucciones['database']['solucion'] ?? 'Verificar configuración de la base de datos.';
    $db_solucion = $base_sol . " (Mensaje del Servidor: " . htmlspecialchars($e->getMessage()) . ")";
}

$diagnostico["pruebas_criticas"][] = [
    "componente"  => "Base de Datos",
    "estado"      => $db_status,
    "descripcion" => $instrucciones['database']['descripcion'] ?? 'Verificación de la conexión a la base de datos.',
    "solucion"    => $db_solucion
];

// 2.3. PRUEBA DE DIRECTORIOS (EXISTENCIA / PERMISOS)
$directorios_a_probar = ['/public/json/', '/public/llaves/'];

foreach ($directorios_a_probar as $dir_relativo) {
    $ruta_absoluta       = $ruta_base . $dir_relativo;
    $estado_disponible   = is_dir($ruta_absoluta);
    $estado_permisos     = $estado_disponible ? is_writable($ruta_absoluta) : false;
    $fallo               = (!$estado_disponible || !$estado_permisos);

    if ($fallo) {
        $diagnostico["fallos_detectados"]++;
        $solucion = $instrucciones['directory']['solucion_permisos'] ?? 'Verificar existencia y permisos del directorio.';
    } else {
        $solucion = "No se requiere acción. En caso de fallo futuro: " .
                    ($instrucciones['directory']['solucion_permisos'] ?? '');
    }

    $diagnostico["pruebas_criticas"][] = [
        "componente"  => "Directorio: " . $dir_relativo,
        "estado"      => $fallo ? "FALLO" : "OK",
        "descripcion" => $instrucciones['directory']['descripcion'] ?? 'Verificación de directorios críticos.',
        "solucion"    => $solucion
    ];
}

// 2.4. PRUEBA DE .htaccess EN CARPETAS SENSIBLES (SEGURIDAD)
if (isset($instrucciones['seguridad']['htaccess'])) {
    $ht_meta  = $instrucciones['seguridad']['htaccess'];
    $carpetas = $ht_meta['carpetas'] ?? [];

    foreach ($carpetas as $carp_rel) {
        $ruta_carpeta  = $ruta_base . '/' . trim($carp_rel, '/');
        $ruta_htaccess = rtrim($ruta_carpeta, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.htaccess';

        $existe_carpeta  = is_dir($ruta_carpeta);
        $existe_htaccess = $existe_carpeta && is_file($ruta_htaccess);

        $contenido_ok = false;

        if ($existe_htaccess) {
            $contenido_actual = file_get_contents($ruta_htaccess);

            if ($contenido_actual !== false) {
                // Normalizamos: minúsculas y colapsar espacios/saltos de línea
                $norm = strtolower(preg_replace('/\s+/', ' ', $contenido_actual));

                $tiene_order = strpos($norm, 'order deny,allow') !== false;
                $tiene_deny  = strpos($norm, 'deny from all')   !== false;

                if ($tiene_order && $tiene_deny) {
                    $contenido_ok = true;
                }
            }
        }

        $fallo = (!$existe_carpeta || !$existe_htaccess || !$contenido_ok);

        if ($fallo) {
            $diagnostico["fallos_detectados"]++;
            $solucion = "Crear/verificar el archivo <code>.htaccess</code> en <strong>'" . htmlspecialchars($carp_rel) .
                        "'</strong> y asegurarse de que contenga <strong>al menos</strong> estas dos líneas:<br><pre># Bloquea acceso directo a los archivos.
Order Deny,Allow
Deny from all</pre>";
        } else {
            $solucion = "No se requiere acción. El archivo <code>.htaccess</code> en <strong>'" . htmlspecialchars($carp_rel) .
                        "'</strong> ya contiene las directivas mínimas:<br><pre>Order Deny,Allow
Deny from all</pre>";
        }

        $diagnostico["pruebas_criticas"][] = [
            "componente"  => "Seguridad .htaccess en: " . $carp_rel,
            "estado"      => $fallo ? "FALLO" : "OK",
            "descripcion" => $ht_meta['descripcion'] ?? 'Validación de .htaccess en carpetas sensibles.',
            "solucion"    => $solucion
        ];
    }
}


// ----------------------------------------------------
// 3. ESTADO GENERAL + RESPUESTA
// ----------------------------------------------------
if ($diagnostico["fallos_detectados"] > 0) {
    $diagnostico["estado_general"] = "FALLA CRÍTICA (" . $diagnostico["fallos_detectados"] . " fallos)";
    $estado_color = "red";
    http_response_code(500);
} else {
    $diagnostico["estado_general"] = "OK (Todos los componentes críticos están correctos)";
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
        pre { margin: 0; font-family: Consolas, monospace; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏥 Panel de Diagnóstico Crítico de la API</h1>
        
        <div class="status <?php echo $estado_color; ?>">
            ESTADO GENERAL DEL SISTEMA: <?php echo htmlspecialchars($diagnostico["estado_general"]); ?>
        </div>

        <h2>Detalles de las Pruebas Críticas</h2>
        <table>
            <thead>
                <tr>
                    <th>Componente</th>
                    <th>Estado</th>
                    <th>Descripción</th>
                    <th>Instrucciones / Solución</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($diagnostico["pruebas_criticas"] as $prueba): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($prueba["componente"]); ?></td>
                        <td class="<?php echo ($prueba["estado"] === "OK" ? 'ok' : 'fail'); ?>">
                            <?php echo htmlspecialchars($prueba["estado"]); ?>
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
