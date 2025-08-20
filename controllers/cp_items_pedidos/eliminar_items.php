<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_delete.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Falta el parámetro id'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM cp_items_pedidos WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Ítem eliminado correctamente'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No se encontró el ítem'
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al eliminar el ítem',
        'error' => $e->getMessage()
    ]);
}
