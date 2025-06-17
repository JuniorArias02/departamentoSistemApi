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
