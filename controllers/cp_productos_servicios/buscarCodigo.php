<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

header('Content-Type: application/json');

// Solo GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Método no permitido"]);
    exit;
}

// Obtener query (?q=texto)
$term = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($term === '') {
    echo json_encode(["success" => false, "error" => "Debes enviar un parámetro q"]);
    exit;
}

try {
    $sql = "SELECT codigo_producto 
            FROM cp_productos_servicios
            WHERE codigo_producto LIKE :term
               OR nombre LIKE :term
            LIMIT 20";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':term' => "%$term%"
    ]);

    $resultados = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        "success" => true,
        "data" => $resultados
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
