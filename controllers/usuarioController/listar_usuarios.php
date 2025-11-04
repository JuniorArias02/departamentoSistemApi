<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';


$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['user_id'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Falta el user_id"]);
    exit();
}

if (!tienePermiso($pdo, $data['user_id'], PERMISOS['USUARIOS']['VER_DATOS'])) {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "message" => "Acceso denegado. No tienes permiso para crear usuarios."
    ]);
    exit();
}


try {
    // Traer todos los usuarios con su rol por nombre
    $stmt = $pdo->prepare("SELECT u.id, u.nombre_completo, u.usuario, u.correo, u.telefono, u.estado, r.nombre AS rol FROM usuarios u JOIN rol r ON u.rol_id = r.id");

    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "data" => $usuarios]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
