<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

header('Content-Type: application/json');

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Método no permitido"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

$entrega_id = $input['entrega_activos_id'] ?? null;
$items      = $input['items'] ?? [];

// Validar
if (!$entrega_id || !is_array($items) || count($items) === 0) {
    echo json_encode([
        "success" => false,
        "error" => "Faltan datos: entrega_activos_id o lista de items"
    ]);
    exit;
}

try {

    // Iniciar transacción
    $pdo->beginTransaction();

    $sql = "INSERT INTO cp_entrega_activos_fijos_items (item_id, entrega_activos_id)
            VALUES (:item_id, :entrega_activos_id)";
    $stmt = $pdo->prepare($sql);

    foreach ($items as $itemId) {
        $stmt->execute([
            ':item_id' => $itemId,
            ':entrega_activos_id' => $entrega_id
        ]);
    }

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Ítems guardados correctamente"
    ]);
} catch (Exception $e) {

    $pdo->rollBack();

    echo json_encode([
        "success" => false,
        "error" => "Error en el servidor",
        "details" => $e->getMessage()
    ]);
}
