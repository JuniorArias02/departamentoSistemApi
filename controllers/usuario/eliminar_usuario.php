<?php
require_once '../../database/conexion.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Responder a preflight OPTIONS
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

if (!isset($data['id_usuario_editor'], $data['id_usuario_objetivo'])) {
    echo json_encode(["success" => false, "message" => "Faltan datos requeridos."]);
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
