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

// Validar campo obligatorio
if (!isset($data['nombre']) || trim($data['nombre']) === '') {
	http_response_code(400);
	echo json_encode(["error" => "El campo 'nombre' es obligatorio."]);
	exit;
}

try {
	if (!empty($data['id'])) {
		// Editar
		$stmt = $pdo->prepare("UPDATE sedes SET nombre = :nombre WHERE id = :id");
		$stmt->execute([
			"nombre" => $data["nombre"],
			"id" => $data["id"]
		]);
		echo json_encode(["msg" => "Sede actualizada con éxito"]);
	} else {
		// Crear
		$stmt = $pdo->prepare("INSERT INTO sedes (nombre) VALUES (:nombre)");
		$stmt->execute(["nombre" => $data["nombre"]]);
		echo json_encode(["msg" => "Sede registrada con éxito"]);
	}
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode(["error" => "Error al guardar la sede: " . $e->getMessage()]);
}
