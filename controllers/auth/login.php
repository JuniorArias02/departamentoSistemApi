<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';


$data = json_decode(file_get_contents("php://input"), true);
$usuario = $data['usuario'] ?? '';
$contrasena = $data['contrasena'] ?? '';

// Hacemos JOIN para traer el nombre del rol
$stmt = $pdo->prepare("
    SELECT u.*, r.nombre AS nombre_rol
    FROM usuarios u
    LEFT JOIN rol r ON u.rol_id = r.id
    WHERE u.usuario = :usuario
");
$stmt->execute(['usuario' => $usuario]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($contrasena, $user['contrasena'])) {
    echo json_encode([
        "status" => "ok",
        "msg" => "Login exitoso",
        "usuario" => [
            "id" => $user['id'],
            "nombre_completo" => $user['nombre_completo'],
            "rol" => $user['nombre_rol']
        ]
    ]);
} else {
    echo json_encode(["status" => "error", "msg" => "Credenciales incorrectas"]);
}
