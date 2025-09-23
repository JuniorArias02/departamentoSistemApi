<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';

try {
	$data = json_decode(file_get_contents("php://input"), true);

	if (!$data) {
		http_response_code(400);
		echo json_encode(["error" => "No se recibieron datos válidos"]);
		exit;
	}

	$usuario_id = $data['usuario_id'];

	if (!tienePermiso($pdo, $usuario_id, PERMISOS['GESTION_COMPRA_PEDIDOS']['CREAR_DESCUENTO_FIJOS'])) {
		http_response_code(403);
		echo json_encode([
			"success" => false,
			"message" => "Acceso denegado. No tienes permiso para crear una solicitud de descuento."
		]);
		exit();
	}


	// Campos requeridos mínimos
	$required = [
		"entrega_fijos_id",
		"consecutivo",
		"fecha_solicitud",
		"trabajador_id",
		"tipo_contrato",
		"motivo_solicitud",
		"valor_total_descontar",
		"numero_cuotas",
		"numero_cuotas_aprobadas"
	];

	foreach ($required as $field) {
		if (!isset($data[$field]) || $data[$field] === "") {
			http_response_code(400);
			echo json_encode(["error" => "Falta el campo requerido: $field"]);
			exit;
		}
	}

	$sql = "INSERT INTO cp_solicitud_descuento 
        (entrega_fijos_id, consecutivo, fecha_solicitud, trabajador_id, tipo_contrato, 
         motivo_solicitud, valor_total_descontar,numero_cuotas_aprobadas, numero_cuotas, observaciones,
         personal_responsable_aprobacion, jefe_inmediato_id, personal_facturacion, 
         personal_gestion_financiera, personal_talento_humano)
        VALUES 
        (:entrega_fijos_id, :consecutivo, :fecha_solicitud, :trabajador_id, :tipo_contrato, 
         :motivo_solicitud, :valor_total_descontar,:numero_cuotas_aprobadas, :numero_cuotas, :observaciones,
         :personal_responsable_aprobacion, :jefe_inmediato_id, :personal_facturacion, 
         :personal_gestion_financiera, :personal_talento_humano)";

	$stmt = $pdo->prepare($sql);
	$stmt->execute([
		":entrega_fijos_id" => $data["entrega_fijos_id"],
		":consecutivo" => $data["consecutivo"],
		":fecha_solicitud" => $data["fecha_solicitud"],
		":trabajador_id" => $data["trabajador_id"],
		":tipo_contrato" => $data["tipo_contrato"],
		":motivo_solicitud" => $data["motivo_solicitud"],
		":valor_total_descontar" => $data["valor_total_descontar"],
		":numero_cuotas_aprobadas" => $data["numero_cuotas_aprobadas"],
		":numero_cuotas" => $data["numero_cuotas"],
		":observaciones" => $data["observaciones"] ?? null,
		":personal_responsable_aprobacion" => $data["personal_responsable_aprobacion"] ?? null,
		":jefe_inmediato_id" => $data["jefe_inmediato_id"] ?? null,
		":personal_facturacion" => $data["personal_facturacion"] ?? null,
		":personal_gestion_financiera" => $data["personal_gestion_financiera"] ?? null,
		":personal_talento_humano" => $data["personal_talento_humano"] ?? null
	]);

	$lastId = $pdo->lastInsertId();

	echo json_encode([
		"success" => true,
		"id" => $lastId
	]);
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode([
		"error" => "Error en la base de datos",
		"detalle" => $e->getMessage()
	]);
}
