<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

header("Content-Type: application/json; charset=utf-8");

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (
        !isset($data['pedido_id']) ||
        !isset($data['usuario_id']) ||
        !isset($data['observacion'])
    ) {
        echo json_encode([
            "status" => false,
            "message" => "Faltan datos requeridos (pedido_id, usuario_id, observacion)"
        ]);
        exit;
    }

    $pedido_id = $data['pedido_id'];
    $usuario_id = $data['usuario_id'];
    $observacion = trim($data['observacion']);

    
    $sql = "UPDATE cp_pedidos 
            SET observaciones_pedidos = :observacion 
            WHERE id = :pedido_id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':observacion' => $observacion,
        ':pedido_id' => $pedido_id
    ]);

    // Verifica si realmente se actualizó
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "status" => true,
            "message" => "Observación actualizada correctamente",
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
