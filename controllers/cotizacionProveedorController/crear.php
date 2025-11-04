<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';

$data = json_decode(file_get_contents("php://input"), true);

$item_pedido_id           = $data['item_pedido_id'] ?? null;
$proveedor_id             = $data['proveedor_id'] ?? null;
$fecha_solicitud_cotizacion = $data['fecha_solicitud_cotizacion'] ?? null;
$precio                   = $data['precio'] ?? null;

// Validar campos obligatorios
if (!$item_pedido_id || !$proveedor_id || !$fecha_solicitud_cotizacion || !$precio) {
    echo json_encode([
        "success" => false,
        "message" => "Faltan campos obligatorios (item_pedido_id, proveedor_id, fecha_solicitud_cotizacion, precio)"
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO cp_cotizaciones_proveedores 
        (item_pedido_id, proveedor_id, fecha_solicitud_cotizacion, precio)
        VALUES (:item_pedido_id, :proveedor_id, :fecha_solicitud_cotizacion, :precio)
    ");

    $stmt->execute([
        ":item_pedido_id" => $item_pedido_id,
        ":proveedor_id" => $proveedor_id,
        ":fecha_solicitud_cotizacion" => $fecha_solicitud_cotizacion,
        ":precio" => $precio
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Cotización creada correctamente",
        "id"      => $pdo->lastInsertId()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error al crear cotización",
        "error"   => $e->getMessage()
    ]);
}
