<?php
// includes/Auth.php

class Auth {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // ---------------------------------------------------------
    // 1. REGISTRAR USUARIO
    // ---------------------------------------------------------
    public function registrarUsuario($nombre, $usuario, $correo, $password, $biografia = "") {
        // Validar existencia
        $sql = "SELECT id FROM usuarios WHERE correo = :correo OR nombre_usuario = :usuario";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':correo' => $correo, ':usuario' => $usuario]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception("El correo o el nombre de usuario ya están en uso.");
        }

        // Encriptar y guardar
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sqlInsert = "INSERT INTO usuarios (nombre_completo, nombre_usuario, correo, password_hash, biografia, rol, estado) 
                      VALUES (:nombre, :usuario, :correo, :pass, :bio, 'estandar', 1)";
        
        $stmtInsert = $this->pdo->prepare($sqlInsert);
        if ($stmtInsert->execute([
            ':nombre' => $nombre, ':usuario' => $usuario, ':correo' => $correo, ':pass' => $hash, ':bio' => $biografia
        ])) {
            return $this->pdo->lastInsertId();
        } else {
            throw new Exception("Error al guardar el usuario.");
        }
    }

    // ---------------------------------------------------------
    // 2. INICIAR SESIÓN (LOGIN)
    // ---------------------------------------------------------
    public function login($correo, $password, $dispositivo = "Desconocido") {
        // Buscar usuario
        $sql = "SELECT id, nombre_completo, nombre_usuario, password_hash, rol, estado FROM usuarios WHERE correo = :correo LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':correo' => $correo]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new Exception("Credenciales incorrectas.");
        }
        if ($user['estado'] == 0) {
            throw new Exception("Cuenta desactivada.");
        }

        // Generar token y guardar sesión
        $token = bin2hex(random_bytes(32));
        $fechaExpiracion = date('Y-m-d H:i:s', strtotime('+30 days'));

        $sqlSesion = "INSERT INTO sesiones_usuarios (usuario_id, token, dispositivo, fecha_expiracion) 
                      VALUES (:uid, :token, :disp, :expira)";
        $stmtSesion = $this->pdo->prepare($sqlSesion);
        $stmtSesion->execute([
            ':uid' => $user['id'], ':token' => $token, ':disp' => $dispositivo, ':expira' => $fechaExpiracion
        ]);

        return [
            "token" => $token,
            "usuario" => ["id" => $user['id'], "nombre" => $user['nombre_completo'], "rol" => $user['rol']]
        ];
    }

    // ---------------------------------------------------------
    // 3. VALIDAR TOKEN (NUEVA FUNCIÓN)
    // Verifica si la sesión está activa y retorna los datos del usuario.
    // ---------------------------------------------------------
    public function validarToken($token) {
        if (empty($token)) {
            return false;
        }

        // Hacemos JOIN con usuarios para saber el Rol inmediatamente
        // Y verificamos 3 cosas: que el token coincida, que esté activo (1) y que NO haya expirado
        $sql = "SELECT s.usuario_id, u.rol, u.nombre_usuario 
                FROM sesiones_usuarios s
                JOIN usuarios u ON s.usuario_id = u.id
                WHERE s.token = :token 
                  AND s.activo = 1 
                  AND s.fecha_expiracion > NOW() 
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':token' => $token]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado) {
            // Token válido: Devolvemos ID y Rol
            return [
                'id' => $resultado['usuario_id'],
                'rol' => $resultado['rol'],
                'usuario' => $resultado['nombre_usuario']
            ];
        } else {
            // Token no encontrado, expirado o cerrado
            return false;
        }
    }

    // ---------------------------------------------------------
    // 4. HELPER: OBTENER TOKEN DE CABECERA
    // Extrae el string "Bearer xyz..." del header HTTP
    // ---------------------------------------------------------
    public function obtenerTokenDeCabecera() {
        $headers = null;
        
        // Apache vs Nginx vs Otros
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }

        // Si encontramos el header, quitamos la palabra "Bearer "
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
}
?>