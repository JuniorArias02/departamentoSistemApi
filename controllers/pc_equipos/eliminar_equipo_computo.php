<?php
require_once __DIR__ . '/../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../utils/registrar_actividad.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';

$data = json_decode(file_get_contents("php://input"), true);
$equipo_id = $data["equipo_id"] ?? null;
$usuario_id = $data["usuario_id"] ?? null;

if (!$equipo_id || !$usuario_id) {
	echo json_encode([
		"status" => false,
		"message" => "Faltan datos necesarios"
	]);
	exit;
}

if (!tienePermiso($pdo, $usuario_id, PERMISOS['GESTION_EQUIPOS']['ELIMINAR'])) {
	http_response_code(403);
	echo json_encode([
		"success" => false,
		"message" => "Acceso denegado. No tienes permiso para eliminar inventario."
	]);
	exit();
}


try {
	$pdo->beginTransaction();

	// Eliminar registros relacionados primero (dependencias)
	$pdo->prepare("DELETE FROM pc_licencias_software WHERE equipo_id = ?")->execute([$equipo_id]);
	$pdo->prepare("DELETE FROM pc_caracteristicas_tecnicas WHERE equipo_id = ?")->execute([$equipo_id]);

	// Eliminar el equipo
	$stmt = $pdo->prepare("DELETE FROM pc_equipos WHERE id = ?");
	$stmt->execute([$equipo_id]);

	// Registrar actividad
	registrarActividad($pdo, $usuario_id, "Eliminación", "pc_equipos", $equipo_id);

	$pdo->commit();

	echo json_encode([
		"status" => true,
		"message" => "Equipo eliminado correctamente"
	]);
} catch (PDOException $e) {
	$pdo->rollBack();
	echo json_encode([
		"status" => false,
		"message" => "Error al eliminar el equipo: " . $e->getMessage()
	]);
}
