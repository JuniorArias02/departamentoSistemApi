<?php
header("Access-Control-Allow-Origin: https://formulario-medico.vercel.app");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../database/conexion.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit();
}

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

    if (!$usuario || $usuario['rol'] !== 'administrador') {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Solo admins pueden ver usuarios."]);
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

