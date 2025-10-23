<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

header("Content-Type: application/json");

try {
    $input = json_decode(file_get_contents("php://input"), true);
    $sede_id = $input['sede_id'] ?? $_GET['sede_id'] ?? null;

    if (!$sede_id) {
        echo json_encode([
            "status" => false,
            "message" => "Debe enviar el parámetro sede_id"
        ]);
        exit;
    }

    $sql = "SELECT id, codigo, nombre, sede_id 
            FROM cp_dependencias 
            WHERE sede_id = :sede_id
            ORDER BY nombre ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":sede_id", $sede_id, PDO::PARAM_INT);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => true,
        "data" => $data
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => false,
        "message" => "Error al listar dependencias: " . $e->getMessage()
    ]);
}
