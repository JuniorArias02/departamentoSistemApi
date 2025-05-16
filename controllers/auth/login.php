<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

require_once '../../database/conexion.php';

$data = json_decode(file_get_contents("php://input"), true);
$usuario = $data['usuario'] ?? '';
$contrasena = $data['contrasena'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario");
$stmt->execute(['usuario' => $usuario]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($contrasena, $user['contrasena'])) {
    echo json_encode([
        "status" => "ok",
        "msg" => "Login exitoso",
        "usuario" => [
            "id" => $user['id'],
            "nombre_completo" => $user['nombre_completo'],
        ]
    ]);
} else {
    echo json_encode(["status" => "error", "msg" => "Credenciales incorrectas"]);
}
