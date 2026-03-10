<?php
// RUTA: web-admin/ajax/login_proxy.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$db_bypass = true;
require_once '../../config/db.php';
require_once '../includes/AdminAuth.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new Exception("Método no permitido.");
    }

    $usuario_correo = trim($_POST['usuario_correo'] ?? '');
    $password       = $_POST['password'] ?? '';
    $persistirFlag  = $_POST['persistir'] ?? '0';
    $persistir      = ($persistirFlag === '1');

    if (empty($usuario_correo) || empty($password)) {
        http_response_code(400);
        throw new Exception("Usuario/Correo y Contraseña son requeridos.");
    }

    $auth        = new AdminAuth($pdo);
    $loginResult = $auth->login($usuario_correo, $password, $persistir);

    // Cookie que lee dashboard.php
    $duracionSegundos = $persistir ? (60 * 60 * 24 * 30) : (60 * 60 * 2);
    setcookie(
        'admin_token',
        $loginResult['token'],
        time() + $duracionSegundos,
        "/",
        "",
        false,
        true
    );

    http_response_code(200);
    echo json_encode([
        "success" => true,
        "mensaje" => "Inicio de sesión exitoso",
        "token"   => $loginResult['token'],
        "usuario" => [
            "id"      => $loginResult['id'],
            "nombre"  => $loginResult['nombre'],
            "usuario" => $loginResult['nombre_usuario'],
            "rol"     => $loginResult['rol']
        ]
    ]);
    exit;

} catch (Exception $e) {
    $msg = $e->getMessage();
    $isAuthError = (
        strpos($msg, 'Credenciales incorrectas') !== false ||
        strpos($msg, 'No tienes los permisos necesarios') !== false ||
        strpos(strtolower($msg), 'desactivada') !== false
    );
    http_response_code($isAuthError ? 401 : 500);
    echo json_encode(["success" => false, "message" => $msg]);
    exit;
}
