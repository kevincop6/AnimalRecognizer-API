<?php
// RUTA: web-admin/animales.php
session_start();

require_once '../config/db.php';
require_once 'includes/AdminAuth.php';

// Validación de sesión
$token = $_COOKIE['admin_token'] ?? null;
if (!$token) { header("Location: index.php"); exit; }

try {
    $stmtSesion = $pdo->prepare("
        SELECT s.usuario_id, s.activo, s.fecha_expiracion,
               u.rol, u.nombre_completo, u.nombre_usuario,
               COALESCE(m.url_archivo, 'https://cdn.pixabay.com/photo/2023/02/18/11/00/icon-7797704_640.png') AS foto_perfil
        FROM sesiones_usuarios s
        INNER JOIN usuarios u ON u.id = s.usuario_id
        LEFT JOIN media_archivos m
            ON m.tipo_entidad = 'usuario' AND m.entidad_id = u.id
            AND m.es_principal = 1 AND m.estado = 'visible'
        WHERE s.token = :token LIMIT 1
    ");
    $stmtSesion->execute([':token' => $token]);
    $sesion = $stmtSesion->fetch(PDO::FETCH_ASSOC);

    if (!$sesion || (int)$sesion['activo'] === 0 || strtotime($sesion['fecha_expiracion']) < time()) {
        setcookie('admin_token', '', time() - 3600, "/");
        header("Location: index.php"); exit;
    }
    if (!in_array($sesion['rol'], ['admin', 'moderador'])) {
        setcookie('admin_token', '', time() - 3600, "/");
        header("Location: index.php"); exit;
    }
} catch (Exception $e) {
    header("Location: index.php"); exit;
}

// Variables globales para includes
$adminNombre     = $sesion['nombre_completo'];
$adminUsuario    = $sesion['nombre_usuario'];
$adminRol        = $sesion['rol'];
$adminFotoPerfil = $sesion['foto_perfil'];

// Contadores para stats cards
$totalAnimales    = (int)$pdo->query("SELECT COUNT(*) FROM animales")->fetchColumn();
$totalEsteAnio    = (int)$pdo->query("SELECT COUNT(*) FROM animales WHERE YEAR(fecha_creacion) = YEAR(NOW())")->fetchColumn();
$totalProvincias  = (int)$pdo->query("SELECT COUNT(DISTINCT provincia_region) FROM animales")->fetchColumn();

$page_title = 'Animales - AnimalRecognizer';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include 'includes/head.php'; ?>
    <style>
        /* ---- Foto del animal en tabla ---- */
        .animal-thumb {
            width: 42px; height: 42px;
            border-radius: 8px;
            object-fit: cover;
            border: 2px solid #e8eaf0;
        }
        .animal-thumb-placeholder {
            width: 42px; height: 42px;
            border-radius: 8px;
            background: #e8f0fe;
            display: flex; align-items: center; justify-content: center;
            color: #4b6cb7; font-size: 18px;
            flex-shrink: 0;
        }
        /* ---- Nombre científico ---- */
        .nombre-cientifico {
            font-size: 11px;
            color: #7c83a0;
            font-style: italic;
        }
        /* ---- Filtros toolbar ---- */
        .toolbar {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            padding: 14px 18px;
            border-bottom: 1px solid #f0f3f6;
        }
        .toolbar .search-box {
            position: relative;
            flex: 1;
            min-width: 200px;
            max-width: 320px;
        }
        .toolbar .search-box i {
            position: absolute;
            left: 10px; top: 50%;
            transform: translateY(-50%);
            color: #b0b8d0; font-size: 15px;
        }
        .toolbar .search-box input {
            width: 100%;
            padding: 7px 10px 7px 32px;
            border: 1px solid #e8eaf0;
            border-radius: 7px;
            font-size: 13px;
            color: #343a40;
            background: #f8f9fc;
            transition: border 0.2s;
        }
        .toolbar .search-box input:focus {
            outline: none;
            border-color: #4b6cb7;
            background: #fff;
        }
        .toolbar select {
            padding: 7px 12px;
            border: 1px solid #e8eaf0;
            border-radius: 7px;
            font-size: 13px;
            color: #343a40;
            background: #f8f9fc;
            cursor: pointer;
        }
        .toolbar select:focus { outline: none; border-color: #4b6cb7; }
        .btn-agregar {
            margin-left: auto;
            background: linear-gradient(135deg, #4b6cb7, #182848);
            color: #fff;
            border: none;
            padding: 8px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            display: flex; align-items: center; gap: 7px;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn-agregar:hover { opacity: 0.88; }

        /* ---- Action buttons en tabla ---- */
        .btn-action {
            width: 30px; height: 30px;
            border-radius: 6px;
            border: none;
            display: inline-flex; align-items: center; justify-content: center;
            cursor: pointer;
            font-size: 15px;
            transition: all 0.15s;
        }
        .btn-action.ver    { background: #e8f0fe; color: #4b6cb7; }
        .btn-action.editar { background: #fff3e0; color: #f57c00; }
        .btn-action.borrar { background: #fdecea; color: #e53935; }
        .btn-action:hover  { filter: brightness(0.92); transform: scale(1.08); }

        /* ---- Paginación ---- */
        .pagination-wrapper {
            padding: 12px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid #f0f3f6;
            font-size: 12px;
            color: #7c83a0;
        }
        .pagination .page-link {
            font-size: 12px;
            padding: 4px 10px;
            color: #4b6cb7;
            border-color: #e8eaf0;
        }
        .pagination .page-item.active .page-link {
            background: #4b6cb7;
            border-color: #4b6cb7;
        }

        /* ---- Loading spinner ---- */
        #loadingAnimales {
            padding: 40px;
            text-align: center;
            color: #7c83a0;
        }
        #loadingAnimales .spinner-border {
            width: 28px; height: 28px;
            border-width: 3px;
            color: #4b6cb7;
        }
    </style>
</head>
<body>

<div class="container-fluid p-0 d-flex">
    <!-- SIDEBAR -->
    <?php include 'includes/sidebar.php'; ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- MAIN PANEL -->
    <div class="main-panel">
        <?php include 'includes/navbar.php'; ?>

        <div class="content-wrapper">

            <!-- PAGE HEADER -->
            <div class="page-header">
                <h4><i class="mdi mdi-paw mr-2" style="color:#4b6cb7;"></i>Gestión de Animales</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Animales</li>
                    </ol>
                </nav>
            </div>

            <!-- STAT CARDS -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="stat-card blue">
                            <div class="stat-info">
                                <div class="stat-label">Total Animales</div>
                                <div class="stat-value"><?php echo $totalAnimales; ?></div>
                                <div class="stat-sub">
                                    <i class="mdi mdi-database-outline"></i> En catálogo
                                </div>
                            </div>
                            <div class="stat-icon"><i class="mdi mdi-paw"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="stat-card green">
                            <div class="stat-info">
                                <div class="stat-label">Agregados este año</div>
                                <div class="stat-value"><?php echo $totalEsteAnio; ?></div>
                                <div class="stat-sub">
                                    <i class="mdi mdi-calendar-check-outline"></i> <?php echo date('Y'); ?>
                                </div>
                            </div>
                            <div class="stat-icon"><i class="mdi mdi-plus-circle-outline"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="stat-card orange">
                            <div class="stat-info">
                                <div class="stat-label">Regiones cubiertas</div>
                                <div class="stat-value"><?php echo $totalProvincias; ?></div>
                                <div class="stat-sub">
                                    <i class="mdi mdi-map-marker-outline"></i> Provincias/zonas
                                </div>
                            </div>
                            <div class="stat-icon"><i class="mdi mdi-map-outline"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLA ANIMALES -->
            <div class="card">
                <!-- Toolbar -->
                <div class="toolbar">
                    <div class="search-box">
                        <i class="mdi mdi-magnify"></i>
                        <input
                            type="text"
                            id="inputBuscar"
                            placeholder="Buscar por nombre, especie..."
                            autocomplete="off"
                        >
                    </div>
                    <select id="filtroProvincia">
                        <option value="">Todas las regiones</option>
                        <option value="San Jose">San José</option>
                        <option value="Alajuela">Alajuela</option>
                        <option value="Cartago">Cartago</option>
                        <option value="Heredia">Heredia</option>
                        <option value="Guanacaste">Guanacaste</option>
                        <option value="Puntarenas">Puntarenas</option>
                        <option value="Limón">Limón</option>
                        <option value="Nacional">Nacional</option>
                    </select>
                    <?php if ($adminRol === 'admin'): ?>
                    <button class="btn-agregar" id="btnAgregarAnimal">
                        <i class="mdi mdi-plus"></i> Agregar Animal
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Tabla -->
                <div class="table-responsive">
                    <table class="table mb-0" id="tablaAnimales">
                        <thead>
                            <tr>
                                <th style="width:50px;">#</th>
                                <th style="width:56px;">Foto</th>
                                <th>Nombre Común</th>
                                <th>Nombre Inglés</th>
                                <th>Región</th>
                                <th>Fecha Registro</th>
                                <th style="width:110px; text-align:center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyAnimales">
                            <!-- Cargado por AJAX -->
                        </tbody>
                    </table>
                </div>

                <!-- Loading -->
                <div id="loadingAnimales">
                    <div class="spinner-border" role="status"></div>
                    <p class="mt-2 mb-0" style="font-size:13px;">Cargando animales...</p>
                </div>

                <!-- Paginación -->
                <div class="pagination-wrapper" id="paginacionWrapper" style="display:none;">
                    <span id="paginacionInfo"></span>
                    <ul class="pagination pagination-sm mb-0" id="paginacionBtns"></ul>
                </div>
            </div>

        </div><!-- /content-wrapper -->
    </div><!-- /main-panel -->
</div>

<!-- MODALES -->
<?php include 'modals/modal_animal_form.php';   ?>
<?php include 'modals/modal_animal_detalle.php'; ?>
<?php include 'modals/modal_confirmar_borrar.php'; ?>

<?php include 'includes/scripts.php'; ?>

<script>
$(document).ready(function () {

    // ============================================================
    // ESTADO DE PAGINACIÓN Y FILTROS
    // ============================================================
    let paginaActual  = 1;
    let busqueda      = '';
    let provincia     = '';
    let totalPaginas  = 1;
    const porPagina   = 10;

    // ============================================================
    // FUNCIÓN PRINCIPAL: CARGAR TABLA VÍA AJAX
    // ============================================================
    function cargarAnimales() {
        $('#loadingAnimales').show();
        $('#tablaAnimales').hide();
        $('#paginacionWrapper').hide();

        $.ajax({
            url: 'ajax/get_animales.php',
            type: 'GET',
            data: {
                pagina:    paginaActual,
                buscar:    busqueda,
                provincia: provincia,
                por_pagina: porPagina
            },
            dataType: 'json',
            success: function (res) {
                $('#loadingAnimales').hide();
                $('#tablaAnimales').show();

                if (!res.success || res.data.length === 0) {
                    $('#tbodyAnimales').html(`
                        <tr>
                            <td colspan="7" class="text-center py-4" style="color:#7c83a0;">
                                <i class="mdi mdi-paw-off" style="font-size:28px;"></i>
                                <p class="mt-2 mb-0">No se encontraron animales.</p>
                            </td>
                        </tr>
                    `);
                    return;
                }

                // Renderizar filas
                totalPaginas = res.total_paginas;
                let html = '';
                res.data.forEach(function (a) {
                    const foto = a.foto
                        ? `<img src="${a.foto}" class="animal-thumb" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                           <div class="animal-thumb-placeholder" style="display:none;"><i class="mdi mdi-paw"></i></div>`
                        : `<div class="animal-thumb-placeholder"><i class="mdi mdi-paw"></i></div>`;

                    const btnEditar = <?php echo $adminRol === 'admin' ? 'true' : 'false'; ?>
                        ? `<button class="btn-action editar" title="Editar" data-id="${a.id}"><i class="mdi mdi-pencil-outline"></i></button>`
                        : '';
                    const btnBorrar = <?php echo $adminRol === 'admin' ? 'true' : 'false'; ?>
                        ? `<button class="btn-action borrar" title="Eliminar" data-id="${a.id}" data-nombre="${a.nombre_comun}"><i class="mdi mdi-trash-can-outline"></i></button>`
                        : '';

                    html += `
                        <tr>
                            <td style="color:#7c83a0;">${a.id}</td>
                            <td>
                                <div style="display:flex;">
                                    ${foto}
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:600;font-size:13px;">${a.nombre_comun}</div>
                                <div class="nombre-cientifico">${a.nombre_cientifico ?? '—'}</div>
                            </td>
                            <td style="font-size:13px;">${a.nombre_ingles}</td>
                            <td>
                                <span class="badge-estado badge-info-soft">${a.provincia_region ?? '—'}</span>
                            </td>
                            <td style="font-size:12px;color:#7c83a0;">
                                ${a.fecha_creacion}
                            </td>
                            <td style="text-align:center;">
                                <button class="btn-action ver" title="Ver detalle" data-id="${a.id}">
                                    <i class="mdi mdi-eye-outline"></i>
                                </button>
                                ${btnEditar}
                                ${btnBorrar}
                            </td>
                        </tr>
                    `;
                });
                $('#tbodyAnimales').html(html);

                // Paginación
                renderPaginacion(res.total, res.total_paginas);
            },
            error: function () {
                $('#loadingAnimales').hide();
                $('#tbodyAnimales').html(`
                    <tr><td colspan="7" class="text-center py-4 text-danger">
                        <i class="mdi mdi-alert-circle-outline"></i> Error al cargar los datos.
                    </td></tr>
                `);
                $('#tablaAnimales').show();
            }
        });
    }

    // ============================================================
    // PAGINACIÓN
    // ============================================================
    function renderPaginacion(total, totalPags) {
        if (totalPags <= 1) { $('#paginacionWrapper').hide(); return; }

        const desde = ((paginaActual - 1) * porPagina) + 1;
        const hasta = Math.min(paginaActual * porPagina, total);
        $('#paginacionInfo').text(`Mostrando ${desde}–${hasta} de ${total} animales`);

        let btns = '';
        // Anterior
        btns += `<li class="page-item ${paginaActual === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-pag="${paginaActual - 1}">
                        <i class="mdi mdi-chevron-left"></i>
                    </a>
                 </li>`;
        // Páginas
        for (let i = 1; i <= totalPags; i++) {
            if (totalPags > 7 && i > 2 && i < totalPags - 1 && Math.abs(i - paginaActual) > 1) {
                if (i === 3 || i === totalPags - 2) btns += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
                continue;
            }
            btns += `<li class="page-item ${i === paginaActual ? 'active' : ''}">
                        <a class="page-link" href="#" data-pag="${i}">${i}</a>
                     </li>`;
        }
        // Siguiente
        btns += `<li class="page-item ${paginaActual === totalPags ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-pag="${paginaActual + 1}">
                        <i class="mdi mdi-chevron-right"></i>
                    </a>
                 </li>`;

        $('#paginacionBtns').html(btns);
        $('#paginacionWrapper').show();
    }

    // Clic en paginación
    $(document).on('click', '#paginacionBtns .page-link', function (e) {
        e.preventDefault();
        const pag = parseInt($(this).data('pag'));
        if (!isNaN(pag) && pag >= 1 && pag <= totalPaginas) {
            paginaActual = pag;
            cargarAnimales();
        }
    });

    // ============================================================
    // FILTROS - búsqueda con debounce
    // ============================================================
    let debounceTimer;
    $('#inputBuscar').on('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
            busqueda     = $('#inputBuscar').val().trim();
            paginaActual = 1;
            cargarAnimales();
        }, 400);
    });

    $('#filtroProvincia').on('change', function () {
        provincia    = $(this).val();
        paginaActual = 1;
        cargarAnimales();
    });

    // ============================================================
    // BOTÓN AGREGAR
    // ============================================================
    $('#btnAgregarAnimal').on('click', function () {
        $('#formAnimalId').val('');                       // Limpia ID → modo crear
        $('#modalAnimalFormLabel').text('Agregar Animal');
        $('#formAnimal')[0].reset();
        $('#previewFotoAnimal').hide();
        $('#modalAnimalForm').modal('show');
    });

    // ============================================================
    // BOTÓN VER DETALLE
    // ============================================================
    $(document).on('click', '.btn-action.ver', function () {
        const id = $(this).data('id');
        $('#modalDetalleAnimal').modal('show');
        $('#detalleAnimalBody').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" style="width:28px;height:28px;"></div>
                <p class="mt-2 mb-0" style="font-size:13px;color:#7c83a0;">Cargando información...</p>
            </div>
        `);

        $.ajax({
            url: 'ajax/get_animal_detalle.php',
            type: 'GET',
            data: { id: id },
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    $('#detalleAnimalBody').html(res.html);
                } else {
                    $('#detalleAnimalBody').html(`<p class="text-danger text-center">${res.message}</p>`);
                }
            },
            error: function () {
                $('#detalleAnimalBody').html('<p class="text-danger text-center">Error al cargar el detalle.</p>');
            }
        });
    });

    // ============================================================
    // BOTÓN EDITAR
    // ============================================================
    $(document).on('click', '.btn-action.editar', function () {
        const id = $(this).data('id');
        $('#modalAnimalFormLabel').text('Editar Animal');
        $('#modalAnimalForm').modal('show');

        $.ajax({
            url: 'ajax/get_animal_detalle.php',
            type: 'GET',
            data: { id: id, raw: 1 },
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    const a = res.data;
                    $('#formAnimalId').val(a.id);
                    $('#formNombreComun').val(a.nombre_comun);
                    $('#formNombreCientifico').val(a.nombre_cientifico);
                    $('#formNombreIngles').val(a.nombre_ingles);
                    $('#formPaisOrigen').val(a.pais_origen);
                    $('#formProvinciaRegion').val(a.provincia_region);
                    $('#formTaxonomia').val(a.taxonomia);
                    $('#formDistribucion').val(a.distribucion);
                    $('#formDescripcion').val(a.descripcion);
                    // Preview foto
                    if (a.foto) {
                        $('#previewFotoAnimal').attr('src', a.foto).show();
                    } else {
                        $('#previewFotoAnimal').hide();
                    }
                }
            }
        });
    });

    // ============================================================
    // BOTÓN ELIMINAR
    // ============================================================
    $(document).on('click', '.btn-action.borrar', function () {
        const id     = $(this).data('id');
        const nombre = $(this).data('nombre');
        $('#confirmarBorrarNombre').text(nombre);
        $('#btnConfirmarBorrar').data('id', id);
        $('#modalConfirmarBorrar').modal('show');
    });

    $('#btnConfirmarBorrar').on('click', function () {
        const id = $(this).data('id');
        $.ajax({
            url: 'ajax/delete_animal.php',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function (res) {
                $('#modalConfirmarBorrar').modal('hide');
                if (res.success) {
                    cargarAnimales(); // Recarga la tabla
                } else {
                    alert('Error: ' + res.message);
                }
            }
        });
    });

    // ============================================================
    // SUBMIT FORMULARIO (crear / editar)
    // ============================================================
    $('#formAnimal').on('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        const $btn = $('#btnGuardarAnimal');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

        $.ajax({
            url: 'ajax/save_animal.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function (res) {
                $btn.prop('disabled', false).html('Guardar');
                if (res.success) {
                    $('#modalAnimalForm').modal('hide');
                    cargarAnimales();
                } else {
                    $('#formAnimalError').text(res.message).show();
                }
            },
            error: function () {
                $btn.prop('disabled', false).html('Guardar');
                $('#formAnimalError').text('Error de conexión.').show();
            }
        });
    });

    // ============================================================
    // CARGA INICIAL
    // ============================================================
    cargarAnimales();

});
</script>

</body>
</html>
