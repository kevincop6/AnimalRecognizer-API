<?php
// RUTA: web-admin/ajax/get_animal_detalle.php
// Retorna detalle completo de un animal.
// Si raw=1 devuelve los datos crudos (para edición), si no devuelve HTML renderizado.

header('Content-Type: application/json');
session_start();
require_once '../../config/db.php';

$token = $_COOKIE['admin_token'] ?? null;
if (!$token) { echo json_encode(['success' => false, 'message' => 'No autorizado']); exit; }

$id  = (int)($_GET['id']  ?? 0);
$raw = (int)($_GET['raw'] ?? 0);

if ($id <= 0) { echo json_encode(['success' => false, 'message' => 'ID inválido']); exit; }

$stmt = $pdo->prepare("
    SELECT
        a.*,
        COALESCE(m.url_archivo, NULL) AS foto
    FROM animales a
    LEFT JOIN media_archivos m
        ON  m.tipo_entidad = 'animal'
        AND m.entidad_id   = a.id
        AND m.es_principal = 1
        AND m.estado       = 'visible'
    WHERE a.id = :id LIMIT 1
");
$stmt->execute([':id' => $id]);
$animal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$animal) { echo json_encode(['success' => false, 'message' => 'Animal no encontrado']); exit; }

// Decodifica JSON almacenados
$taxonomia    = json_decode($animal['taxonomia'],    true) ?? [];
$distribucion = json_decode($animal['distribucion'], true) ?? [];
$descripcion  = json_decode($animal['descripcion'],  true) ?? [];

// Modo raw para el formulario de edición
if ($raw) {
    echo json_encode([
        'success' => true,
        'data' => [
            'id'               => $animal['id'],
            'nombre_comun'     => $animal['nombre_comun'],
            'nombre_cientifico'=> $animal['nombre_cientifico'],
            'nombre_ingles'    => $animal['nombre_ingles'],
            'pais_origen'      => $animal['pais_origen'],
            'provincia_region' => $animal['provincia_region'],
            'taxonomia'        => $animal['taxonomia'],
            'distribucion'     => $animal['distribucion'],
            'descripcion'      => $animal['descripcion'],
            'foto'             => $animal['foto'],
        ]
    ]);
    exit;
}

// Modo detalle → generar HTML
$foto    = $animal['foto'] ?? null;
$taxInfo = '';
foreach (['reino','filo','clase','orden','familia','genero'] as $k) {
    if (!empty($taxonomia[$k])) {
        $taxInfo .= "<div class='tax-row'><span class='tax-key'>".ucfirst($k)."</span><span class='tax-val'>{$taxonomia[$k]}</span></div>";
    }
}

$paises  = $distribucion['distribucion']['paises_extant'] ?? [];
$habitat = $distribucion['habitat'] ?? [];
$estatus = $distribucion['estatus_conservacion'] ?? 'Desconocido';
$textoDesc = $descripcion['descripcion']['texto'] ?? 'Sin descripción disponible.';

ob_start();
?>
<div class="detalle-animal">
    <!-- Foto + nombre -->
    <div class="detalle-header">
        <?php if ($foto): ?>
            <img src="<?php echo htmlspecialchars($foto); ?>" class="detalle-foto" alt="<?php echo htmlspecialchars($animal['nombre_comun']); ?>">
        <?php else: ?>
            <div class="detalle-foto-placeholder"><i class="mdi mdi-paw"></i></div>
        <?php endif; ?>
        <div class="detalle-nombre-bloque">
            <h5><?php echo htmlspecialchars($animal['nombre_comun']); ?></h5>
            <div class="text-italic mb-1" style="color:#7c83a0;font-style:italic;"><?php echo htmlspecialchars($animal['nombre_cientifico'] ?? ''); ?></div>
            <div style="font-size:12px;color:#aaa;"><?php echo htmlspecialchars($animal['nombre_ingles']); ?></div>
            <div class="mt-2">
                <span class="badge-estado badge-info-soft"><?php echo htmlspecialchars($animal['provincia_region'] ?? '—'); ?></span>
                <span class="badge-estado badge-success-soft ml-1"><?php echo htmlspecialchars($estatus); ?></span>
            </div>
        </div>
    </div>

    <!-- Taxonomía -->
    <?php if ($taxInfo): ?>
    <div class="detalle-seccion">
        <div class="detalle-seccion-titulo"><i class="mdi mdi-dna"></i> Taxonomía</div>
        <div class="tax-grid"><?php echo $taxInfo; ?></div>
    </div>
    <?php endif; ?>

    <!-- Descripción -->
    <div class="detalle-seccion">
        <div class="detalle-seccion-titulo"><i class="mdi mdi-text-box-outline"></i> Descripción</div>
        <p style="font-size:13px;line-height:1.7;color:#343a40;"><?php echo nl2br(htmlspecialchars($textoDesc)); ?></p>
    </div>

    <!-- Hábitat -->
    <?php if (!empty($habitat)): ?>
    <div class="detalle-seccion">
        <div class="detalle-seccion-titulo"><i class="mdi mdi-tree-outline"></i> Hábitat</div>
        <ul style="font-size:12px;color:#555;padding-left:18px;margin:0;">
            <?php foreach ($habitat as $h): ?>
                <li><?php echo htmlspecialchars($h); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Países -->
    <?php if (!empty($paises)): ?>
    <div class="detalle-seccion">
        <div class="detalle-seccion-titulo"><i class="mdi mdi-map-marker-multiple-outline"></i> Distribución (<?php echo count($paises); ?> países)</div>
        <div style="display:flex;flex-wrap:wrap;gap:5px;">
            <?php foreach ($paises as $p): ?>
                <span class="badge-estado badge-info-soft"><?php echo htmlspecialchars($p); ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Meta -->
    <div style="margin-top:14px;font-size:11px;color:#b0b8d0;text-align:right;">
        Registrado el <?php echo date('d/m/Y', strtotime($animal['fecha_creacion'])); ?>
    </div>
</div>
<?php
$html = ob_get_clean();
echo json_encode(['success' => true, 'html' => $html]);
