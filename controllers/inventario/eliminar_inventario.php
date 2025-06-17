<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_delete.php';

$data = json_decode(file_get_contents("php://input"), true);

// Validar que venga el ID
if (!isset($data['id']) || !is_numeric($data['id'])) {
	http_response_code(400);
	echo json_encode(["error" => "Se requiere un ID válido para eliminar."]);
	exit;
}

try {
	$stmt = $pdo->prepare("DELETE FROM inventario WHERE id = :id");
	$stmt->execute(['id' => $data['id']]);

	if ($stmt->rowCount() > 0) {
		echo json_encode(["msg" => "Inventario eliminado correctamente"]);
	} else {
		http_response_code(404);
		echo json_encode(["error" => "Inventario no encontrado"]);
	}
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode(["error" => "Error al eliminar: " . $e->getMessage()]);
}
