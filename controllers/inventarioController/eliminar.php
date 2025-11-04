<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';
require_once __DIR__ . '/../utils/registrar_actividad.php';

$data = json_decode(file_get_contents("php://input"), true);

// Validar que vengan los datos
if (!isset($data['id'], $data['creado_por']) || !is_numeric($data['id'])) {
	http_response_code(400);
	echo json_encode(["error" => "Faltan datos requeridos o ID inválido."]);
	exit();
}

// Validar permiso
if (!tienePermiso($pdo, $data['creado_por'], PERMISOS['INVENTARIO']['ELIMINAR'])) {
	http_response_code(403);
	echo json_encode([
		"success" => false,
		"message" => "Acceso denegado. No tienes permiso para ELIMINAR inventario."
	]);
	exit();
}
try {
	// Traer el código del inventario antes de eliminar
	$stmt = $pdo->prepare("SELECT codigo FROM inventario WHERE id = :id");
	$stmt->execute(['id' => $data['id']]);
	$inventario = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$inventario) {
		http_response_code(404);
		echo json_encode(["success" => false, "message" => "Inventario no encontrado"]);
		exit();
	}

	// Eliminar
	$stmt = $pdo->prepare("DELETE FROM inventario WHERE id = :id");
	$stmt->execute(['id' => $data['id']]);

	if ($stmt->rowCount() > 0) {

		// Registrar actividad con código
		registrarActividad(
			$pdo,
			$data['creado_por'],
			"Eliminó el inventario con código {$inventario['codigo']}",
			"inventario",
			$data['id']
		);

		echo json_encode(["success" => true, "message" => "Inventario eliminado correctamente"]);
	} else {
		http_response_code(500);
		echo json_encode(["success" => false, "message" => "Error inesperado al eliminar"]);
	}
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode(["success" => false, "message" => "Error al eliminar: " . $e->getMessage()]);
}
