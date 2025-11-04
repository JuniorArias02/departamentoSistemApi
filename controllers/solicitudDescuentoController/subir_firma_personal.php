<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';

header("Content-Type: application/json; charset=utf-8");

// Validar campos básicos
if (!isset($_POST['id_solicitud']) || empty($_POST['id_solicitud'])) {
    http_response_code(400);
    echo json_encode(["error" => "El id_solicitud es obligatorio"]);
    exit;
}

if (!isset($_POST['tipo_firma']) || empty($_POST['tipo_firma'])) {
    http_response_code(400);
    echo json_encode(["error" => "El tipo_firma es obligatorio"]);
    exit;
}

$idSolicitud = intval($_POST['id_solicitud']);
$tipoFirma   = $_POST['tipo_firma'];

// Firmas permitidas (coinciden con los campos de la tabla)
$firmasPermitidas = [
    'firma_trabajador',
    'firma_responsable_aprobacion',
    'firma_jefe_inmediato',
    'firma_facturacion',
    'firma_gestion_financiera',
    'firma_talento_humano'
];

if (!in_array($tipoFirma, $firmasPermitidas)) {
    http_response_code(400);
    echo json_encode(["error" => "Tipo de firma no válido"]);
    exit;
}

// Si no llegó firma → no es error, solo avisamos
if (!isset($_FILES['firma']) || $_FILES['firma']['error'] === UPLOAD_ERR_NO_FILE) {
    echo json_encode([
        "success" => false,
        "message" => "No se seleccionó ninguna firma"
    ]);
    exit;
}

// Validar extensión de archivo
$extPermitidas = ['png'];
$extension = strtolower(pathinfo($_FILES['firma']['name'], PATHINFO_EXTENSION));
if (!in_array($extension, $extPermitidas)) {
    http_response_code(400);
    echo json_encode(["error" => "Solo se permite formato PNG"]);
    exit;
}

// Carpeta destino
$directorio = __DIR__ . '/../../public/descuento/';
if (!file_exists($directorio)) {
    mkdir($directorio, 0755, true);
}

// Nombre único del archivo
$nombreArchivo = uniqid("firma_") . ".png";
$rutaAbsoluta  = $directorio . $nombreArchivo;
$rutaRelativa  = "public/descuento/" . $nombreArchivo;

// Mover archivo subido
if (!move_uploaded_file($_FILES['firma']['tmp_name'], $rutaAbsoluta)) {
    http_response_code(500);
    echo json_encode(["error" => "No se pudo guardar la firma"]);
    exit;
}

try {
    // Actualizar la firma en la solicitud
    $sql = "UPDATE cp_solicitud_descuento 
            SET {$tipoFirma} = :firma 
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':firma' => $rutaRelativa,
        ':id'    => $idSolicitud
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Firma subida correctamente",
        "campo"   => $tipoFirma,
        "ruta"    => $rutaRelativa
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error al guardar la firma: " . $e->getMessage()]);
}
