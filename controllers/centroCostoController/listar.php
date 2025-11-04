<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

try {
    $sql = "SELECT 
                cc.id,
                cc.codigo,
                cc.nombre
            FROM cp_centro_costo cc
            ORDER BY cc.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $centros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => true,
        "data" => $centros
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => false,
        "error" => $e->getMessage()
    ]);
}