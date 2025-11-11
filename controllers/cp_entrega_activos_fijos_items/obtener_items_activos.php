<?php
require_once __DIR__ . '/../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $entregaId = $_GET['entrega_activos_id'] ?? null;

    if (!$entregaId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Falta el ID de la entrega']);
        exit;
    }

    $sql = "SELECT 
                ei.id,
                ei.item_id,
                ei.es_accesorio,
                ei.accesorio_descripcion,
                i.nombre AS nombre_item,
                i.codigo AS codigo_item
            FROM cp_entrega_activos_fijos_items ei
            LEFT JOIN inventario i ON i.id = ei.item_id
            WHERE ei.entrega_activos_id = :entregaId";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':entregaId', $entregaId, PDO::PARAM_INT);
    $stmt->execute();

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $items
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener los items',
        'error' => $e->getMessage()
    ]);
}
