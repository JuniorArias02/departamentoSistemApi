<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_get.php';

try {
	$sql = "SELECT i.*, s.nombre AS sede_nombre
			FROM inventario i
			LEFT JOIN sedes s ON i.sede_id = s.id
			ORDER BY i.fecha_creacion DESC";

	$stmt = $pdo->query($sql);
	$inventarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode($inventarios);
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode(["error" => "Error al obtener inventarios: " . $e->getMessage()]);
}
