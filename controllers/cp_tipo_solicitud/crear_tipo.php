<?php
require_once '../../database/conexion.php'; // Aquí tienes $pdo listo
require_once __DIR__ . '/../../middlewares/cors.php'; // Maneja CORS y headers

// Leer y decodificar el JSON recibido
$input = json_decode(file_get_contents("php://input"), true);

// Validar que llegaron los datos obligatorios
if (!isset($input['nombre']) || empty(trim($input['nombre']))) {
    http_response_code(400);
    echo json_encode(["error" => "El campo 'nombre' es obligatorio"]);
    exit;
}

$nombre = trim($input['nombre']);
$descripcion = isset($input['descripcion']) ? trim($input['descripcion']) : null;

try {
    $stmt = $pdo->prepare("
        INSERT INTO cp_tipo_solicitud (nombre, descripcion)
        VALUES (:nombre, :descripcion)
    ");
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':descripcion', $descripcion);

    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Tipo de solicitud creado correctamente",
            "id" => $pdo->lastInsertId()
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Error al crear el tipo de solicitud"]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
