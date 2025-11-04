<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

// Recibir el cuerpo JSON
$data = json_decode(file_get_contents("php://input"), true);

// Validar que lleguen los campos
if (!isset($data["personal_id"], $data["sede_id"], $data["fecha_entrega"],$data["proceso_solicitante"])) {
	http_response_code(400);
	echo json_encode([
		"ok" => false,
		"error" => "Faltan campos obligatorios: personal_id, sede_id, fecha_entrega,proceso_solicitante"
	]);
	exit;
}

$personal_id   = (int)$data["personal_id"];
$sede_id       = (int)$data["sede_id"];
$fecha_entrega = $data["fecha_entrega"];
$proceso_solicitante = $data["proceso_solicitante"];
try {
	$sql = "INSERT INTO cp_entrega_activos_fijos (personal_id, sede_id, fecha_entrega, proceso_solicitante) 
            VALUES (:personal_id, :sede_id, :fecha_entrega, :proceso_solicitante)";
	$stmt = $pdo->prepare($sql);
	$stmt->bindParam(":personal_id", $personal_id, PDO::PARAM_INT);
	$stmt->bindParam(":sede_id", $sede_id, PDO::PARAM_INT);
	$stmt->bindParam(":fecha_entrega", $fecha_entrega, PDO::PARAM_STR);
	$stmt->bindParam(":proceso_solicitante", $proceso_solicitante, PDO::PARAM_STR);

	if ($stmt->execute()) {
		echo json_encode([
			"ok" => true,
			"id" => $pdo->lastInsertId()
		]);
	} else {
		http_response_code(500);
		echo json_encode([
			"ok" => false,
			"error" => "No se pudo insertar la entrega"
		]);
	}
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode([
		"ok" => false,
		"error" => $e->getMessage()
	]);
}
