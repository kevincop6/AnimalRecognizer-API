<?php
// RUTA: api/usuarios/search_users.php

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/Auth.php';

try {

    // --------------------------------------------------
    // 1. AUTENTICACIÓN (TOKEN POR POST)
    // --------------------------------------------------
    if (empty($_POST['token'])) {
        http_response_code(401);
        echo json_encode(["error" => "Token no proporcionado."]);
        exit;
    }

    $token = trim($_POST['token']);

    $auth = new Auth($pdo);
    $usuarioSesion = $auth->validarToken($token);

    if (!$usuarioSesion) {
        http_response_code(401);
        echo json_encode(["error" => "Token inválido o expirado."]);
        exit;
    }

    $usuarioIdSesion = (int)$usuarioSesion['usuario_id'];

    // --------------------------------------------------
    // 2. PARÁMETROS DE BÚSQUEDA Y PAGINACIÓN
    // --------------------------------------------------
    if (empty($_POST['buscar'])) {
        http_response_code(400);
        echo json_encode(["error" => "Debe enviar el parámetro buscar."]);
        exit;
    }

    $buscar = '%' . trim($_POST['buscar']) . '%';

    $limite = 5;
    $pagina = (isset($_POST['pagina']) && is_numeric($_POST['pagina']) && $_POST['pagina'] > 0)
        ? (int)$_POST['pagina']
        : 1;

    $offset = ($pagina - 1) * $limite;

    // --------------------------------------------------
    // 3. TOTAL DE RESULTADOS (SIN INCLUIR AL USUARIO LOGUEADO)
    // --------------------------------------------------
    $sqlTotal = "
        SELECT COUNT(DISTINCT u.id)
        FROM usuarios u
        WHERE u.id != :uid
          AND (
                u.nombre_completo LIKE :t_nombre
                OR u.nombre_usuario LIKE :t_usuario
                OR u.correo LIKE :t_correo
          )
    ";

    $stmtTotal = $pdo->prepare($sqlTotal);
    $stmtTotal->execute([
        ':uid'        => $usuarioIdSesion,
        ':t_nombre'   => $buscar,
        ':t_usuario'  => $buscar,
        ':t_correo'   => $buscar
    ]);

    $totalResultados = (int)$stmtTotal->fetchColumn();

    // --------------------------------------------------
    // 4. CONSULTA PRINCIPAL PAGINADA
    // --------------------------------------------------
    $sql = "
        SELECT 
            u.id,
            u.nombre_usuario,
            u.nombre_completo,
            u.correo,

            -- FOTO DE PERFIL
            ma.url_archivo AS foto_perfil,

            -- CONTADOR DE LIKES
            (
                SELECT COUNT(*)
                FROM usuarios_likes ul
                WHERE ul.usuario_id = u.id
            ) AS likes

        FROM usuarios u

        LEFT JOIN media_archivos ma
            ON ma.tipo_entidad = 'usuario'
           AND ma.entidad_id = u.id
           AND ma.es_principal = 1
           AND ma.estado = 'visible'

        WHERE u.id != :uid_excluir
          AND (
                u.nombre_completo LIKE :buscar_nombre
                OR u.nombre_usuario LIKE :buscar_usuario
                OR u.correo LIKE :buscar_correo
          )

        GROUP BY u.id
        ORDER BY u.nombre_usuario ASC
        LIMIT :limite OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->bindValue(':uid_excluir',     $usuarioIdSesion, PDO::PARAM_INT);
    $stmt->bindValue(':buscar_nombre',  $buscar, PDO::PARAM_STR);
    $stmt->bindValue(':buscar_usuario', $buscar, PDO::PARAM_STR);
    $stmt->bindValue(':buscar_correo',  $buscar, PDO::PARAM_STR);
    $stmt->bindValue(':limite',          $limite, PDO::PARAM_INT);
    $stmt->bindValue(':offset',          $offset, PDO::PARAM_INT);

    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --------------------------------------------------
    // 5. RESPUESTA
    // --------------------------------------------------
    echo json_encode([
        "pagina_actual" => $pagina,
        "por_pagina"    => $limite,
        "total"         => $totalResultados,
        "hay_mas"       => ($offset + $limite) < $totalResultados,
        "usuarios"      => $usuarios
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error"   => "Error interno del servidor.",
        "detalle" => $e->getMessage()
    ]);
}
