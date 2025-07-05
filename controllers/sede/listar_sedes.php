<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_get.php';

try {
	$stmt = $pdo->query("SELECT * FROM sedes ORDER BY nombre ASC");
	$sedes = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode($sedes);
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode(["error" => "Error al obtener las sedes: " . $e->getMessage()]);
}
