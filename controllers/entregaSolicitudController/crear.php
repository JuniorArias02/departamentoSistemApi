<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';

try {
	$data = json_decode(file_get_contents("php://input"), true);

	if (!$data) {
		http_response_code(400);
		echo json_encode(["error" => "Cuerpo inválido"]);
		exit;
	}

	$camposRequeridos = ["consecutivo_id", "usuario_id", "fecha", "factura_proveedor"];
	foreach ($camposRequeridos as $campo) {
		if (!isset($data[$campo]) || $data[$campo] === "") {
			http_response_code(400);
			echo json_encode(["error" => "Falta el campo: $campo"]);
			exit;
		}
	}

	$usuarioId = $data["usuario_id"];

	// Validar permiso
	if (!tienePermiso($pdo, $usuarioId, PERMISOS['GESTION_COMPRA_PEDIDOS']['CREAR_ENTREGA_SOLICITUD'])) {
		http_response_code(403);
		echo json_encode(["error" => "No tienes permiso para crear entregas de solicitud"]);
		exit;
	}

	// Insertar con estado = 0 fijo
	$sql = "INSERT INTO cp_entrega_solicitud (consecutivo_id, fecha, factura_proveedor, estado, created_at)
	VALUES (?, ?, ?, 0, NOW())";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([
		$data["consecutivo_id"],
		$data["fecha"],
		$data["factura_proveedor"]
	]);

	echo json_encode([
		"success" => true,
		"message" => "Entrega creada correctamente"
	]);
} catch (Exception $e) {
	http_response_code(500);
	echo json_encode([
		"error" => "Error interno",
		"detalle" => $e->getMessage()
	]);
}
