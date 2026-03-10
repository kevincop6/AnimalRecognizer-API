<?php
// RUTA: web-admin/includes/AdminAuth.php

class AdminAuth {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function login($dato_login, $password, $persistir = false, $dispositivo = "Desconocido") {

        // Tabla: usuarios
        // Columnas: id, nombre_completo, nombre_usuario, correo, password_hash, rol, estado
        $stmt = $this->pdo->prepare("
            SELECT 
                id,
                nombre_completo,
                nombre_usuario,
                password_hash,
                rol,
                estado
            FROM usuarios
            WHERE correo = :correo OR nombre_usuario = :usuario
            LIMIT 1
        ");
        $stmt->execute([
            ':correo'  => $dato_login,
            ':usuario' => $dato_login
        ]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new Exception("Credenciales incorrectas.");
        }

        if ((int)$user['estado'] === 0) {
            throw new Exception("Tu cuenta está desactivada.");
        }

        if ($user['rol'] !== 'admin' && $user['rol'] !== 'moderador') {
            throw new Exception("No tienes los permisos necesarios para acceder a la administración.");
        }

        // Tabla: sesiones_usuarios
        // Columnas: usuario_id, token, dispositivo, fecha_expiracion, activo
        $stmtCerrar = $this->pdo->prepare("
            UPDATE sesiones_usuarios
            SET activo = 0
            WHERE usuario_id = :uid
        ");
        $stmtCerrar->execute([':uid' => $user['id']]);

        $token = bin2hex(random_bytes(32));
        $duracionSegundos = $persistir ? (60 * 60 * 24 * 30) : (60 * 60 * 2);
        $fechaExpiracion  = date('Y-m-d H:i:s', time() + $duracionSegundos);

        $stmtAbrir = $this->pdo->prepare("
            INSERT INTO sesiones_usuarios (usuario_id, token, dispositivo, fecha_expiracion, activo)
            VALUES (:uid, :token, :disp, :expira, 1)
        ");
        $stmtAbrir->execute([
            ':uid'    => $user['id'],
            ':token'  => $token,
            ':disp'   => $dispositivo,
            ':expira' => $fechaExpiracion
        ]);

        return [
            "token"          => $token,
            "id"             => $user['id'],
            "rol"            => $user['rol'],
            "nombre"         => $user['nombre_completo'],
            "nombre_usuario" => $user['nombre_usuario']
        ];
    }
}
