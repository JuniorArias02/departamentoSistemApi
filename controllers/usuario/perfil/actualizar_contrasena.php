<?php
require_once '../../../database/conexion.php';
require_once __DIR__ . '/../../../middlewares/headers_post.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    // Validar datos requeridos
    if (!isset($data['id']) || !isset($data['current_password'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Se requiere ID de usuario y contraseña actual'
        ]);
        exit();
    }

    $id = filter_var($data['id'], FILTER_VALIDATE_INT);
    $currentPassword = trim($data['current_password']);
    $newPassword = isset($data['new_password']) ? trim($data['new_password']) : null;
    $confirmPassword = isset($data['confirm_password']) ? trim($data['confirm_password']) : null;

    // 1. Verificar si el usuario existe y obtener su contraseña actual
    $sql = "SELECT contrasena FROM usuarios WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Usuario no encontrado'
        ]);
        exit();
    }

    // 2. Verificar contraseña actual
    if (!password_verify($currentPassword, $user['contrasena'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Contraseña actual incorrecta'
        ]);
        exit();
    }

    // 3. Si se envió nueva contraseña, validar y actualizar
    if ($newPassword !== null) {
        // Validar que las contraseñas coincidan
        if ($newPassword !== $confirmPassword) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Las contraseñas nuevas no coinciden'
            ]);
            exit();
        }

        // Validar fortaleza de contraseña
        if (strlen($newPassword) < 8) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'La contraseña debe tener al menos 8 caracteres'
            ]);
            exit();
        }

        // Hashear la nueva contraseña
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Actualizar en la base de datos
        $updateSql = "UPDATE usuarios SET contrasena = :password WHERE id = :id";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->bindParam(':password', $hashedPassword);
        $updateStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $updateStmt->execute();

        echo json_encode([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente'
        ]);
    } else {
        // Si no se envió nueva contraseña, solo confirmar que la actual es correcta
        echo json_encode([
            'success' => true,
            'message' => 'Contraseña actual verificada'
        ]);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}