<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id_usuario']) || empty($data['id_usuario'])) {
	http_response_code(400);
	echo json_encode(["error" => "El id_usuario es obligatorio."]);
	exit;
}

try {
	if (tienePermiso($pdo, $data['id_usuario'], PERMISOS['MANTENIMIENTOS']['GRAFICAR_MANTENIMIENTO_PROPIA_SEDE'])) {
		// Consulta filtrando por sede del usuario
		$sql = "
			SELECT 
				DATE(m.fecha_creacion) AS fecha, 
				COUNT(*) AS total, 
				us.nombre_completo
			FROM mantenimientos AS m
			JOIN usuarios AS us 
				ON m.creado_por = us.id
			JOIN sedes AS s
				ON m.sede_id = s.id
			WHERE m.sede_id = (
				SELECT sede_id FROM usuarios WHERE id = :id_usuario
			)
			GROUP BY DATE(m.fecha_creacion), us.nombre_completo
			ORDER BY fecha ASC
		";
		$stmt = $pdo->prepare($sql);
		$stmt->bindValue(':id_usuario', $data['id_usuario'], PDO::PARAM_INT);
		$stmt->execute();
	} else {
		$sql = "
			SELECT 
				DATE(fecha_creacion) AS fecha, 
				COUNT(*) AS total, 
				us.nombre_completo
			FROM mantenimientos
			JOIN usuarios AS us 
				ON mantenimientos.creado_por = us.id
			GROUP BY DATE(fecha_creacion), us.nombre_completo
			ORDER BY fecha ASC
		";
		$stmt = $pdo->query($sql);
	}

	$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
	echo json_encode($resultados);

} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode(["error" => "Error al obtener datos: " . $e->getMessage()]);
}
