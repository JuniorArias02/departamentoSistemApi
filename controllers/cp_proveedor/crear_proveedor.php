<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    // Validar datos obligatorios
    if (
        empty($data['nombre']) ||
        empty($data['nit']) ||
        empty($data['telefono']) ||
        empty($data['correo']) ||
        empty($data['direccion'])
    ) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Faltan campos obligatorios"
        ]);
        exit;
    }

    // Preparar query
    $stmt = $pdo->prepare("
        INSERT INTO cp_proveedores (nombre, nit, telefono, correo, direccion)
        VALUES (:nombre, :nit, :telefono, :correo, :direccion)
    ");

    // Ejecutar
    $stmt->execute([
        ":nombre"   => $data['nombre'],
        ":nit"      => $data['nit'],
        ":telefono" => $data['telefono'],
        ":correo"   => $data['correo'],
        ":direccion"=> $data['direccion'],
    ]);

    // Respuesta
    echo json_encode([
        "success" => true,
        "data" => [
            "id"        => $pdo->lastInsertId(),
            "nombre"    => $data['nombre'],
            "nit"       => $data['nit'],
            "telefono"  => $data['telefono'],
            "correo"    => $data['correo'],
            "direccion" => $data['direccion']
        ]
    ]);
    
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error en el servidor",
        "error"   => $e->getMessage()
    ]);
}
