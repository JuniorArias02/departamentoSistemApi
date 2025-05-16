<?php
require_once '../../database/conexion.php';

// Headers para CORS y tipo JSON
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit();
}

// Obtener datos JSON del request
$data = json_decode(file_get_contents("php://input"), true);

// Validar campos obligatorios
$campos = [
	"nombre", "marca", "presentacion_comercial", "registro_sanitario",
	"clasificacion_riesgo", "vida_util", "fecha_vencimiento", "lote", "creado_por"
];

foreach ($campos as $campo) {
	if (!isset($data[$campo]) || trim($data[$campo]) === "") {
		http_response_code(400);
		echo json_encode(["error" => "El campo '$campo' es obligatorio."]);
		exit;
	}
}

try {
	if (!empty($data['id'])) {
		// EDITAR reactivo existente
		$stmt = $pdo->prepare("UPDATE reactivo_vigilancia SET 
			nombre = :nombre,
			marca = :marca,
			presentacion_comercial = :presentacion_comercial,
			registro_sanitario = :registro_sanitario,
			clasificacion_riesgo = :clasificacion_riesgo,
			vida_util = :vida_util,
			fecha_vencimiento = :fecha_vencimiento,
			lote = :lote
			WHERE id = :id");

		$stmt->execute([
			"nombre" => $data["nombre"],
			"marca" => $data["marca"],
			"presentacion_comercial" => $data["presentacion_comercial"],
			"registro_sanitario" => $data["registro_sanitario"],
			"clasificacion_riesgo" => $data["clasificacion_riesgo"],
			"vida_util" => $data["vida_util"],
			"fecha_vencimiento" => $data["fecha_vencimiento"],
			"lote" => $data["lote"],
			"id" => $data["id"]
		]);

		echo json_encode(["msg" => "Reactivo actualizado con éxito"]);
	} else {
		// CREAR nuevo reactivo
		$stmt = $pdo->prepare("INSERT INTO reactivo_vigilancia
			(nombre, marca, presentacion_comercial, registro_sanitario, clasificacion_riesgo, vida_util, fecha_vencimiento, lote, creado_por)
			VALUES (:nombre, :marca, :presentacion_comercial, :registro_sanitario, :clasificacion_riesgo, :vida_util, :fecha_vencimiento, :lote, :creado_por)");

		$stmt->execute([
			"nombre" => $data["nombre"],
			"marca" => $data["marca"],
			"presentacion_comercial" => $data["presentacion_comercial"],
			"registro_sanitario" => $data["registro_sanitario"],
			"clasificacion_riesgo" => $data["clasificacion_riesgo"],
			"vida_util" => $data["vida_util"],
			"fecha_vencimiento" => $data["fecha_vencimiento"],
			"lote" => $data["lote"],
			"creado_por" => $data["creado_por"]
		]);

		echo json_encode(["msg" => "Reactivo registrado con éxito"]);
	}
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode(["error" => "Error al guardar el reactivo: " . $e->getMessage()]);
}
