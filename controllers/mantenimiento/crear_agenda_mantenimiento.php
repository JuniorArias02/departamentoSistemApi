<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../utils/registrar_actividad.php';

$data = json_decode(file_get_contents("php://input"), true);

$titulo = $data['titulo'] ?? null;
$descripcion = $data['descripcion'] ?? '';
$fecha_agendada = $data['fecha_agendada'] ?? null;
$sede_id = $data['sede_id'] ?? null;
$usuario_id = $data['usuario_id'] ?? null;

if (!$titulo || !$fecha_agendada || !$sede_id || !$usuario_id) {
    http_response_code(400);
    echo json_encode(["error" => "Faltan datos requeridos"]);
    exit;
}

try {
    $pdo->beginTransaction();

    // Crear mantenimiento
    $sqlMantenimiento = "INSERT INTO mantenimientos (
        titulo, descripcion, sede_id, creado_por, fecha_creacion, fecha_ultima_actualizacion, esta_revisado
    ) VALUES (?, ?, ?, ?, NOW(), NOW(), 0)";
    
    $stmt = $pdo->prepare($sqlMantenimiento);
    $stmt->execute([$titulo, $descripcion, $sede_id, $usuario_id]);
    $mantenimiento_id = $pdo->lastInsertId();

    // Crear agenda
    $sqlAgenda = "INSERT INTO agenda_mantenimientos (
        mantenimiento_id, titulo, descripcion, sede_id, fecha_agendada, creado_por, fecha_creacion
    ) VALUES (?, ?, ?, ?, ?, ?, NOW())";

    $stmt2 = $pdo->prepare($sqlAgenda);
    $stmt2->execute([
        $mantenimiento_id, $titulo, $descripcion, $sede_id, $fecha_agendada, $usuario_id
    ]);
    $agenda_id = $pdo->lastInsertId();

    // Registrar actividades
    // registrarActividad($pdo, $usuario_id, "Creó mantenimiento '$titulo'", "mantenimientos", $mantenimiento_id);
    registrarActividad($pdo, $usuario_id, "Agendó mantenimiento '$titulo'", "agenda_mantenimientos", $agenda_id);

    $pdo->commit();

    echo json_encode(["mensaje" => "Mantenimiento y agendamiento creado correctamente"]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["error" => "Error al guardar: " . $e->getMessage()]);
}
