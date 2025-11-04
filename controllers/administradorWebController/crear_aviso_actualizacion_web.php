<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';

// Obtener datos del cuerpo de la solicitud
$data = json_decode(file_get_contents('php://input'), true);

// Validar que exista el ID del usuario creador
if (!isset($data['id_usuario'])) {
    echo json_encode(["success" => false, "message" => "Faltan parámetros"]);
    exit();
}

// Verificar permisos
if (!tienePermiso($pdo, $data['id_usuario'], PERMISOS['ADMINISTRADOR_WEB']['CREAR_AVISO_ACTUALIZACION'])) {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "message" => "Acceso denegado. No tienes permiso para crear avisos."
    ]);
    exit();
}

try {
    // Insertar actualización
    $sql = "INSERT INTO actualizaciones_web 
        (titulo, descripcion, mostrar_desde, mostrar_hasta, fecha_actualizacion, duracion_minutos, estado) 
        VALUES 
        (:titulo, :descripcion, :mostrar_desde, :mostrar_hasta, :fecha_actualizacion, :duracion_minutos, :estado)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':titulo' => $data['titulo'],
        ':descripcion' => $data['descripcion'],
        ':mostrar_desde' => $data['mostrar_desde'],
        ':mostrar_hasta' => $data['mostrar_hasta'],
        ':fecha_actualizacion' => $data['fecha_actualizacion'],
        ':duracion_minutos' => $data['duracion_minutos'],
        ':estado' => $data['estado'] ?? 'pendiente'
    ]);

    echo json_encode(['success' => true, 'message' => 'Actualización creada con éxito']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
