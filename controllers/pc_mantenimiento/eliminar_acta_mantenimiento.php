<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';


$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["id"])) {
    echo json_encode([
        "status" => false,
        "message" => "Falta el id del mantenimiento"
    ]);
    exit;
}

$usuario_id = $data["usuario_id"] ?? null;

if (!$usuario_id) {
    echo json_encode([
        "status" => false,
        "message" => "No tienes permisos para realizar esta acción"
    ]);
    exit;
}

if (!tienePermiso($pdo, $usuario_id, PERMISOS['GESTION_EQUIPOS']['ELIMINAR_ACTA_MANTENIMIENTO'])) {
    http_response_code(403);
    echo json_encode(["error" => "No tienes permisos para aprobar pedidos"]);
    exit;
}

$id = intval($data["id"]);

try {
    // Verificar que el registro exista
    $stmtCheck = $pdo->prepare("SELECT id FROM pc_mantenimientos WHERE id = :id");
    $stmtCheck->execute(["id" => $id]);
    $existe = $stmtCheck->fetch();

    if (!$existe) {
        echo json_encode([
            "status" => false,
            "message" => "El acta no existe"
        ]);
        exit;
    }

    // Eliminar registro
    $stmt = $pdo->prepare("DELETE FROM pc_mantenimientos WHERE id = :id");
    $stmt->execute(["id" => $id]);

    echo json_encode([
        "status" => true,
        "message" => "Acta eliminada correctamente"
    ]);
} catch (Exception $e) {
    echo json_encode([
        "status" => false,
        "message" => "Error al eliminar el acta",
        "error" => $e->getMessage()
    ]);
}
