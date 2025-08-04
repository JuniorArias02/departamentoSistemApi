<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_get.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';

$fecha = $_GET['fecha'] ?? null;
$usuarioId = $_GET['usuario_id'] ?? null;

if (!$fecha || !$usuarioId) {
	http_response_code(400);
	echo json_encode(["error" => "Parámetros 'fecha' y 'usuario_id' son requeridos"]);
	exit;
}

// Validación de permisos generales
if (!tienePermiso($pdo, $usuarioId, PERMISOS['AGENDAMIENTO_MANTENIMIENTOS']['VER_CALENDARIO'])) {
	http_response_code(403);
	echo json_encode(["error" => "No tienes permiso para ver el calendario de mantenimientos."]);
	exit;
}

try {
	$inicioMes = date("Y-m-01 00:00:00", strtotime($fecha));
	$finMes = date("Y-m-t 23:59:59", strtotime($fecha));

	$sql = "SELECT 
		a.id AS agenda_id,
		a.titulo AS agenda_titulo,
		a.descripcion AS agenda_descripcion,
		a.fecha_inicio,
		a.fecha_fin,
		a.sede_id AS agenda_sede_id,
		m.id AS mantenimiento_id,
		m.titulo AS mantenimiento_titulo,
		m.codigo,
		m.modelo,
		m.dependencia,
		m.sede_id,
		m.nombre_receptor,
		m.descripcion AS mantenimiento_descripcion,
		m.imagen,
		m.esta_revisado,
		m.fecha_ultima_actualizacion,
		u.nombre_completo AS asignado_a
	FROM agenda_mantenimientos a
	INNER JOIN mantenimientos m ON m.id = a.mantenimiento_id
	LEFT JOIN usuarios u ON a.creado_por = u.id
	WHERE a.fecha_inicio BETWEEN :inicio AND :fin";

	$params = [
		"inicio" => $inicioMes,
		"fin" => $finMes
	];

	// Si NO tiene permiso para ver todos, filtrar por agendado_por
	if (!tienePermiso($pdo, $usuarioId, PERMISOS['MANTENIMIENTOS']['VER_TODOS_EVENTOS_AGENDADOS'])) {
		$sql .= " AND a.creado_por = :usuario_id";
		$params["usuario_id"] = $usuarioId;
	}

	$stmt = $pdo->prepare($sql);
	$stmt->execute($params);

	$resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// 🔄 Parsear imágenes
	foreach ($resultado as &$item) {
		$item['imagen'] = !empty($item['imagen']) ? json_decode($item['imagen'], true) : [];
	}

	echo json_encode($resultado);
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode(["error" => "Error al consultar: " . $e->getMessage()]);
}
