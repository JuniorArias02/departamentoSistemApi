<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';

try {
	$data = json_decode(file_get_contents("php://input"), true);
	$sede_id = $data['sede_id'] ?? null;

	if (!$sede_id) {
		http_response_code(400);
		echo json_encode([
			"status" => false,
			"message" => "El campo 'sede_id' es requerido"
		]);
		exit;
	}

	// Consulta de áreas por sede
	$stmt = $pdo->prepare("SELECT * FROM areas WHERE sede_id = ?");
	$stmt->execute([$sede_id]);
	$areas = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode([
		"status" => true,
		"data" => $areas
	]);
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode([
		"status" => false,
		"message" => "Error al obtener áreas: " . $e->getMessage()
	]);
}
