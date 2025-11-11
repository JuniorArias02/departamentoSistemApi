<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

header("Content-Type: application/json; charset=utf-8");

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (
        !isset($data['pedido_id']) ||
        !isset($data['usuario_id'])
    ) {
        echo json_encode([
            "status" => false,
            "message" => "Faltan datos requeridos (pedido_id, usuario_id)"
        ]);
        exit;
    }

    $pedido_id = $data['pedido_id'];
    $usuario_id = $data['usuario_id'];
    $observacion = trim($data['observacion'] ?? '');

    // Nuevos campos (pueden venir vacíos)
    $fecha_solicitud_cotizacion   = $data['fecha_solicitud_cotizacion'] ?? null;
    $fecha_respuesta_cotizacion   = $data['fecha_respuesta_cotizacion'] ?? null;
    $firma_aprobacion_orden       = $data['firma_aprobacion_orden'] ?? null;
    $fecha_envio_proveedor        = $data['fecha_envio_proveedor'] ?? null;

    $sql = "UPDATE cp_pedidos 
            SET observaciones_pedidos = :observacion,
                fecha_solicitud_cotizacion = :fecha_solicitud_cotizacion,
                fecha_respuesta_cotizacion = :fecha_respuesta_cotizacion,
                firma_aprobacion_orden = :firma_aprobacion_orden,
                fecha_envio_proveedor = :fecha_envio_proveedor
            WHERE id = :pedido_id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':observacion' => $observacion,
        ':fecha_solicitud_cotizacion' => $fecha_solicitud_cotizacion,
        ':fecha_respuesta_cotizacion' => $fecha_respuesta_cotizacion,
        ':firma_aprobacion_orden' => $firma_aprobacion_orden,
        ':fecha_envio_proveedor' => $fecha_envio_proveedor,
        ':pedido_id' => $pedido_id
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "status" => true,
            "message" => "Datos actualizados correctamente",
            "pedido_id" => $pedido_id,
            "usuario_id" => $usuario_id
        ]);
    } else {
        echo json_encode([
            "status" => false,
            "message" => "No se encontró el pedido o no hubo cambios"
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "status" => false,
        "message" => "Error en la base de datos: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        "status" => false,
        "message" => "Error inesperado: " . $e->getMessage()
    ]);
}
