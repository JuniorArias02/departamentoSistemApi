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
	$sql = "SELECT DATE(fecha_creacion) AS fecha, COUNT(*) AS total, us.nombre_completo FROM reactivo_vigilancia JOIN usuarios AS us ON reactivo_vigilancia.creado_por = us.id GROUP BY DATE(fecha_creacion), us.nombre_completo ORDER BY fecha ASC";
	$stmt = $pdo->query($sql);
	$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode($resultados);
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode(["error" => "Error al obtener datos de medicamentos: " . $e->getMessage()]);
}
