<?php // RUTA: web-admin/modals/modal_animal_detalle.php ?>

<div class="modal fade" id="modalDetalleAnimal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content" style="border:none;border-radius:14px;overflow:hidden;">

            <div class="modal-header" style="background:linear-gradient(135deg,#4b6cb7,#182848);border:none;padding:18px 24px;">
                <h5 class="modal-title" style="color:#fff;font-weight:700;margin:0;">
                    <i class="mdi mdi-information-outline mr-2"></i>Detalle del Animal
                </h5>
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;opacity:0.8;">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body p-0" id="detalleAnimalBody" style="padding:0 !important;">
                <!-- Contenido cargado por AJAX -->
            </div>

            <div class="modal-footer" style="border-top:1px solid #f0f3f6;padding:12px 24px;">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Estilos del detalle del animal */
    .detalle-animal { padding: 20px 24px; }

    .detalle-header {
        display: flex; gap: 18px; align-items: flex-start;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid #f0f3f6;
    }
    .detalle-foto {
        width: 90px; height: 90px;
        border-radius: 12px; object-fit: cover;
        border: 2px solid #e8eaf0; flex-shrink: 0;
    }
    .detalle-foto-placeholder {
        width: 90px; height: 90px;
        border-radius: 12px; background: #e8f0fe;
        display: flex; align-items: center; justify-content: center;
        color: #4b6cb7; font-size: 36px; flex-shrink: 0;
    }
    .detalle-nombre-bloque h5 {
        font-size: 18px; font-weight: 700;
        color: #1a1a2e; margin: 0 0 4px;
    }

    .detalle-seccion { margin-bottom: 18px; }
    .detalle-seccion-titulo {
        font-size: 11px; font-weight: 700;
        text-transform: uppercase; letter-spacing: 1px;
        color: #4b6cb7; margin-bottom: 10px;
        display: flex; align-items: center; gap: 6px;
    }
    .detalle-seccion-titulo i { font-size: 15px; }

    /* Taxonomía grid */
    .tax-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
    .tax-row {
        display: flex; gap: 8px; align-items: center;
        background: #f8f9fc; border-radius: 6px; padding: 6px 10px;
    }
    .tax-key {
        font-size: 11px; font-weight: 700; color: #7c83a0;
        text-transform: uppercase; min-width: 60px;
    }
    .tax-val { font-size: 12px; color: #343a40; }
</style>
