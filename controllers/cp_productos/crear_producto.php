<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

// Solo aceptar POST
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Método no permitido"]);
    exit;
}

// Obtener datos JSON del body
$data = json_decode(file_get_contents("php://input"), true);

// Validar campos
$codigo = isset($data['codigo']) ? trim($data['codigo']) : null;
$nombre = isset($data['nombre']) ? trim($data['nombre']) : null;

if (!$codigo || !$nombre) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "El código y el nombre son obligatorios"
    ]);
    exit;
}

try {
    // Validar que el código no exista
    $stmt = $pdo->prepare("SELECT id FROM cp_productos WHERE codigo = :codigo");
    $stmt->execute([':codigo' => $codigo]);

    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "error" => "El código ya existe"
        ]);
        exit;
    }

    // Crear producto
    $stmt = $pdo->prepare("
        INSERT INTO cp_productos (codigo, nombre)
        VALUES (:codigo, :nombre)
    ");

    $stmt->execute([
        ':codigo' => $codigo,
        ':nombre' => $nombre
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Producto creado con éxito",
        "producto" => [
            "id" => $pdo->lastInsertId(),
            "codigo" => $codigo,
            "nombre" => $nombre
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Error en el servidor: " . $e->getMessage()
    ]);
}
