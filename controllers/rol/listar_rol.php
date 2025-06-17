<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_get.php';


try {
	$stmt = $pdo->query("SELECT * FROM rol ORDER BY nombre ASC");
	$roles = $stmt->fetchAll(PDO::FETCH_ASSOC); 

	echo json_encode($roles);
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode(["error" => "Error al obtener los roles: " . $e->getMessage()]);
}
