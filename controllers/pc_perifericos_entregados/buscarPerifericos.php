<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

header('Content-Type: application/json');

// Solo GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
	http_response_code(405);
	echo json_encode(["success" => false, "error" => "Método no permitido"]);
	exit;
}

$query = isset($_GET['query']) ? trim($_GET['query']) : null;

if (!$query || strlen($query) < 2) {
	http_response_code(400);
	echo json_encode([
		"success" => false,
		"error" => "Debes enviar mínimo 2 caracteres para buscar."
	]);
	exit;
}

try {

	$sql = "
    SELECT 
        id AS inventario_id,
        codigo,
        nombre,
        serial,
        marca,
        modelo
    FROM inventario
    WHERE 
        LOWER(codigo) LIKE :filtro OR 
        LOWER(nombre) LIKE :filtro OR 
        LOWER(serial) LIKE :filtro
    LIMIT 20
";


	$stmt = $pdo->prepare($sql);

	$stmt->execute([
		":filtro" => "%" . strtolower($query) . "%"
	]);

	$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode([
		"success" => true,
		"resultados" => $equipos
	]);
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode([
		"success" => false,
		"error" => "Error en la búsqueda: " . $e->getMessage()
	]);
}
