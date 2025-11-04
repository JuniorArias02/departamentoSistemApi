<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_delete.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';
require_once __DIR__ . '/../../notificaciones/enviarCorreoCancelarMantenimiento.php';

$data = json_decode(file_get_contents("php://input"), true);

// Validación de datos
if (!isset($data['id']) || !is_numeric($data['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Se requiere un ID válido de mantenimiento."]);
    exit;
}

if (!isset($data['usuario_id']) || !is_numeric($data['usuario_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Se requiere el ID del usuario que realiza la acción."]);
    exit;
}

if (!tienePermiso($pdo, $data['usuario_id'], PERMISOS['MANTENIMIENTOS']['ELIMINAR'])) {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "message" => "Acceso denegado. No tienes permiso para eliminar mantenimientos."
    ]);
    exit();
}

try {
    $pdo->beginTransaction();

    // Obtener información completa del mantenimiento
    $stmtSelect = $pdo->prepare("
    SELECT m.id, m.titulo, m.descripcion, m.creado_por, a.fecha_inicio, a.fecha_fin, 
           s.nombre AS sede, u.correo, u.nombre_completo
    FROM mantenimientos m
    JOIN agenda_mantenimientos a ON a.mantenimiento_id = m.id
    JOIN sedes s ON s.id = m.sede_id
    JOIN usuarios u ON u.id = a.creado_por
    WHERE m.id = :id
");

    $stmtSelect->execute(['id' => $data['id']]);
    $mantenimiento = $stmtSelect->fetch(PDO::FETCH_ASSOC);

    if (!$mantenimiento) {
        http_response_code(404);
        echo json_encode(["error" => "Mantenimiento no encontrado"]);
        exit;
    }

    // 1. Eliminar de agenda_mantenimientos
    $stmtAgenda = $pdo->prepare("DELETE FROM agenda_mantenimientos WHERE mantenimiento_id = :id");
    $stmtAgenda->execute(['id' => $data['id']]);

    // 2. Eliminar de mantenimientos
    $stmtMantenimiento = $pdo->prepare("DELETE FROM mantenimientos WHERE id = :id");
    $stmtMantenimiento->execute(['id' => $data['id']]);

    if ($stmtMantenimiento->rowCount() === 0) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(["error" => "No se pudo eliminar el mantenimiento."]);
        exit;
    }

    // 3. Registrar actividad
    $stmtActividad = $pdo->prepare("INSERT INTO actividades 
        (usuario_id, accion, tabla_afectada, registro_id, fecha)
        VALUES 
        (:usuario_id, :accion, 'mantenimientos', :registro_id, NOW())");

    $accion = "Eliminó el mantenimiento '" . $mantenimiento['titulo'] . "'";
    $stmtActividad->execute([
        "usuario_id" => $data['usuario_id'],
        "accion" => $accion,
        "registro_id" => $data['id']
    ]);

    $pdo->commit();

    // 4. Enviar correo de cancelación
    $correoEnviado = enviarCorreoCancelarMantenimiento(
        $mantenimiento['correo'],
        $mantenimiento['nombre_completo'],
        $mantenimiento['titulo'],
        $mantenimiento['descripcion'],
        $mantenimiento['fecha_inicio'],
        $mantenimiento['fecha_fin'],
        $mantenimiento['sede']
    );


    echo json_encode([
        "msg" => "Mantenimiento eliminado correctamente",
        "correo_enviado" => $correoEnviado,
        "actividad_registrada" => true
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["error" => "Error al eliminar: " . $e->getMessage()]);
}
