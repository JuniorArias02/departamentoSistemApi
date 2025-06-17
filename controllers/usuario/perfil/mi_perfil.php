<?php
require_once '../../../database/conexion.php';
require_once __DIR__ . '/../../../middlewares/headers_post.php';


try {
    // Obtener el ID del usuario desde el cuerpo de la petición POST
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['id']) || empty($data['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID de usuario no proporcionado'
        ]);
        exit();
    }

    $userId = filter_var($data['id'], FILTER_VALIDATE_INT);
    
    if ($userId === false) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID de usuario no válido'
        ]);
        exit();
    }

    // Consulta SQL para obtener los datos del usuario específico (sin contraseña)
    $sql = "SELECT id, nombre_completo, usuario, correo, telefono, rol_id 
            FROM usuarios 
            WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Usuario no encontrado'
        ]);
        exit();
    }

    // Devolver respuesta JSON con los datos del usuario
    echo json_encode([
        'success' => true,
        'data' => $usuario,
        'message' => 'Datos del usuario obtenidos correctamente'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener los datos del usuario: ' . $e->getMessage()
    ]);
}