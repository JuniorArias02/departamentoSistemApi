<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php'; // <- usamos headers POST
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';

// Leer JSON del body
$data = json_decode(file_get_contents("php://input"), true);

// Verifica que venga el ID del usuario
if (!isset($data['creado_por'])) {
	http_response_code(400);
	echo json_encode([
		"success" => false,
		"message" => "Falta el campo 'creado_por'."
	]);
	exit();
}

// Validar permisos
if (!tienePermiso($pdo, $data['creado_por'], PERMISOS['INVENTARIO']['VER_DATOS'])) {
	http_response_code(403);
	echo json_encode([
		"success" => false,
		"message" => "Acceso denegado. No tienes permiso para ver datos de inventario."
	]);
	exit();
}

try {
	$sql = "SELECT i.*, s.nombre AS sede_nombre
			FROM inventario i
			LEFT JOIN sedes s ON i.sede_id = s.id
			ORDER BY i.fecha_creacion DESC";

	$stmt = $pdo->query($sql);
	$inventarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode($inventarios);
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode(["error" => "Error al obtener inventarios: " . $e->getMessage()]);
}
