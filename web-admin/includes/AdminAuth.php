<?php
// RUTA: web-admin/includes/AdminAuth.php (AUTÓNOMA SIN HERENCIA)

/**
 * Clase autónoma para manejar la autenticación del Panel de Administración.
 * Esta clase contiene toda la lógica de login, rol y persistencia.
 */
class AdminAuth {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Inicia sesión, aplica filtro de rol (admin/moderador) y maneja la persistencia.
     * @param bool $persistir Indica si la sesión debe ser de larga duración (30 días).
     * @throws Exception Si las credenciales son incorrectas o el rol no está autorizado.
     * @return array Datos de la sesión para el JSON del proxy.
     */
    public function login($dato_login, $password, $persistir = false, $dispositivo = "Desconocido") {
        
        // 1. Buscar usuario
        $sql = "SELECT id, nombre_completo, nombre_usuario, password_hash, rol, estado 
                FROM usuarios 
                WHERE correo = :correo OR nombre_usuario = :usuario LIMIT 1"; 
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':correo' => $dato_login, ':usuario' => $dato_login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Verificación de credenciales y estado
        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new Exception("Credenciales incorrectas.");
        }
        if ($user['estado'] == 0) {
            throw new Exception("Tu cuenta está desactivada.");
        }

        // 🚩 FILTRO DE ROL CRÍTICO (ADMIN/MODERADOR) 🚩
        if ($user['rol'] !== 'admin' && $user['rol'] !== 'moderador') {
             throw new Exception("No tienes los permisos necesarios para acceder a la administración.");
        }

        // 3. PASO DE SEGURIDAD: CERRAR LA SESIÓN ANTERIOR 
        // Se invalida cualquier token viejo que el usuario pueda tener en otro dispositivo.
        $sqlCerrar = "UPDATE sesiones_usuarios SET activo = 0 WHERE usuario_id = :uid";
        $stmtCerrar = $this->pdo->prepare($sqlCerrar);
        $stmtCerrar->execute([':uid' => $user['id']]);

        // 4. GENERAR TOKEN
        $token = bin2hex(random_bytes(32));
        
        // 🚩 LÓGICA DE PERSISTENCIA: Determinar la duración de la sesión.
        $duracionSegundos = $persistir ? (60 * 60 * 24 * 30) : (60 * 60 * 2); // 30 días vs 2 horas
        $fechaExpiracion = date('Y-m-d H:i:s', time() + $duracionSegundos);

        // 5. GUARDAR NUEVA SESIÓN: El token se almacena en la base de datos con su fecha de expiración.
        $sqlAbrir = "INSERT INTO sesiones_usuarios (usuario_id, token, dispositivo, fecha_expiracion, activo) 
                     VALUES (:uid, :token, :disp, :expira, 1)";
        $stmtAbrir = $this->pdo->prepare($sqlAbrir);
        $stmtAbrir->execute([
            ':uid' => $user['id'], 
            ':token' => $token, 
            ':disp' => $dispositivo, 
            ':expira' => $fechaExpiracion
        ]);

        // 6. Devolver datos completos para el JSON anidado del proxy
        return [
            "token" => $token,
            "id" => $user['id'],
            "rol" => $user['rol'],
            "nombre" => $user['nombre_completo'],
            "nombre_usuario" => $user['nombre_usuario']
        ];
    }
}