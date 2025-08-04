<?php
require_once __DIR__ . '/../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_get.php';
require_once __DIR__ . '/../utils/registrar_actividad.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';

$stmt = $pdo->query("SELECT COUNT(*) AS total FROM pc_equipos");
$total = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
	"status" => true,
	"total" => $total["total"]
]);
