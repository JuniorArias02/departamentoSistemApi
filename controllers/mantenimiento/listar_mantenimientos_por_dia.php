<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_get.php';

$fecha = $_GET['fecha'] ?? null;
$usuario_id = isset($_GET['usuario_id']) ? (int) $_GET['usuario_id'] : null;


if (!$fecha) {
	http_response_code(400);
	echo json_encode(["error" => "Parámetro 'fecha' requerido"]);
	exit;
}

try {
	$inicioDia = date("Y-m-d 00:00:00", strtotime($fecha));
	$finDia = date("Y-m-d 23:59:59", strtotime($fecha));

	$sql = "SELECT a.id, a.titulo, a.descripcion, a.fecha_inicio, a.fecha_fin, m.esta_revisado 
	        FROM agenda_mantenimientos a
	        INNER JOIN mantenimientos m ON m.id = a.mantenimiento_id
	        WHERE a.fecha_inicio BETWEEN :inicio AND :fin";

	// Si se pasa un técnico específico
	if ($usuario_id !== null && $usuario_id !== '') {
		$sql .= " AND a.creado_por = :usuario_id";
	}


	$stmt = $pdo->prepare($sql);

	$params = [
		"inicio" => $inicioDia,
		"fin" => $finDia
	];

	if ($usuario_id !== null && $usuario_id !== '') {
		$params["usuario_id"] = $usuario_id;
	}

	error_log("SQL: " . $sql);
	error_log("Params: " . json_encode($params));

	$stmt->execute($params);

	echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode(["error" => "Error al consultar: " . $e->getMessage()]);
}
