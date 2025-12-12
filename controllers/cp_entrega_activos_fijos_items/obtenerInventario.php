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

$input = json_decode(file_get_contents("php://input"), true);

// Campos esperados
$requiredFields = [
    "responsable_id",
    "coordinador_id",
    "dependencia_id"
];

$missing = [];

// Validar cuáles faltan
foreach ($requiredFields as $field) {
    if (!isset($input[$field]) || $input[$field] === "" || $input[$field] === null) {
        $missing[] = $field;
    }
}

if (!empty($missing)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Faltan campos obligatorios",
        "missing_fields" => $missing
    ]);
    exit;
}

$responsable_id = $input['responsable_id'];
$coordinador_id = $input['coordinador_id'];
$proceso_id     = $input['dependencia_id'];

$sql = "SELECT * FROM inventario 
        WHERE coordinador_id = :coordinador_id
        AND responsable_id = :responsable_id
        AND proceso_id = :proceso_id";

$stmt = $pdo->prepare($sql);

$stmt->bindParam(':coordinador_id', $coordinador_id, PDO::PARAM_INT);
$stmt->bindParam(':responsable_id', $responsable_id, PDO::PARAM_INT);
$stmt->bindParam(':proceso_id', $proceso_id, PDO::PARAM_INT);

$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "data" => $data
]);
