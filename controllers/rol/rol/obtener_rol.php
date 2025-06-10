<?php
require_once '../../../database/conexion.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
	http_response_code(200);
	exit();
}
// Consulta para obtener todos los permisos
$sql = "SELECT id, nombre FROM rol ORDER BY nombre";

try {
	$stmt = $pdo->query($sql);
	$permisos = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode([
		"success" => true,
		"rol" => $permisos,
		"count" => count($permisos)
	]);
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode([
		"success" => false,
		"error" => "Error en la base de datos",
		"detalles" => $e->getMessage()
	]);
}
