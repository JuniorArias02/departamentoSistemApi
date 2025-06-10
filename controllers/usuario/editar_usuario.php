<?php
require_once '../../database/conexion.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

// Validar campos obligatorios
if (!isset($data['id_usuario_editor'], $data['id_usuario_objetivo'], $data['nombre_completo'], $data['usuario'], $data['rol_id'])) {
    echo json_encode(["success" => false, "message" => "Faltan datos requeridos."]);
    exit();
}

try {
    // Validar que editor es administrador
    $stmt = $pdo->prepare("SELECT r.nombre AS rol FROM usuarios u 
                           JOIN rol r ON u.rol_id = r.id 
                           WHERE u.id = ?");
    $stmt->execute([$data['id_usuario_editor']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario || $usuario['rol'] !== 'administrador') {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Acceso denegado. Solo administradores pueden editar usuarios."]);
        exit();
    }

    // Validar que el usuario a editar existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmt->execute([$data['id_usuario_objetivo']]);
    if (!$stmt->fetch()) {
        echo json_encode(["success" => false, "message" => "Usuario a editar no encontrado."]);
        exit();
    }

    // Construir query update
    $sql = "UPDATE usuarios SET nombre_completo = :nombre, usuario = :usuario, rol_id = :rol_id";
    $params = [
        ':nombre' => $data['nombre_completo'],
        ':usuario' => $data['usuario'],
        ':rol_id' => $data['rol_id']
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


