<?php
require_once '../../database/conexion.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit();
}

try {
	$sql = "SELECT COUNT(*) AS total FROM inventario";
	$stmt = $pdo->query($sql);
	$resultado = $stmt->fetch(PDO::FETCH_ASSOC);

	echo json_encode(["total" => (int)$resultado['total']]);
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode(["error" => "Error al contar inventarios: " . $e->getMessage()]);
}
