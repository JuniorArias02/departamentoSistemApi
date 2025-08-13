<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/headers_get.php';

try {
	// Consulta principal de pedidos con datos del usuario creador
	$sql = "
    SELECT 
        p.id,
        p.estado_compras,
        p.fecha_solicitud,
        p.proceso_solicitante,
        p.tipo_solicitud,
        ts.nombre AS tipo_solicitud_nombre, -- Aquí agregamos el nombre
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
        p.estado_gerencia
    FROM cp_pedidos p
    LEFT JOIN usuarios u1 ON p.elaborado_por = u1.id
    LEFT JOIN usuarios u2 ON p.proceso_compra = u2.id
    LEFT JOIN usuarios u3 ON p.responsable_aprobacion = u3.id
    LEFT JOIN usuarios u4 ON p.creador_por = u4.id
    LEFT JOIN cp_tipo_solicitud ts ON p.tipo_solicitud = ts.id -- Join para obtener el nombre
    ORDER BY p.id DESC
";


	$stmt = $pdo->query($sql);
	$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// Obtener items por pedido
	foreach ($pedidos as &$pedido) {
		$stmtItems = $pdo->prepare("
            SELECT id, nombre, cantidad, referencia_items 
            FROM cp_items_pedidos 
            WHERE cp_pedido = :id
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
