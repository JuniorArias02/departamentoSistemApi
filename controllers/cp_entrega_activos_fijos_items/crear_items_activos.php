<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

// Leer JSON
$data = json_decode(file_get_contents("php://input"), true);

// Validar campos principales
if (!isset($data['entrega_activos_id']) || empty($data['entrega_activos_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "El id de la entrega es obligatorio"]);
    exit;
}

if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
    http_response_code(400);
    echo json_encode(["error" => "Se requiere un arreglo de items"]);
    exit;
}

$entregaId = intval($data['entrega_activos_id']);
$items = $data['items'];

try {
    $pdo->beginTransaction();

    $sql = "INSERT INTO cp_entrega_activos_fijos_items (item_id, es_accesorio, accesorio_descripcion, entrega_activos_id)
            VALUES (:item_id, :es_accesorio, :accesorio_descripcion, :entrega_activos_id)";
    $stmt = $pdo->prepare($sql);

    foreach ($items as $index => $item) {
        // Validar campos obligatorios
        if (!isset($item['item_id']) || empty($item['item_id'])) {
            throw new Exception("El campo item_id es obligatorio en el item #" . ($index+1));
        }
        if (!isset($item['es_accesorio'])) {
            throw new Exception("El campo es_accesorio es obligatorio en el item #" . ($index+1));
        }

        $itemId = intval($item['item_id']);
        $esAccesorio = intval($item['es_accesorio']);
        $desc = isset($item['accesorio_descripcion']) ? $item['accesorio_descripcion'] : null;

        $stmt->execute([
            ':item_id' => $itemId,
            ':es_accesorio' => $esAccesorio,
            ':accesorio_descripcion' => $desc,
            ':entrega_activos_id' => $entregaId
        ]);
    }

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Items agregados correctamente"
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
