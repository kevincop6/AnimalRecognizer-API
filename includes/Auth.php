<?php
// RUTA: includes/Auth.php

class Auth {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // --- REGISTRO (Sin cambios) ---
    public function registrarUsuario($nombre, $usuario, $correo, $password, $biografia = "") {
        
        // --- Paso 1: Verificar duplicados ---
        $sql = "SELECT id FROM usuarios WHERE correo = :correo OR nombre_usuario = :usuario";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':correo' => $correo, ':usuario' => $usuario]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception("El correo o usuario ya existen.");
        }

        // --- Paso 2: Crear usuario ---
        $this->pdo->beginTransaction(); // Inicia la transacción para asegurar atomicidad
        
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sqlInsert = "INSERT INTO usuarios (nombre_completo, nombre_usuario, correo, password_hash, biografia, rol, estado) 
                      VALUES (:nombre, :usuario, :correo, :pass, :bio, 'estandar', 1)";
        $stmtInsert = $this->pdo->prepare($sqlInsert);
        
        if (!$stmtInsert->execute([':nombre' => $nombre, ':usuario' => $usuario, ':correo' => $correo, ':pass' => $hash, ':bio' => $biografia])) {
            $this->pdo->rollBack();
            throw new Exception("Error al registrar el usuario.");
        }
        
        $usuario_id = $this->pdo->lastInsertId();

        // --- Paso 3: Crear Configuración por Defecto ---
        // Se utilizan los valores ENUM definidos previamente: 'geolocalizacion', 1 (activo), 0 (claro)
        $sqlConfig = "INSERT INTO configuracion_usuario (usuario_id, notificaciones_activas, tema_oscura, paquete_predeterminado) 
                      VALUES (:uid, 1, 0, 'geolocalizacion')";
        $stmtConfig = $this->pdo->prepare($sqlConfig);
        
        if (!$stmtConfig->execute([':uid' => $usuario_id])) {
            $this->pdo->rollBack();
            throw new Exception("Error al crear la configuración por defecto.");
        }

        $this->pdo->commit(); // Confirma la transacción (ambas inserciones fueron exitosas)
        return $usuario_id;
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

        // Consulta SQL actualizada para incluir el paquete_predeterminado:
        $sql = "SELECT s.usuario_id, u.rol, u.nombre_usuario, u.nombre_completo, c.paquete_predeterminado 
                FROM sesiones_usuarios s
                JOIN usuarios u ON s.usuario_id = u.id
                JOIN configuracion_usuario c ON s.usuario_id = c.usuario_id  /* <-- 1. Nuevo JOIN */
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

 // -----------------------------------------------------------------------------------
    // --- GENERAR LLAVE ADMIN (USANDO PHPSECLIB)
    // -----------------------------------------------------------------------------------
   public function generarLlaveAdmin($usuario_id, $pin)
{
    // Separador de directorios compatible Windows/Linux
    $DS = DIRECTORY_SEPARATOR;

    // 1. Verificar si el usuario es un administrador
    $sql_rol = "SELECT rol, nombre_usuario FROM usuarios WHERE id = :uid";
    $stmt_rol = $this->pdo->prepare($sql_rol);
    $stmt_rol->execute([':uid' => $usuario_id]);
    $user_data = $stmt_rol->fetch(PDO::FETCH_ASSOC);

    if (!$user_data) {
        throw new Exception("Usuario no encontrado.");
    }

    if ($user_data['rol'] !== 'admin') {
        throw new Exception("Solo los administradores pueden generar llaves criptográficas.");
    }

    // 2. Definir rutas y generar identificador único
    // Solo subimos DOS niveles (includes -> AnimalRecognizer-API/)
    $ruta_base_proyecto = dirname(dirname(__FILE__));

    // RUTA FINAL: .../AnimalRecognizer-API/public/llaves/
    $dir_llaves = $ruta_base_proyecto . $DS . 'public' . $DS . 'llaves' . $DS;

    if (!is_dir($dir_llaves)) {
        if (!mkdir($dir_llaves, 0700, true)) {
            throw new Exception("Error: No se pudo crear el directorio de llaves. Verifique permisos.");
        }
    }

    // OJO: la columna identificador_llave es VARCHAR(50), recortamos el SHA-256
    $identificador = substr(
        hash('sha256', $user_data['nombre_usuario'] . time() . rand()),
        0,
        50
    );

    $ruta_privada  = $dir_llaves . $identificador . "_privada.pem";

    // 3. Verificar disponibilidad de OpenSSL
    if (!extension_loaded('openssl') || !function_exists('openssl_pkey_new')) {
        throw new Exception("OpenSSL no está disponible en este servidor.");
    }

    // 4. Determinar openssl.cnf según el sistema operativo (Windows o Linux)
    $opensslConfig = null;

    if (stripos(PHP_OS, 'WIN') === 0) {
        // Posibles rutas típicas en Windows (XAMPP/WAMP, etc.)
        $posiblesRutasWin = [
            'C:\\xampp\\apache\\bin\\openssl.cnf',
            'C:\\xampp\\php\\extras\\openssl\\openssl.cnf',
            'C:\\xampp\\php\\extras\\ssl\\openssl.cnf',
            'C:\\Program Files\\Apache24\\conf\\openssl.cnf',
        ];

        foreach ($posiblesRutasWin as $ruta) {
            if (is_file($ruta)) {
                $opensslConfig = $ruta;
                break;
            }
        }

        // Si tú ya conoces exactamente la ruta en tu servidor, la puedes fijar aquí:
        // $opensslConfig = 'C:\\xampp\\apache\\bin\\openssl.cnf';
    } else {
        // Linux / Unix (Apache, Nginx, etc.)
        $posiblesRutasLinux = [
            '/etc/ssl/openssl.cnf',
            '/usr/lib/ssl/openssl.cnf',
            '/usr/local/ssl/openssl.cnf',
            '/etc/pki/tls/openssl.cnf',
        ];

        foreach ($posiblesRutasLinux as $ruta) {
            if (is_file($ruta)) {
                $opensslConfig = $ruta;
                break;
            }
        }

        // Si tu distro usa una ruta específica, también la puedes fijar manualmente:
        // $opensslConfig = '/etc/ssl/openssl.cnf';
    }

    // 5. Configuración para generar la llave
    $config = [
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    ];

    // Solo añadimos 'config' si encontramos un openssl.cnf existente
    if ($opensslConfig !== null) {
        $config['config'] = $opensslConfig;
    }

    // Limpiar errores previos de OpenSSL
    while (openssl_error_string()) {
        // Vaciar el stack de errores
    }

    // 6. Generar el par de llaves con OpenSSL nativo
    $res = openssl_pkey_new($config);

    if (!$res) {
        $errores = [];
        while ($e = openssl_error_string()) {
            $errores[] = $e;
        }
        $detalle = $errores ? implode(" | ", $errores) : "OpenSSL devolvió false sin más detalles.";

        throw new Exception("Error al generar el par de llaves OpenSSL. Detalle: " . $detalle);
    }

    // 7. Exportar la llave privada al archivo físico, protegida con el PIN
    if (!openssl_pkey_export_to_file($res, $ruta_privada, $pin, $config)) {
        $errores = [];
        while ($e = openssl_error_string()) {
            $errores[] = $e;
        }
        $detalle = $errores ? implode(" | ", $errores) : "Error desconocido al exportar la llave.";

        throw new Exception(
            "Error al escribir el archivo de llave privada. " .
            "Verifique permisos del directorio de llaves y antivirus. Detalle: " . $detalle
        );
    }

    // 7.1 (Opcional pero recomendado)
    // Revocar llaves anteriores activas de este usuario
    $sql_revocar_previas = "
        UPDATE llaves_criptograficas
        SET revocada = 1
        WHERE usuario_id = :uid
          AND revocada = 0
    ";
    $stmt_revocar_previas = $this->pdo->prepare($sql_revocar_previas);
    $stmt_revocar_previas->execute([
        ':uid' => $usuario_id,
    ]);

    // 8. Registrar la metadata en la base de datos
    $sql_insert = "
        INSERT INTO llaves_criptograficas (
            usuario_id,
            identificador_llave,
            fecha_creacion,
            revocada
        ) VALUES (
            :uid,
            :identificador,
            NOW(),
            0
        )";
    $stmt_insert = $this->pdo->prepare($sql_insert);
    $stmt_insert->execute([
        ':uid'          => $usuario_id,
        ':identificador'=> $identificador
    ]);

    return [
        "mensaje"        => "Llave criptográfica generada y registrada nativamente con OpenSSL.",
        "identificador"  => $identificador,
        "archivo_privado"=> basename($ruta_privada)
    ];
}


}
?>