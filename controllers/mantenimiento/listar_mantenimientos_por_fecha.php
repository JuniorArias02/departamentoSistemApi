<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_get.php';

// Obtener mes y año desde parámetros GET
$fecha = $_GET['fecha'] ?? null;

if (!$fecha) {
	http_response_code(400);
	echo json_encode(["error" => "Parámetro 'fecha' requerido"]);
	exit;
}

try {
	$inicioMes = date("Y-m-01 00:00:00", strtotime($fecha));
	$finMes = date("Y-m-t 23:59:59", strtotime($fecha));

	$sql = "SELECT 
			a.id, 
			a.titulo, 
			a.descripcion, 
			a.fecha_inicio, 
			a.fecha_fin,
			m.esta_revisado 
		FROM agenda_mantenimientos a
		INNER JOIN mantenimientos m ON m.id = a.mantenimiento_id
		WHERE a.fecha_inicio BETWEEN :inicio AND :fin";


	$stmt = $pdo->prepare($sql);
	$stmt->execute([
		"inicio" => $inicioMes,
		"fin" => $finMes
	]);

	$resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
	echo json_encode($resultado);
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode(["error" => "Error al consultar: " . $e->getMessage()]);
}
