<?php
require_once __DIR__ . '/../../database/conexion.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';

// Leer JSON del body
$data = json_decode(file_get_contents("php://input"), true);
$usuario_id = $data['usuario_id'] ?? null;
$reporte_id = $data['reporte_id'] ?? null;

if (!$usuario_id || !$reporte_id) {
	echo json_encode([
		"success" => false,
		"message" => "usuario_id y reporte_id son obligatorios"
	]);
	exit;
}

// Validar permiso
if (!tienePermiso($pdo, $usuario_id, PERMISOS['REPORTES']['RECIBIR_REPORTES'])) {
	echo json_encode([
		"success" => false,
		"message" => "No tienes permiso para tomar reportes"
	]);
	exit;
}

try {
	// Intentar asignar el reporte solo si nadie lo tomó
	$stmt = $pdo->prepare("
        UPDATE rf_reportes_fallas
        SET responsable_id = :usuario_id, estado = 'en proceso'
        WHERE id = :reporte_id AND responsable_id IS NULL
    ");
	$stmt->execute([
		':usuario_id' => $usuario_id,
		':reporte_id' => $reporte_id
	]);

	if ($stmt->rowCount() > 0) {
		echo json_encode([
			"success" => true,
			"message" => "Reporte tomado con éxito"
		]);
	} else {
		echo json_encode([
			"success" => false,
			"message" => "El reporte ya fue tomado por otro usuario"
		]);
	}
} catch (Exception $e) {
	echo json_encode([
		"success" => false,
		"message" => "Error: " . $e->getMessage()
	]);
}
