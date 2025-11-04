<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_crud.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['usuario_id'])) {
        http_response_code(400);
        echo json_encode(["error" => "Se requiere el ID de usuario"]);
        exit;
    }

    $usuarioId = filter_var($data['usuario_id'], FILTER_VALIDATE_INT);
    if ($usuarioId === false) {
        http_response_code(400);
        echo json_encode(["error" => "ID de usuario inválido"]);
        exit;
    }

    // Verificar permisos
    $puedeVerTodos = tienePermiso($pdo, $usuarioId, PERMISOS['MANTENIMIENTOS']['VER_TODOS']);
    $puedeVerPropios = tienePermiso($pdo, $usuarioId, PERMISOS['MANTENIMIENTOS']['VER_PROPIOS']);

    if (!$puedeVerTodos && !$puedeVerPropios) {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "message" => "Acceso denegado. No tienes permiso para ver los mantenimientos."
        ]);
        exit();
    }

    // Consulta base de mantenimientos
    $sql = "SELECT 
                mf.id,
                mf.titulo,
                mf.codigo,
                mf.modelo,
                mf.dependencia,
                mf.sede_id,
                s.nombre AS nombre_sede,
                mf.nombre_receptor,
                ur.nombre_completo AS nombre_receptor_completo,
                mf.imagen,
                mf.descripcion,
                mf.revisado_por,
                urv.nombre_completo AS nombre_revisor,
                mf.fecha_revisado,
                mf.creado_por,
                uc.nombre_completo AS nombre_creador,
                mf.fecha_creacion,
                mf.esta_revisado,
                mf.fecha_ultima_actualizacion
            FROM 
                mantenimientos mf
            LEFT JOIN 
                sedes s ON mf.sede_id = s.id
            LEFT JOIN 
                usuarios ur ON mf.nombre_receptor = ur.id
            LEFT JOIN 
                usuarios urv ON mf.revisado_por = urv.id
            LEFT JOIN 
                usuarios uc ON mf.creado_por = uc.id";

    $params = [];

    if (!$puedeVerTodos && $puedeVerPropios) {
        $sql .= " WHERE mf.nombre_receptor = ? OR mf.creado_por = ?";
        $params = [$usuarioId, $usuarioId];
    }

    $sql .= " ORDER BY mf.fecha_creacion DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $mantenimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Agregar agenda a cada mantenimiento
    foreach ($mantenimientos as &$mantenimiento) {
        $mantenimiento['esta_revisado'] = (bool)$mantenimiento['esta_revisado'];

        if (isset($mantenimiento['imagen']) && is_string($mantenimiento['imagen'])) {
            $decoded = json_decode($mantenimiento['imagen'], true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $mantenimiento['imagen'] = $decoded;
            } else {
                $mantenimiento['imagen'] = [$mantenimiento['imagen']];
            }
        } else {
            $mantenimiento['imagen'] = [];
        }

        // Agenda
        $stmtAgenda = $pdo->prepare("SELECT 
            a.id,
            a.titulo,
            a.descripcion,
            a.sede_id,
            s.nombre AS nombre_sede,
            a.fecha_inicio,
            a.fecha_fin,
            a.creado_por,
            u1.nombre_completo AS nombre_creador,
            a.agendado_por,
            u2.nombre_completo AS nombre_agendador
        FROM agenda_mantenimientos a
        LEFT JOIN sedes s ON a.sede_id = s.id
        LEFT JOIN usuarios u1 ON a.creado_por = u1.id
        LEFT JOIN usuarios u2 ON a.agendado_por = u2.id
        WHERE a.mantenimiento_id = ?
        ORDER BY a.fecha_inicio DESC");

        $stmtAgenda->execute([$mantenimiento['id']]);
        $agenda = $stmtAgenda->fetch(PDO::FETCH_ASSOC);
        $mantenimiento['agenda'] = $agenda ?: null;
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $mantenimientos,
        'count' => count($mantenimientos),
        'permiso' => $puedeVerTodos ? 'todos' : 'propios'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener los mantenimientos: ' . $e->getMessage()
    ]);
}
