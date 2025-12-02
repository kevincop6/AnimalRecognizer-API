<?php
// RUTA: includes/Auth.php

class Auth {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // --- REGISTRO (Sin cambios) ---
    public function registrarUsuario($nombre, $usuario, $correo, $password, $biografia = "") {
        // Verificar duplicados
        $sql = "SELECT id FROM usuarios WHERE correo = :correo OR nombre_usuario = :usuario";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':correo' => $correo, ':usuario' => $usuario]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception("El correo o usuario ya existen.");
        }

        // Crear usuario
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sqlInsert = "INSERT INTO usuarios (nombre_completo, nombre_usuario, correo, password_hash, biografia, rol, estado) 
                      VALUES (:nombre, :usuario, :correo, :pass, :bio, 'estandar', 1)";
        $stmtInsert = $this->pdo->prepare($sqlInsert);
        
        if ($stmtInsert->execute([':nombre' => $nombre, ':usuario' => $usuario, ':correo' => $correo, ':pass' => $hash, ':bio' => $biografia])) {
            return $this->pdo->lastInsertId();
        } else {
            throw new Exception("Error al registrar.");
        }
    }

    // --- LOGIN (CIERRA ANTERIOR -> ABRE NUEVA) ---
    public function login($dato_login, $password, $dispositivo = "Desconocido") {
        
        // 1. Buscar usuario
        $sql = "SELECT id, nombre_completo, nombre_usuario, password_hash, rol, estado 
                FROM usuarios 
                WHERE correo = :correo OR nombre_usuario = :usuario LIMIT 1";     
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':correo' => $dato_login, ':usuario' => $dato_login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new Exception("Credenciales incorrectas.");
        }
        if ($user['estado'] == 0) {
            throw new Exception("Cuenta desactivada.");
        }

        // 2. [PASO CRÍTICO] CERRAR LA SESIÓN ANTERIOR
        // Esto invalida cualquier token viejo inmediatamente.
        $sqlCerrar = "UPDATE sesiones_usuarios SET activo = 0 WHERE usuario_id = :uid";
        $stmtCerrar = $this->pdo->prepare($sqlCerrar);
        $stmtCerrar->execute([':uid' => $user['id']]);

        // 3. [PASO CRÍTICO] ABRIR LA NUEVA SESIÓN
        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', strtotime('+30 days'));

        $sqlAbrir = "INSERT INTO sesiones_usuarios (usuario_id, token, dispositivo, fecha_expiracion, activo) 
                     VALUES (:uid, :token, :disp, :expira, 1)";
        $stmtAbrir = $this->pdo->prepare($sqlAbrir);
        $stmtAbrir->execute([
            ':uid' => $user['id'], 
            ':token' => $token, 
            ':disp' => $dispositivo, 
            ':expira' => $expira
        ]);

        return [
            "token" => $token,
            "usuario" => [
                "id" => $user['id'], 
                "nombre" => $user['nombre_completo'], 
                "usuario" => $user['nombre_usuario'],
                "rol" => $user['rol']
            ]
        ];
    }

    // --- LOGOUT MANUAL (CERRAR LA ACTUAL) ---
    public function logout($token) {
        $sql = "UPDATE sesiones_usuarios SET activo = 0 WHERE token = :token";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':token' => $token]);
    }

    // --- VALIDAR TOKEN ---
    /**
     * Verifica si un token es válido, está activo y no ha expirado.
     * Retorna los datos del usuario si es válido, o FALSE si no lo es.
     */
    public function validarToken($token) {
        if (empty($token)) {
            return false;
        }

        // Consulta SQL estricta:
        // 1. Coincide el token
        // 2. activo = 1 (No se ha cerrado sesión en otro lado)
        // 3. fecha_expiracion es mayor que AHORA (No ha caducado por tiempo)
        $sql = "SELECT s.usuario_id, u.rol, u.nombre_usuario, u.nombre_completo 
                FROM sesiones_usuarios s
                JOIN usuarios u ON s.usuario_id = u.id
                WHERE s.token = :token 
                  AND s.activo = 1 
                  AND s.fecha_expiracion > NOW() 
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':token' => $token]);
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si devuelve datos, es válido. Si devuelve false, es inválido.
        return $resultado ?: false;
    }

    // --- OBTENER HEADER ---
    public function obtenerTokenDeCabecera() {
        $headers = null;
        if (isset($_SERVER['Authorization'])) $headers = trim($_SERVER["Authorization"]);
        else if (isset($_SERVER['HTTP_AUTHORIZATION'])) $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) $headers = trim($requestHeaders['Authorization']);
        }
        if (!empty($headers) && preg_match('/Bearer\s(\S+)/', $headers, $matches)) return $matches[1];
        return null;
    }
}
?>