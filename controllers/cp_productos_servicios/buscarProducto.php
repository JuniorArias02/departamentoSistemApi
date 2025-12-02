<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Método no permitido"]);
    exit;
}

$query = isset($_GET['query']) ? trim($_GET['query']) : null;

if (!$query || strlen($query) < 2) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Debes enviar mínimo 2 caracteres para buscar."
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, codigo_producto, nombre
        FROM cp_productos_servicios
        WHERE codigo_producto LIKE :q
           OR nombre LIKE :q
        ORDER BY nombre ASC
        LIMIT 20
    ");

    $stmt->execute([
        ':q' => "%$query%"
    ]);

    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "resultados" => $productos
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Error en la búsqueda: " . $e->getMessage()
    ]);
}
