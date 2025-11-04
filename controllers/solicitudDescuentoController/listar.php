<?php
require_once '../../database/conexion.php';
require_once __DIR__ . '/../../middlewares/cors.php';

try {
	$sql = "SELECT 
            d.id,
            d.entrega_fijos_id,
			d.estado_solicitud,
            d.consecutivo,
            d.fecha_solicitud,
            d.trabajador_id,
            p.nombre AS trabajador_nombre,
            p.cedula AS trabajador_cedula,
            p.telefono AS trabajador_telefono,
            d.tipo_contrato,
            d.firma_trabajador,
            d.motivo_solicitud,
            d.valor_total_descontar,
            d.numero_cuotas,
            d.numero_cuotas_aprobadas,
            d.personal_responsable_aprobacion,
            d.firma_responsable_aprobacion,
            d.jefe_inmediato_id,
            d.firma_jefe_inmediato,
            d.personal_facturacion,
            d.firma_facturacion,
            d.personal_gestion_financiera,
            d.firma_gestion_financiera,
            d.personal_talento_humano,
            d.firma_talento_humano,
            d.observaciones
        FROM cp_solicitud_descuento d
        LEFT JOIN personal p ON d.trabajador_id = p.id
        ORDER BY d.fecha_solicitud DESC";
		
	$stmt = $pdo->query($sql);
	$descuentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

	foreach ($descuentos as &$d) {
		if ($d['entrega_fijos_id']) {
			// Datos de la entrega
			$sqlEntrega = "SELECT e.*, s.nombre AS sede_nombre, u.nombre_completo AS personal_nombre
                           FROM cp_entrega_activos_fijos e
                           LEFT JOIN sedes s ON e.sede_id = s.id
                           LEFT JOIN usuarios u ON e.personal_id = u.id
                           WHERE e.id = ?";
			$stmtEntrega = $pdo->prepare($sqlEntrega);
			$stmtEntrega->execute([$d['entrega_fijos_id']]);
			$d['entrega'] = $stmtEntrega->fetch(PDO::FETCH_ASSOC);


			// Items de la entrega
			$sqlItems = "SELECT i.*, inv.nombre AS nombre_item
			FROM cp_entrega_activos_fijos_items i
			LEFT JOIN inventario inv ON i.item_id = inv.id
			WHERE i.entrega_activos_id = ?";
			$stmtItems = $pdo->prepare($sqlItems);
			$stmtItems->execute([$d['entrega_fijos_id']]);
			$d['entrega']['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
		} else {
			$d['entrega'] = null;
		}
	}

	echo json_encode([
		"success" => true,
		"data" => $descuentos
	]);
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode([
		"success" => false,
		"error" => "Error al obtener los descuentos: " . $e->getMessage()
	]);
}
