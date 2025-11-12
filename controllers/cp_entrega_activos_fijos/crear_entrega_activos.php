<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

$data = json_decode(file_get_contents("php://input"), true);

// Validar campos obligatorios
if (!isset($data["personal_id"], $data["sede_id"], $data["fecha_entrega"], $data["proceso_solicitante"])) {
	http_response_code(400);
	echo json_encode([
		"ok" => false,
		"error" => "Faltan campos obligatorios: personal_id, sede_id, fecha_entrega, proceso_solicitante"
	]);
	exit;
}

// Variables
$id                 = isset($data["id"]) ? (int)$data["id"] : null;
$personal_id        = (int)$data["personal_id"];
$sede_id            = (int)$data["sede_id"];
$fecha_entrega      = $data["fecha_entrega"];
$proceso_solicitante = $data["proceso_solicitante"];
$coordinador_id =  (int)$data["coordinador_id"];

try {
	if ($id) {
		$sql = "UPDATE cp_entrega_activos_fijos 
				SET personal_id = :personal_id, 
					sede_id = :sede_id, 
					fecha_entrega = :fecha_entrega,
					proceso_solicitante = :proceso_solicitante
				WHERE id = :id";
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(":id", $id, PDO::PARAM_INT);
	} else {
		$sql = "INSERT INTO cp_entrega_activos_fijos (personal_id, coordinador_id, sede_id, fecha_entrega, proceso_solicitante)
				VALUES (:personal_id,:coordinador_id , :sede_id, :fecha_entrega, :proceso_solicitante)";
		$stmt = $pdo->prepare($sql);
	}

	// Bind comunes
	$stmt->bindParam(":personal_id", $personal_id, PDO::PARAM_INT);
	$stmt->bindParam(":coordinador_id", $coordinador_id, PDO::PARAM_INT);
	$stmt->bindParam(":sede_id", $sede_id, PDO::PARAM_INT);
	$stmt->bindParam(":fecha_entrega", $fecha_entrega, PDO::PARAM_STR);
	$stmt->bindParam(":proceso_solicitante", $proceso_solicitante, PDO::PARAM_STR);

	if ($stmt->execute()) {
		if ($id) {
			echo json_encode([
				"ok" => true,
				"message" => "Registro actualizado correctamente",
				"id" => $id
			]);
		} else {
			echo json_encode([
				"ok" => true,
				"message" => "Registro creado correctamente",
				"id" => $pdo->lastInsertId()
			]);
		}
	} else {
		http_response_code(500);
		echo json_encode([
			"ok" => false,
			"error" => "Error al guardar los datos"
		]);
	}
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode([
		"ok" => false,
		"error" => $e->getMessage()
	]);
}
