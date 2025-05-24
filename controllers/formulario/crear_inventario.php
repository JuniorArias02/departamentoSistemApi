<?php
require_once '../../database/conexion.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit();
}

$data = json_decode(file_get_contents("php://input"), true);

// Validar campos obligatorios
$campos = ['codigo', 'nombre', 'creado_por'];
foreach ($campos as $campo) {
	if (!isset($data[$campo]) || trim($data[$campo]) === '') {
		http_response_code(400);
		echo json_encode(["error" => "El campo '$campo' es obligatorio."]);
		exit;
	}
}

try {
	if (!empty($data['id'])) {
		// EDITAR INVENTARIO
		$stmt = $pdo->prepare("UPDATE inventario SET 
			codigo = :codigo,
			nombre = :nombre,
			dependencia = :dependencia,
			responsable = :responsable,
			marca = :marca,
			modelo = :modelo,
			serial = :serial,
			sede_id = :sede_id
			WHERE id = :id");

		$stmt->execute([
			"codigo" => $data["codigo"],
			"nombre" => $data["nombre"],
			"dependencia" => $data["dependencia"] ?? null,
			"responsable" => $data["responsable"] ?? null,
			"marca" => $data["marca"] ?? null,
			"modelo" => $data["modelo"] ?? null,
			"serial" => $data["serial"] ?? null,
			"sede_id" => $data["sede_id"] ?? null,
			"id" => $data["id"]
		]);

		echo json_encode(["msg" => "Inventario actualizado con éxito"]);
	} else {
		// CREAR INVENTARIO
		$stmt = $pdo->prepare("INSERT INTO inventario 
			(codigo, nombre, dependencia, responsable, marca, modelo, serial, sede_id, creado_por)
			VALUES 
			(:codigo, :nombre, :dependencia, :responsable, :marca, :modelo, :serial, :sede_id, :creado_por)");

		$stmt->execute([
			"codigo" => $data["codigo"],
			"nombre" => $data["nombre"],
			"dependencia" => $data["dependencia"] ?? null,
			"responsable" => $data["responsable"] ?? null,
			"marca" => $data["marca"] ?? null,
			"modelo" => $data["modelo"] ?? null,
			"serial" => $data["serial"] ?? null,
			"sede_id" => $data["sede_id"] ?? null,
			"creado_por" => $data["creado_por"]
		]);

		echo json_encode(["msg" => "Inventario registrado con éxito"]);
	}
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode(["error" => "Error al guardar el inventario: " . $e->getMessage()]);
}
