<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../utils/registrar_actividad.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';

try {
    // Recibir el body JSON
    $data = json_decode(file_get_contents("php://input"), true);
    $usuario_id = $data['usuario_id'] ?? null;
    $nombre = $data['nombre'] ?? null;
    $sede_id = $data['sede_id'] ?? null;

    // Validar campos requeridos
    if (!$usuario_id || !$nombre || !$sede_id) {
        http_response_code(400);
        echo json_encode([
            "status" => false,
            "message" => "Faltan campos obligatorios: usuario_id, nombre o sede_id"
        ]);
        exit;
    }

    // Validar permisos del usuario
    if (!tienePermiso($pdo, $usuario_id, PERMISOS['INVENTARIO']['CREAR'])) {
        http_response_code(403);
        echo json_encode([
            "status" => false,
            "message" => "No tienes permiso para crear áreas"
        ]);
        exit;
    }

    // Insertar área
    $stmt = $pdo->prepare("INSERT INTO areas (nombre, sede_id) VALUES (?, ?)");
    $stmt->execute([$nombre, $sede_id]);
    $area_id = $pdo->lastInsertId();

    // Registrar actividad
    registrarActividad($pdo, $usuario_id, "Creó un área llamada '$nombre'", 'areas', $area_id);

    // Respuesta exitosa
    echo json_encode([
        "status" => true,
        "message" => "Área creada correctamente",
        "id" => $area_id
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => false,
        "message" => "Error al crear área: " . $e->getMessage()
    ]);
}
