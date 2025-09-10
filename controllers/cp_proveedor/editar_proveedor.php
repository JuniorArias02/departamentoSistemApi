<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

try {
    // Recibir JSON desde el frontend
    $data = json_decode(file_get_contents("php://input"), true);

    // Validar ID
    if (empty($data['proveedor_id'])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "El ID del proveedor es obligatorio"
        ]);
        exit;
    }

    // Preparar query con los campos que pueden cambiar
    $stmt = $pdo->prepare("
        UPDATE cp_proveedores 
        SET nombre = :nombre,
            nit = :nit,
            telefono = :telefono,
            correo = :correo,
            direccion = :direccion
        WHERE id = :id
    ");

    // Ejecutar
    $stmt->execute([
        ":id"        => $data['proveedor_id'],
        ":nombre"    => $data['nombre'] ?? '',
        ":nit"       => $data['nit'] ?? '',
        ":telefono"  => $data['telefono'] ?? '',
        ":correo"    => $data['correo'] ?? '',
        ":direccion" => $data['direccion'] ?? '',
    ]);

    // Validar si actualizó
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "success" => true,
            "message" => "Proveedor actualizado correctamente",
            "data" => [
                "id"        => $data['proveedor_id'],
                "nombre"    => $data['nombre'],
                "nit"       => $data['nit'],
                "telefono"  => $data['telefono'],
                "correo"    => $data['correo'],
                "direccion" => $data['direccion']
            ]
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "No se actualizó el proveedor (quizá los datos son iguales)"
        ]);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error en el servidor",
        "error"   => $e->getMessage()
    ]);
}
