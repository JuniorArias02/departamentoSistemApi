<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_post.php';
require_once __DIR__ . '/../rol/permisos/permisos.php';
require_once __DIR__ . '/../rol/permisos/validador_permisos.php';

$data = json_decode(file_get_contents("php://input"), true);

// Obtener el ID del usuario desde el body
$usuarioId = isset($data['usuarioId']) ? intval($data['usuarioId']) : null;

if (!$usuarioId) {
    http_response_code(400);
    echo json_encode(["error" => "El usuarioId es obligatorio"]);
    exit;
}

try {
    // Verificar permisos
    $puedeVerTodos = tienePermiso($pdo, $usuarioId, PERMISOS['GESTION_COMPRA_PEDIDOS']['RECIBIR_NUEVOS_PEDIDOS']);
    $puedeVerPropios = tienePermiso($pdo, $usuarioId, PERMISOS['GESTION_COMPRA_PEDIDOS']['VER_PEDIDOS_PROPIOS']);
    $puedeVerEncargado = tienePermiso($pdo, $usuarioId, PERMISOS['GESTION_COMPRA_PEDIDOS']['VER_PEDIDOS_ENCARGADO']);

    if (!$puedeVerTodos && !$puedeVerPropios && !$puedeVerEncargado) {
        http_response_code(403);
        echo json_encode(["error" => "No tienes permisos para ver pedidos"]);
        exit;
    }

    // Base de la consulta
    $sql = "
    SELECT 
        p.id,
        p.estado_compras,
        p.fecha_solicitud,
        dp.nombre AS proceso_solicitante,
        p.tipo_solicitud,
        p.motivo_aprobacion,
        s.nombre AS sede_nombre,
        ts.nombre AS tipo_solicitud_nombre,
        p.consecutivo,
        p.observacion,
        p.elaborado_por,
        u1.nombre_completo AS elaborado_por_nombre,
        p.elaborado_por_firma,
        p.proceso_compra,
        u2.nombre_completo AS proceso_compra_nombre,
        p.proceso_compra_firma,
        p.responsable_aprobacion,
        u3.nombre_completo AS responsable_aprobacion_nombre,
        p.responsable_aprobacion_firma,
        p.creador_por,
        u4.nombre_completo AS creador_nombre,
        p.pedido_visto,
        p.observacion_diligenciado,
        p.estado_gerencia,
        es.estado AS estado_entrega,
        p.adjunto_pdf AS adjunto_url,
        -- 👇 campo para el front
        CASE 
            WHEN p.adjunto_pdf IS NOT NULL AND p.adjunto_pdf <> '' THEN 'Sí'
            ELSE 'No'
        END AS tiene_adjunto

    FROM cp_pedidos p
    LEFT JOIN usuarios u1 ON p.elaborado_por = u1.id
    LEFT JOIN usuarios u2 ON p.proceso_compra = u2.id
    LEFT JOIN usuarios u3 ON p.responsable_aprobacion = u3.id
    LEFT JOIN usuarios u4 ON p.creador_por = u4.id
    LEFT JOIN cp_tipo_solicitud ts ON p.tipo_solicitud = ts.id
    LEFT JOIN sedes s ON p.sede_id = s.id
    LEFT JOIN cp_entrega_solicitud es ON es.consecutivo_id = p.consecutivo
    LEFT JOIN dependencias_sedes dp ON dp.id = p.proceso_solicitante
";


    // Construir filtros según permisos
    $conditions = [];
    $params = [];

    if ($puedeVerPropios) {
        $conditions[] = "p.creador_por = :usuarioId";
        $params[':usuarioId'] = $usuarioId;
    }

    if ($puedeVerEncargado) {
        $conditions[] = "p.estado_compras = 'APROBADO'";
    }

    // Si no puede ver todos, aplicamos filtros
    if (!$puedeVerTodos && !empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY p.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener items por pedido
    foreach ($pedidos as &$pedido) {
        $stmtItems = $pdo->prepare("
        SELECT 
            cp.id, 
            cp.nombre, 
            cp.cantidad,
            cp.unidad_medida, 
            cp.referencia_items, 
            cp.comprado, 
            cpro.codigo AS codigo_producto
        FROM cp_items_pedidos AS cp
        LEFT JOIN cp_productos AS cpro ON cp.productos_id = cpro.id
        WHERE cp.cp_pedido = :id
    ");
        $stmtItems->execute([':id' => $pedido['id']]);
        $pedido['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
    }


    echo json_encode([
        "success" => true,
        "data" => $pedidos
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Error al obtener pedidos: " . $e->getMessage()
    ]);
}
