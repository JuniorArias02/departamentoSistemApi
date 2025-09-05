<?php
require_once __DIR__ . '/../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';

$data = json_decode(file_get_contents("php://input"), true);
$usuario_id = $data['usuario_id'] ?? null;
$estado = $data['estado'] ?? null;

if (!$usuario_id) {
    echo json_encode([
        "success" => false,
        "message" => "usuario_id es obligatorio"
    ]);
    exit;
}

try {
    $query = "
        SELECT r.*, u.nombre_completo AS usuario_nombre
        FROM rf_reportes_fallas r
        JOIN usuarios u ON r.usuario_id = u.id
        WHERE r.responsable_id = :usuario_id
    ";
    $params = [':usuario_id' => $usuario_id];

    if ($estado) {
        $query .= " AND r.estado = :estado";
        $params[':estado'] = $estado;
    }

    $query .= " ORDER BY r.fecha_reporte DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "reportes" => $reportes
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
