<?php
require_once __DIR__ . '/../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';

try {
    $stmt = $pdo->prepare("
    SELECT
    p.id,
    p.nombre,
    p.cedula,
    c.nombre as cargo
    FROM personal as p
    LEFT JOIN p_cargo as c ON p.cargo_id = c.id
    
    ");
    $stmt->execute();
    $personales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => true,
        "data" => $personales
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => false,
        "message" => "Error al obtener personales: " . $e->getMessage()
    ]);
}
