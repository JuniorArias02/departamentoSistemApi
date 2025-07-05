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

    // Verificar si puede ver TODOS los registros o solo los propios
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

    // Consulta base
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

    // Formatear respuesta
    foreach ($mantenimientos as &$mantenimiento) {
        $mantenimiento['esta_revisado'] = (bool)$mantenimiento['esta_revisado'];
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
