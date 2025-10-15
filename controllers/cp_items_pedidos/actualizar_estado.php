<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';


$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['item_id']) || !isset($data['comprado']) || !isset($data['id_usuario'])) {
	http_response_code(400);
	echo json_encode(["error" => "Faltan campos requeridos"]);
	exit;
}

$usuarioId = intval($data['id_usuario']);
$itemId = intval($data['item_id']);
$comprado = intval($data['comprado']);

if (!tienePermiso($pdo, $usuarioId, PERMISOS['GESTION_COMPRA_PEDIDOS']['MARCAR_ENTREGA'])) {
	http_response_code(403);
	echo json_encode(["error" => "No tienes permisos para esta opcion"]);
	exit;
}

try {
	$stmt = $pdo->prepare("UPDATE cp_items_pedidos 
                           SET comprado = :comprado 
                           WHERE id = :id");
	$stmt->execute([
		":comprado" => $comprado,
		":id" => $itemId
	]);

	echo json_encode([
		"message" => "Estado actualizado correctamente",
		"item_id" => $itemId,
		"comprado" => $comprado
	]);
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode([
		"error" => "Error al actualizar: " . $e->getMessage()
	]);
}
