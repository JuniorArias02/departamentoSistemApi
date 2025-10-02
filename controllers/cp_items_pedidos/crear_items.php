<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!is_array($data) || empty($data)) {
        echo json_encode(["status" => false, "message" => "No se recibieron ítems válidos"]);
        exit;
    }

    $sql = "INSERT INTO cp_items_pedidos 
            (nombre, cantidad, unidad_medida, referencia_items, cp_pedido, productos_id) 
            VALUES 
            (:nombre, :cantidad, :unidad_medida, :referencia_items, :cp_pedido, :productos_id)";

    $stmt = $pdo->prepare($sql);

    foreach ($data as $item) {
        if (!isset($item['nombre'], $item['cantidad'], $item['cp_pedido'])) {
            continue; 
        }

        $stmt->execute([
            ':nombre'           => $item['nombre'],
            ':cantidad'         => $item['cantidad'],
            ':unidad_medida'    => $item['unidad_medida'] ?? null,
            ':referencia_items' => $item['referencia_items'] ?? null,
            ':cp_pedido'        => $item['cp_pedido'],
            ':productos_id'     => $item['productos_id'] ?? null 
        ]);
    }

    echo json_encode([
        "status" => true,
        "message" => "Ítems registrados correctamente"
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "status" => false,
        "message" => "Error en la base de datos: " . $e->getMessage()
    ]);
}
