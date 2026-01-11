<?php
// RUTA: api/usuarios/search_users.php

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/Auth.php';

try {

    // ==================================================
    // 1. AUTENTICACIÓN (TOKEN POR POST)
    // ==================================================
    if (empty($_POST['token'])) {
        http_response_code(401);
        echo json_encode(["error" => "Token no proporcionado."]);
        exit;
    }

    $auth = new Auth($pdo);
    $usuarioSesion = $auth->validarToken(trim($_POST['token']));

    if (!$usuarioSesion) {
        http_response_code(401);
        echo json_encode(["error" => "Token inválido o expirado."]);
        exit;
    }

    $usuarioIdSesion = (int)$usuarioSesion['usuario_id'];

    // ==================================================
    // 2. PARÁMETROS DE BÚSQUEDA Y PAGINACIÓN
    // ==================================================
    if (empty($_POST['buscar'])) {
        http_response_code(400);
        echo json_encode(["error" => "Debe enviar el parámetro buscar."]);
        exit;
    }

    $buscar = '%' . trim($_POST['buscar']) . '%';

    $limite = 5;
    $pagina = (
        isset($_POST['pagina']) &&
        is_numeric($_POST['pagina']) &&
        (int)$_POST['pagina'] > 0
    ) ? (int)$_POST['pagina'] : 1;

    $offset = ($pagina - 1) * $limite;

    // ==================================================
    // 3. TOTAL DE RESULTADOS (EXCLUYENDO AL USUARIO LOGUEADO)
    // ==================================================
    $sqlTotal = "
        SELECT COUNT(*)
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
        ':uid'       => $usuarioIdSesion,
        ':t_nombre'  => $buscar,
        ':t_usuario' => $buscar,
        ':t_correo'  => $buscar
    ]);

    $totalResultados = (int)$stmtTotal->fetchColumn();

    // ==================================================
    // 4. CONSULTA PRINCIPAL PAGINADA (LIKES CORRECTOS)
    //    usuario_id = quien dio like
    //    liked_by_usuario_id = perfil que recibe el like
    // ==================================================
    $sql = "
        SELECT 
            u.id,
            u.nombre_usuario,
            u.nombre_completo,
            u.correo,

            -- FOTO DE PERFIL
            ma.url_archivo AS foto_perfil,

            -- TOTAL DE LIKES DEL PERFIL (cuántos usuarios le dieron like)
            (
                SELECT COUNT(*)
                FROM usuarios_likes ul
                WHERE ul.liked_by_usuario_id = u.id
            ) AS likes,

            -- SI EL USUARIO LOGUEADO YA LE DIO LIKE A ESTE PERFIL
            (
                SELECT IF(COUNT(*) > 0, 1, 0)
                FROM usuarios_likes ul2
                WHERE ul2.usuario_id = :uid_sesion
                  AND ul2.liked_by_usuario_id = u.id
                LIMIT 1
            ) AS yo_di_like

        FROM usuarios u

        LEFT JOIN media_archivos ma
            ON ma.tipo_entidad = 'usuario'
           AND ma.entidad_id = u.id
           AND ma.es_principal = 1
           AND ma.estado = 'visible'

        WHERE u.id != :uid_excluir
          AND (
            u.nombre_completo LIKE :q_nombre
            OR u.nombre_usuario LIKE :q_usuario
            OR u.correo LIKE :q_correo
          )

        ORDER BY u.nombre_usuario ASC
        LIMIT $limite OFFSET $offset
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->bindValue(':uid_excluir', $usuarioIdSesion, PDO::PARAM_INT);
    $stmt->bindValue(':uid_sesion',  $usuarioIdSesion, PDO::PARAM_INT);

    $stmt->bindValue(':q_nombre',  $buscar, PDO::PARAM_STR);
    $stmt->bindValue(':q_usuario', $buscar, PDO::PARAM_STR);
    $stmt->bindValue(':q_correo',  $buscar, PDO::PARAM_STR);

    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ==================================================
    // 5. RESPUESTA
    // ==================================================
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
