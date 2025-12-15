<?php
require_once __DIR__ . '/../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_get.php';

try {
    $stmt = $pdo->query("
        SELECT id, nombre
        FROM p_cargo
        ORDER BY nombre ASC
    ");

    $cargos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => true,
        "data" => $cargos
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => false,
        "message" => "Error al cargar los cargos"
    ]);
}
