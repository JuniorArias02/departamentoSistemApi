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

// Recibir query (?query=algo)
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
        SELECT 
            id,
            nombre_equipo,
            marca,
            modelo,
            serial,
            numero_inventario,
            estado
        FROM pc_equipos
        WHERE nombre_equipo LIKE :q
           OR serial LIKE :q
           OR numero_inventario LIKE :q
        ORDER BY nombre_equipo ASC
        LIMIT 10
    ");

    $stmt->execute([
        ':q' => "%$query%"
    ]);

    $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "resultados" => $equipos
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Error en la búsqueda: " . $e->getMessage()
    ]);
}
