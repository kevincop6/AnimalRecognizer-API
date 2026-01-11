<?php
// RUTA: api/usuarios/toggle_interaccion.php
header('Content-Type: application/json');

require_once '../../config/db.php';
require_once '../../includes/Auth.php';

try {

    // ===============================
    // 1️⃣ Validación básica
    // ===============================
    if (!isset($_POST['token'], $_POST['usuario_objetivo_id'], $_POST['accion'])) {
        throw new Exception("Datos incompletos");
    }

    $token             = $_POST['token'];
    $usuarioObjetivoId = (int) $_POST['usuario_objetivo_id'];
    $accion            = trim($_POST['accion']);

    if (!in_array($accion, ['like', 'follow'], true)) {
        throw new Exception("Acción no válida");
    }

    // ===============================
    // 2️⃣ Validar token
    // ===============================
    $auth = new Auth($pdo);
    $usuarioToken = $auth->validarToken($token);

    if (!$usuarioToken) {
        http_response_code(401);
        echo json_encode(["error" => "Token inválido o expirado"]);
        exit;
    }

    $usuarioId = (int) $usuarioToken['usuario_id'];

    // ===============================
    // 3️⃣ Evitar auto-interacción
    // ===============================
    if ($usuarioId === $usuarioObjetivoId) {
        throw new Exception("No puedes interactuar contigo mismo");
    }

    // ===============================
    // 4️⃣ Mapeo REAL según BD
    // ===============================
    if ($accion === 'like') {
        // ✔ YA EXISTE y es funcional en tu proyecto
        $tabla        = 'usuarios_likes';
        $campoOrigen  = 'usuario_id';
        $campoDestino = 'liked_by_usuario_id';
    } else {
        // ✔ Confirmado por la BD real
        $tabla        = 'usuarios_seguidores';
        $campoOrigen  = 'seguidor_id';
        $campoDestino = 'seguido_id';
    }

    // ===============================
    // 5️⃣ Toggle transaccional
    // ===============================
    $pdo->beginTransaction();

    $sqlCheck = "
        SELECT id
        FROM $tabla
        WHERE $campoOrigen = :origen
          AND $campoDestino = :destino
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sqlCheck);
    $stmt->execute([
        ':origen'  => $usuarioId,
        ':destino' => $usuarioObjetivoId
    ]);

    $existe = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existe) {

        // ❌ Quitar interacción
        $stmt = $pdo->prepare("DELETE FROM $tabla WHERE id = :id");
        $stmt->execute([':id' => $existe['id']]);

        $pdo->commit();

        echo json_encode([
            "accion"  => $accion,
            "estado"  => false,
            "mensaje" => "Interacción eliminada"
        ]);

    } else {

        // ✅ Agregar interacción
        try {
            $stmt = $pdo->prepare("
                INSERT INTO $tabla ($campoOrigen, $campoDestino)
                VALUES (:origen, :destino)
            ");
            $stmt->execute([
                ':origen'  => $usuarioId,
                ':destino' => $usuarioObjetivoId
            ]);

            $pdo->commit();

            echo json_encode([
                "accion"  => $accion,
                "estado"  => true,
                "mensaje" => "Interacción agregada"
            ]);

        } catch (PDOException $e) {
            $pdo->rollBack();

            // UNIQUE compuesto (doble tap / race condition)
            if ($e->getCode() === '23000') {
                echo json_encode([
                    "accion"  => $accion,
                    "estado"  => true,
                    "mensaje" => "Interacción ya existente"
                ]);
            } else {
                throw $e;
            }
        }
    }

} catch (Exception $e) {

    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(400);
    echo json_encode([
        "error" => $e->getMessage()
    ]);
}
