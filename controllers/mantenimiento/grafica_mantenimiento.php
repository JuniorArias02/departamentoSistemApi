<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_get.php';

try {
	$sql = "SELECT DATE(fecha_creacion) AS fecha, COUNT(*) AS total, us.nombre_completo FROM mantenimientos_freezer JOIN usuarios AS us ON mantenimientos_freezer.creado_por = us.id GROUP BY DATE(fecha_creacion), us.nombre_completo ORDER BY fecha ASC";
	$stmt = $pdo->query($sql);
	$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode($resultados);
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode(["error" => "Error al obtener datos de inventario: " . $e->getMessage()]);
}
