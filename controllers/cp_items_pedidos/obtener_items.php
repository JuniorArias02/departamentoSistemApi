<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_get.php';

$pedidoId = $_GET['cp_pedido'] ?? null;

try {
    if ($pedidoId) {
        $stmt = $pdo->prepare("SELECT * FROM cp_items_pedidos WHERE cp_pedido = ?");
        $stmt->execute([$pedidoId]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM cp_items_pedidos");
        $stmt->execute();
    }

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $items
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al obtener los ítems',
        'error' => $e->getMessage()
    ]);
}
