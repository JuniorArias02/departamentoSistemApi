<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: PUT, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../database/conexion.php';
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

// Validar campos obligatorios
if (!isset($data['id']) || !isset($data['usuario_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Se requieren el ID del mantenimiento y el ID del usuario revisor"]);
    exit;
}

try {
    // Verificar que el mantenimiento existe y no está ya revisado
    $stmtCheck = $pdo->prepare("SELECT revisado_por FROM mantenimientos WHERE id = :id");
    $stmtCheck->execute(["id" => $data["id"]]);
    $mantenimiento = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$mantenimiento) {
        http_response_code(404);
        echo json_encode(["error" => "Mantenimiento no encontrado"]);
        exit;
    }
    
    if ($mantenimiento['revisado_por'] !== null) {
        http_response_code(409);
        echo json_encode(["error" => "El mantenimiento ya fue revisado anteriormente"]);
        exit;
    }

    // Marcar como revisado
    $stmt = $pdo->prepare("UPDATE mantenimientos SET 
        revisado_por = :usuario_id,
        fecha_revisado = NOW(),
        esta_revisado = TRUE
        WHERE id = :id");

    $stmt->execute([
        "usuario_id" => $data["usuario_id"],
        "id" => $data["id"]
    ]);

    echo json_encode([
        "msg" => "Mantenimiento marcado como revisado con éxito",
        "fecha_revisado" => date("Y-m-d H:i:s")
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error al actualizar el mantenimiento: " . $e->getMessage()]);
}