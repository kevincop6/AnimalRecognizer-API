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

// ---------------------------------------------
// 0. CORS / MÉTODO HTTP
// ---------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "status"  => false,
        "mensaje" => "Método no permitido."
    ]);
    exit;
}

// ---------------------------------------------
// 1. TOKEN + PIN (SOLO POST / BODY)
// ---------------------------------------------
$auth = new Auth($pdo);

$token_post = $_POST['token'] ?? null;
$pin_post   = $_POST['pin']   ?? null;

$raw_body  = file_get_contents("php://input");
$json_body = json_decode($raw_body, true);

$token_json = $json_body['token'] ?? null;
$pin_json   = $json_body['pin']   ?? null;

$token_final = $token_post ?: $token_json;
$pin_final   = $pin_post   ?: $pin_json;

if (empty($token_final)) {
    http_response_code(401);
    echo json_encode([
        "status"  => false,
        "mensaje" => "Acceso denegado. Token no proporcionado."
    ]);
    exit;
}

if (empty($pin_final)) {
    http_response_code(400);
    echo json_encode([
        "status"  => false,
        "mensaje" => "Se requiere el PIN para descifrar la llave."
    ]);
    exit;
}

// ---------------------------------------------
// 2. AUTENTICAR USUARIO Y ROL
// ---------------------------------------------
try {

    $usuarioData = $auth->validarToken($token_final);

    if (!$usuarioData || !in_array($usuarioData['rol'], $ROLES_REQUERIDOS, true)) {
        http_response_code(403);
        echo json_encode([
            "status"  => false,
            "mensaje" => "Acceso denegado. Se requiere rol Administrador."
        ]);
        exit;
    }

    $usuario_id = (int)$usuarioData['usuario_id'];

    // ---------------------------------------------
    // 2.1 LLAVE CRIPTOGRÁFICA
    // ---------------------------------------------
    $sql_llave = "
        SELECT identificador_llave
        FROM llaves_criptograficas
        WHERE usuario_id = :uid
          AND revocada = 0
        ORDER BY fecha_creacion DESC
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql_llave);
    $stmt->execute([':uid' => $usuario_id]);
    $llave = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$llave) {
        throw new Exception("No existe una llave criptográfica activa.");
    }

    if (!extension_loaded('openssl')) {
        throw new Exception("OpenSSL no disponible.");
    }

    while (openssl_error_string()) {}

    $ruta_base    = dirname(dirname(__DIR__));
    $ruta_llaves  = $ruta_base . $DS . 'public' . $DS . 'llaves' . $DS;
    $ruta_privada = $ruta_llaves . $llave['identificador_llave'] . "_privada.pem";

    if (!file_exists($ruta_privada)) {
        throw new Exception("Llave privada no encontrada.");
    }

    $rsa = openssl_pkey_get_private(
        file_get_contents($ruta_privada),
        $pin_final
    );

    if (!$rsa) {
        throw new Exception("PIN incorrecto o llave inválida.");
    }

    // ---------------------------------------------
    // 3. GENERAR CLASES CON EMOJI COHERENTE
    // ---------------------------------------------
    $sql = "
        SELECT taxonomia
        FROM animales
        WHERE taxonomia IS NOT NULL
          AND taxonomia <> ''
    ";

    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    $clasesSet = [];

    foreach ($rows as $row) {
        $tax = json_decode($row['taxonomia'], true);
        if (!is_array($tax) || empty($tax['clase'])) continue;

        $texto = trim($tax['clase']);
        if ($texto === '') continue;

        $clasesSet[$texto] = true;
    }

    // ---------------------------------------------
    // FUNCIÓN DE EMOJI SEGÚN NOMBRE
    // ---------------------------------------------
    function emojiPorClase(string $clase): string
    {
        $c = mb_strtolower($clase, 'UTF-8');

        if (str_contains($c, 'mammal') || str_contains($c, 'mamí')) return '🐆';
        if (str_contains($c, 'bird')   || str_contains($c, 'ave'))  return '🐦';
        if (str_contains($c, 'reptil')) return '🐍';
        if (str_contains($c, 'amphib') || str_contains($c, 'anfib'))return '🐸';
        if (str_contains($c, 'fish')   || str_contains($c, 'pez'))  return '🐟';
        if (str_contains($c, 'insect'))return '🦋';
        if (str_contains($c, 'arach')) return '🕷️';
        if (str_contains($c, 'crusta'))return '🦀';
        if (str_contains($c, 'mollus'))return '🐌';

        // fallback genérico
        return '🐾';
    }

    $clasesBase = array_keys($clasesSet);
    sort($clasesBase, SORT_STRING);

    $clases = [];
    foreach ($clasesBase as $texto) {
        $clases[] = emojiPorClase($texto) . ' ' . $texto;
    }

    // ---------------------------------------------
    // 4. GUARDAR JSON
    // ---------------------------------------------
    $ruta_destino = $ruta_base . $DS . 'public' . $DS . 'categorias';
    if (!is_dir($ruta_destino)) {
        mkdir($ruta_destino, 0755, true);
    }

    file_put_contents(
        $ruta_destino . $DS . 'clases.json',
        json_encode(["clases" => $clases], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    openssl_pkey_free($rsa);

    // ---------------------------------------------
    // 5. RESPUESTA
    // ---------------------------------------------
    echo json_encode([
        "status"       => true,
        "mensaje"      => "Clases generadas con emojis coherentes.",
        "total_clases" => count($clases)
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $e) {

    http_response_code(500);
    echo json_encode([
        "status"  => false,
        "mensaje" => "Error de tarea privilegiada",
        "detalle" => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
