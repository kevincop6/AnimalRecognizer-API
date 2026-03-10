<?php // RUTA: web-admin/modals/modal_animal_form.php ?>

<div class="modal fade" id="modalAnimalForm" tabindex="-1" role="dialog" aria-labelledby="modalAnimalFormLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content" style="border:none;border-radius:14px;overflow:hidden;">

            <!-- Header -->
            <div class="modal-header" style="background:linear-gradient(135deg,#4b6cb7,#182848);border:none;padding:18px 24px;">
                <h5 class="modal-title" id="modalAnimalFormLabel" style="color:#fff;font-weight:700;margin:0;">
                    <i class="mdi mdi-paw mr-2"></i>Animal
                </h5>
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;opacity:0.8;">
                    <span>&times;</span>
                </button>
            </div>

            <!-- Body -->
            <div class="modal-body" style="padding:24px;">
                <div id="formAnimalError" class="alert alert-danger py-2" style="display:none;font-size:13px;"></div>

                <form id="formAnimal" enctype="multipart/form-data">
                    <!-- ID oculto (vacío = crear, con valor = editar) -->
                    <input type="hidden" id="formAnimalId" name="id">

                    <!-- Foto -->
                    <div class="form-group text-center mb-4">
                        <img id="previewFotoAnimal" src="" alt="Preview"
                             style="display:none;width:100px;height:100px;border-radius:12px;object-fit:cover;border:2px solid #e8eaf0;margin-bottom:10px;">
                        <div>
                            <label style="font-size:12px;font-weight:600;color:#7c83a0;display:block;margin-bottom:5px;">
                                Foto principal
                            </label>
                            <input type="file" name="foto" id="inputFotoAnimal" accept="image/*"
                                   style="font-size:12px;">
                        </div>
                    </div>

                    <div class="row">
                        <!-- Nombre común -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label-custom">Nombre Común <span class="text-danger">*</span></label>
                                <input type="text" class="form-control-custom" id="formNombreComun" name="nombre_comun" required>
                            </div>
                        </div>
                        <!-- Nombre científico -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label-custom">Nombre Científico</label>
                                <input type="text" class="form-control-custom" id="formNombreCientifico" name="nombre_cientifico" style="font-style:italic;">
                            </div>
                        </div>
                        <!-- Nombre inglés -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label-custom">Nombre en Inglés <span class="text-danger">*</span></label>
                                <input type="text" class="form-control-custom" id="formNombreIngles" name="nombre_ingles" required>
                            </div>
                        </div>
                        <!-- País origen -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label-custom">País de origen</label>
                                <select class="form-control-custom" id="formPaisOrigen" name="pais_origen">
                                    <option value="">-- Seleccionar --</option>
                                    <option value="Costa Rica">Costa Rica</option>
                                    <option value="Panamá">Panamá</option>
                                    <option value="Nicaragua">Nicaragua</option>
                                    <option value="Colombia">Colombia</option>
                                    <option value="México">México</option>
                                    <option value="Brasil">Brasil</option>
                                </select>
                            </div>
                        </div>
                        <!-- Provincia/Región -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label-custom">Provincia / Región</label>
                                <select class="form-control-custom" id="formProvinciaRegion" name="provincia_region">
                                    <option value="">-- Seleccionar --</option>
                                    <option value="San Jose">San José</option>
                                    <option value="Alajuela">Alajuela</option>
                                    <option value="Cartago">Cartago</option>
                                    <option value="Heredia">Heredia</option>
                                    <option value="Guanacaste">Guanacaste</option>
                                    <option value="Puntarenas">Puntarenas</option>
                                    <option value="Limón">Limón</option>
                                    <option value="Nacional">Nacional</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Taxonomía JSON -->
                    <div class="form-group">
                        <label class="form-label-custom">
                            Taxonomía <small style="color:#7c83a0;">(JSON)</small>
                        </label>
                        <textarea class="form-control-custom" id="formTaxonomia" name="taxonomia" rows="4"
                                  placeholder='{"reino":"Animalia","filo":"Chordata","clase":"...","orden":"...","familia":"...","genero":"..."}'></textarea>
                    </div>

                    <!-- Distribución JSON -->
                    <div class="form-group">
                        <label class="form-label-custom">
                            Distribución <small style="color:#7c83a0;">(JSON)</small>
                        </label>
                        <textarea class="form-control-custom" id="formDistribucion" name="distribucion" rows="4"
                                  placeholder='{"distribucion":{"paises_extant":[]},"habitat":[],"estatus_conservacion":"..."}'></textarea>
                    </div>

                    <!-- Descripción JSON -->
                    <div class="form-group">
                        <label class="form-label-custom">
                            Descripción <small style="color:#7c83a0;">(JSON)</small>
                        </label>
                        <textarea class="form-control-custom" id="formDescripcion" name="descripcion" rows="5"
                                  placeholder='{"descripcion":{"texto":"...","fuente":{"nombre":"Wikipedia","url":"..."}}}'></textarea>
                    </div>

                </form>
            </div>

            <!-- Footer -->
            <div class="modal-footer" style="border-top:1px solid #f0f3f6;padding:14px 24px;">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="submit" form="formAnimal" id="btnGuardarAnimal"
                        style="background:linear-gradient(135deg,#4b6cb7,#182848);color:#fff;border:none;padding:8px 22px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                    <i class="mdi mdi-content-save-outline mr-1"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Estilos del formulario modal */
    .form-label-custom {
        font-size: 12px; font-weight: 600;
        color: #7c83a0; text-transform: uppercase;
        letter-spacing: 0.6px; margin-bottom: 5px; display: block;
    }
    .form-control-custom {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #e8eaf0;
        border-radius: 8px;
        font-size: 13px;
        color: #343a40;
        background: #f8f9fc;
        transition: border 0.2s, background 0.2s;
        font-family: 'Source Sans Pro', sans-serif;
    }
    .form-control-custom:focus {
        outline: none;
        border-color: #4b6cb7;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(75,108,183,0.1);
    }
    textarea.form-control-custom {
        resize: vertical;
        font-family: 'Courier New', monospace;
        font-size: 12px;
    }
</style>

<script>
    // Preview de foto al seleccionar archivo
    document.getElementById('inputFotoAnimal').addEventListener('change', function () {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                const preview = document.getElementById('previewFotoAnimal');
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });
</script>
