<?php
require_once __DIR__ . '/../../../database/conexion.php';
require_once __DIR__ . '/../../../middlewares/headers_post.php';

$input = json_decode(file_get_contents("php://input"), true);

// Validaciones
if (!isset($input['rol_id']) || !isset($input['permiso_id'])) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Faltan parámetros requeridos (rol_id o permiso_id)"
    ]);
    exit();
}

$rol_id = intval($input['rol_id']);
$permiso_id = intval($input['permiso_id']);

try {
    // Verificar si ya existe la relación
    $checkSql = "SELECT id FROM rol_permisos WHERE rol_id = :rol_id AND permiso_id = :permiso_id";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([':rol_id' => $rol_id, ':permiso_id' => $permiso_id]);
    
    if ($checkStmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "error" => "Este permiso ya está asignado al rol"
        ]);
        exit();
    }

    // Insertar nueva relación
    $insertSql = "INSERT INTO rol_permisos (rol_id, permiso_id) VALUES (:rol_id, :permiso_id)";
    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->execute([':rol_id' => $rol_id, ':permiso_id' => $permiso_id]);
    
    $id = $pdo->lastInsertId();

    echo json_encode([
        "success" => true,
        "id" => $id,
        "message" => "Permiso asignado al rol correctamente"
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Error en la base de datos",
        "details" => $e->getMessage()
    ]);
}
?>