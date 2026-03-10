<?php
// RUTA: web-admin/includes/scripts.php
?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
$(document).ready(function () {

    // ============================================================
    // 1. SIDEBAR TOGGLE (minimize)
    // ============================================================

    $('[data-toggle="minimize"]').on('click', function () {
        $('body').toggleClass('sidebar-hidden');

        // Alterna ícono menú / menú-abierto
        const $icon = $(this).find('.mdi');
        if ($('body').hasClass('sidebar-hidden')) {
            $icon.removeClass('mdi-menu').addClass('mdi-menu-open');
        } else {
            $icon.removeClass('mdi-menu-open').addClass('mdi-menu');
        }
    });

    // Clic fuera del sidebar en mobile → lo cierra
    $(document).on('click', function (e) {
        if (
            $('body').hasClass('sidebar-hidden') === false &&
            $(window).width() <= 991 &&
            !$(e.target).closest('.sidebar').length &&
            !$(e.target).closest('[data-toggle="minimize"]').length
        ) {
            $('body').addClass('sidebar-hidden');
            $('[data-toggle="minimize"] .mdi')
                .removeClass('mdi-menu')
                .addClass('mdi-menu-open');
        }
    });

    // Clic en el overlay mobile cierra el sidebar
    $('#sidebarOverlay').on('click', function () {
        $('body').addClass('sidebar-hidden');
        $('[data-toggle="minimize"] .mdi')
            .removeClass('mdi-menu')
            .addClass('mdi-menu-open');
    });

    // ============================================================
    // 2. DROPDOWN USUARIO (navbar)
    // ============================================================

    const $dropdownWrapper = $('#adminDropdownWrapper');
    const $dropdownMenu    = $('#adminDropdownMenu');
    const $dropdownToggle  = $('#adminDropdownToggle');

    // Abrir / cerrar al hacer clic en el trigger
    $dropdownToggle.on('click', function (e) {
        e.stopPropagation();
        const isOpen = $dropdownWrapper.hasClass('open');

        // Cierra cualquier otro dropdown abierto
        $('.admin-dropdown').removeClass('open');
        $('.admin-dropdown-menu').removeClass('show');

        if (!isOpen) {
            $dropdownWrapper.addClass('open');
            $dropdownMenu.addClass('show');
        }
    });

    // Cerrar al hacer clic fuera
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#adminDropdownWrapper').length) {
            $dropdownWrapper.removeClass('open');
            $dropdownMenu.removeClass('show');
        }
    });

    // Cerrar con tecla Escape
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            $dropdownWrapper.removeClass('open');
            $dropdownMenu.removeClass('show');
        }
    });

});
</script>
