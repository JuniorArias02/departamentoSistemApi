<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';

// Leer JSON enviado en el body
$input = json_decode(file_get_contents('php://input'), true);


if (!isset($input['id_usuario_editor'], $input['id_usuario_objetivo'])) {
    echo json_encode(["success" => false, "message" => "Faltan parámetros"]);
    exit();
}

$id_editor = $input['id_usuario_editor'];
$id_objetivo = $input['id_usuario_objetivo'];

try {
    // Validar admin
    $stmt = $pdo->prepare("SELECT r.nombre AS rol FROM usuarios u 
                           JOIN rol r ON u.rol_id = r.id 
                           WHERE u.id = ?");
    $stmt->execute([$id_editor]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!tienePermiso($pdo, $input['id_usuario_editor'], PERMISOS['USUARIOS']['EDITAR'])) {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "message" => "Acceso denegado. No tienes permiso para ver datos de inventario."
        ]);
        exit();
    }


    // Obtener usuario a editar
    $stmt = $pdo->prepare("SELECT id, nombre_completo, usuario, rol_id FROM usuarios WHERE id = ?");
    $stmt->execute([$id_objetivo]);
    $usuarioObjetivo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuarioObjetivo) {
        echo json_encode(["success" => true, "data" => $usuarioObjetivo]);
    } else {
        echo json_encode(["success" => false, "message" => "Usuario no encontrado"]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
