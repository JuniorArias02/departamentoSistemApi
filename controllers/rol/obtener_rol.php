<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_getPost.php';

// Consulta para obtener todos los permisos

try {
	$sql = "SELECT id, nombre FROM rol ORDER BY nombre";
	$stmt = $pdo->query($sql);
	$permisos = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode([
		"success" => true,
		"rol" => $permisos,
		"count" => count($permisos)
	]);
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode([
		"success" => false,
		"error" => "Error en la base de datos",
		"detalles" => $e->getMessage()
	]);
}
