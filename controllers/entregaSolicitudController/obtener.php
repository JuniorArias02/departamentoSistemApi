<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

try {
	$sql = "SELECT
              p.proceso_solicitante,
              p.consecutivo,
              p.observacion,
              COUNT(i.id) AS total_items,
              e.fecha       AS fecha_entrega,
              e.firma_quien_recibe,
              e.factura_proveedor
            FROM cp_entrega_solicitud e
            INNER JOIN cp_pedidos p
              ON p.consecutivo = e.consecutivo_id
            LEFT JOIN cp_items_pedidos i
              ON i.cp_pedido = p.id
            WHERE e.estado = 1
            GROUP BY
              p.id,
              p.proceso_solicitante,
              p.consecutivo,
              p.observacion,
              e.fecha,
              e.firma_quien_recibe,
              e.factura_proveedor";

	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode([
		"success" => true,
		"data"    => $result
	]);
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode([
		"success" => false,
		"error"   => "Error al obtener entregas",
		"detalle" => $e->getMessage()
	]);
}
