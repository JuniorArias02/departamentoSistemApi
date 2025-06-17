<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_get.php';

try {

    $sql = "SELECT COUNT(*) AS total FROM mantenimientos";
    $stmt = $pdo->query($sql);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(["total" => (int)$resultado['total']]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error al contar mantenimientos: " . $e->getMessage()]);
}