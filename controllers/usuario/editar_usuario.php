<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';

$data = json_decode(file_get_contents("php://input"), true);

// Validar campos obligatorios
if (!isset($data['id_usuario_editor'], $data['id_usuario_objetivo'], $data['nombre_completo'], $data['usuario'], $data['rol_id'], $data['estado'])) {
    echo json_encode(["success" => false, "message" => "Faltan datos requeridos."]);
    exit();
}

if (!tienePermiso($pdo, $data['id_usuario_editor'], PERMISOS['USUARIOS']['EDITAR'])) {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "message" => "Acceso denegado. No tienes permiso para crear usuarios."
    ]);
    exit();
}

try {
    // Validar que el usuario a editar existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmt->execute([$data['id_usuario_objetivo']]);
    if (!$stmt->fetch()) {
        echo json_encode(["success" => false, "message" => "Usuario a editar no encontrado."]);
        exit();
    }

    // Construir query update
    $sql = "UPDATE usuarios SET nombre_completo = :nombre, usuario = :usuario, rol_id = :rol_id, estado = :estado";
    $params = [
        ':nombre' => $data['nombre_completo'],
        ':usuario' => $data['usuario'],
        ':rol_id' => $data['rol_id'],
        ':estado' => $data['estado']
    ];

    // Si enviaron contraseña, hashearla y agregarla
    if (!empty($data['contrasena'])) {
        $params[':contrasena'] = password_hash($data['contrasena'], PASSWORD_DEFAULT);
        $sql .= ", contrasena = :contrasena";
    }

    $sql .= " WHERE id = :id";
    $params[':id'] = $data['id_usuario_objetivo'];

    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
        echo json_encode(["success" => true, "message" => "Usuario editado con éxito"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al editar usuario"]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
