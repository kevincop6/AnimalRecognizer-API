<?php
// RUTA: api/usuarios/profile_view.php

header('Content-Type: application/json');

require_once '../../config/db.php';
require_once '../../includes/Auth.php';

try {
    $auth = new Auth($pdo);

    // =====================================================
    // 1️⃣ LEER POST OBLIGATORIO
    // =====================================================
    if (!isset($_POST['token'])) {
        throw new Exception("Token no enviado");
    }

    $token  = $_POST['token'];
    $pagina = isset($_POST['pagina']) ? max(1, (int)$_POST['pagina']) : 1;

    // =====================================================
    // 2️⃣ VALIDAR TOKEN
    // =====================================================
    $usuarioToken = $auth->validarToken($token);

    if (!$usuarioToken) {
        http_response_code(401);
        echo json_encode(["error" => "Token inválido o expirado"]);
        exit;
    }

    // 👉 PERFIL DEL USUARIO AUTENTICADO
    $usuarioPerfilId = (int)$usuarioToken['usuario_id'];

    // =====================================================
    // 3️⃣ CONFIGURACIÓN PAGINACIÓN
    // =====================================================
    $limite = 10;
    $offset = ($pagina - 1) * $limite;

    // =====================================================
    // 4️⃣ DATOS DEL PERFIL + FOTO (media_archivos)
    // =====================================================
    $sqlPerfil = "
        SELECT 
            u.id,
            u.nombre_usuario,
            u.nombre_completo,
            u.biografia,
            m.url_archivo AS foto_perfil
        FROM usuarios u
        LEFT JOIN media_archivos m
            ON m.entidad_id = u.id
           AND m.tipo_entidad = 'usuario'
           AND m.es_principal = 1
           AND m.estado = 'visible'
        WHERE u.id = :uid
        LIMIT 1
    ";
    $stmtPerfil = $pdo->prepare($sqlPerfil);
    $stmtPerfil->execute([':uid' => $usuarioPerfilId]);
    $perfil = $stmtPerfil->fetch(PDO::FETCH_ASSOC);

    if (!$perfil) {
        throw new Exception("Perfil no encontrado");
    }

    // =====================================================
    // 5️⃣ LIKES AL PERFIL (usuarios_likes)
    // =====================================================
    $sqlLikes = "
        SELECT COUNT(id)
        FROM usuarios_likes
        WHERE usuario_id = :uid
    ";
    $stmtLikes = $pdo->prepare($sqlLikes);
    $stmtLikes->execute([':uid' => $usuarioPerfilId]);
    $likesPerfil = (int)$stmtLikes->fetchColumn();

    // =====================================================
    // 6️⃣ AVISTAMIENTOS (PUBLICACIONES PAGINADAS)
    // =====================================================
    $sqlPublicaciones = "
        SELECT 
            a.id,
            a.titulo,
            a.descripcion,
            a.fecha_avistamiento,
            a.latitud,
            a.longitud,
            a.pais_avistamiento,
            a.provincia_avistamiento,
            m.url_archivo AS imagen
        FROM avistamientos a
        LEFT JOIN media_archivos m
            ON m.entidad_id = a.id
           AND m.tipo_entidad = 'avistamiento'
           AND m.es_principal = 1
           AND m.estado = 'visible'
        WHERE a.usuario_id = :uid
          AND a.validado = 1
        ORDER BY a.fecha_avistamiento DESC
        LIMIT :lim OFFSET :off
    ";
    $stmtPub = $pdo->prepare($sqlPublicaciones);
    $stmtPub->bindValue(':uid', $usuarioPerfilId, PDO::PARAM_INT);
    $stmtPub->bindValue(':lim', $limite, PDO::PARAM_INT);
    $stmtPub->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmtPub->execute();

    $publicaciones = $stmtPub->fetchAll(PDO::FETCH_ASSOC);

    // =====================================================
    // 7️⃣ TOTAL PARA PAGINACIÓN
    // =====================================================
    $sqlTotal = "
        SELECT COUNT(id)
        FROM avistamientos
        WHERE usuario_id = :uid
          AND validado = 1
    ";
    $stmtTotal = $pdo->prepare($sqlTotal);
    $stmtTotal->execute([':uid' => $usuarioPerfilId]);
    $totalPublicaciones = (int)$stmtTotal->fetchColumn();

    $totalPaginas = ceil($totalPublicaciones / $limite);

    // =====================================================
    // 8️⃣ RESPUESTA JSON FINAL
    // =====================================================
    echo json_encode([
        "perfil" => [
            "id" => $perfil['id'],
            "usuario" => $perfil['nombre_usuario'],
            "nombre" => $perfil['nombre_completo'],
            "bio" => $perfil['biografia'],
            "foto_perfil" => $perfil['foto_perfil'],
            "likes" => $likesPerfil
        ],
        "publicaciones" => $publicaciones,
        "paginacion" => [
            "pagina_actual" => $pagina,
            "por_pagina" => $limite,
            "total_publicaciones" => $totalPublicaciones,
            "total_paginas" => $totalPaginas
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "error" => $e->getMessage()
    ]);
}
