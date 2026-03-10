<?php
// RUTA: web-admin/includes/head.php
$page_title = $page_title ?? 'Panel Admin - AnimalRecognizer';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($page_title); ?></title>

<!-- Bootstrap 4 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
<!-- Material Design Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css">
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600;700&display=swap" rel="stylesheet">
<!-- Font Awesome local -->
<link rel="stylesheet" href="assets/vendors/fontawesome-7.2.0/css/all.min.css">

<style>
    /* ============================================================
       1. BASE
    ============================================================ */
    * { box-sizing: border-box; }

    body {
        font-family: 'Source Sans Pro', sans-serif;
        background: #f0f3f6;
        margin: 0;
        font-size: 14px;
        color: #343a40;
    }

    /* ============================================================
       2. SIDEBAR
    ============================================================ */
    .sidebar {
        width: 260px;
        min-height: 100vh;
        position: fixed;
        top: 0; left: 0;
        background: #1a1a2e;
        z-index: 999;
        display: flex;
        flex-direction: column;
        transition: width 0.3s ease, transform 0.3s ease;
        overflow: hidden;
    }

    /* -- Brand -- */
    .sidebar-brand {
        padding: 22px 20px 16px;
        border-bottom: 1px solid rgba(255,255,255,0.07);
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }
    .sidebar-brand .brand-logo {
        width: 36px; height: 36px;
        background: linear-gradient(135deg, #4b6cb7, #182848);
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .sidebar-brand .brand-logo i { color: #fff; font-size: 18px; }
    .sidebar-brand .brand-text h5 {
        margin: 0; font-size: 15px; font-weight: 700;
        color: #fff; line-height: 1.2;
    }
    .sidebar-brand .brand-text small { color: #7c83a0; font-size: 11px; }

    /* -- Nav sections -- */
    .sidebar .nav-section {
        padding: 20px 0 4px 20px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        color: #4a5080;
        white-space: nowrap;
    }
    .sidebar .nav-item { list-style: none; }
    .sidebar .nav-link {
        display: flex;
        align-items: center;
        padding: 10px 20px;
        color: #8a91b4;
        font-size: 13.5px;
        font-weight: 400;
        border-left: 3px solid transparent;
        transition: all 0.2s;
        text-decoration: none;
        white-space: nowrap;
    }
    .sidebar .nav-link i {
        font-size: 17px;
        margin-right: 12px;
        width: 20px;
        text-align: center;
        flex-shrink: 0;
    }
    .sidebar .nav-link .nav-badge {
        margin-left: auto;
        background: #2d3066;
        color: #a0a8d0;
        font-size: 10px;
        padding: 2px 7px;
        border-radius: 10px;
        font-weight: 600;
    }
    .sidebar .nav-link:hover {
        background: rgba(255,255,255,0.04);
        color: #fff;
        border-left-color: #4b6cb7;
        text-decoration: none;
    }
    .sidebar .nav-link.active {
        background: rgba(75,108,183,0.15);
        color: #fff;
        border-left-color: #4b6cb7;
        font-weight: 600;
    }
    .sidebar .nav-link.active i { color: #4b6cb7; }

    /* -- Nav Profile -- */
    .sidebar .nav-item.nav-profile {
        padding: 16px 0 8px;
        border-bottom: 1px solid rgba(255,255,255,0.07);
        margin-bottom: 8px;
    }
    .sidebar .nav-item.nav-profile .nav-link {
        display: flex;
        align-items: center;
        padding: 10px 20px;
        border-left: none;
        background: rgba(255,255,255,0.03);
        border-radius: 0;
        gap: 12px;
        position: relative;
    }
    .sidebar .nav-item.nav-profile .nav-link:hover {
        background: rgba(255,255,255,0.06);
        border-left-color: transparent;
    }
    .sidebar .nav-profile-image {
        position: relative;
        width: 40px; height: 40px;
        flex-shrink: 0;
    }
    .sidebar .nav-profile-image img {
        width: 40px; height: 40px;
        border-radius: 50%;
        object-fit: cover;
        display: block;
        border: 2px solid rgba(255,255,255,0.15);
    }

    /* -- Indicador de estado -- */
    .sidebar .login-status {
        position: absolute;
        bottom: 1px; right: 1px;
        width: 10px; height: 10px;
        border-radius: 50%;
        border: 2px solid #1a1a2e;
        display: block;
    }
    .sidebar .login-status.online  { background: #1a9c5b; }
    .sidebar .login-status.offline { background: #7c83a0; }
    .sidebar .login-status.busy    { background: #fc5c65; }

    /* -- Texto del perfil -- */
    .sidebar .nav-profile-text { flex: 1; min-width: 0; }
    .sidebar .nav-profile-text .font-weight-bold {
        font-size: 13px; font-weight: 600; color: #fff;
        white-space: nowrap; overflow: hidden;
        text-overflow: ellipsis; line-height: 1.3;
    }
    .sidebar .nav-profile-text .text-secondary {
        font-size: 11px; color: #7c83a0 !important;
        white-space: nowrap; overflow: hidden;
        text-overflow: ellipsis; line-height: 1.3;
    }
    .sidebar .nav-profile-badge {
        font-size: 18px; color: #1a9c5b;
        flex-shrink: 0; margin-left: auto; line-height: 1;
    }

    /* ============================================================
       3. SIDEBAR TOGGLE (minimize)
    ============================================================ */

    /* Desktop: colapsa a ancho 0 */
    body.sidebar-hidden .sidebar {
        width: 0;
        min-width: 0;
    }
    body.sidebar-hidden .main-panel {
        margin-left: 0;
    }

    /* Mobile: desliza fuera de pantalla */
    @media (max-width: 991px) {
        .sidebar {
            position: fixed;
            transform: translateX(0);
            width: 260px !important;
        }
        body.sidebar-hidden .sidebar {
            transform: translateX(-260px);
            width: 260px !important;
        }
        body.sidebar-hidden .main-panel { margin-left: 0; }

        .sidebar-overlay { display: none; }
        body:not(.sidebar-hidden) .sidebar-overlay {
            display: block;
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.45);
            z-index: 998;
        }
    }

    /* ============================================================
       4. MAIN PANEL
    ============================================================ */
    .main-panel {
        margin-left: 260px;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        transition: margin-left 0.3s ease;
    }

    /* ============================================================
       5. NAVBAR TOP
    ============================================================ */
    .navbar-top {
        background: #fff;
        border-bottom: 1px solid #e8eaf0;
        padding: 0 24px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: sticky;
        top: 0;
        z-index: 100;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    }
    .navbar-top .page-title {
        font-size: 16px; font-weight: 600;
        color: #1a1a2e; margin: 0;
    }
    .navbar-top .nav-right {
        display: flex; align-items: center; gap: 16px;
    }
    .navbar-top .nav-left {
        display: flex; align-items: center; gap: 12px;
    }

    /* -- Botón toggler -- */
    .navbar-toggler.align-self-center {
        background: none;
        border: none;
        color: #7c83a0;
        font-size: 22px;
        padding: 4px 8px;
        cursor: pointer;
        border-radius: 6px;
        transition: background 0.2s, color 0.2s;
        line-height: 1;
    }
    .navbar-toggler.align-self-center:hover {
        background: #f0f3f6;
        color: #1a1a2e;
    }
    .navbar-toggler.align-self-center:focus {
        outline: none;
        box-shadow: none;
    }

    /* -- Admin info + avatar -- */
    .admin-dropdown { position: relative; }

    .admin-info {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        padding: 5px 10px;
        border-radius: 8px;
        transition: background 0.2s;
        user-select: none;
    }
    .admin-info:hover { background: #f0f3f6; }

    .admin-dropdown-arrow {
        font-size: 16px;
        color: #7c83a0;
        transition: transform 0.25s ease;
        margin-left: 2px;
    }
    .admin-dropdown.open .admin-dropdown-arrow {
        transform: rotate(180deg);
    }

    .navbar-top .admin-avatar {
        width: 36px; height: 36px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
        border: 2px solid #e8eaf0;
        background: linear-gradient(135deg, #4b6cb7, #182848);
    }
    .navbar-top .admin-avatar img {
        width: 100%; height: 100%;
        object-fit: cover;
        display: block;
    }
    .navbar-top .admin-name {
        font-size: 13px; font-weight: 600; color: #343a40;
    }
    .navbar-top .admin-role {
        font-size: 11px; color: #7c83a0;
        background: #f0f3f6;
        padding: 2px 8px;
        border-radius: 10px;
    }

    /* ============================================================
       6. DROPDOWN USUARIO
    ============================================================ */
    .admin-dropdown-menu {
        display: none;
        position: absolute;
        top: calc(100% + 10px);
        right: 0;
        width: 240px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.13);
        border: 1px solid #e8eaf0;
        z-index: 200;
        overflow: hidden;
        opacity: 0;
        transform: translateY(-8px);
        transition: opacity 0.2s ease, transform 0.2s ease;
    }
    .admin-dropdown-menu.show {
        display: block;
        opacity: 1;
        transform: translateY(0);
    }

    /* -- Cabecera del dropdown -- */
    .dropdown-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 16px 12px;
    }
    .dropdown-avatar {
        width: 46px; height: 46px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e8eaf0;
        flex-shrink: 0;
    }
    .dropdown-user-info {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    .dropdown-user-info strong {
        font-size: 13px; font-weight: 700; color: #1a1a2e;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .dropdown-user-info span {
        font-size: 11px; color: #7c83a0;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .dropdown-role-badge {
        display: inline-block;
        margin-top: 3px;
        background: #e8f0fe;
        color: #4b6cb7;
        font-size: 10px; font-weight: 700;
        padding: 2px 8px;
        border-radius: 10px;
        text-transform: capitalize;
    }

    /* -- Separador -- */
    .dropdown-divider {
        height: 1px;
        background: #f0f3f6;
        margin: 4px 0;
    }

    /* -- Items del dropdown -- */
    .dropdown-item-custom {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 16px;
        font-size: 13px;
        color: #343a40;
        text-decoration: none;
        transition: background 0.15s;
    }
    .dropdown-item-custom i {
        font-size: 16px; color: #7c83a0;
        width: 18px; text-align: center; flex-shrink: 0;
    }
    .dropdown-item-custom:hover {
        background: #f8f9fc; color: #1a1a2e; text-decoration: none;
    }
    .dropdown-item-custom:hover i { color: #4b6cb7; }

    /* -- Item cerrar sesión -- */
    .dropdown-logout { color: #e53935; }
    .dropdown-logout i { color: #e53935; }
    .dropdown-logout:hover { background: #fdecea; color: #c62828; }
    .dropdown-logout:hover i { color: #c62828; }

    /* -- Botón logout (legacy, por si se usa fuera del dropdown) -- */
    .btn-logout {
        background: none;
        border: 1px solid #e0e4ef;
        color: #7c83a0;
        padding: 5px 12px;
        border-radius: 6px;
        font-size: 12px;
        display: flex; align-items: center; gap: 5px;
        transition: all 0.2s;
        text-decoration: none;
    }
    .btn-logout:hover {
        background: #fc5c65; color: #fff;
        border-color: #fc5c65; text-decoration: none;
    }

    /* ============================================================
       7. CONTENT WRAPPER
    ============================================================ */
    .content-wrapper { padding: 24px; flex: 1; }

    .page-header { margin-bottom: 22px; }
    .page-header h4 {
        font-size: 20px; font-weight: 700;
        color: #1a1a2e; margin: 0 0 4px;
    }
    .breadcrumb {
        background: transparent; padding: 0;
        margin: 0; font-size: 12px;
    }
    .breadcrumb-item + .breadcrumb-item::before { color: #b0b8d0; }
    .breadcrumb-item.active { color: #7c83a0; }

    /* ============================================================
       8. CARDS
    ============================================================ */
    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        background: #fff;
    }

    /* -- Stat cards -- */
    .stat-card {
        padding: 20px 22px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-radius: 10px;
        overflow: hidden;
        position: relative;
    }
    .stat-card .stat-info .stat-label {
        font-size: 12px; font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.8px; margin-bottom: 6px;
    }
    .stat-card .stat-info .stat-value {
        font-size: 28px; font-weight: 700;
        line-height: 1; margin-bottom: 6px;
    }
    .stat-card .stat-info .stat-sub {
        font-size: 11px; display: flex;
        align-items: center; gap: 4px;
    }
    .stat-card .stat-icon {
        width: 56px; height: 56px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .stat-card .stat-icon i { font-size: 26px; }

    .stat-card.blue  .stat-label { color: #1a6fc4; }
    .stat-card.blue  .stat-value { color: #1a1a2e; }
    .stat-card.blue  .stat-icon  { background: #e8f0fe; color: #4b6cb7; }

    .stat-card.green .stat-label { color: #1a9c5b; }
    .stat-card.green .stat-value { color: #1a1a2e; }
    .stat-card.green .stat-icon  { background: #e6f9f0; color: #1a9c5b; }

    .stat-card.orange .stat-label { color: #c46a00; }
    .stat-card.orange .stat-value { color: #1a1a2e; }
    .stat-card.orange .stat-icon  { background: #fff3e0; color: #f57c00; }

    /* -- Card header -- */
    .card-header-custom {
        padding: 16px 20px 12px;
        border-bottom: 1px solid #f0f3f6;
        display: flex; align-items: center;
        justify-content: space-between;
    }
    .card-header-custom h6 {
        margin: 0; font-size: 14px;
        font-weight: 700; color: #1a1a2e;
    }
    .card-header-custom .header-badge {
        font-size: 10px; padding: 3px 9px;
        border-radius: 10px; font-weight: 600;
        background: #e8f0fe; color: #4b6cb7;
    }

    /* ============================================================
       9. TABLA
    ============================================================ */
    .table thead th {
        background: #f8f9fc;
        color: #7c83a0;
        font-size: 11px; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.8px;
        border-top: none;
        border-bottom: 1px solid #e8eaf0;
        padding: 10px 16px;
    }
    .table tbody td {
        font-size: 13px; color: #343a40;
        padding: 10px 16px;
        vertical-align: middle;
        border-top: 1px solid #f0f3f6;
    }
    .table tbody tr:hover td { background: #f8f9fc; }

    /* ============================================================
       10. BADGES
    ============================================================ */
    .badge-estado {
        font-size: 10px; font-weight: 600;
        padding: 3px 9px; border-radius: 10px;
    }
    .badge-success-soft { background: #e6f9f0; color: #1a9c5b; }
    .badge-warning-soft { background: #fff3e0; color: #f57c00; }
    .badge-danger-soft  { background: #fdecea; color: #e53935; }
    .badge-info-soft    { background: #e8f0fe; color: #4b6cb7; }

    /* ============================================================
       11. BARRAS DE PROGRESO
    ============================================================ */
    .progress-item { margin-bottom: 14px; }
    .progress-item .progress-header {
        display: flex; justify-content: space-between; margin-bottom: 5px;
    }
    .progress-item .progress-header span {
        font-size: 12px; font-weight: 600; color: #343a40;
    }
    .progress-item .progress-header small { font-size: 11px; color: #7c83a0; }
    .progress { height: 6px; border-radius: 3px; background: #f0f3f6; }
    .progress-bar { border-radius: 3px; }

   /* ============================================================
   12. APP WRAPPER
   El sidebar es position:fixed, no ocupa flujo del documento.
   El wrapper solo necesita existir como contenedor semántico.
   El main-panel se posiciona con margin-left: 260px por CSS.
============================================================ */
#appWrapper {
    display: block;
    width: 100%;
    min-height: 100vh;
}

/* main-panel ocupa todo el ancho restante */
.main-panel {
    margin-left: 260px;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    width: calc(100% - 260px);   /* ← CLAVE: ancho explícito */
    transition: margin-left 0.3s ease, width 0.3s ease;
}

/* Cuando el sidebar está oculto, main-panel ocupa todo */
body.sidebar-hidden .main-panel {
    margin-left: 0;
    width: 100%;
}

/* ============================================================
   RESPONSIVE
============================================================ */
@media (max-width: 991px) {
    .sidebar { position: fixed; min-height: 100vh; }
    .main-panel {
        margin-left: 0;
        width: 100%;   /* En mobile siempre ocupa todo */
    }
}
</style>
