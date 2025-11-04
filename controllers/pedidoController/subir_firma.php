<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';
require_once __DIR__ . '/../utils/registrar_actividad.php';

// Verificar campos obligatorios
if (!isset($_POST['id_pedido']) || empty($_POST['id_pedido'])) {
    http_response_code(400);
    echo json_encode(["error" => "El id_pedido es obligatorio"]);
    exit;
}

if (!isset($_POST['tipo_firma']) || empty($_POST['tipo_firma'])) {
    http_response_code(400);
    echo json_encode(["error" => "El tipo_firma es obligatorio"]);
    exit;
}

if (!isset($_POST['id_usuario']) || empty($_POST['id_usuario'])) {
    http_response_code(400);
    echo json_encode(["error" => "El id_usuario es obligatorio"]);
    exit;
}


if (!isset($_FILES['firma']) || $_FILES['firma']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(["error" => "La firma es obligatoria"]);
    exit;
}

$id_pedido = intval($_POST['id_pedido']);
$tipo_firma = $_POST['tipo_firma'];
$usuarioId = $_POST['id_usuario'];

// Validar tipo de firma permitido
$firmas_permitidas = [
    'elaborado_por_firma',
    'proceso_compra_firma',
    'responsable_aprobacion_firma'
];

if (!in_array($tipo_firma, $firmas_permitidas)) {
    http_response_code(400);
    echo json_encode(["error" => "Tipo de firma no válido"]);
    exit;
}

// Validar permisos según tipo de firma
switch ($tipo_firma) {
    case 'proceso_compra_firma':
    case 'responsable_aprobacion_firma':
        if (!tienePermiso($pdo, $usuarioId, PERMISOS['GESTION_COMPRA_PEDIDOS']['APROBAR_PEDIDO'])) {
            http_response_code(403);
            echo json_encode(["error" => "No tienes permisos para aprobar pedidos"]);
            exit;
        }
        break;
}

// Validar formato de archivo
$ext_permitidas = ['png'];
$extension = strtolower(pathinfo($_FILES['firma']['name'], PATHINFO_EXTENSION));
if (!in_array($extension, $ext_permitidas)) {
    http_response_code(400);
    echo json_encode(["error" => "Solo se permite formato PNG"]);
    exit;
}

// Carpeta destino
$directorio = __DIR__ . '/../../public/firmas/';
if (!file_exists($directorio)) {
    mkdir($directorio, 0755, true);
}

$nombre_archivo = uniqid("firma_") . ".png";
$ruta_absoluta = $directorio . $nombre_archivo;
$ruta_relativa = "public/firmas/" . $nombre_archivo;

// Mover archivo
if (!move_uploaded_file($_FILES['firma']['tmp_name'], $ruta_absoluta)) {
    http_response_code(500);
    echo json_encode(["error" => "No se pudo guardar la firma"]);
    exit;
}

try {
    $campo_fecha = null;
    if ($tipo_firma === 'proceso_compra_firma') {
        $campo_fecha = 'fecha_compra';
    } elseif ($tipo_firma === 'responsable_aprobacion_firma') {
        $campo_fecha = 'fecha_gerencia';
    }

   if ($campo_fecha) {
        // Actualiza firma y fecha
        $sql = "UPDATE cp_pedidos 
                SET {$tipo_firma} = :firma, {$campo_fecha} = NOW() 
                WHERE id = :id";
    } else {
        // Solo actualiza firma
        $sql = "UPDATE cp_pedidos 
                SET {$tipo_firma} = :firma 
                WHERE id = :id";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':firma' => $ruta_relativa,
        ':id'    => $id_pedido
    ]);

    registrarActividad(
        $pdo,
        null,
        "Subió {$tipo_firma} para el pedido ID {$id_pedido}",
        "cp_pedidos",
        $id_pedido
    );

    echo json_encode([
        "success" => true,
        "message" => "Firma subida correctamente",
        "ruta" => $ruta_relativa
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error al guardar la firma: " . $e->getMessage()]);
}
