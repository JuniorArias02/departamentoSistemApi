<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

header('Content-Type: application/json');

try {
    $sql = "
        SELECT 
            p.id AS ID,
            u.sede_id AS SEDE,
            p.consecutivo AS CONSECUTIVO,
            ds.nombre AS PROCESO,
            s.nombre AS SEDE,
            p.observacion AS DESCRIPCION,
            p.observacion_diligenciado AS OBSERVACION,
            ts.nombre AS TIPO_COMPRA,
            p.estado_compras AS APROBACION,
            p.fecha_solicitud AS FECHA_SOLICITUD,
            p.fecha_compra AS FECHA_RESPUESTA,
            p.responsable_aprobacion_firma AS FIRMA_RESPONSABLE,
            p.fecha_gerencia AS FECHA_RESPUESTA_SOLICITANTE,
            p.observaciones_pedidos AS OBSERVACIONES_PEDIDOS
        FROM cp_pedidos p
        LEFT JOIN usuarios u ON u.id = p.elaborado_por
        LEFT JOIN cp_tipo_solicitud ts ON ts.id = p.tipo_solicitud
        LEFT JOIN sedes s ON s.id = p.sede_id
		LEFT JOIN dependencias_sedes ds ON ds.id = p.proceso_solicitante
        ORDER BY p.fecha_solicitud ASC
    ";

    $stmt = $pdo->query($sql);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'total'  => count($pedidos),
        'data'   => $pedidos
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Error en la base de datos',
        'detalle' => $e->getMessage()
    ]);
}
