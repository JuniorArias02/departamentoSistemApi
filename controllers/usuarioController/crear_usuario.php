<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';

$data = json_decode(file_get_contents("php://input"), true);

// Validar que venga el user_id para saber si es admin
if (!isset($data['id_usuario_creador'], $data['nombre_completo'], $data['usuario'], $data['rol_id'], $data['estado'])) {
    echo json_encode(["success" => false, "message" => "Faltan datos requeridos."]);
    exit();
}

if (!tienePermiso($pdo, $data['id_usuario_creador'], PERMISOS['USUARIOS']['CREAR'])) {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "message" => "Acceso denegado. No tienes permiso para crear usuarios."
    ]);
    exit();
}

try {
    // Crear nuevo usuario
    $nombre = $data['nombre_completo'];
    $usuarioNuevo = $data['usuario'];
    $rol_id = $data['rol_id'];

    if (!isset($data['contrasena']) || empty(trim($data['contrasena']))) {
        $contrasena = password_hash($usuarioNuevo . '@house', PASSWORD_DEFAULT);
    } else {
        $contrasena = password_hash($data['contrasena'], PASSWORD_DEFAULT);
    }

    $sql = "INSERT INTO usuarios (nombre_completo, usuario, contrasena, rol_id, estado) 
            VALUES (:nombre, :usuario, :contrasena, :rol_id, :estado)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':usuario', $usuarioNuevo);
    $stmt->bindParam(':contrasena', $contrasena);
    $stmt->bindParam(':rol_id', $rol_id, PDO::PARAM_INT);
    $stmt->bindParam(':estado', $data['estado'], PDO::PARAM_BOOL);

    if ($stmt->execute()) {
        $nuevoId = $pdo->lastInsertId();
        echo json_encode([
            "success" => true,
            "message" => "Usuario creado con éxito",
            "id" => $nuevoId 
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al crear usuario"]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
 