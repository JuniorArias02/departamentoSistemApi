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

// Validar que venga el user_id para saber si es admin
if (!isset($data['id_usuario_creador'], $data['nombre_completo'], $data['usuario'], $data['contrasena'], $data['rol_id'])) {
    echo json_encode(["success" => false, "message" => "Faltan datos requeridos."]);
    exit();
}

// Validar si el user_id tiene rol administrador

try {

$stmt = $pdo->prepare("SELECT r.nombre AS rol FROM usuarios u 
                       JOIN rol r ON u.rol_id = r.id 
                       WHERE u.id = ?");
$stmt->execute([$data['id_usuario_creador']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC); 
    if (!$usuario || $usuario['rol'] !== 'administrador') {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Acceso denegado. Solo administradores pueden crear usuarios."]);
        exit();
    }

    // Crear nuevo usuario
    $nombre = $data['nombre_completo'];
    $usuarioNuevo = $data['usuario'];
    $contrasena = password_hash($data['contrasena'], PASSWORD_DEFAULT);
    $rol_id = $data['rol_id'];

    $sql = "INSERT INTO usuarios (nombre_completo, usuario, contrasena, rol_id) 
            VALUES (:nombre, :usuario, :contrasena, :rol_id)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':usuario', $usuarioNuevo);
    $stmt->bindParam(':contrasena', $contrasena);
    $stmt->bindParam(':rol_id', $rol_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Usuario creado con éxito"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al crear usuario"]);
    }

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
