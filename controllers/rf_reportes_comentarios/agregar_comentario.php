<?php
require_once __DIR__ . '/../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';

// Recibimos los datos
$data = json_decode(file_get_contents("php://input"), true);
$reporte_id   = $data['reporte_id'] ?? null;
$usuario_id   = $data['usuario_id'] ?? null;
$comentario   = trim($data['comentario'] ?? '');

if (!$reporte_id || !$usuario_id || !$comentario) {
    echo json_encode([
        "success" => false,
        "message" => "reporte_id, usuario_id y comentario son obligatorios"
    ]);
    exit;
}

try {
    // Insertamos el comentario
    $stmt = $pdo->prepare("
        INSERT INTO rf_reportes_comentarios (reporte_id, usuario_id, comentario, fecha)
        VALUES (:reporte_id, :usuario_id, :comentario, NOW())
    ");
    $stmt->execute([
        ':reporte_id' => $reporte_id,
        ':usuario_id' => $usuario_id,
        ':comentario' => $comentario
    ]);

    // Obtenemos el comentario recién creado con el nombre del usuario
    $comentario_id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("
        SELECT c.*, u.nombre_completo AS usuario_nombre
        FROM rf_reportes_comentarios c
        JOIN usuarios u ON c.usuario_id = u.id
        WHERE c.id = :id
    ");
    $stmt->execute([':id' => $comentario_id]);
    $comentario_completo = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "comentario" => $comentario_completo
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
