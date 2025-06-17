<?php
require_once '../../database/conexion.php';

header("Access-Control-Allow-Origin: https://formulario-medico.vercel.app");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit();
}

try {
	$sql = "SELECT s.nombre AS sede, COUNT(i.id) AS total_inventario 
	        FROM inventario i 
	        JOIN sedes s ON i.sede_id = s.id 
	        GROUP BY s.nombre 
	        ORDER BY total_inventario DESC";

	$stmt = $pdo->query($sql);
	$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// Reformatear los datos para el gráfico
	$datosFormateados = array_map(function ($row) {
		return [
			"name" => $row["sede"],
			"value" => (int)$row["total_inventario"]
		];
	}, $resultados);

	echo json_encode($datosFormateados);
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode(["error" => "Error al obtener datos de inventario: " . $e->getMessage()]);
}
