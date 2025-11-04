<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id_usuario']) || empty($data['id_usuario'])) {
	http_response_code(400);
	echo json_encode(["error" => "El id_usuario es obligatorio."]);
	exit;
}

try {
	if (tienePermiso($pdo, $data['id_usuario'], PERMISOS['INVENTARIO']['GRAFICAR_INVENTARIO_PROPIA_SEDE'])) {
		// Filtra por sede del usuario
		$sql = "
			SELECT 
				DATE(i.fecha_creacion) AS fecha, 
				COUNT(*) AS total, 
				us.nombre_completo
			FROM inventario AS i
			JOIN usuarios AS us 
				ON i.creado_por = us.id
			JOIN sedes AS s
				ON i.sede_id = s.id
			WHERE i.sede_id = (
				SELECT sede_id FROM usuarios WHERE id = :id_usuario
			)
			GROUP BY DATE(i.fecha_creacion), us.nombre_completo
			ORDER BY fecha ASC
		";
		$stmt = $pdo->prepare($sql);
		$stmt->bindValue(':id_usuario', $data['id_usuario'], PDO::PARAM_INT);
		$stmt->execute();
	} else {
		// Sin filtro: trae todo
		$sql = "
			SELECT 
				DATE(fecha_creacion) AS fecha, 
				COUNT(*) AS total, 
				us.nombre_completo
			FROM inventario
			JOIN usuarios AS us 
				ON inventario.creado_por = us.id
			GROUP BY DATE(fecha_creacion), us.nombre_completo
			ORDER BY fecha ASC
		";
		$stmt = $pdo->query($sql);
	}

	$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
	echo json_encode($resultados);
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode(["error" => "Error al obtener datos de inventario: " . $e->getMessage()]);
}
