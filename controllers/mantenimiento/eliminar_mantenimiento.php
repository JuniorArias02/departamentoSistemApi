<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_delete.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';

$data = json_decode(file_get_contents("php://input"), true);

// Validar que venga el ID y el usuario que realiza la acción
if (!isset($data['id']) || !is_numeric($data['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Se requiere un ID válido para eliminar."]);
    exit;
}

if (!isset($data['usuario_id']) || !is_numeric($data['usuario_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Se requiere el ID del usuario que realiza la acción."]);
    exit;
}

if (!tienePermiso($pdo, $data['usuario_id'], PERMISOS['MANTENIMIENTOS']['ELIMINAR'])) {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "message" => "Acceso denegado. No tienes permiso para ver datos de inventario."
    ]);
    exit();
}

try {
    // Primero obtenemos los datos del item a eliminar para registrar la actividad
    $stmtSelect = $pdo->prepare("SELECT nombre, creado_por FROM inventario WHERE id = :id");
    $stmtSelect->execute(['id' => $data['id']]);
    $item = $stmtSelect->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        http_response_code(404);
        echo json_encode(["error" => "Inventario no encontrado"]);
        exit;
    }

    // Iniciamos transacción
    $pdo->beginTransaction();

    // 1. Eliminar el item
    $stmtDelete = $pdo->prepare("DELETE FROM inventario WHERE id = :id");
    $stmtDelete->execute(['id' => $data['id']]);

    if ($stmtDelete->rowCount() === 0) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(["error" => "Inventario no encontrado"]);
        exit;
    }

    // 2. Registrar la actividad
    $stmtActividad = $pdo->prepare("INSERT INTO actividades 
        (usuario_id, accion, tabla_afectada, registro_id, fecha)
        VALUES 
        (:usuario_id, :accion, 'inventario', :registro_id, NOW())");

    $accion = "Eliminó el item '" . $item['nombre'] . "' del inventario";

    $stmtActividad->execute([
        "usuario_id" => $data['usuario_id'],
        "accion" => $accion,
        "registro_id" => $data['id']
    ]);

    // Confirmar transacción
    $pdo->commit();

    echo json_encode([
        "msg" => "Inventario eliminado correctamente",
        "actividad_registrada" => true
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["error" => "Error al eliminar: " . $e->getMessage()]);
}
