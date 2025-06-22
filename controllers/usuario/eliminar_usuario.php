<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id_usuario_editor'], $data['id_usuario_objetivo'])) {
    echo json_encode(["success" => false, "message" => "Faltan datos requeridos."]);
    exit();
}

if (!tienePermiso($pdo, $data['id_usuario_editor'], PERMISOS['USUARIOS']['ELIMINAR'])) {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "message" => "Acceso denegado. No tienes permiso para crear usuarios."
    ]);
    exit();
}


try {
    // Verificar que editor sea administrador
    $stmt = $pdo->prepare("SELECT r.nombre AS rol FROM usuarios u 
                           JOIN rol r ON u.rol_id = r.id 
                           WHERE u.id = ?");
    $stmt->execute([$data['id_usuario_editor']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario || $usuario['rol'] !== 'administrador') {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Acceso denegado. Solo administradores pueden eliminar usuarios."]);
        exit();
    }

    // Verificar que usuario a eliminar existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmt->execute([$data['id_usuario_objetivo']]);
    if (!$stmt->fetch()) {
        echo json_encode(["success" => false, "message" => "Usuario a eliminar no encontrado."]);
        exit();
    }

    // Ejecutar eliminación
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    if ($stmt->execute([$data['id_usuario_objetivo']])) {
        echo json_encode(["success" => true, "message" => "Usuario eliminado con éxito"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al eliminar usuario"]);
    }

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
