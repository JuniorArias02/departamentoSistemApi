<?php
require_once '../../../database/conexion.php';
require_once __DIR__ . '/../../../middlewares/headers_post.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (
        !isset($data['id']) || 
        !isset($data['nombre_completo']) ||
        !isset($data['correo'])
    ) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Faltan datos requeridos'
        ]);
        exit();
    }

    $id = filter_var($data['id'], FILTER_VALIDATE_INT);
    $nombre = trim($data['nombre_completo']);
    $correo = trim($data['correo']);
    $telefono = isset($data['telefono']) ? trim($data['telefono']) : null;

    $sql = "UPDATE usuarios 
            SET nombre_completo = :nombre, 
                correo = :correo, 
                telefono = :telefono 
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':correo', $correo);
    $stmt->bindParam(':telefono', $telefono);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    $stmt->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Perfil actualizado correctamente'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al actualizar perfil: ' . $e->getMessage()
    ]);
}
