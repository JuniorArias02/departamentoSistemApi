<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_delete.php'; // Si tienes headers para DELETE

// Leer el id desde query o desde JSON
$input = json_decode(file_get_contents("php://input"), true);

if (isset($_GET['id'])) {
    $id = $_GET['id'];
} elseif (isset($input['id'])) {
    $id = $input['id'];
} else {
    http_response_code(400);
    echo json_encode(["error" => "El parámetro 'id' es obligatorio"]);
    exit;
}

if (!is_numeric($id)) {
    http_response_code(400);
    echo json_encode(["error" => "El id debe ser numérico"]);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM cp_tipo_solicitud WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute() && $stmt->rowCount() > 0) {
        echo json_encode([
            "success" => true,
            "message" => "Tipo de solicitud eliminado correctamente"
        ]);
    } else {
        http_response_code(404);
        echo json_encode(["error" => "No se encontró el tipo de solicitud"]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
