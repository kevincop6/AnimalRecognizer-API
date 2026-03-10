<?php // RUTA: web-admin/modals/modal_confirmar_borrar.php ?>

<div class="modal fade" id="modalConfirmarBorrar" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content" style="border:none;border-radius:14px;overflow:hidden;">

            <div class="modal-header" style="background:#fdecea;border:none;padding:16px 20px;">
                <h5 class="modal-title" style="color:#e53935;font-weight:700;margin:0;font-size:15px;">
                    <i class="mdi mdi-alert-outline mr-2"></i>Confirmar eliminación
                </h5>
                <button type="button" class="close" data-dismiss="modal" style="color:#e53935;opacity:0.7;">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body" style="padding:20px;text-align:center;">
                <i class="mdi mdi-trash-can-outline" style="font-size:42px;color:#e53935;display:block;margin-bottom:10px;"></i>
                <p style="font-size:14px;color:#343a40;margin:0;">
                    ¿Eliminar el animal<br>
                    <strong id="confirmarBorrarNombre" style="color:#1a1a2e;"></strong>?
                </p>
                <p style="font-size:12px;color:#7c83a0;margin-top:6px;">Esta acción no se puede deshacer.</p>
            </div>

            <div class="modal-footer" style="border-top:1px solid #f0f3f6;padding:12px 20px;justify-content:center;gap:10px;">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" id="btnConfirmarBorrar"
                        style="background:#e53935;color:#fff;border:none;padding:7px 20px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                    <i class="mdi mdi-trash-can-outline mr-1"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
