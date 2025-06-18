<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';


$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['user_id'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Falta el user_id"]);
    exit();
}

try {
    // Validar si es admin
    $stmt = $pdo->prepare("SELECT r.nombre AS rol FROM usuarios u JOIN rol r ON u.rol_id = r.id WHERE u.id = ?");
    $stmt->execute([$data['user_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario || $usuario['rol'] !== 'administrador') {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Acceso denegado"]);
        exit();
    }

    // Traer todos los usuarios con su rol por nombre
    $stmt = $pdo->prepare("SELECT u.id, u.nombre_completo, u.usuario, u.estado, r.nombre AS rol FROM usuarios u JOIN rol r ON u.rol_id = r.id");
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "data" => $usuarios]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
