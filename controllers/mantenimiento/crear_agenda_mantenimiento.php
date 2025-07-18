<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../utils/registrar_actividad.php';
require_once __DIR__ . '/../../notificaciones/enviarCorreoMantenimientoAgendado.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';


date_default_timezone_set("America/Bogota");

// Capturar los datos del frontend
$data = json_decode(file_get_contents("php://input"), true);

$titulo = $data['titulo'] ?? null;
$descripcion = $data['descripcion'] ?? '';
$fecha_inicio = $data['fecha_inicio'] ?? null;
$fecha_fin = $data['fecha_fin'] ?? null;
$sede_id = $data['sede_id'] ?? null;

$usuario_id = $data['usuario_id'] ?? null; // quien agenda
$usuario_asignado = $data['usuario_asignado'] ?? null; // técnico que hará el mantenimiento

$campos_faltantes = [];

if (!$titulo) $campos_faltantes[] = 'titulo';
if (!$fecha_inicio) $campos_faltantes[] = 'fecha_inicio';
if (!$fecha_fin) $campos_faltantes[] = 'fecha_fin';
if (!$sede_id) $campos_faltantes[] = 'sede_id';
if (!$usuario_id) $campos_faltantes[] = 'usuario_id';
if (!$usuario_asignado) $campos_faltantes[] = 'usuario_asignado';

if (!empty($campos_faltantes)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Faltan los siguientes campos: " . implode(', ', $campos_faltantes),
        "data_recibida" => $data
    ]);
    exit;
}
// Validar campos obligatorios
if (!$titulo || !$fecha_inicio || !$fecha_fin || !$sede_id || !$usuario_id || !$usuario_asignado) {
    http_response_code(400);
    echo json_encode(["error" => "Faltan datos requeridos"]);
    exit;
}

try {
    $pdo->beginTransaction();

    // Crear mantenimiento (lo crea el técnico)
    $sqlMantenimiento = "INSERT INTO mantenimientos (
        titulo, descripcion, sede_id, creado_por, fecha_creacion, fecha_ultima_actualizacion, esta_revisado
    ) VALUES (?, ?, ?, ?, NOW(), NOW(), 0)";

    $stmt = $pdo->prepare($sqlMantenimiento);
    $stmt->execute([
        $titulo,
        $descripcion,
        $sede_id,
        $usuario_asignado
    ]);

    $mantenimiento_id = $pdo->lastInsertId();

    // Crear agendamiento (lo agenda el usuario logueado)
    $sqlAgenda = "INSERT INTO agenda_mantenimientos (
        mantenimiento_id, titulo, descripcion, sede_id, fecha_inicio, fecha_fin, creado_por, agendado_por, fecha_creacion
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt2 = $pdo->prepare($sqlAgenda);
    $stmt2->execute([
        $mantenimiento_id,
        $titulo,
        $descripcion,
        $sede_id,
        $fecha_inicio,
        $fecha_fin,
        $usuario_asignado,
        $usuario_id
    ]);

    $agenda_id = $pdo->lastInsertId();

    // Registrar actividad
    registrarActividad($pdo, $usuario_id, "Agendó mantenimiento '$titulo' para el usuario ID $usuario_asignado", "agenda_mantenimientos", $agenda_id);


    $sqlCorreo = $pdo->prepare("SELECT correo, nombre_completo FROM usuarios WHERE id = ?");
    $sqlCorreo->execute([$usuario_asignado]);
    $tecnico = $sqlCorreo->fetch();

    $sqlSede = $pdo->prepare("SELECT nombre FROM sedes WHERE id = ?");
    $sqlSede->execute([$sede_id]);
    $sede = $sqlSede->fetch();
    $sedeNombre = $sede ? $sede['nombre'] : 'Sede desconocida';

    $enviado = enviarCorreoMantenimientoAgendado(
        $tecnico['correo'],
        $tecnico['nombre_completo'],
        $titulo,
        $descripcion,
        $fecha_inicio,
        $fecha_fin,
        $sedeNombre
    );

    if (!$enviado) {
        error_log("⚠️ No se pudo enviar el correo al técnico {$tecnico['correo']}");
    }
    $pdo->commit();

    echo json_encode(["mensaje" => "Mantenimiento agendado correctamente"]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["error" => "Error al guardar: " . $e->getMessage()]);
}
