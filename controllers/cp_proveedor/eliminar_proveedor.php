<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

// Recibir datos JSON
$data = json_decode(file_get_contents("php://input"), true);


if (!isset($data['id']) || empty($data['id'])) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "ID de proveedor requerido"
    ]);
    exit;
}

$id = intval($data['id']);

try {
    // Verificar si existe el proveedor
    $check = $pdo->prepare("SELECT id FROM cp_proveedores WHERE id = ?");

    $check->execute([$id]);

    if ($check->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            "status" => "error",
            "message" => "Proveedor no encontrado"
        ]);
        exit;
    }

    // Eliminar proveedor
    $query = $pdo->prepare("DELETE FROM cp_proveedores WHERE id = ?");
    $query->execute([$id]);

    echo json_encode([
        "status" => "success",
        "message" => "Proveedor eliminado correctamente"
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error al eliminar: " . $e->getMessage()
    ]);
}
