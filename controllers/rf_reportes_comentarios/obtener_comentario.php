<?php
require_once __DIR__ . '/../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';

$data = json_decode(file_get_contents("php://input"), true);
$reporte_id = $data['reporte_id'] ?? null;

if (!$reporte_id) {
    echo json_encode([
        "success" => false,
        "message" => "reporte_id es obligatorio"
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.nombre_completo AS usuario_nombre
        FROM rf_reportes_comentarios c
        JOIN usuarios u ON c.usuario_id = u.id
        WHERE c.reporte_id = :reporte_id
        ORDER BY c.fecha ASC
    ");
    $stmt->execute([':reporte_id' => $reporte_id]);
    $comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "comentarios" => $comentarios
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
