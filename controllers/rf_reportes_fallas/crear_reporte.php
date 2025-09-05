<?php
require_once __DIR__ . '/../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || empty($data['usuario_id']) || empty($data['titulo']) || empty($data['descripcion'])) {
    echo json_encode([
        "success" => false,
        "message" => "usuario_id, titulo y descripcion son obligatorios"
    ]);
    exit;
}

try {
    $prioridad = $data['prioridad'] ?? 'media';
    $stmt = $pdo->prepare("
        INSERT INTO rf_reportes_fallas (usuario_id, titulo, descripcion, prioridad, fecha_reporte)
        VALUES (:usuario_id, :titulo, :descripcion, :prioridad, NOW())
    ");
    $stmt->execute([
        ':usuario_id' => $data['usuario_id'],
        ':titulo' => $data['titulo'],
        ':descripcion' => $data['descripcion'],
        ':prioridad' => $prioridad
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Reporte creado con éxito",
        "reporte_id" => $pdo->lastInsertId()
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
