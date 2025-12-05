<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

header('Content-Type: application/json');

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Método no permitido"]);
    exit;
}

// Recibir JSON
$input = json_decode(file_get_contents("php://input"), true);

$coordinador_id = $input['coordinador_id'] ?? null;
$dependencia_id = $input['dependencia_id'] ?? null;
$sede_id        = $input['sede_id'] ?? null;

// Validar
if (!$coordinador_id || !$dependencia_id || !$sede_id) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Faltan filtros obligatorios: coordinador_id, dependencia_id, sede_id"
    ]);
    exit;
}

try {

    $sql = "SELECT * FROM inventario 
            WHERE coordinador_id = :coordinador_id
            AND dependencia = :dependencia_id
            AND sede_id = :sede_id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':coordinador_id', $coordinador_id, PDO::PARAM_INT);
    $stmt->bindParam(':dependencia_id', $dependencia_id, PDO::PARAM_INT);
    $stmt->bindParam(':sede_id', $sede_id, PDO::PARAM_INT);

    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => $data
    ]);
} catch (Exception $e) {

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Error en el servidor",
        "details" => $e->getMessage()
    ]);
}
