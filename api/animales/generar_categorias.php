<?php
// RUTA: api/animales/generar_clases.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../../config/db.php';
require_once '../../includes/Auth.php';

$ROLES_REQUERIDOS = ['admin'];
$DS = DIRECTORY_SEPARATOR;

// --------------------------------------------------
// 0. CORS / MÉTODO HTTP
// --------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(
        ["status" => false, "mensaje" => "Método no permitido."],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

// --------------------------------------------------
// 1. DATOS SOLO DESDE FORM-DATA
// --------------------------------------------------
$token = $_POST['token'] ?? null;
$pin   = $_POST['pin']   ?? null;

if (empty($token) || empty($pin)) {
    http_response_code(400);
    echo json_encode(
        ["status" => false, "mensaje" => "Token y PIN son obligatorios."],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

try {

    // --------------------------------------------------
    // 2. AUTENTICACIÓN Y ROL
    // --------------------------------------------------
    $auth = new Auth($pdo);
    $usuarioData = $auth->validarToken($token);

    if (
        !$usuarioData ||
        !in_array($usuarioData['rol'], $ROLES_REQUERIDOS, true)
    ) {
        http_response_code(403);
        echo json_encode(
            ["status" => false, "mensaje" => "Acceso denegado."],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        exit;
    }

    // --------------------------------------------------
    // 3. OBTENER CLASES REALES DESDE animales
    // --------------------------------------------------
    $clasesRaw = $pdo->query("
        SELECT DISTINCT
            JSON_UNQUOTE(JSON_EXTRACT(taxonomia, '$.clase')) AS clase
        FROM animales
        WHERE taxonomia IS NOT NULL
          AND JSON_EXTRACT(taxonomia, '$.clase') IS NOT NULL
    ")->fetchAll(PDO::FETCH_ASSOC);

    // --------------------------------------------------
    // 4. CONSULTA QUE GARANTIZA IMAGEN POR CLASE
    //    (desde CUALQUIER animal de la clase)
    // --------------------------------------------------
    $stmtImagenClase = $pdo->prepare("
        SELECT m.url_archivo
        FROM animales a
        JOIN media_archivos m
          ON m.tipo_entidad = 'animal'
         AND m.entidad_id = a.id
        WHERE JSON_UNQUOTE(JSON_EXTRACT(a.taxonomia, '$.clase')) = :clase
        LIMIT 1
    ");

    $clases = [];

    foreach ($clasesRaw as $row) {

        $clase = trim((string)$row['clase']);
        if ($clase === '') {
            continue;
        }

        $stmtImagenClase->execute([':clase' => $clase]);
        $img = $stmtImagenClase->fetch(PDO::FETCH_ASSOC);

        if (!$img || empty($img['url_archivo'])) {
            // Esto NO debería pasar según tu modelo
            throw new Exception("No se encontró imagen para la clase: $clase");
        }

        $clases[] = [
            "nombre" => $clase,
            "imagen" => $img['url_archivo']
        ];
    }

    // --------------------------------------------------
    // 5. GUARDAR JSON (SIN ESCAPAR SLASHES)
    // --------------------------------------------------
    $ruta_base = dirname(dirname(__DIR__));
    $ruta_destino = $ruta_base . $DS . 'public' . $DS . 'categorias';

    if (!is_dir($ruta_destino)) {
        mkdir($ruta_destino, 0755, true);
    }

    file_put_contents(
        $ruta_destino . $DS . 'clases.json',
        json_encode(
            ["clases" => $clases],
            JSON_PRETTY_PRINT
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        )
    );

    // --------------------------------------------------
    // 6. RESPUESTA FINAL
    // --------------------------------------------------
    echo json_encode(
        [
            "status"       => true,
            "mensaje"      => "Clases generadas correctamente con imagen obligatoria por clase.",
            "total_clases" => count($clases)
        ],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;

} catch (Exception $e) {

    http_response_code(500);
    echo json_encode(
        [
            "status"  => false,
            "mensaje" => "Error interno",
            "detalle" => $e->getMessage()
        ],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}
