<?php
require_once __DIR__ . '/../../database/conexion.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';

$data = json_decode(file_get_contents("php://input"), true);
$usuario_id = $data['usuario_id'] ?? null;

if (!$usuario_id) {
    echo json_encode([
        "success" => false,
        "message" => "usuario_id es obligatorio"
    ]);
    exit;
}

// Validar permiso
if (!tienePermiso($pdo, $usuario_id, PERMISOS['REPORTES']['RECIBIR_REPORTES'])) {
    echo json_encode([
        "success" => false,
        "message" => "No tienes permiso para recibir reportes"
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT r.*, u.nombre_completo AS usuario_nombre
        FROM rf_reportes_fallas r
        JOIN usuarios u ON r.usuario_id = u.id
        WHERE r.responsable_id IS NULL
        ORDER BY r.fecha_reporte DESC
    ");
    $stmt->execute();
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
