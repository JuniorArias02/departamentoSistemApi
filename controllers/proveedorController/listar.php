<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

try {
    $stmt = $pdo->query("SELECT id, nombre, nit, telefono, correo, direccion FROM cp_proveedores ORDER BY nombre ASC");
    $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data"    => $proveedores
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error al obtener proveedores",
        "error"   => $e->getMessage()
    ]);
}
