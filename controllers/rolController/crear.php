<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';

// Recibir los datos
$data = json_decode(file_get_contents('php://input'), true);

// Validar datos requeridos
if (!isset($data['creado_por']) || !isset($data['nombre'])) {
    echo json_encode(["success" => false, "message" => "Faltan parámetros"]);
    exit();
}

// Verificar permiso
if (!tienePermiso($pdo, $data['creado_por'], PERMISOS['ROLES']['CREAR'])) {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "message" => "Acceso denegado. No tienes permiso para crear roles."
    ]);
    exit();
}

try {
    // Insertar el nuevo rol
    $sql = "INSERT INTO rol (nombre) VALUES (:nombre)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre' => $data['nombre']
    ]);

    echo json_encode(['success' => true, 'message' => 'Rol creado exitosamente']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error al crear el rol: ' . $e->getMessage()]);
}
